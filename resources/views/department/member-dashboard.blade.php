@extends('department.layout')
@section('title', 'Dashboard Anggota Departemen')
@section('content')
@php
    $done = (int) ($taskCounts['done'] ?? 0);
    $working = (int) ($taskCounts['working'] ?? 0);
    $segments = collect($taskCounts)->map(fn ($value, $key) => ['label' => config('department.task_statuses.'.$key, $key), 'value' => (int) $value, 'color' => ['assigned' => '#adb9ce', 'working' => '#3284ff', 'submitted' => '#ffd51c', 'revision' => '#ef6464', 'done' => '#2daa65'][$key] ?? '#929eb3'])->values();
@endphp
<x-role-dashboard name="department-member" eyebrow="HIMSI UNSULBAR — FT" :title="'Selamat datang, '.auth()->user()->name" :description="'Kelola tugas, pantau progres, dan berkontribusi pada program Departemen '.$departmentLabel.'.'">
    <x-dashboard-stats :items="['Total Tugas' => $totalTasks, 'Dalam Proses' => $working, 'Selesai' => $done, 'Terlambat' => $overdueTasks]" :icons="['clipboard-check', 'play-circle', 'check-circle', 'clock']" />
    <div class="rd-notice rd-summary"><strong><i class="bi bi-bar-chart-fill"></i>Ringkasan Pekerjaan Saya</strong><p>Kamu memiliki <mark>{{ $totalTasks }} tugas</mark>, dengan <mark>{{ $working }} sedang dikerjakan</mark>, <mark>{{ $done }} selesai</mark>, dan <mark>{{ $overdueTasks }} lewat tenggat</mark>.</p></div>
    <div class="rd-grid rd-grid-wide">
        <x-dashboard-panel title="Tugas Saya" icon="clipboard-check" :href="route('department.my-tasks', $departmentKey)" link-label="Lihat Semua Tugas">
            <div class="rd-scroll"><table class="rd-table"><thead><tr><th>No.</th><th>Nama Tugas</th><th>Program</th><th>Deadline</th><th>Status</th></tr></thead><tbody>
            @forelse($tasks as $task)
                <tr><td>{{ $loop->iteration }}</td><td><a href="{{ route('department.my-tasks', [$departmentKey, 'task' => $task->id]) }}">{{ $task->title }}</a></td><td>{{ $task->program->title }}</td><td>{{ $task->deadline->format('d M Y') }}</td><td><span class="rd-pill {{ $task->status === 'done' ? 'green' : 'blue' }}">{{ config('department.task_statuses.'.$task->status) }}</span>@if($task->deadline->toDateString() < today('Asia/Makassar')->toDateString() && !in_array($task->status, ['done', 'submitted']))<small class="text-danger">Terlambat</small>@endif</td></tr>
            @empty<tr><td colspan="5" class="rd-empty">Belum ada tugas yang ditugaskan kepada Anda.</td></tr>@endforelse
            </tbody></table></div>
        </x-dashboard-panel>
        <x-dashboard-panel title="Status Tugas" icon="bar-chart-fill"><x-dashboard-donut :segments="$segments" caption="Total Tugas" /><p class="rd-empty-note">Keterlambatan dihitung dari tenggat tugas yang belum selesai atau dikirim untuk review.</p></x-dashboard-panel>
    </div>
    <x-dashboard-panel title="Program Departemen" icon="layers" :href="route('department.member.programs', $departmentKey)" link-label="Lihat Semua Program">
        <div class="rd-programs">@forelse($programs as $program)<a class="rd-program" href="{{ route('department.member.program', [$departmentKey, $program]) }}"><header><h3>{{ $program->title }}</h3><span class="rd-pill {{ $program->status === 'completed' ? 'green' : 'blue' }}">{{ config('department.program_statuses.'.$program->status) }}</span></header><p>{{ $program->description }}</p><div class="rd-results"><progress value="{{ $program->progress }}" max="100" aria-label="Progres {{ $program->title }}"></progress><small>{{ $program->progress }}% · {{ $program->done_count }}/{{ $program->tasks_count }} tugas selesai</small></div></a>@empty<p class="rd-empty">Belum ada program departemen yang disetujui.</p>@endforelse</div>
    </x-dashboard-panel>
</x-role-dashboard>
@endsection
