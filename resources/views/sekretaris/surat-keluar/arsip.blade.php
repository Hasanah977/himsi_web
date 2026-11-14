@extends('layouts.sekretaris')
@section('title','Arsip')
@section('content')
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <h2 class="fs-4 fw-bold m-0"><i class="bi bi-archive me-2"></i>Arsip</h2>
        <a href="{{ route('sekretaris.surat-keluar.index') }}" class="btn btn-outline-primary">Kembali</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <p class="text-secondary">Seluruh arsip surat, LPJ departemen, PPRK, dan RPK dalam satu tempat.</p>
    <nav class="notification-tabs mb-4" aria-label="Jenis arsip">@foreach(['surat' => 'Surat Keluar', 'lpj' => 'LPJ Departemen', 'pprk' => 'RPK', 'rpk' => 'PPRK'] as $key => $label)<a class="{{ $tab === $key ? 'active' : '' }}" href="{{ route('sekretaris.arsip-surat.index', ['tab' => $key]) }}">{{ $label }}</a>@endforeach</nav>
    @if($tab === 'surat')
    <div class="table-responsive"><table class="table table-hover align-middle">
    <thead><tr><th>Nomor Surat</th><th>Jenis</th><th>Perihal</th><th>Diarsipkan</th><th>Aksi</th></tr></thead>
    <tbody>@forelse($suratKeluars as $surat)
        <tr><td>{{ $surat->nomor_surat }}</td><td>{{ $surat->jenis_surat }}</td><td>{{ $surat->perihal }}</td><td>{{ $surat->diarsipkan_pada?->format('d/m/Y') }}</td>
        <td class="text-nowrap">
            <a class="btn btn-sm btn-primary" href="{{ route('sekretaris.surat-keluar.show',$surat) }}">Detail</a>
            <form class="d-inline" method="POST" action="{{ route('sekretaris.surat-keluar.pulihkan-arsip',$surat) }}">@csrf
                <button class="btn btn-sm btn-outline-warning" type="submit">Keluarkan dari arsip</button>
            </form>
        </td></tr>
    @empty<tr><td colspan="5" class="text-center py-5">Belum ada surat yang diarsipkan.</td></tr>@endforelse</tbody>
    </table></div>{{ $suratKeluars->links() }}
    @elseif(in_array($tab, ['lpj', 'rpk'], true))
    <div class="table-responsive"><table class="table"><thead><tr><th>Program Kerja</th><th>Departemen</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($reports as $report)<tr><td>{{ $report->program->title }}</td><td>{{ config('superadmin.departments.'.$report->program->department) }}</td><td>@include('department.status', ['status' => $report->status, 'kind' => 'submission'])</td><td><a class="btn btn-sm btn-outline-navy" href="{{ route('department.review', $report) }}">Lihat {{ $tab === 'rpk' ? 'PPRK' : 'LPJ' }}</a></td></tr>@empty<tr><td colspan="4" class="text-center py-5">Belum ada {{ $tab === 'rpk' ? 'PPRK' : 'LPJ' }} yang diarsipkan.</td></tr>@endforelse</tbody></table></div>{{ $reports->links() }}
    @else
    <div class="table-responsive"><table class="table"><thead><tr><th>Departemen</th><th>Periode</th><th>Diarsipkan</th><th>Aksi</th></tr></thead><tbody>@forelse($pprks as $pprk)<tr><td>{{ config('superadmin.departments.'.$pprk->department) }}</td><td>{{ $pprk->period }}</td><td>{{ $pprk->archived_at?->format('d M Y') ?? '—' }}</td><td><a class="btn btn-sm btn-outline-navy" href="{{ route('department.pprk.file', $pprk) }}">Unduh RPK</a></td></tr>@empty<tr><td colspan="4" class="text-center py-5">Belum ada RPK yang diarsipkan.</td></tr>@endforelse</tbody></table></div>{{ $pprks->links() }}
    @endif
</div></div>
@endsection
