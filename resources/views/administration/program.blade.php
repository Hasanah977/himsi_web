@extends('administration.layout')
@section('title', $program->title)
@section('content')
<div class="admin-panel"><h2 class="h4">{{ $program->title }}</h2><p>{{ $program->description }}</p><p>{{ $program->location }} · {{ $program->status }}</p><p>PIC: {{ $program->pic?->name ?? '—' }}</p>
@if(auth()->user()->role==='koordinator_pengawas')<a class="btn btn-navy" href="{{ route('pengawas.laporan.create',['ops_program_id'=>$program->id]) }}">Catat Pengawasan</a>@endif</div>
<div class="admin-panel"><h3 class="h5">Pengajuan</h3>@forelse($program->submissions as $submission)<p>{{ $submission->type }} — {{ $submission->status }}</p>@empty<p>Belum ada pengajuan.</p>@endforelse</div>
<div class="admin-panel"><h3 class="h5">Tugas Pelaksanaan</h3>@forelse($program->tasks as $task)<p>{{ $task->title }} — {{ $task->status }}</p>@empty<p>Belum ada tugas.</p>@endforelse</div>
@endsection
