<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\NotifikasiHimsi;
use App\Models\OpsTask;
use App\Models\User;
use App\Support\DepartmentWorkflow as Flow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskController extends Controller
{
    public function index(Request $request, string $department): View
    {
        $request->validate(['status' => ['nullable', Rule::in(array_keys(config('department.task_statuses')))], 'task' => ['nullable', 'integer']]);
        $query = OpsTask::with(['program', 'assignee', 'creator'])->whereHas('program', fn ($q) => $q->where('department', $department));
        $mine = $request->routeIs('department.my-tasks');
        if ($mine && ! $request->user()->isSuperAdmin()) {
            $query->where('assigned_to', $request->user()->id);
        }
        $counts = (clone $query)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $selected = $request->filled('task') ? (clone $query)->findOrFail($request->integer('task')) : null;
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        $tasks = $query->latest()->paginate(12)->withQueryString();

        return view('department.tasks', compact('tasks', 'counts', 'selected', 'mine'));
    }

    public function create(string $department): View
    {
        $people = Flow::people($department)->where('role', 'anggota_departemen')->orderBy('name')->get();
        $counts = OpsTask::whereIn('assigned_to', $people->pluck('id'))->where('status', '!=', 'done')->selectRaw('assigned_to, COUNT(*) as total')->groupBy('assigned_to')->pluck('total', 'assigned_to');
        $programs = Flow::programs($department)->whereIn('status', ['approved', 'running'])->with('members:id,name')->get();

        return view('department.task-form', compact('people', 'counts', 'programs'));
    }

    public function store(Request $request, string $department): RedirectResponse
    {
        $data = $request->validate(['program_id' => ['required', 'integer'], 'members' => ['required', 'array', 'min:1', 'max:200'], 'members.*' => ['integer', 'distinct'], 'title' => ['required', 'string', 'max:160'], 'description' => ['required', 'string', 'max:5000'], 'deadline' => ['required', 'date_format:Y-m-d']]);
        DB::transaction(function () use ($department, $data) {
            $p = Flow::program($department, (int) $data['program_id'], true);
            Flow::require(in_array($p->status, ['approved', 'running'], true), 'Pilih proker yang sudah disetujui atau berjalan.');
            Flow::require($data['deadline'] <= $p->ends_on->toDateString(), 'Deadline tidak boleh melewati tanggal selesai proker.');
            $members = $p->members()->whereIn('users.id', $data['members'])->where('department', $department)->where('is_active', true)->where('role', 'anggota_departemen')->get();
            Flow::require($members->count() === count($data['members']), 'Pilih anggota aktif yang terlibat dalam proker ini dan berasal dari departemen yang sama.');
            foreach ($members as $member) {
                $task = OpsTask::create(['program_id' => $p->id, 'assigned_to' => $member->id, 'created_by' => auth()->id(), 'title' => $data['title'], 'description' => $data['description'], 'deadline' => $data['deadline']]);
                NotifikasiHimsi::kirim($member->id, 'Tugas baru', $task->title, route('department.my-tasks', [$department, 'task' => $task->id], false), 'tugas');
            }
            Flow::history($p, 'Tugas dibagikan', $data['title'].' — '.$members->count().' penerima, masing-masing memiliki status sendiri.');
        });

        return redirect()->route('department.tasks', [$department])->with('success', 'Tugas dibagikan kepada setiap anggota terpilih.');
    }

    public function submit(Request $request, string $department, int $task): RedirectResponse
    {
        $task = OpsTask::whereHas('program', fn ($q) => $q->where('department', $department))->where('assigned_to', $request->user()->id)->findOrFail($task);
        $data = $request->validate(['action' => ['required', Rule::in(['start', 'progress', 'submit'])], 'progress' => ['nullable', 'required_if:action,progress', 'integer', 'min:0', 'max:100'], 'version' => ['required', 'integer'], 'result' => ['nullable', 'required_if:action,submit', 'string', 'max:8000'], 'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:10240']]);
        $path = null;
        try {
            DB::transaction(function () use ($department, $task, $data, $request, &$path) {
                $p = Flow::program($department, $task->program_id, true);
                $t = OpsTask::whereKey($task->id)->lockForUpdate()->firstOrFail();
                Flow::version($t->version, (int) $data['version']);
                Flow::require(in_array($p->status, ['approved', 'running'], true), 'Proker tidak sedang menerima pembaruan tugas.');
                Flow::require(in_array($t->status, ['assigned', 'working', 'revision'], true), 'Tugas menunggu review atau sudah selesai.');
                if ($request->hasFile('file')) {
                    $path = $request->file('file')->store('department/tasks', 'local');
                    Flow::require((bool) $path, 'Lampiran gagal disimpan.');
                    $t->file_path = $path;
                    $t->file_name = $request->file('file')->getClientOriginalName();
                }
                $t->fill(['status' => $data['action'] === 'submit' ? 'submitted' : 'working', 'progress' => $data['action'] === 'submit' ? 100 : ($data['progress'] ?? $t->progress), 'result' => $data['result'] ?? $t->result, 'version' => $t->version + 1])->save();
                Flow::history($p, 'Pembaruan tugas anggota', $t->title.' — '.config('department.task_statuses')[$t->status], $t->toArray());
                $recipients = User::where('is_active', true)->where(fn ($query) => $query
                    ->where(fn ($query) => $query->where('role', 'koordinator_departemen')->where('department', $department))
                    ->orWhere(fn ($query) => $query->whereKey($t->created_by)->where('role', 'super_admin')))->pluck('id');
                foreach ($recipients as $recipient) {
                    NotifikasiHimsi::kirim($recipient, $data['action'] === 'submit' ? 'Tugas menunggu review' : 'Progres tugas diperbarui', $t->title.' — '.$t->progress.'%', route('department.monitoring', [$department, 'task' => $t->id], false), 'tugas');
                }
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }

        return back()->with('success', 'Status dan hasil pekerjaan tersimpan.');
    }

    public function review(Request $request, string $department, int $task): RedirectResponse
    {
        $task = OpsTask::whereHas('program', fn ($q) => $q->where('department', $department))->findOrFail($task);
        $data = $request->validate(['decision' => ['required', Rule::in(['done', 'revision'])], 'feedback' => ['required', 'string', 'max:3000'], 'version' => ['required', 'integer']]);
        DB::transaction(function () use ($department, $task, $data) {
            $p = Flow::program($department, $task->program_id, true);
            $t = OpsTask::whereKey($task->id)->lockForUpdate()->firstOrFail();
            Flow::version($t->version, (int) $data['version']);
            Flow::require($t->status === 'submitted', 'Hanya tugas menunggu review yang dapat divalidasi.');
            $t->update(['status' => $data['decision'], 'feedback' => $data['feedback'], 'progress' => $data['decision'] === 'done' ? 100 : $t->progress, 'completed_at' => $data['decision'] === 'done' ? now() : null, 'version' => $t->version + 1]);
            Flow::history($p, 'Validasi tugas', $t->title.' — '.$data['feedback'], $t->toArray());
            NotifikasiHimsi::kirim($t->assigned_to, $data['decision'] === 'done' ? 'Tugas selesai divalidasi' : 'Tugas perlu revisi', $t->title.' — '.$data['feedback'], route('department.my-tasks', [$department, 'task' => $t->id], false), 'tugas');
        });

        return back()->with('success', 'Hasil validasi tersimpan dan terlihat oleh anggota.');
    }

    public function file(Request $request, string $department, int $task): StreamedResponse
    {
        $t = OpsTask::whereHas('program', fn ($q) => $q->where('department', $department))->findOrFail($task);
        abort_unless($request->user()->isSuperAdmin() || $request->user()->role === 'koordinator_departemen' || $t->assigned_to === $request->user()->id, 404);

        return Flow::file($t->file_path, $t->file_name);
    }
}
