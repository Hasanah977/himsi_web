@extends('layouts.sekretaris')

@section('title', 'Dashboard Sekertaris Umum')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/himsi-secretary-dashboard.css') }}?v=20261111">
@endpush

@section('content')
<section class="secretary-dashboard">
    <header class="secretary-hero">
        <div><span>Pusat Administrasi HIMSI</span><h1>Selamat datang, {{ auth()->user()?->nama_lengkap ?? 'Sekertaris' }}</h1><p>Pantau persuratan, agenda, dan arsip organisasi dari data yang tercatat.</p></div>
    </header>

    @php($statistik = [['Surat Menunggu', $jumlahSuratKeluar, 'envelope'], ['Menunggu Persetujuan', $jumlahMenungguPersetujuan, 'hourglass-split'], ['Agenda 7 Hari', $jumlahAgendaTujuhHari, 'calendar3'], ['LPJ Perlu Ditinjau', $jumlahLpjPerluDitinjau, 'file-earmark-text']])
    <div class="secretary-stats">@foreach($statistik as [$label, $jumlah, $icon])<article><i class="bi bi-{{ $icon }}"></i><div><small>{{ $label }}</small><strong>{{ $jumlah }}</strong></div></article>@endforeach</div>

    <div class="secretary-section-title"><h2>Ringkasan Administrasi</h2><span><i class="bi bi-info-circle"></i> Data diperbarui dari sistem</span></div>
    <div class="row g-3">
        <div class="col-12 col-xl-7"><section class="secretary-panel"><h3><i class="bi bi-file-earmark-text"></i> Alur Persuratan</h3><div class="letter-flow">@foreach($alurPersuratan as $label => $jumlah)<div><b>{{ $jumlah }}</b><span>{{ $label }}</span></div>@endforeach</div><div class="secretary-chart-title"><i class="bi bi-bar-chart-line"></i> Surat keluar 7 hari terakhir</div><div class="secretary-bars">@foreach($statistikSuratKeluar as $hari)<div><b style="height: {{ max(7, min(100, $hari['jumlah'] * 24)) }}%">{{ $hari['jumlah'] }}</b><span>{{ $hari['label'] }}<small>{{ $hari['tanggal'] }}</small></span></div>@endforeach</div></section></div>
        <div class="col-12 col-xl-5"><section class="secretary-panel"><h3><i class="bi bi-calendar-event"></i> Agenda Terdekat</h3><div class="secretary-agenda">@forelse($agendaTerdekat as $agenda)<a href="{{ route('meetings.show', $agenda) }}"><time>{{ $agenda->scheduled_at->timezone('Asia/Makassar')->format('d M') }}<small>{{ $agenda->scheduled_at->timezone('Asia/Makassar')->format('H.i') }}</small></time><span><b>{{ $agenda->title }}</b><small><i class="bi bi-geo-alt"></i> {{ $agenda->location }}</small></span></a>@empty<p class="secretary-empty">Belum ada agenda rapat yang dijadwalkan.</p>@endforelse</div></section></div>
        <div class="col-12 col-xl-8"><section class="secretary-panel"><h3><i class="bi bi-envelope-open"></i> Surat Masuk Terbaru</h3><div class="table-responsive"><table class="table secretary-table"><thead><tr><th>Nomor</th><th>Pengirim</th><th>Perihal</th><th>Diterima</th><th></th></tr></thead><tbody>@forelse($suratMasukTerbaru as $surat)<tr><td>{{ $surat->nomor_surat ?? '—' }}</td><td>{{ $surat->pengirim }}</td><td>{{ $surat->perihal }}</td><td>{{ $surat->tanggal_diterima?->format('d M Y') ?? '—' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('sekretaris.surat-masuk.show', $surat) }}">Buka</a></td></tr>@empty<tr><td colspan="5" class="text-center text-secondary py-4">Belum ada surat masuk yang dicatat.</td></tr>@endforelse</tbody></table></div></section></div>
        <div class="col-12 col-xl-4"><section class="secretary-panel secretary-archive"><h3><i class="bi bi-archive"></i> LPJ & Arsip</h3><a href="{{ route('department.reviews') }}"><i class="bi bi-file-earmark-check"></i><span>LPJ menunggu pengarsipan</span><b>{{ $jumlahLpjPerluDitinjau }}</b></a><a href="{{ route('sekretaris.arsip-surat.index') }}"><i class="bi bi-archive"></i><span>Arsip surat</span><b>{{ $jumlahArsipSurat }}</b></a><a href="{{ route('sekretaris.arsip-surat.index', ['tab' => 'lpj']) }}"><i class="bi bi-journal-check"></i><span>LPJ telah diarsipkan</span><b>{{ $jumlahLpjDiarsipkan }}</b></a></section></div>
    </div>
</section>
@endsection
