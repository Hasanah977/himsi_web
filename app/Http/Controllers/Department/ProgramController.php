<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\OpsProgram;
use App\Models\PprkSubmission;
use App\Support\DepartmentWorkflow as Flow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(Request $request, string $department): View
    {
        $request->validate(['status' => ['nullable', Rule::in(array_keys(config('department.program_statuses')))], 'q' => ['nullable', 'string', 'max:120']]);
        $query = Flow::programs($department)->with('submissions');
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->input('q').'%');
        }

        return view('department.programs', ['programs' => $query->latest()->paginate(9)->withQueryString()]);
    }

    public function create(string $department): View|RedirectResponse
    {
        if (! PprkSubmission::approvedFor($department, config('department.period'))) {
            return redirect()->route('department.pprk')->with('success', 'Ajukan RPK terlebih dahulu. Pembuatan proker terbuka setelah seluruh persetujuan selesai.');
        }

        return view('department.program-form', ['program' => new OpsProgram, 'people' => Flow::people($department)->orderBy('name')->get()]);
    }

    public function edit(string $department, int $program): View
    {
        $program = Flow::program($department, $program);
        Flow::require(in_array($program->status, ['draft', 'revision', 'rejected'], true), 'Informasi proker terkunci selama proses persetujuan dan pelaksanaan.');
        $program->load('members');

        return view('department.program-form', ['program' => $program, 'people' => Flow::people($department)->orderBy('name')->get()]);
    }

    public function store(Request $request, string $department): RedirectResponse
    {
        return $this->save($request, $department, null);
    }

    public function update(Request $request, string $department, int $program): RedirectResponse
    {
        return $this->save($request, $department, $program);
    }

    public function destroy(Request $request, string $department, int $program): RedirectResponse
    {
        $data = $request->validate(['version' => ['required', 'integer', 'min:0']]);
        DB::transaction(function () use ($department, $program, $data) {
            $record = Flow::program($department, $program, true);
            Flow::version($record->version, (int) $data['version']);
            Flow::require($record->status === 'draft', 'Hanya proker draft yang dapat dihapus.');
            Flow::require(! $record->submissions()->exists() && ! $record->tasks()->exists(), 'Proker yang memiliki pengajuan atau tugas tidak dapat dihapus.');
            $record->delete();
        });

        return redirect()->route('department.programs', $department)->with('success', 'Draft program kerja berhasil dihapus.');
    }

    private function save(Request $request, string $department, ?int $id): RedirectResponse
    {
        $people = Rule::exists('users', 'id')->where(fn ($q) => $q->where('department', $department)->where('is_active', true)->whereIn('role', ['koordinator_departemen', 'anggota_departemen']));
        $data = $request->validate(['title' => ['required', 'string', 'max:160'], 'description' => ['required', 'string', 'max:8000'], 'period' => ['required', 'string', 'max:40'], 'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'], 'location' => ['required', 'string', 'max:200'], 'target' => ['required', 'string', 'max:200'], 'pic_id' => ['required', 'integer', $people], 'members' => ['required', 'array', 'min:1', 'max:200'], 'members.*' => ['integer', 'distinct', $people], 'version' => ['required', 'integer', 'min:0']]);
        $program = DB::transaction(function () use ($data, $department, $id) {
            if (! $id) {
                Flow::require($data['period'] === config('department.period') && PprkSubmission::approvedFor($department, $data['period']), 'RPK departemen untuk periode aktif harus disetujui tiga pengurus dan Koordinator Pengawas sebelum membuat proker.');
            }
            $program = $id ? Flow::program($department, $id, true) : new OpsProgram;
            if ($id) {
                Flow::version($program->version, (int) $data['version']);
                Flow::require(in_array($program->status, ['draft', 'revision', 'rejected'], true), 'Proker ini tidak dapat diedit pada tahap sekarang.');
            }
            $values = collect($data)->except(['members', 'version'])->all();
            if (! $id) {
                $values += ['department' => $department, 'created_by' => auth()->id(), 'status' => 'draft'];
            }
            $program->fill($values);
            $program->version = ($program->version ?? 0) + 1;
            $program->save();
            $program->members()->sync(array_unique([...$data['members'], $data['pic_id']]));
            Flow::history($program, 'Informasi proker disimpan', null, $values);

            return $program;
        });

        return redirect()->route('department.program.show', [$department, $program->id])->with('success', 'Informasi proker tersimpan. Lanjutkan unggah surat proposal.');
    }

    public function show(string $department, int $program): View
    {
        $program = Flow::program($department, $program);
        $program->load(['members', 'submissions', 'tasks.assignee']);
        $history = DB::table('ops_history')->leftJoin('users', 'users.id', '=', 'ops_history.actor_id')->where('program_id', $program->id)->select('ops_history.*', 'users.name as actor')->orderByDesc('ops_history.id')->paginate(15);

        return view('department.program-show', compact('program', 'history'));
    }

    public function transition(Request $request, string $department, int $program): RedirectResponse
    {
        $data = $request->validate(['action' => ['required', Rule::in(['start', 'finish'])], 'version' => ['required', 'integer']]);
        DB::transaction(function () use ($data, $department, $program) {
            $p = Flow::program($department, $program, true);
            Flow::version($p->version, (int) $data['version']);
            if ($data['action'] === 'start') {
                Flow::require($p->status === 'approved', 'Proker harus disetujui Ketua Umum terlebih dahulu.');
                Flow::require($p->submissions()->where('type', 'rpk')->where('status', 'archived')->exists(), 'PPRK harus diverifikasi Sekertaris, Bendahara, Ketua Umum, dan Koordinator Pengawas, lalu diarsipkan Sekertaris sebelum pelaksanaan.');
                Flow::require($p->submissions()->where('type', 'budget')->where('status', 'disbursed')->exists(), 'Dana RAB harus dicairkan Bendahara setelah persetujuan Ketua Umum sebelum kegiatan dimulai.');
                $status = 'running';
            } else {
                Flow::require($p->status === 'running', 'Hanya proker berjalan yang dapat diselesaikan.');
                Flow::require(! $p->tasks()->where('status', '!=', 'done')->exists(), 'Selesaikan dan validasi seluruh tugas terlebih dahulu.');
                Flow::require($p->submissions()->where('type', 'report')->where('status', 'archived')->exists(), 'LPJ harus disetujui Ketua Umum dan Koordinator Pengawas, lalu diarsipkan Sekertaris.');
                Flow::require($p->ends_on->toDateString() <= now('Asia/Makassar')->toDateString(), 'Tanggal selesai kegiatan belum tercapai.');
                $status = 'completed';
            }
            $p->update(['status' => $status, 'version' => $p->version + 1]);
            Flow::history($p, 'Status proker diperbarui', config('department.program_statuses')[$status]);
        });

        return back()->with('success', 'Status proker diperbarui.');
    }
}
