@extends('administration.layout')
@section('title', 'Monitoring Program Departemen')
@section('content')
<div class="row g-3">@forelse($programs as $program)<div class="col-md-6"><article class="admin-panel h-100"><small>{{ config('superadmin.departments.'.$program->department) }}</small><h2 class="h5 mt-2">{{ $program->title }}</h2><p>{{ $program->starts_on?->format('d M Y') }} · {{ $program->status }}</p><p>PIC: {{ $program->pic?->name ?? '—' }}</p><a class="btn btn-outline-navy" href="{{ route('administration.program',$program) }}">Lihat Program</a></article></div>@empty<div class="admin-panel">Belum ada program departemen.</div>@endforelse</div><div class="mt-4">{{ $programs->links() }}</div>
@endsection
