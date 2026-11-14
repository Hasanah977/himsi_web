@extends('layouts.koordinator-pengawas')
@section('title','Pengawasan Aktif')
@section('page-title','Pengawasan Aktif')
@section('content')
<div class="pg-card"><h2 class="h5 fw-bold">Kegiatan dalam Pengawasan</h2><p class="pg-muted">Daftar laporan yang masuk, sedang diaudit, atau memerlukan perbaikan.</p>@include('koordinator-pengawas.partials.tabel-laporan')</div>
@endsection
