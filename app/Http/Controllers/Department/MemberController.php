<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\OpsProgram;
use App\Models\OpsTask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    private function programsFor(string $department): Builder
    {
        return OpsProgram::where('department', $department)->whereIn('status', ['approved', 'running', 'completed'])
            ->with('pic:id,name')->withCount(['tasks', 'tasks as done_count' => fn (Builder $query) => $query->where('status', 'done')]);
    }

    public function dashboard(Request $request, string $department): View
    {
        $tasks = OpsTask::whereHas('program', fn (Builder $query) => $query->where('department', $department))
            ->when(! $request->user()->isSuperAdmin(), fn (Builder $query) => $query->where('assigned_to', $request->user()->id));
        $stats = [
            'Tugas Aktif' => (clone $tasks)->where('status', '!=', 'done')->count(),
            'Menunggu Review' => (clone $tasks)->where('status', 'submitted')->count(),
            'Tugas Selesai' => (clone $tasks)->where('status', 'done')->count(),
            'Program Berjalan' => $this->programsFor($department)->where('status', 'running')->count(),
        ];

        return view('department.member-dashboard', [
            'taskCounts' => (clone $tasks)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'totalTasks' => (clone $tasks)->count(),
            'overdueTasks' => (clone $tasks)->whereNotIn('status', ['done', 'submitted'])->whereDate('deadline', '<', today('Asia/Makassar')->toDateString())->count(),
            'stats' => $stats,
            'tasks' => $tasks->with('program')->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")->orderBy('deadline')->limit(6)->get(),
            'programs' => $this->programsFor($department)->latest()->limit(4)->get(),
        ]);
    }

    public function programs(Request $request, string $department): View
    {
        $request->validate(['status' => ['nullable', Rule::in(['approved', 'running', 'completed'])], 'q' => ['nullable', 'string', 'max:120']]);
        $programs = $this->programsFor($department)
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->input('status')))
            ->when($request->filled('q'), fn (Builder $query) => $query->where('title', 'like', '%'.$request->input('q').'%'))
            ->latest()->paginate(12)->withQueryString();

        return view('department.member-programs', compact('programs'));
    }

    public function program(Request $request, string $department, int $program): View
    {
        $program = $this->programsFor($department)->findOrFail($program);
        $program->load(['members:id,name', 'tasks' => fn (HasMany $query) => $query
            ->when(! $request->user()->isSuperAdmin(), fn (Builder $query) => $query->where('assigned_to', $request->user()->id))
            ->orderBy('deadline')]);

        return view('department.member-program', compact('program'));
    }
}
