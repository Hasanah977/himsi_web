@extends('portal.layout')
@section('portal-title', 'Selamat Bergabung')
@section('portal-width', 'celebration-container')
@section('portal-body-class', 'celebration-page')
@section('portal-content')
<div class="celebration-backdrop" aria-hidden="true">@for($i = 0; $i < 32; $i++)<span style="--i:{{ $i }}"></span>@endfor</div>
<section class="celebration-card">
<x-role-celebration :name="$user->name" :role="config('superadmin.roles.'.$user->role)" :department="$user->department ? config('superadmin.departments.'.$user->department) : null" />
@if($result)<p class="text-center fw-bold">Periode {{ $result['period'] }}</p><div class="row text-center rounded-3 p-3 mb-4" style="background:#fff9df"><div class="col-4"><strong class="fs-3">{{ $result['votes'] }}</strong><small class="d-block">Suara</small></div><div class="col-4 border-start border-end"><strong class="fs-3">{{ $result['percent'] }}%</strong><small class="d-block">Persentase</small></div><div class="col-4"><strong class="fs-3">{{ $result['electors'] }}</strong><small class="d-block">Pemilih</small></div></div>@endif
<form method="POST" action="{{ route('role.welcome.acknowledge') }}">@csrf<button class="btn btn-navy w-100 py-3">{{ $user->role==='ketua_umum' ? 'Mulai Setup Kepengurusan' : 'Mulai Berkarya Bersama HIMSI' }} <i class="bi bi-arrow-right ms-2"></i></button></form>
</section>
@endsection
