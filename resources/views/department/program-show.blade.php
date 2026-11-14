@extends('department.layout')
@section('title', 'Detail Program Kerja')
@section('content')
<div class="k-heading">
<div>
<div class="k-eyebrow">{{ $departmentLabel }} · {{ $program->period }}</div>
<h1>{{ $program->title }}</h1>
<p>{{ $program->starts_on->translatedFormat('d M Y') }} – {{ $program->ends_on->translatedFormat('d M Y') }}</p>
</div>@include('department.status', ['status' => $program->status])</div>
@include('department.program-actions')
@include('department.steps', ['step' => 2])
<div class="o-detail-grid">
<section class="k-panel">
<div class="k-panel-head">
<h2>Informasi Kegiatan</h2>@if(in_array($program->status, ['draft', 'revision', 'rejected']))<a href="{{ route('department.program.edit', [$departmentKey, $program->id]) }}">Edit informasi</a>@endif</div>
<p class="o-preline">{{ $program->description }}</p>
<dl class="o-dl">
<dt>Lokasi</dt>
<dd>{{ $program->location }}</dd>
<dt>Target</dt>
<dd>{{ $program->target }}</dd>
<dt>PIC</dt>
<dd>{{ $program->pic?->name }}</dd>
<dt>Anggota</dt>
<dd>{{ $program->members->pluck('name')->join(', ') }}</dd>
</dl>
</section>
<section class="k-panel">
<h2 class="mb-3">Kemajuan Pelaksanaan</h2>
<strong class="o-big">{{ $program->progress }}%</strong>
<progress class="k-progress my-3" value="{{ $program->progress }}" max="100" aria-label="Kemajuan pelaksanaan">
</progress>
<p>{{ $program->done_count }} dari {{ $program->tasks_count }} tugas selesai.</p>@if($program->status === 'approved')<form method="POST" action="{{ route('department.program.transition', [$departmentKey, $program->id]) }}" data-k-confirm="Perbarui status pelaksanaan proker? Sistem akan memeriksa persetujuan dan penyelesaian yang diperlukan.">@csrf<input type="hidden" name="version" value="{{ $program->version }}">
<input type="hidden" name="action" value="{{ $program->status === 'approved' ? 'start' : 'finish' }}">
<button class="btn btn-navy w-100" type="submit">{{ $program->status === 'approved' ? 'Mulai Pelaksanaan' : 'Selesaikan Proker' }}</button>
</form>@endif</section>
</div>
<div class="o-card-grid">@foreach(config('department.types') as $type => $label)@php($submission = $program->submissions->firstWhere('type', $type))<section class="k-panel o-submission-card">
<div class="k-panel-head">
<h2>{{ $label }}</h2>
<i class="bi {{ ['letter'=>'bi-envelope-paper','budget'=>'bi-wallet2','report'=>'bi-file-earmark-text','rpk'=>'bi-file-earmark-check'][$type] }}" aria-hidden="true">
</i>
</div>@if($submission)@include('department.status', ['status'=>$submission->status, 'kind'=>'submission'])<p class="mt-3">{{ $submission->note ?: 'Pengajuan tercatat. Status mengikuti pemeriksaan pihak berwenang.' }}</p>@if($type === 'budget')<strong class="mb-3">Rp {{ number_format($submission->total, 0, ',', '.') }}</strong>@endif @else<p>Belum ada pengajuan.</p>@endif<a class="btn btn-outline-navy" href="{{ route('department.submission.edit', [$departmentKey, $program->id, $type]) }}">{{ $submission ? 'Lihat / Kelola' : 'Unggah '.$label }}</a>
@if(config('department.templates.'.$type))<a class="small mt-3" href="{{ route('department.document.template', [$departmentKey, $type]) }}"><i class="bi bi-download"></i> Unduh template {{ $label }}</a>@endif
@if($type === 'rpk')<small class="text-secondary mt-2">Wajib diverifikasi tiga pengurus dan Koordinator Pengawas, lalu diarsipkan sebelum pelaksanaan.</small>@endif
@if($type === 'report')<small class="text-secondary mt-2">Unggah setelah progres 100%. Proker selesai setelah LPJ diarsipkan Sekertaris.</small>@endif
</section>@endforeach</div>
<section class="k-panel">
<div class="k-panel-head">
<h2>Tugas Anggota</h2>
<a href="{{ route('department.task.create', $departmentKey) }}">Bagikan tugas</a>
</div>
<div class="table-responsive">
<table class="table k-table">
<thead>
<tr>
<th>Tugas</th>
<th>Penerima</th>
<th>Deadline</th>
<th>Status</th>
</tr>
</thead>
<tbody>@forelse($program->tasks as $task)<tr>
<td>
<a href="{{ route('department.monitoring', [$departmentKey, 'task'=>$task->id]) }}">{{ $task->title }}</a>
</td>
<td>{{ $task->assignee?->name }}</td>
<td>{{ $task->deadline->format('d M Y') }}</td>
<td>@include('department.status', ['status'=>$task->status,'kind'=>'task'])</td>
</tr>@empty<tr>
<td colspan="4" class="py-4 text-center">Belum ada tugas.</td>
</tr>@endforelse</tbody>
</table>
</div>
</section>
@include('department.history')
@endsection
