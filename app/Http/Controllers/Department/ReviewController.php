<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\NotifikasiHimsi;
use App\Models\OpsProgram;
use App\Models\OpsSubmission;
use App\Models\SuratKeluar;
use App\Models\User;
use App\Support\DepartmentWorkflow as Flow;
use App\Support\FinanceLedger;
use App\Support\RpkWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    private function types(Request $request): array
    {
        return match ($request->user()->role) {
            'sekretaris_umum' => ['letter', 'rpk', 'report'], 'koordinator_pengawas' => ['rpk', 'report'],'ketua_umum' => ['letter', 'budget', 'rpk', 'report'],'bendahara_umum' => ['budget', 'rpk'],'super_admin' => ['letter', 'budget', 'rpk', 'report'],default => []
        };
    }

    public function index(Request $request): View
    {
        abort_unless($this->types($request), 403);
        $request->validate(['status' => ['nullable', Rule::in(array_keys(config('department.submission_statuses')))], 'type' => ['nullable', Rule::in($this->types($request))]]);
        $items = OpsSubmission::with('program')->whereIn('type', $this->types($request))->where('status', '!=', 'draft')
            ->when($request->user()->role === 'koordinator_pengawas', fn ($query) => $query->where(fn ($visible) => $visible->where('type', '!=', 'rpk')->orWhereIn('status', ['pending_supervisor', 'pending_archive', 'archived'])))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))->latest()->paginate(15)->withQueryString();

        return view('department.reviews', compact('items'));
    }

    public function show(Request $request, int $submission): View
    {
        abort_unless($this->types($request), 403);
        $submission = OpsSubmission::with('program')->whereIn('type', $this->types($request))->findOrFail($submission);
        abort_unless(Flow::canReadSubmission($request->user(), $submission), 404);
        $history = DB::table('ops_history')->leftJoin('users', 'users.id', '=', 'ops_history.actor_id')->where('program_id', $submission->program_id)->select('ops_history.*', 'users.name as actor')->orderByDesc('ops_history.id')->paginate(15);

        $linkedLetter = SuratKeluar::where('ops_submission_id', $submission->id)->first();

        return view('department.review', compact('submission', 'history', 'linkedLetter'));
    }

    public function decide(Request $request, int $submission): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['sekretaris_umum', 'ketua_umum', 'bendahara_umum', 'koordinator_pengawas'], true), 403);
        $s = OpsSubmission::with('program')->findOrFail($submission);
        abort_unless(Flow::canReadSubmission($request->user(), $s), 404);
        $data = $request->validate(['version' => ['required', 'integer'], 'action' => ['required', Rule::in(['forward', 'approve', 'revision', 'reject', 'sent', 'archive'])], 'note' => ['required', 'string', 'max:3000'], 'number' => ['nullable', 'string', 'max:100'], 'file' => ['nullable', 'file', 'mimes:pdf', 'max:10240']]);
        $path = null;
        try {
            DB::transaction(function () use ($request, $s, $data, &$path) {
                $program = OpsProgram::whereKey($s->program_id)->lockForUpdate()->firstOrFail();
                $s = OpsSubmission::whereKey($s->id)->lockForUpdate()->firstOrFail();
                Flow::version($s->version, (int) $data['version']);
                if ($s->type === 'rpk') {
                    RpkWorkflow::decide($s, $program, $request->user(), $data);

                    return;
                }
                if (SuratKeluar::where('ops_submission_id', $s->id)->exists()) {
                    Flow::require($s->status === 'approved' && $data['action'] === 'sent', 'Surat terhubung ke Administrasi. Proses keputusan di menu Persetujuan Surat.');
                }
                $role = $request->user()->role;
                $action = $data['action'];
                $expected = match ($s->status) {
                    'pending_supervisor' => 'koordinator_pengawas', 'pending_archive' => 'sekretaris_umum', 'pending_secretary' => 'sekretaris_umum','pending_chair' => 'ketua_umum','pending_treasurer' => 'bendahara_umum','approved' => $s->type === 'letter' ? 'sekretaris_umum' : ($s->type === 'report' && $program->status === 'running' ? 'koordinator_pengawas' : ($s->type === 'budget' && ! $s->finance_approved_by ? 'ketua_umum' : null)),default => null
                };
                abort_unless($role === $expected, 403);
                if ($s->type === 'report' && $s->status === 'pending_archive') {
                    Flow::require($action === 'archive', 'LPJ siap diarsipkan setelah seluruh persetujuan selesai.');
                    Flow::require($program->status === 'running' && $program->tasks()->exists() && ! $program->tasks()->where('status', '!=', 'done')->exists(), 'Seluruh tugas proker harus selesai sebelum pengarsipan LPJ.');
                    $status = 'archived';
                    $s->payload = array_merge($s->payload ?? [], ['archived_by' => $request->user()->id, 'archived_at' => now()->toIso8601String()]);
                    $program->update(['status' => 'completed', 'version' => $program->version + 1]);
                } elseif ($s->status === 'approved' && $s->type === 'letter') {
                    Flow::require($action === 'sent', 'Surat yang disetujui hanya dapat ditandai dikirim.');
                    $status = 'sent';
                } elseif ($action === 'revision') {
                    $status = 'revision';
                } elseif ($action === 'reject') {
                    $status = 'rejected';
                } elseif ($role === 'bendahara_umum') {
                    Flow::require($s->type === 'budget' && $action === 'forward', 'Bendahara meneruskan RAB kepada Ketua Umum untuk persetujuan.');
                    $status = 'pending_chair';
                    FinanceLedger::audit('review-rab', 'RAB #'.$s->id.' diteruskan kepada Ketua Umum: '.$data['note']);
                    User::where('role', 'ketua_umum')->where('is_active', true)->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, 'RAB Menunggu Persetujuan', $program->title, route('department.review', $s->id, false), 'keuangan'));
                } elseif ($role === 'sekretaris_umum') {
                    Flow::require($action === 'forward', 'Sekertaris meneruskan surat kepada Ketua Umum untuk keputusan akhir.');
                    Flow::require((bool) $s->file_path, 'Dokumen proposal belum tersedia. Minta koordinator mengunggah ulang.');
                    $status = 'pending_chair';
                } else {
                    Flow::require($action === 'approve', 'Tindakan tidak sesuai tahap pengajuan.');
                    $status = match ($s->type) {
                        'budget' => 'ready_funding',
                        'report' => $role === 'ketua_umum' ? 'pending_supervisor' : 'pending_archive',
                        default => 'approved',
                    };
                    if ($s->type === 'budget') {
                        $s->finance_approved_by = $request->user()->id;
                        $s->finance_approved_at = now();
                        FinanceLedger::audit('persetujuan-rab', 'Ketua menyetujui RAB #'.$s->id.' Rp'.$s->total);
                        User::where('role', 'bendahara_umum')->where('is_active', true)->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, 'RAB Siap Dicairkan', $program->title, route('finance.funding.show', $s->id, false), 'keuangan'));
                    }
                }
                $s->fill(['status' => $status, 'note' => $data['note'], 'version' => $s->version + 1])->save();
                if ($s->type === 'letter' && in_array($status, ['revision', 'rejected', 'approved'], true)) {
                    $program->update(['status' => $status === 'approved' ? 'approved' : $status, 'version' => $program->version + 1]);
                }
                if ($s->type === 'report' || $s->type === 'letter') {
                    $nextRole = match ($status) {
                        'pending_chair' => 'ketua_umum', 'pending_supervisor' => 'koordinator_pengawas', 'pending_archive' => 'sekretaris_umum', default => null
                    };
                    if ($nextRole) {
                        User::where('role', $nextRole)->where('is_active', true)->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, config('department.types')[$s->type].' menunggu tindakan', $program->title, route('department.review', $s->id, false), 'pengajuan'));
                    }
                    User::where('role', 'koordinator_departemen')->where('department', $program->department)->where('is_active', true)->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, 'Pembaruan '.config('department.types')[$s->type], config('department.submission_statuses')[$status].': '.$data['note'], route('department.program.show', [$program->department, $program->id], false), 'pengajuan'));
                }
                Flow::history($program, config('department.types')[$s->type].' — '.config('department.submission_statuses')[$status], $data['note'], $s->toArray());
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }

        return back()->with('success', 'Keputusan tersimpan. Sistem tidak menambahkan tanda tangan atau mengubah isi dokumen.');
    }
}
