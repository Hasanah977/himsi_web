@extends('department.layout')
@section('title', 'Program Kerja')
@section('content')
<div class="k-heading">
<div>
<div class="k-eyebrow">PERENCANAAN & PELAKSANAAN</div>
<h1>Program Kerja</h1>
<p>Kelola rencana dan kemajuan kegiatan Departemen {{ $departmentLabel }}.</p>
</div>
<a class="btn o-btn-yellow" href="{{ route('department.program.create', $departmentKey) }}">
<i class="bi bi-plus-lg" aria-hidden="true">
</i> Buat Proker Baru</a>
</div>
<div class="k-tabs">
<a class="{{ !request('status') ? 'active' : '' }}" href="{{ route('department.programs', $departmentKey) }}">Semua</a>@foreach(config('department.program_statuses') as $key => $label)<a class="{{ request('status') === $key ? 'active' : '' }}" href="{{ route('department.programs', [$departmentKey, 'status' => $key]) }}">{{ $label }}</a>@endforeach</div>
<form class="o-filters" method="GET">
<input type="hidden" name="status" value="{{ request('status') }}">
<label class="k-search">
<i class="bi bi-search" aria-hidden="true">
</i>
<input name="q" value="{{ request('q') }}" placeholder="Cari nama program kerja..." aria-label="Cari program kerja" maxlength="120">
</label>
<button class="btn btn-navy" type="submit">Cari</button>
</form>
<div class="o-card-grid">@forelse($programs as $program)<article class="o-program-card">
<div class="o-card-top">
<span class="o-icon">
<i class="bi bi-calendar-event" aria-hidden="true">
</i>
</span>@include('department.status', ['status' => $program->status])</div>
<small>{{ $departmentLabel }} · {{ $program->period }}</small>
<h2>{{ $program->title }}</h2>
<p>{{ \Illuminate\Support\Str::limit($program->description, 130) }}</p>
<ul class="o-meta">
<li>
<i class="bi bi-calendar3" aria-hidden="true">
</i>{{ $program->starts_on->format('d M') }} – {{ $program->ends_on->format('d M Y') }}</li>
<li>
<i class="bi bi-person" aria-hidden="true">
</i>{{ $program->pic?->name ?? '—' }} · {{ $program->members_count }} anggota</li>
<li>
<i class="bi bi-geo-alt" aria-hidden="true">
</i>{{ $program->location }}</li>
</ul>@php($budget = $program->submissions->firstWhere('type', 'budget'))<div class="o-budget-line">RAB <strong>{{ $budget ? 'Rp '.number_format($budget->total, 0, ',', '.') : 'Belum disusun' }}</strong>
</div>
<div class="o-card-progress">
<small>Progress tugas <b>{{ $program->progress }}%</b>
</small>
<progress class="k-progress" value="{{ $program->progress }}" max="100" aria-label="Kemajuan tugas">
</progress>
</div>
<a class="btn btn-outline-navy w-100" href="{{ route('department.program.show', [$departmentKey, $program->id]) }}">Lihat Detail <i class="bi bi-arrow-right" aria-hidden="true">
</i>
</a>
@include('department.program-actions')
</article>@empty<div class="k-panel k-empty o-full">
<i class="bi bi-calendar2-plus" aria-hidden="true">
</i>
<h2>Belum ada program kerja</h2>
<p>Tambahkan proker atau ubah filter pencarian.</p>
</div>@endforelse</div>
@include('department.pagination', ['items' => $programs])
@endsection
