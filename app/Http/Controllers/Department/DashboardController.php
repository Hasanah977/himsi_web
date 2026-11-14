<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\OpsSubmission;
use App\Models\OpsTask;
use App\Support\DepartmentWorkflow as Flow;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(string $department): View
    {
        $programs = Flow::programs($department);
        $tasks = OpsTask::whereHas('program', fn ($q) => $q->where('department', $department));
        $submissions = OpsSubmission::whereHas('program', fn ($q) => $q->where('department', $department));

        return view('department.dashboard', [
            'programs' => (clone $programs)->whereNotIn('status', ['completed', 'rejected'])->latest()->limit(6)->get(),
            'stats' => ['Total Program Kerja' => (clone $programs)->count(), 'Anggota Aktif' => Flow::people($department)->where('role', 'anggota_departemen')->count(), 'Tugas Aktif' => (clone $tasks)->where('status', '!=', 'done')->count(), 'LPJ Perlu Diselesaikan' => (clone $programs)->where('status', 'running')->whereDoesntHave('submissions', fn ($q) => $q->where('type', 'report')->where('status', 'archived'))->count()],
            'revisions' => (clone $submissions)->with('program')->where('status', 'revision')->latest()->limit(5)->get(),
            'reviewTasks' => (clone $tasks)->where('status', 'submitted')->count(),
            'doneTasks' => (clone $tasks)->where('status', 'done')->count(), 'totalTasks' => (clone $tasks)->count(),
            'logs' => DB::table('ops_history')->join('ops_programs', 'ops_programs.id', '=', 'ops_history.program_id')->where('department', $department)->select('ops_history.*', 'ops_programs.title')->orderByDesc('ops_history.id')->limit(8)->get(),
        ]);
    }
}
