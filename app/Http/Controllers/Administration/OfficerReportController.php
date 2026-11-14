<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\NotifikasiHimsi;
use App\Models\OfficerReport;
use App\Models\OpsSubmission;
use App\Models\User;
use App\Support\DepartmentWorkflow as Flow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfficerReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || in_array($user->role, [...OfficerReport::SubmitterRoles, 'koordinator_pengawas'], true), 403);
        $request->validate(['status' => ['nullable', Rule::in(array_keys(OfficerReport::Statuses))]]);
        $items = OfficerReport::with('user')
            ->when(in_array($user->role, OfficerReport::SubmitterRoles, true), fn ($query) => $query->where('user_id', $user->id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest('updated_at')->paginate(15)->withQueryString();
        $departmentPending = $user->role === 'koordinator_pengawas'
            ? OpsSubmission::where('type', 'report')->where(function ($query): void {
                $query->where('status', 'pending_supervisor')->orWhere(function ($query): void {
                    $query->where('status', 'approved')->whereHas('program', fn ($program) => $program->where('status', 'running'));
                });
            })->count() : 0;

        return view('administration.officer-reports', compact('items', 'departmentPending'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, OfficerReport::SubmitterRoles, true), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'period' => ['required', 'string', 'max:40'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:25600'],
        ]);
        $path = $request->file('file')->store('officer-reports', 'local');
        try {
            $report = DB::transaction(function () use ($request, $data, $path): OfficerReport {
                $report = OfficerReport::create([
                    'user_id' => $request->user()->id, 'role' => $request->user()->role,
                    'title' => $data['title'], 'period' => $data['period'], 'file_path' => $path,
                    'file_name' => $request->file('file')->getClientOriginalName(),
                    'status' => 'pending_supervisor', 'version' => 1,
                    'history' => [$this->history($request, 'Diunggah', $path)],
                ]);
                $this->notifySupervisors($report);

                return $report;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('officer-reports.show', $report)->with('success', 'LPJ dikirim ke Koordinator Pengawas Himpunan untuk diverifikasi.');
    }

    public function show(Request $request, OfficerReport $report): View
    {
        abort_unless($report->canRead($request->user()), 404);
        $report->load('user');

        return view('administration.officer-report', compact('report'));
    }

    public function update(Request $request, OfficerReport $report): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, OfficerReport::SubmitterRoles, true) && $report->user_id === $request->user()->id, 403);
        $data = $request->validate(['version' => ['required', 'integer'], 'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:25600']]);
        $path = null;
        try {
            DB::transaction(function () use ($request, $report, $data, &$path): void {
                $report = OfficerReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
                Flow::version($report->version, (int) $data['version']);
                Flow::require(in_array($report->status, ['revision', 'rejected'], true), 'LPJ hanya dapat diperbaiki setelah dikembalikan oleh pengawas.');
                $path = $request->file('file')->store('officer-reports', 'local');
                $report->update([
                    'file_path' => $path, 'file_name' => $request->file('file')->getClientOriginalName(),
                    'status' => 'pending_supervisor', 'note' => null, 'reviewed_by' => null, 'reviewed_at' => null,
                    'version' => $report->version + 1,
                    'history' => [...($report->history ?? []), $this->history($request, 'Revisi diunggah', $path)],
                ]);
                $this->notifySupervisors($report);
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return back()->with('success', 'Revisi LPJ dikirim kembali untuk diverifikasi.');
    }

    public function review(Request $request, OfficerReport $report): RedirectResponse
    {
        abort_unless($request->user()->role === 'koordinator_pengawas' && $report->user_id !== $request->user()->id, 403);
        $data = $request->validate(['version' => ['required', 'integer'], 'status' => ['required', Rule::in(['approved', 'revision', 'rejected'])], 'note' => ['required', 'string', 'max:3000']]);
        DB::transaction(function () use ($request, $report, $data): void {
            $report = OfficerReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            Flow::version($report->version, (int) $data['version']);
            Flow::require($report->status === 'pending_supervisor', 'LPJ ini sudah diperiksa. Muat ulang halaman.');
            $report->update([
                'status' => $data['status'], 'note' => $data['note'], 'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(), 'version' => $report->version + 1,
                'history' => [...($report->history ?? []), $this->history($request, OfficerReport::Statuses[$data['status']], $report->file_path, $data['note'])],
            ]);
            NotifikasiHimsi::kirim($report->user_id, 'Pembaruan LPJ', OfficerReport::Statuses[$report->status].': '.$report->title, route('officer-reports.show', $report, false), 'pengajuan');
        });

        return back()->with('success', 'Hasil verifikasi LPJ tersimpan.');
    }

    public function file(Request $request, OfficerReport $report): StreamedResponse
    {
        abort_unless($report->canRead($request->user()), 404);

        return Flow::file($report->file_path, $report->file_name);
    }

    public function template(Request $request): StreamedResponse
    {
        abort_unless($request->user()->isSuperAdmin() || in_array($request->user()->role, [...OfficerReport::SubmitterRoles, 'koordinator_pengawas'], true), 403);

        return Flow::file(config('department.lpj_template'), 'Template-LPJ-HIMSI.docx');
    }

    private function notifySupervisors(OfficerReport $report): void
    {
        User::where('role', 'koordinator_pengawas')->where('is_active', true)->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, 'LPJ Menunggu Verifikasi', $report->title, route('officer-reports.show', $report, false), 'pengajuan'));
    }

    /** @return array{actor: string, action: string, file: string, note: ?string, at: string} */
    private function history(Request $request, string $action, string $path, ?string $note = null): array
    {
        return ['actor' => $request->user()->name, 'action' => $action, 'file' => $path, 'note' => $note, 'at' => now()->toIso8601String()];
    }
}
