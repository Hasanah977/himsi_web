@extends('layouts.koordinator-pengawas')
@section('title','Laporan Masuk')
@section('page-title','Laporan Masuk')
@section('content')
<div class="pg-card"><div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3"><div><h2 class="h5 fw-bold mb-1">Daftar Laporan Pengawasan</h2><p class="pg-muted mb-0">Laporan yang masuk dan siap ditinjau.</p></div><a class="pg-btn" href="{{ route('pengawas.laporan.create') }}"><i class="bi bi-plus-lg me-1"></i>Catat Laporan</a></div>@include('koordinator-pengawas.partials.tabel-laporan')</div>
@endsection
