<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\KbhPendaftaranSeleksi as Application;
use App\Models\KbhPeriode as Period;
use App\Support\RecruitmentWorkflow as Flow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecruitmentController extends Controller
{
    public function __construct(private Flow $flow) {}

    private function authorize(Request $request, bool $chairOnly = false): void
    {
        abort_unless($chairOnly ? $request->user()->role === 'ketua_umum' : in_array($request->user()->role, Flow::REVIEWERS, true) || ($request->user()->isSuperAdmin() && $request->isMethodSafe()), 403);
    }

    public function index(Request $request): View
    {
        $this->authorize($request);
        $periods = Period::whereNotNull('batch')->latest('id')->get();
        $period = $request->filled('period') ? $periods->firstWhere('id', (int) $request->input('period')) : $periods->first();
        abort_if($request->filled('period') && ! $period, 404);
        $applications = $period ? Application::with(['user', 'reviewer'])->where('periode_id', $period->id)->orderByDesc('score')->orderBy('id')->get() : collect();
        $assigned = $applications->where('reviewer_id', $request->user()->id);
        $groups = $applications->whereNotNull('score')->groupBy($period?->batch === 2 ? 'assigned_department' : 'position');

        return view('administration.recruitment', compact('periods', 'period', 'applications', 'assigned', 'groups'));
    }

    public function open(Request $request): RedirectResponse
    {
        $this->authorize($request, true);
        $data = $request->validate(['batch' => ['required', 'integer', 'in:1,2'], 'nama' => ['required', 'string', 'max:150'], 'selesai_pada' => ['required', 'date_format:Y-m-d\TH:i']]);
        Flow::require(Flow::localDate($data['selesai_pada'])->isFuture(), 'Batas pendaftaran harus setelah waktu sekarang (WITA).');
        $period = $this->flow->open($request->user(), $data);

        return redirect()->route('recruitment.index', ['period' => $period->id])->with('success', 'Pendaftaran Batch '.$period->batch.' dibuka.');
    }

    public function close(Request $request, Period $period): RedirectResponse
    {
        $this->authorize($request, true);
        abort_unless($period->batch, 404);
        $this->flow->close($period);

        return back()->with('success', 'Pendaftaran ditutup. Selesaikan verifikasi untuk mengirim jadwal wawancara.');
    }

    public function reopen(Request $request, Period $period): RedirectResponse
    {
        $this->authorize($request, true);
        abort_unless($period->batch, 404);
        $data = $request->validate(['selesai_pada' => ['required', 'date_format:Y-m-d\TH:i']]);
        $this->flow->reopen($period, $data['selesai_pada']);

        return back()->with('success', 'Pendaftaran dibuka kembali. Hasil verifikasi sebelumnya tetap tersimpan.');
    }

    public function review(Request $request, Application $application): RedirectResponse
    {
        $this->authorize($request);
        $data = $request->validate(['status' => ['required', 'in:lulus_berkas,tidak_lolos'], 'catatan' => ['required', 'string', 'max:3000']]);
        $this->flow->review($request->user(), $application, $data);

        return back()->with('success', 'Verifikasi tersimpan dan notifikasi dikirim kepada peserta.');
    }

    public function schedule(Request $request, Application $application): RedirectResponse
    {
        $this->authorize($request);
        $data = $request->validate(['interview_at' => ['required', 'date_format:Y-m-d\TH:i'], 'interview_location' => ['required', 'string', 'max:255']]);
        $this->flow->schedule($request->user(), $application, $data);

        return back()->with('success', 'Jadwal wawancara dikirim kepada peserta.');
    }

    public function interview(Request $request, Application $application): View
    {
        $this->authorize($request);
        $this->flow->authorizeReviewer($request->user(), $application);
        abort_unless($application->period->stage === 'review' && in_array($application->status, ['wawancara', 'dinilai'], true), 403);

        return view('administration.recruitment-interview', ['application' => $application->load('user'), 'questions' => Flow::questions()]);
    }

    public function score(Request $request, Application $application): RedirectResponse
    {
        $this->authorize($request);
        $rules = ['scores' => ['required', 'array:'.implode(',', array_keys(Flow::questions()))], 'interview_notes' => ['required', 'string', 'max:3000'], 'outcome' => ['required', 'in:pass,fail'], 'assigned_department' => ['nullable', Rule::in(array_keys(config('superadmin.departments')))]];
        foreach (Flow::questions() as $key => $question) {
            $rules['scores.'.$key] = ['required', 'integer', 'between:0,100'];
        }
        $this->flow->score($request->user(), $application, $request->validate($rules));

        return redirect()->route('recruitment.index', ['period' => $application->periode_id])->with('success', 'Nilai tersimpan. Peringkat bersama diperbarui otomatis.');
    }

    public function finalize(Request $request, Period $period): RedirectResponse
    {
        $this->authorize($request, true);
        abort_unless($period->batch, 404);
        $data = $request->validate(['confirmed' => ['accepted'], 'sekretaris_umum' => ['nullable', 'integer'], 'bendahara_umum' => ['nullable', 'integer'], 'coordinators' => ['nullable', 'array'], 'coordinators.*' => ['integer']]);
        $this->flow->finalize($period, $data);

        return back()->with('success', 'Batch selesai. Role peserta terpilih dan notifikasi hasil telah diperbarui.');
    }

    public function file(Request $request, Application $application, string $type): StreamedResponse
    {
        $this->authorize($request);
        $this->flow->authorizeReviewer($request->user(), $application);
        abort_unless(array_key_exists($type, Flow::documents($application->period->batch ?? 0)), 404);
        $path = $application->documents[$type] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $type.'.'.pathinfo($path, PATHINFO_EXTENSION), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function applyForm(Request $request): View|RedirectResponse
    {
        abort_unless($request->user()->assignedRole() === 'kbh', 403);
        $period = Period::whereNotNull('batch')->latest('id')->first();
        abort_unless($period, 404);
        if (Application::where('periode_id', $period->id)->where('user_id', $request->user()->id)->exists()) {
            return redirect()->route('recruitment.result');
        }
        Flow::require($period->stage === 'registration' && $period->sedangDibuka(), 'Pendaftaran belum dibuka atau sudah ditutup.');

        return view('kbh.seleksi.recruitment-upload', ['period' => $period, 'documents' => Flow::documents($period->batch)]);
    }

    public function apply(Request $request): RedirectResponse
    {
        abort_unless($request->user()->assignedRole() === 'kbh', 403);
        $period = Period::whereNotNull('batch')->latest('id')->first();
        abort_unless($period, 404);
        $rules = ['period_id' => ['required', 'integer', Rule::in([$period->id])]];
        foreach (Flow::documents($period->batch) as $key => $label) {
            $rules['documents.'.$key] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        }
        if ($period->batch === 1) {
            $rules['position'] = ['required', 'in:sekretaris_umum,bendahara_umum'];
        } else {
            $rules['department_choices'] = ['required', 'array', 'size:2', 'list'];
            $rules['department_choices.*'] = ['required', 'distinct', Rule::in(array_keys(config('superadmin.departments')))];
            $rules['department_reasons'] = ['required', 'array', 'size:2', 'list'];
            $rules['department_reasons.*'] = ['required', 'string', 'min:10', 'max:2000'];
        }
        $data = $request->validate($rules);
        $paths = [];
        try {
            DB::transaction(function () use ($request, $period, $data, &$paths) {
                $period = Period::whereKey($period->id)->lockForUpdate()->firstOrFail();
                Flow::require($period->stage === 'registration' && $period->sedangDibuka(), 'Pendaftaran sudah ditutup.');
                Flow::require(! Application::where('periode_id', $period->id)->where('user_id', $request->user()->id)->exists(), 'Anda sudah mendaftar pada batch ini.');
                $reviewer = $this->flow->reviewer($period);
                foreach (Flow::documents($period->batch) as $key => $label) {
                    $path = $data['documents'][$key]->store('kbh/seleksi/'.$period->id, 'local');
                    Flow::require($path !== false, 'Berkas gagal disimpan. Silakan coba lagi.');
                    $paths[$key] = $path;
                }
                Application::create([
                    'periode_id' => $period->id, 'user_id' => $request->user()->id,
                    'documents' => $paths, 'khs_path' => $paths['khs'] ?? null,
                    'position' => $data['position'] ?? null,
                    'department_choices' => $data['department_choices'] ?? null,
                    'department_reasons' => $data['department_reasons'] ?? null,
                    'reviewer_id' => $reviewer->id, 'status' => 'menunggu',
                ]);
            }, 1);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_values($paths));
            throw $exception;
        }

        return redirect()->route('recruitment.result')->with('success', 'Pendaftaran diterima. Berkas ditugaskan otomatis kepada satu pemeriksa.');
    }

    public function result(Request $request): View
    {
        $period = Period::whereNotNull('batch')->latest('id')->first();
        $applications = Application::with('period')->where('user_id', $request->user()->id)->whereHas('period', fn ($q) => $q->whereNotNull('batch'))->latest('id')->get();
        $application = $applications->first();

        return view('kbh.seleksi.recruitment-result', compact('period', 'application', 'applications'));
    }
}
