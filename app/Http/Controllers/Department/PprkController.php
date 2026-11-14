<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\NotifikasiHimsi;
use App\Models\PprkSubmission;
use App\Models\User;
use App\Support\DepartmentWorkflow as Flow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PprkController extends Controller
{
    private const REVIEWERS = ['ketua_umum', 'bendahara_umum', 'sekretaris_umum'];

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless(in_array($user->role, [...self::REVIEWERS, 'koordinator_departemen', 'koordinator_pengawas', 'super_admin'], true), 403);
        $query = PprkSubmission::query();
        if ($user->role === 'koordinator_departemen') {
            $query->where('department', $user->department);
        } elseif ($user->role === 'koordinator_pengawas') {
            $query->whereIn('status', ['pending_supervisor', 'pending_archive', 'archived', 'supervisor_rejected']);
        }
        $items = $query->latest()->paginate(15);
        $current = $user->role === 'koordinator_departemen' ? PprkSubmission::where('department', $user->department)->where('period', config('department.period'))->first() : null;

        return view('department.pprk', compact('items', 'current'));
    }

    public function upload(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'koordinator_departemen' && isset(config('superadmin.departments')[$user->department]), 403);
        $data = $request->validate(['version' => ['required', 'integer', 'min:0'], 'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'extensions:pdf,doc,docx', 'max:10240']]);
        $path = null;
        try {
            DB::transaction(function () use ($request, $user, $data, &$path) {
                $record = PprkSubmission::firstOrCreate(['department' => $user->department, 'period' => config('department.period')]);
                $record = PprkSubmission::whereKey($record->id)->lockForUpdate()->firstOrFail();
                Flow::version($record->version, (int) $data['version']);
                Flow::require(in_array($record->status, ['draft', 'rejected', 'supervisor_rejected'], true), 'RPK masih ditinjau atau sudah disetujui.');
                $path = $request->file('file')->store('department/pprk', 'local');
                Flow::require((bool) $path, 'Berkas gagal disimpan.');
                $record->fill(['file_path' => $path, 'file_name' => $request->file('file')->getClientOriginalName(), 'submitted_by' => $user->id, 'status' => 'pending_officers', 'reviews' => [], 'revision' => $record->revision + 1, 'version' => $record->version + 1]);
                $this->recordHistory($record, $user, 'Unggah RPK', 'Dokumen diajukan untuk pemeriksaan tiga pengurus.');
                $record->save();
                $this->notify(self::REVIEWERS, 'RPK menunggu tinjauan', $record);
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return back()->with('success', 'RPK dikirim kepada Ketua Umum, Bendahara, dan Sekertaris.');
    }

    public function review(Request $request, int $pprk): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, [...self::REVIEWERS, 'koordinator_pengawas'], true), 403);
        $data = $request->validate(['version' => ['required', 'integer'], 'action' => ['required', Rule::in(['approve', 'reject', 'archive'])], 'note' => ['required', 'string', 'max:3000']]);
        DB::transaction(function () use ($request, $pprk, $data) {
            $record = PprkSubmission::whereKey($pprk)->lockForUpdate()->firstOrFail();
            Flow::version($record->version, (int) $data['version']);
            $role = $request->user()->role;
            $reviews = $record->reviews ?? [];
            if ($record->status === 'pending_officers') {
                abort_unless(in_array($role, self::REVIEWERS, true), 403);
                Flow::require(! isset($reviews[$role]), 'Role Anda sudah memberikan keputusan untuk versi ini.');
                Flow::require(in_array($data['action'], ['approve', 'reject'], true), 'Pilih setuju atau tidak setuju.');
                $reviews[$role] = ['decision' => $data['action'], 'note' => $data['note'], 'actor' => $request->user()->name, 'actor_id' => $request->user()->id, 'at' => now()->toIso8601String()];
                $record->reviews = $reviews;
                if (count(array_intersect(array_keys($reviews), self::REVIEWERS)) === 3) {
                    $allApproved = collect(self::REVIEWERS)->every(fn ($reviewer) => $reviews[$reviewer]['decision'] === 'approve');
                    $record->status = $allApproved ? 'pending_supervisor' : 'rejected';
                    if ($allApproved) {
                        $this->notify(['koordinator_pengawas'], 'RPK menunggu verifikasi Pengawas', $record);
                    }
                }
            } elseif ($record->status === 'pending_supervisor') {
                abort_unless($role === 'koordinator_pengawas', 403);
                Flow::require(in_array($data['action'], ['approve', 'reject'], true), 'Pilih setuju atau tidak setuju.');
                $record->status = $data['action'] === 'approve' ? 'pending_archive' : 'supervisor_rejected';
                $record->reviews = [...$reviews, $role => ['decision' => $data['action'], 'note' => $data['note'], 'actor' => $request->user()->name, 'actor_id' => $request->user()->id, 'at' => now()->toIso8601String()]];
                if ($record->status === 'pending_archive') {
                    $this->notify(['sekretaris_umum'], 'RPK siap diarsipkan', $record);
                }
            } elseif ($record->status === 'pending_archive') {
                abort_unless($role === 'sekretaris_umum', 403);
                Flow::require($data['action'] === 'archive', 'RPK yang disetujui siap diarsipkan.');
                $record->fill(['status' => 'archived', 'archived_at' => now(), 'archived_by' => $request->user()->id]);
            } else {
                abort(403);
            }
            $record->version++;
            $this->recordHistory($record, $request->user(), ['approve' => 'Setuju', 'reject' => 'Tidak setuju', 'archive' => 'Diarsipkan'][$data['action']], $data['note']);
            $record->save();
            User::where('role', 'koordinator_departemen')->where('department', $record->department)->where('is_active', true)->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, 'Pembaruan RPK', config('department.pprk_statuses.'.$record->status), route('department.pprk', [], false), 'pengajuan'));
        });

        return back()->with('success', 'Keputusan RPK tersimpan.');
    }

    public function file(Request $request, int $pprk): StreamedResponse
    {
        $record = PprkSubmission::findOrFail($pprk);
        $user = $request->user();
        $allowed = in_array($user->role, [...self::REVIEWERS, 'super_admin'], true)
            || ($user->role === 'koordinator_departemen' && $user->department === $record->department)
            || ($user->role === 'koordinator_pengawas' && in_array($record->status, ['pending_supervisor', 'pending_archive', 'archived', 'supervisor_rejected'], true));
        abort_unless($allowed, 404);

        return Flow::file($record->file_path, $record->file_name, $request->boolean('preview'));
    }

    private function recordHistory(PprkSubmission $record, User $user, string $action, string $note): void
    {
        $record->history = [...($record->history ?? []), ['revision' => $record->revision, 'actor' => $user->name, 'actor_id' => $user->id, 'role' => $user->role, 'action' => $action, 'note' => $note, 'at' => now()->toIso8601String(), 'file_path' => $record->file_path]];
    }

    private function notify(array $roles, string $title, PprkSubmission $record): void
    {
        User::whereIn('role', $roles)->where('is_active', true)->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, $title, config('superadmin.departments.'.$record->department).' · '.$record->period, route('department.pprk', [], false), 'pengajuan'));
    }
}
