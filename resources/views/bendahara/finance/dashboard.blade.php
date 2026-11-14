@extends('administration.layout')
@section('title', 'Dashboard Keuangan')
@section('content')
<x-role-dashboard name="treasury" eyebrow="Pusat Keuangan HIMSI" :title="'Selamat datang, '.auth()->user()->name" description="Pantau kas, pengajuan dana, dan iuran organisasi dari data yang tercatat.">
<div class="finance-page">
@if($unsynced)<div class="alert alert-warning mt-3">Ada {{ $unsynced }} pembayaran lunas lama yang belum masuk kas. Sinkronisasikan iuran sebelum menggunakan saldo.</div>@endif
<div class="finance-dark-stats">@include('bendahara.finance.stats')</div>
<div class="rd-section-heading"><h2>Ringkasan Keuangan</h2><span>Data diperbarui dari sistem · {{ config('finance.start') }} — {{ config('finance.end') }}</span></div>
<div class="rd-grid rd-grid-wide">
<x-dashboard-panel title="Arus Kas 6 Bulan Terakhir" icon="bar-chart">
@php
    $maximum = max(1, collect($months)->max(fn ($month) => max($month['income'], $month['expense'])));
    $colors = ['#142b5c', '#ffcf00', '#24ad68', '#3d7bf3', '#a8afbe'];
    $stops = [];
    $offset = 0;
    foreach ($categories as $name => $amount) {
        $end = $offset + ($summary['income'] ? $amount / $summary['income'] * 100 : 0);
        $stops[] = $colors[count($stops) % count($colors)].' '.$offset.'% '.$end.'%';
        $offset = $end;
    }
@endphp
@if(count($months))
<div class="treasury-chart-scroll">
<svg class="treasury-chart" viewBox="0 0 720 300" role="img" aria-label="Pemasukan dan pengeluaran per bulan dalam rupiah">
@for($i=0;$i<=4;$i++)
<line x1="84" y1="{{ 30+$i*52 }}" x2="705" y2="{{ 30+$i*52 }}" stroke="#e6e9ef"/>
<text x="77" y="{{ 34+$i*52 }}" text-anchor="end">{{ number_format($maximum*(4-$i)/4,0,',','.') }}</text>
@endfor
@foreach($months as $i=>$month)
@php
    $x = 100 + $i * 600 / count($months);
@endphp
@foreach(['income'=>'#24ad68','expense'=>'#142b5c'] as $key=>$color)
@php
    $height = $month[$key]/$maximum*208;
    $barX = $x + ($key === 'income' ? 0 : 35);
@endphp
<rect x="{{ $barX }}" y="{{ 238-$height }}" width="28" height="{{ $height }}" rx="2" fill="{{ $color }}"><title>{{ $month['label'] }} · {{ $key==='income'?'Pemasukan':'Pengeluaran' }}: Rp {{ number_format($month[$key],0,',','.') }}</title></rect>
<text x="{{ $barX+14 }}" y="{{ 231-$height }}" text-anchor="middle" fill="{{ $color }}" class="treasury-value">{{ number_format($month[$key],0,',','.') }}</text>
@endforeach
<text x="{{ $x+31 }}" y="261" text-anchor="middle">{{ $month['label'] }}</text>
@endforeach
</svg>
</div>
<div class="treasury-legend"><span><i style="background:#24ad68"></i>Pemasukan</span><span><i style="background:#142b5c"></i>Pengeluaran</span></div>
@else<p class="rd-empty">Periode kepengurusan belum dimulai.</p>@endif
</x-dashboard-panel>
<x-dashboard-panel title="Komposisi Pemasukan" icon="pie-chart">
<p class="treasury-caption">Sumber pemasukan pada periode kepengurusan.</p>
<div class="treasury-composition">
<div class="treasury-donut" role="img" aria-label="Total pemasukan Rp {{ number_format($summary['income'],0,',','.') }}" style="background:{{ count($stops)?'conic-gradient('.implode(',',$stops).')':'#e5e9f0' }}">
<div><small>Total Pemasukan</small><strong>Rp {{ number_format($summary['income'],0,',','.') }}</strong></div>
</div>
<div class="treasury-category-list">
@forelse($categories as $name=>$amount)
<div><i style="background:{{ $colors[$loop->index % count($colors)] }}"></i><span><b>{{ $name }}</b><small>Rp {{ number_format($amount,0,',','.') }} ({{ $summary['income']?round($amount/$summary['income']*100,1):0 }}%)</small></span></div>
@empty<p>Belum ada pemasukan.</p>@endforelse
</div>
</div>
</x-dashboard-panel>
</div>
<div class="rd-grid rd-grid-wide">
<x-dashboard-panel title="Pengajuan Dana Terbaru" icon="file-earmark-text" :href="route('finance.funding')">
<div class="rd-scroll"><table class="rd-table"><thead><tr><th>Kegiatan</th><th>Departemen</th><th>Nominal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($recentFunding as $submission)
<tr><td>{{ $submission->program?->title ?? 'Program tidak tersedia' }}</td><td>{{ config('superadmin.departments.'.$submission->program?->department, '—') }}</td><td class="text-nowrap">Rp {{ number_format($submission->total,0,',','.') }}</td><td><span class="rd-pill">{{ config('department.submission_statuses.'.$submission->status, $submission->status) }}</span></td><td><a class="rd-action" href="{{ route('finance.funding.show',$submission) }}">Tinjau</a></td></tr>
@empty<tr><td colspan="5" class="rd-empty">Belum ada pengajuan dana.</td></tr>@endforelse
</tbody></table></div>
</x-dashboard-panel>
<x-dashboard-panel title="Iuran & Pengajuan" icon="people">
<div class="treasury-dues"><div><strong>Iuran {{ now()->translatedFormat('F Y') }}</strong><small>{{ $dues['paid'] }} dari {{ $dues['total'] }} anggota telah lunas</small></div><a class="rd-action" href="{{ route('finance.billing') }}">Detail</a></div>
@php($paidPercent=$dues['total']?round($dues['paid']/$dues['total']*100,1):0)
<div class="treasury-progress"><progress max="100" value="{{ $paidPercent }}" aria-label="Persentase iuran lunas"></progress><strong>{{ $paidPercent }}%</strong></div>
@foreach([
    ['Pembayaran perlu verifikasi',$pending,route('finance.billing',['status'=>'menunggu_verifikasi'])],
    ['Pengajuan menunggu review',$incoming,route('finance.funding',['tab'=>'incoming'])],
    ['Pengajuan siap dicairkan',$ready,route('finance.funding',['tab'=>'ready'])]
] as [$label,$count,$url])
<div class="treasury-summary-row"><span>{{ $label }}</span><strong>{{ $count }}</strong><a class="rd-action" href="{{ $url }}">Detail</a></div>
@endforeach
</x-dashboard-panel>
</div>
</div>
</x-role-dashboard>
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/himsi-treasury-dashboard.css') }}?v=20261112">
@endpush
