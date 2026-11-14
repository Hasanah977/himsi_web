@extends('administration.layout')
@section('title', 'Dashboard')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/himsi-role-dashboards.css') }}?v=20261111">
@endpush
@section('content')
<div class="role-dashboard">
<section class="admin-welcome"><div><small>RUANG KOLABORASI HIMSI</small><h2>Selamat datang, {{ auth()->user()->name }}</h2><p>Kelola tanggung jawab dan pantau perkembangan organisasi dalam satu sistem.</p></div></section>
<div class="admin-stat-grid">@foreach($stats as $label=>$count)<article class="admin-stat-card"><strong>{{ $count }}</strong><span>{{ $label }}</span></article>@endforeach</div>
@if($role === 'ketua_umum')
<section class="admin-panel"><div class="d-flex justify-content-between flex-wrap gap-3"><div><h2 class="h5">Program Kerja per Departemen</h2><p class="text-secondary">Pantau jumlah program dan pencapaian setiap departemen.</p></div><a href="{{ route('ketua-umum.monitoring-organisasi') }}" class="btn btn-outline-navy align-self-start">Lihat Rincian</a></div>
@php($maximum = max(1, $departmentStats->max('total') ?? 0))
<div class="d-flex gap-4 small my-3"><span><i class="bi bi-square-fill" style="color:#283964"></i> Total program</span><span><i class="bi bi-square-fill" style="color:#2b9e91"></i> Selesai</span></div>
@foreach(config('superadmin.departments') as $key=>$label)@php($entry=$departmentStats->get($key))<div class="mb-4"><div class="d-flex justify-content-between mb-2"><strong>{{ $label }}</strong><span>{{ $entry->completed ?? 0 }} / {{ $entry->total ?? 0 }} selesai</span></div><div class="rounded-2 bg-light" style="height:24px"><div class="rounded-2" style="height:100%;background:#283964;width:{{ (($entry->total ?? 0)/$maximum)*100 }}%"><div class="rounded-2" style="height:100%;background:#2b9e91;width:{{ ($entry->total ?? 0) ? $entry->completed / $entry->total * 100 : 0 }}%"></div></div></div></div>@endforeach
@if(!$departmentStats->sum('total'))<p class="text-secondary">Belum ada program kerja tercatat. Grafik akan terisi saat departemen mengajukan program.</p>@endif</section>
@else
<section class="admin-panel"><h2 class="h5 mb-2">{{ $chartTitle }}</h2><p class="text-secondary mb-4">{{ $chartDescription }}</p><x-stat-bars :items="$chartStats" /></section>
@endif
</div>
@endsection
