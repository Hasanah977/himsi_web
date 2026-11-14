<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\NotifikasiHimsi;
use App\Models\OpsSubmission;
use App\Models\User;
use App\Support\DepartmentExpenses;
use App\Support\DepartmentWorkflow as Flow;
use App\Support\FinanceLedger;
use App\Support\RpkWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function index(Request $request, string $department, string $type, DepartmentExpenses $expenses): View
    {
        abort_unless(isset(config('department.types')[$type]), 404);
        $request->validate(['status' => ['nullable', Rule::in(array_keys(config('department.submission_statuses')))], 'program' => ['nullable', 'integer'], 'tab' => ['nullable', Rule::in(['rab', 'expenses'])]]);
        $query = OpsSubmission::with('program')->where('type', $type)->whereHas('program', fn ($q) => $q->where('department', $department));
        $all = clone $query;
        $counts = (clone $all)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        $items = $query->latest()->paginate(12)->withQueryString();
        $programs = Flow::programs($department)->orderByDesc('id')->get();
        $selected = $request->filled('program') ? Flow::program($department, $request->integer('program')) : $programs->first();
        $expenseData = $type === 'budget' && $selected ? $expenses->forProgram($department, $selected->id) : ['connected' => false, 'rows' => collect(), 'total' => null];
        $selectedBudget = $selected?->submissions()->where('type', 'budget')->first();
        $totals = [];
        foreach (['approved' => ['ready_funding', 'disbursed'], 'pending_treasurer' => ['pending_chair', 'pending_treasurer', 'approved'], 'revision' => ['revision']] as $status => $statuses) {
            $totals[$status] = (clone $all)->whereIn('status', $statuses)->sum('total');
        }

        return view('department.submissions', compact('type', 'items', 'programs', 'selected', 'expenseData', 'totals', 'selectedBudget', 'counts'));
    }

    public function edit(string $department, int $program, string $type): View
    {
        abort_unless(isset(config('department.types')[$type]), 404);
        $program = Flow::program($department, $program);
        $submission = $program->submissions()->where('type', $type)->first() ?? new OpsSubmission(['type' => $type, 'status' => 'draft', 'version' => 0]);
        $entries = $type === 'budget' && $submission->exists
            ? FinanceLedger::query()->where('submission_id', $submission->id)->orderByDesc('id')->get()
            : collect();
        $paid = $entries->where('origin', 'disbursement')->sum('amount');
        $returned = $entries->where('origin', 'return')->sum('amount');
        $rpkUploadAllowed = $type === 'rpk' && RpkWorkflow::canUpload($program);

        return view('department.submission-form', compact('program', 'submission', 'type', 'paid', 'returned', 'entries', 'rpkUploadAllowed'));
    }

    public function save(Request $request, string $department, int $program, string $type): RedirectResponse
    {
        abort_unless(isset(config('department.types')[$type]), 404);
        Flow::program($department, $program);
        $rules = ['version' => ['required', 'integer', 'min:0'], 'action' => ['required', Rule::in(['draft', 'submit'])], 'file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'extensions:pdf,doc,docx', 'max:'.($type === 'report' ? 25600 : 10240)]];
        if ($type === 'budget') {
            $rules['total'] = ['required', 'integer', 'between:1,1000000000000'];
        }
        $rules['summary'] = ['nullable', 'string', 'max:5000'];
        $data = $request->validate($rules);
        $path = null;
        try {
            DB::transaction(function () use ($request, $data, $department, $program, $type, &$path) {
                $p = Flow::program($department, $program, true);
                Flow::require($p->status !== 'completed', 'Proker selesai tidak dapat diubah.');
                if ($type === 'letter') {
                    Flow::require(in_array($p->status, ['draft', 'revision', 'rejected'], true), 'Pengajuan surat terkunci pada tahap ini.');
                }
                if ($type === 'budget') {
                    Flow::require(in_array($p->status, ['approved', 'running'], true), 'RAB hanya dapat disusun setelah surat/kegiatan disetujui Ketua Umum.');
                }
                if ($type === 'report') {
                    Flow::require($p->status === 'running', 'LPJ disusun setelah kegiatan masuk tahap berjalan.');
                    Flow::require($p->tasks()->exists() && ! $p->tasks()->where('status', '!=', 'done')->exists(), 'LPJ hanya dapat diunggah setelah progres pelaksanaan 100% dan seluruh tugas divalidasi selesai.');
                }
                if ($type === 'rpk') {
                    Flow::require(RpkWorkflow::canUpload($p), 'PPRK hanya dapat diunggah setelah proposal dan RAB disetujui, sebelum program kerja dimulai.');
                }
                $s = $p->submissions()->where('type', $type)->lockForUpdate()->first() ?? new OpsSubmission(['program_id' => $p->id, 'type' => $type, 'version' => 0]);
                Flow::version($s->version, (int) $data['version']);
                Flow::require(! $s->exists || in_array($s->status, ['draft', 'revision', 'rejected'], true), 'Pengajuan terkunci selama pemeriksaan atau setelah disetujui.');
                $payload = collect($data)->except(['version', 'action', 'file'])->all();
                $total = $type === 'budget' ? (int) $data['total'] : 0;
                Flow::require(! $s->exists || ! in_array($s->status, ['revision', 'rejected'], true) || $request->hasFile('file'), 'Unggah berkas versi terbaru untuk pengajuan ulang.');
                if ($request->hasFile('file')) {
                    $path = $request->file('file')->store('department/reports', 'local');
                    Flow::require((bool) $path, 'Berkas gagal disimpan.');
                    $s->file_path = $path;
                    $s->file_name = $request->file('file')->getClientOriginalName();
                }
                Flow::require((bool) $s->file_path, 'Unggah dokumen pengajuan terlebih dahulu.');
                $s->fill(['payload' => $payload, 'total' => $total, 'note' => null, 'version' => $s->version + 1, 'status' => $data['action'] === 'draft' ? 'draft' : match ($type) {
                    'letter' => 'pending_secretary','budget' => 'pending_treasurer','report' => 'pending_chair', 'rpk' => 'pending_officers'
                }])->save();
                if ($type === 'letter') {
                    $p->update(['status' => $data['action'] === 'submit' ? 'pending' : 'draft', 'version' => $p->version + 1]);
                }
                if ($data['action'] === 'submit' && $type === 'rpk') {
                    User::whereIn('role', RpkWorkflow::OFFICERS)->where('is_active', true)->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, 'PPRK menunggu verifikasi', $p->title, route('department.review', $s, false), 'pengajuan'));
                }
                if ($data['action'] === 'submit' && ! in_array($type, ['budget', 'rpk'], true)) {
                    $reviewer = $type === 'letter' ? 'sekretaris_umum' : 'ketua_umum';
                    User::where('role', $reviewer)->where('is_active', true)->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, config('department.types')[$type].' menunggu tinjauan', $p->title, route('department.review', $s->id, false), 'pengajuan'));
                }
                if ($type === 'budget' && $data['action'] === 'submit') {
                    User::where('role', 'bendahara_umum')->where('is_active', true)->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, 'Pengajuan Dana Masuk', $p->title, route('finance.funding.show', $s->id, false), 'keuangan'));
                    FinanceLedger::audit('pengajuan-rab', 'RAB #'.$s->id.' diajukan oleh '.$department);
                }
                Flow::history($p, config('department.types')[$type].($data['action'] === 'submit' ? ' diajukan' : ' disimpan sebagai draft'), null, $s->toArray());
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }

        return redirect()->route('department.program.show', [$department, $program])->with('success', 'Pengajuan tersimpan sesuai tahapnya. Persetujuan menunggu tindakan pejabat yang berwenang.');
    }

    public function file(Request $request, int $submission): StreamedResponse
    {
        $s = OpsSubmission::with('program')->findOrFail($submission);
        abort_unless(Flow::canReadSubmission($request->user(), $s), 404);

        return Flow::file($s->file_path, $s->file_name, $request->boolean('preview'));
    }

    public function documentTemplate(string $department, string $type): StreamedResponse
    {
        $template = config('department.templates.'.$type);
        abort_unless($template, 404);

        return Flow::file($template['path'], $template['name']);
    }

    public function template(): StreamedResponse
    {
        return Flow::file(config('department.lpj_template'), 'Template-LPJ-HIMSI.docx');
    }
}
