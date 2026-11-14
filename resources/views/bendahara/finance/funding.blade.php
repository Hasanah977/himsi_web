@extends('administration.layout')
@section('title', 'Pengajuan Dana')
@section('content')
<div class="finance-page"><div class="finance-heading"><div><h2>Pengeluaran & Pengajuan Dana</h2><p>Kelola pengajuan dana dari seluruh departemen HIMSI.</p></div></div>
@php($tabs=['incoming'=>'Pengajuan Masuk','waiting'=>'Menunggu Ketua','ready'=>'Siap Dicairkan','history'=>'Riwayat'])
<div class="finance-stats">@foreach($tabs as $key=>$label)<article class="finance-stat"><span>{{ $label }}</span><strong>{{ $counts[$key] }}</strong></article>@endforeach</div>
<div class="finance-cash-note"><i class="bi bi-wallet2"></i> Saldo kas tersedia: <strong>Rp {{ number_format($balance,0,',','.') }}</strong></div>
<nav class="finance-tabs finance-funding-tabs" aria-label="Status pengajuan">@foreach($tabs as $key=>$label)<a class="{{ $tab===$key?'active':'' }}" href="{{ route('finance.funding',['tab'=>$key]) }}">{{ $label }} <span class="badge text-bg-light">{{ $counts[$key] }}</span></a>@endforeach</nav>
@forelse($items as $item)<article class="finance-panel mb-3"><div class="finance-heading"><div><span class="finance-badge">{{ config('superadmin.departments.'.$item->program->department) }}</span><span class="o-status {{ $item->status }} ms-2">{{ config('department.submission_statuses.'.$item->status) }}</span><h2 class="mt-3">{{ $item->program->title }}</h2><p>{{ Illuminate\Support\Str::limit($item->program->description,180) }}</p></div><div><strong class="fs-5 text-danger">Rp {{ number_format($item->total,0,',','.') }}</strong><p class="small">{{ $item->created_at->format('d M Y') }}</p></div></div><a class="btn btn-navy w-100" href="{{ route('finance.funding.show',$item) }}"><i class="bi bi-clipboard-check"></i> {{ $tab==='incoming'?'Review Pengajuan':'Lihat Detail Pengajuan' }}</a></article>@empty<div class="finance-panel finance-empty">Tidak ada pengajuan pada tahap ini.</div>@endforelse{{ $items->links() }}</div>
@endsection
