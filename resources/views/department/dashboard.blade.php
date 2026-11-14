@extends('department.layout')
@section('title', 'Dashboard')
@section('content')
<div class="k-heading">
<div>
<div class="k-eyebrow">RUANG OPERASIONAL HIMSI</div>
<h1>Dashboard — {{ $departmentLabel }}</h1>
<p>Periode {{ config('department.period') }} · Diperbarui {{ now('Asia/Makassar')->format('d M Y H:i') }} WITA</p>
</div>
<a class="btn o-btn-yellow" href="{{ route('department.program.create', $departmentKey) }}">
<i class="bi bi-plus-lg" aria-hidden="true">
</i> Buat Proker</a>
</div>
@foreach($revisions as $revision)<a class="k-callout" href="{{ route('department.submission.edit', [$departmentKey, $revision->program_id, $revision->type]) }}">
<i class="bi bi-arrow-repeat" aria-hidden="true">
</i>
<span>
<strong>{{ config('department.types')[$revision->type] }} {{ $revision->program->title }} perlu revisi</strong>
<small>{{ $revision->note }}</small>
</span>
<span>Perbaiki <i class="bi bi-chevron-right" aria-hidden="true">
</i>
</span>
</a>@endforeach
<div class="o-stats">@foreach($stats as $label => $value)<article class="o-stat">
<span class="k-stat-icon">
<i class="bi {{ ['bi-calendar-event', 'bi-people', 'bi-list-task', 'bi-file-earmark-text'][$loop->index] }}" aria-hidden="true">
</i>
</span>
<div>
<small>{{ $label }}</small>
<strong>{{ $value }}</strong>
</div>
</article>@endforeach</div>
<div class="o-dashboard-grid">
<section class="k-panel">
<div class="k-panel-head">
<h2>Status Program Kerja Aktif</h2>
<a href="{{ route('department.programs', $departmentKey) }}">Lihat semua</a>
</div>@forelse($programs as $program)<a class="o-program-progress" href="{{ route('department.program.show', [$departmentKey, $program->id]) }}">
<div>
<strong>{{ $program->title }}</strong>@include('department.status', ['status' => $program->status])</div>
<progress class="k-progress" value="{{ $program->progress }}" max="100" aria-label="Kemajuan {{ $program->title }}">
</progress>
<small>{{ $program->done_count }}/{{ $program->tasks_count }} tugas selesai · {{ $program->progress }}%<span>{{ $program->ends_on->format('d M Y') }}</span>
</small>
</a>@empty<div class="k-empty">
<i class="bi bi-calendar-plus" aria-hidden="true">
</i>
<p>Belum ada program kerja aktif.</p>
<a href="{{ route('department.program.create', $departmentKey) }}">Rencanakan proker pertama</a>
</div>@endforelse</section>
<section class="k-panel o-performance">
<div class="k-panel-head">
<h2>Performa Departemen</h2>
</div>@php($percent = $totalTasks ? round($doneTasks / $totalTasks * 100) : 0)<div class="o-ring" style="--progress:{{ $percent }}%">
<div>
<strong>{{ $percent }}%</strong>
<small>Tugas selesai</small>
</div>
</div>
<p>{{ $doneTasks }} dari {{ $totalTasks }} tugas telah divalidasi.</p>
<a class="btn btn-outline-navy w-100" href="{{ route('department.monitoring', [$departmentKey, 'status' => 'submitted']) }}">{{ $reviewTasks }} tugas menunggu review</a>
</section>
</div>
<section class="k-panel">
<div class="k-panel-head">
<h2>Aktivitas Departemen</h2>
<small>Berdasarkan perubahan data</small>
</div>
<div class="table-responsive">
<table class="table k-table">
<thead>
<tr>
<th>Aktivitas</th>
<th>Program Kerja</th>
<th>Keterangan</th>
<th>Waktu</th>
</tr>
</thead>
<tbody>@forelse($logs as $log)<tr>
<td>{{ $log->action }}</td>
<td>{{ $log->title }}</td>
<td>{{ $log->note ?: '—' }}</td>
<td class="text-nowrap">{{ \Carbon\Carbon::parse($log->created_at)->timezone('Asia/Makassar')->format('d M H:i') }}</td>
</tr>@empty<tr>
<td colspan="4" class="text-center py-5">Belum ada aktivitas.</td>
</tr>@endforelse</tbody>
</table>
</div>
</section>
@endsection
