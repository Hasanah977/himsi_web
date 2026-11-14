@extends('layouts.sekretaris')
@section('title', 'Pengajuan Tanda Tangan')

@section('content')
<style>
    .ttd-page { width: 100%; max-width: 1180px; margin: 0 auto; min-width: 0; }
    .ttd-heading { color: #101b4c; font-size: clamp(1.3rem, 2vw, 1.6rem); font-weight: 800; }
    .ttd-help { color: #64748b; font-size: .94rem; line-height: 1.65; }
    .ttd-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 16px; }
    .ttd-item { background: white; border: 1px solid #e6ebf3; border-radius: 16px; padding: 20px; min-width: 0; box-shadow: 0 5px 18px rgba(16,27,76,.04); }
    .ttd-item-title { color: #101b4c; font-weight: 800; overflow-wrap: anywhere; }
    .ttd-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 18px; }
    .ttd-label { color: #64748b; font-size: .77rem; margin-bottom: 4px; }
    .ttd-value { font-weight: 600; color: #172454; font-size: .93rem; overflow-wrap: anywhere; }
    .ttd-pill { border-radius: 100px; padding: 5px 10px; font-weight: 700; font-size: .78rem; display: inline-block; }
    .ttd-pill-ok { color:#17653d; background:#dcfce7; }
    .ttd-pill-wait { color:#855800; background:#fff3c4; }
    .ttd-pill-no { color:#9e2633; background:#fee2e2; }
    .ttd-pill-muted { color:#475569; background:#edf2f7; }
    .ttd-action { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; border-top: 1px solid #e6ebf3; padding-top: 16px; margin-top: 18px; }
    .ttd-action .btn { white-space: normal; }
    @media(max-width: 575px) {
        .ttd-item { padding: 16px; }
        .ttd-meta { grid-template-columns: 1fr; gap: 12px; }
        .ttd-action .btn { flex: 1; }
    }
</style>

<div class="ttd-page">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div>
            <h2 class="ttd-heading mb-1">Pengajuan Tanda Tangan</h2>
            <p class="ttd-help mb-0">Pantau persetujuan Surat Keluar, baik Word, PDF, maupun gambar.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('sekretaris.surat-keluar.index') }}">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Surat Keluar
        </a>
    </div>

    <div class="alert alert-info border-0 rounded-3 mb-4" role="status">
        <i class="bi bi-info-circle me-1"></i>
        Surat dapat diunggah sebagai Word, PDF, atau gambar dan disetujui tanpa konversi. Penerbitan PDF bertanda tangan otomatis hanya berlaku untuk dokumen PDF asli setelah seluruh izin pejabat lengkap.
        Surat lama yang tidak memilih pendamping tetap akan ditampilkan sebagai <strong>Ketua Umum saja</strong>.
    </div>

    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="ttd-list">
        @forelse($suratKeluars as $surat)
            @php
                $izin = $surat->persetujuanTandaTangan->first();
                $status = $surat->status;
                $sudahFinal = filled($surat->file_final);
                $pendamping = $surat->penandatanganPendamping;
                $formatAsli = strtolower(pathinfo($surat->file_surat ?? '', PATHINFO_EXTENSION));
            @endphp
            <article class="ttd-item">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="flex-grow-1" style="min-width:0">
                        <div class="ttd-label">Perihal surat</div>
                        <div class="ttd-item-title">{{ $surat->perihal ?: 'Tanpa perihal' }}</div>
                    </div>
                    @if($sudahFinal)
                        <span class="ttd-pill ttd-pill-ok">PDF Final</span>
                    @elseif($status === 'ditolak')
                        <span class="ttd-pill ttd-pill-no">Ditolak</span>
                    @elseif($status === 'disetujui' && $formatAsli !== 'pdf')
                        <span class="ttd-pill ttd-pill-ok">Dokumen Asli Disetujui</span>
                    @elseif($status === 'disetujui')
                        <span class="ttd-pill ttd-pill-wait">Belum Final</span>
                    @else
                        <span class="ttd-pill ttd-pill-muted">Diproses</span>
                    @endif
                </div>

                <div class="ttd-meta">
                    <div>
                        <div class="ttd-label">Persetujuan Ketua Umum</div>
                        <div class="ttd-value">
                            @if($status === 'disetujui')
                                <span class="ttd-pill ttd-pill-ok">Disetujui</span>
                            @elseif($status === 'ditolak')
                                <span class="ttd-pill ttd-pill-no">Ditolak</span>
                            @else
                                <span class="ttd-pill ttd-pill-wait">Menunggu</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="ttd-label">Penandatangan pendamping</div>
                        <div class="ttd-value">{{ $pendamping?->nama_lengkap ?: 'Ketua Umum saja' }}</div>
                    </div>
                    <div>
                        <div class="ttd-label">Keputusan pendamping</div>
                        <div class="ttd-value">
                            @if(!$pendamping)
                                Tidak diperlukan
                            @elseif($izin?->status === 'disetujui')
                                <span class="ttd-pill ttd-pill-ok">Disetujui</span>
                            @elseif($izin?->status === 'ditolak')
                                <span class="ttd-pill ttd-pill-no">Ditolak</span>
                            @else
                                <span class="ttd-pill ttd-pill-wait">Menunggu</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="ttd-label">Arsip</div>
                        <div class="ttd-value">{{ $surat->diarsipkan_pada ? 'Sudah diarsipkan' : 'Belum diarsipkan' }}</div>
                    </div>
                </div>

                <div class="ttd-action">
                    <a class="btn btn-primary btn-sm" href="{{ route('sekretaris.surat-keluar.show', $surat) }}">
                        <i class="bi bi-eye me-1"></i> Detail & Pengesahan
                    </a>
                    @if($sudahFinal)
                        <a class="btn btn-outline-success btn-sm" href="{{ route('surat-keluar.dokumen-final.lihat', $surat) }}" data-himsi-pdf-preview rel="noopener">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Lihat PDF Final
                        </a>
                    @endif
                </div>
            </article>
        @empty
            <div class="ttd-item text-center" style="grid-column:1/-1">
                <i class="bi bi-file-earmark-text fs-2 text-secondary"></i>
                <h3 class="fs-6 fw-bold mt-2">Belum ada pengajuan tanda tangan</h3>
                <p class="ttd-help">Surat yang sudah diajukan oleh Sekertaris akan muncul di sini.</p>
                <a href="{{ route('sekretaris.surat-keluar.index') }}" class="btn btn-primary">Buka Surat Keluar</a>
            </div>
        @endforelse
    </div>
    <div class="mt-4">{{ $suratKeluars->links() }}</div>
</div>

@include('components.himsi-pdf-preview-modal')
@endsection
