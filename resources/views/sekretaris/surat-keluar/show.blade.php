@extends('layouts.sekretaris')

@section('title', 'Detail Surat Keluar')

@push('styles')
<style>
    .sk-detail-page {
        max-width: 1100px;
        margin: 0 auto;
        padding: 16px 0 32px;
    }

    .sk-detail-title {
        color: #101b4c;
        font-size: 26px;
        font-weight: 800;
    }

    .sk-detail-card {
        background: #fff;
        border: 1px solid #e8ecf4;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 6px 24px rgba(16,27,76,.05);
        margin-bottom: 20px;
    }

    .sk-detail-section {
        color: #101b4c;
        font-size: 17px;
        font-weight: 800;
        padding-bottom: 12px;
        border-bottom: 2px solid #facc15;
        margin-bottom: 22px;
    }

    .sk-detail-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .sk-detail-value {
        color: #1e293b;
        font-size: 14px;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .sk-detail-content {
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        line-height: 1.8;
        font-size: 14px;
        color: #334155;
    }

    .sk-detail-status {
        display: inline-block;
        border-radius: 30px;
        padding: 8px 14px;
        font-size: 12px;
        font-weight: 700;
    }

    .sk-detail-draft {
        background: #e2e8f0;
        color: #334155;
    }

    .sk-detail-menunggu {
        background: #fef3c7;
        color: #92400e;
    }

    .sk-detail-disetujui {
        background: #dcfce7;
        color: #166534;
    }

    .sk-detail-ditolak {
        background: #fee2e2;
        color: #991b1b;
    }

    .sk-detail-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 10px;
        background: #101b4c;
        color: #fff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 700;
    }

    .sk-detail-btn:hover {
        background: #facc15;
        color: #101b4c;
    }

    .sk-detail-notice {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 12px;
        padding: 15px;
        color: #92400e;
        font-size: 13px;
    }

    @media (max-width: 767px) {
        .sk-detail-card {
            padding: 17px;
        }

        .sk-detail-title {
            font-size: 22px;
        }
    }
</style>
@endpush

@section('content')
<div class="sk-detail-page">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <a href="{{ route('sekretaris.surat-keluar.index') }}"
               class="text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>
                Kembali ke Surat Keluar
            </a>

            <h2 class="sk-detail-title mt-3 mb-1">
                Detail Surat Keluar
            </h2>

            <p class="text-secondary mb-0">
                Informasi administrasi dan status surat HIMSI.
            </p>
        </div>

        @php
            $statusClass = match($suratKeluar->status) {
                'draft' => 'sk-detail-draft',
                'menunggu_persetujuan' => 'sk-detail-menunggu',
                'disetujui' => 'sk-detail-disetujui',
                'ditolak' => 'sk-detail-ditolak',
                default => 'sk-detail-draft',
            };
        @endphp

        <span class="sk-detail-status {{ $statusClass }}">
            {{ ucwords(str_replace('_', ' ', $suratKeluar->status)) }}
        </span>
    </div>

    <div class="sk-detail-card">
        <h5 class="sk-detail-section">
            <i class="bi bi-file-earmark-text me-2"></i>
            Identitas Surat
        </h5>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="sk-detail-label">Nomor Surat</div>
                <div class="sk-detail-value">
                    {{ $suratKeluar->nomor_surat ?? 'Belum diterbitkan' }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="sk-detail-label">Jenis Surat</div>
                <div class="sk-detail-value">
                    {{ $suratKeluar->jenis_surat }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="sk-detail-label">Kode Lingkup</div>
                <div class="sk-detail-value">
                    {{ $suratKeluar->kode_lingkup ?? '-' }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="sk-detail-label">Kode Penerbit</div>
                <div class="sk-detail-value">
                    {{ $suratKeluar->kode_penerbit ?? '-' }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="sk-detail-label">Tujuan Surat</div>
                <div class="sk-detail-value">
                    {{ $suratKeluar->tujuan }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="sk-detail-label">Tanggal Surat</div>
                <div class="sk-detail-value">
                    {{ $suratKeluar->tanggal_surat?->format('d/m/Y') ?? '-' }}
                </div>
            </div>

            <div class="col-12">
                <div class="sk-detail-label">Perihal</div>
                <div class="sk-detail-value">
                    {{ $suratKeluar->perihal }}
                </div>
            </div>

            <div class="col-12">
                <div class="sk-detail-label">Lampiran</div>
                <div class="sk-detail-value">
                    {{ $suratKeluar->lampiran ?? '-' }}
                </div>
            </div>
        </div>
    </div>

    <div class="sk-detail-card">
        <h5 class="sk-detail-section">
            <i class="bi bi-card-text me-2"></i>
            Isi Surat
        </h5>

        <div class="sk-detail-content">{{ $suratKeluar->isi_surat ?: 'Belum ada isi surat.' }}</div>
    </div>

    <div class="sk-detail-card">
        <h5 class="sk-detail-section">
            <i class="bi bi-paperclip me-2"></i>
            Dokumen Surat
        </h5>

        @if($suratKeluar->file_surat)
            <div class="sk-detail-value">
                <i class="bi bi-file-earmark-check text-success me-2"></i>
                Dokumen telah diunggah.
            </div>

            <p class="text-secondary small mt-2 mb-0">
                PDF dan gambar dapat ditampilkan langsung. File Word dapat diunduh dalam format aslinya.
            </p>
        @else
            <p class="text-secondary mb-0">
                Belum ada dokumen yang diunggah.
            </p>
        @endif
    </div>

    <div class="sk-detail-card">
        <h5 class="sk-detail-section">
            <i class="bi bi-shield-check me-2"></i>
            Informasi Persetujuan
        </h5>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="sk-detail-label">Dibuat Oleh</div>
                <div class="sk-detail-value">
                    {{ $suratKeluar->pembuat?->nama_lengkap ?? '-' }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="sk-detail-label">Disetujui Oleh</div>
                <div class="sk-detail-value">
                    {{ $suratKeluar->penyetuju?->nama_lengkap ?? 'Belum disetujui' }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="sk-detail-label">Tanggal Pengajuan</div>
                <div class="sk-detail-value">
                    {{ $suratKeluar->diajukan_pada?->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i') ?? '-' }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="sk-detail-label">Tanggal Keputusan</div>
                <div class="sk-detail-value">
                    {{ $suratKeluar->diputuskan_pada?->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i') ?? '-' }}
                </div>
            </div>

            <div class="col-12">
                <div class="sk-detail-label">Catatan Persetujuan</div>
                <div class="sk-detail-value">
                    {{ $suratKeluar->catatan_persetujuan ?? '-' }}
                </div>
            </div>
        </div>
    </div>

    @if($riwayatPersetujuan->isNotEmpty())
        <div class="sk-detail-card">
            <h5 class="sk-detail-section">
                <i class="bi bi-clock-history me-2"></i>
                Riwayat Persetujuan
            </h5>

            <div class="d-flex flex-column gap-3">
                @foreach($riwayatPersetujuan as $riwayat)
                    <div class="border rounded-3 p-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <strong>
                                Pengajuan ke-{{ $riwayat->pengajuan_ke }}
                            </strong>

                            @if($riwayat->status_keputusan === 'disetujui')
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Disetujui
                                </span>
                            @elseif($riwayat->status_keputusan === 'ditolak')
                                <span class="badge bg-danger">
                                    <i class="bi bi-x-circle me-1"></i>
                                    Ditolak
                                </span>
                            @endif
                        </div>

                        <div class="sk-detail-label">Tanggal Keputusan</div>
                        <div class="sk-detail-value mb-2">
                            {{ $riwayat->diputuskan_pada
                                ? \Carbon\Carbon::parse($riwayat->diputuskan_pada)->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i')
                                : '-' }}
                        </div>

                        <div class="sk-detail-label">Catatan Ketua Umum</div>
                        <div class="sk-detail-value">
                            {{ $riwayat->catatan ?: 'Tidak ada catatan.' }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    <div class="sk-detail-notice">
        <i class="bi bi-info-circle me-2"></i>
        Tanda tangan Ketua Umum hanya dapat digunakan setelah
        surat memperoleh persetujuan melalui akun Ketua Umum.
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">

        <a href="{{ route('sekretaris.surat-keluar.index') }}"
           class="sk-detail-btn">
            <i class="bi bi-arrow-left"></i>
            Kembali ke Daftar
        </a>

        @if(in_array($suratKeluar->status, ['draft', 'ditolak'], true))
            <a href="{{ route('sekretaris.surat-keluar.edit', $suratKeluar) }}"
               class="sk-detail-btn">
                <i class="bi bi-pencil-square"></i>
                {{ $suratKeluar->status === 'ditolak' ? 'Revisi Surat' : 'Edit Draft' }}
            </a>
        @endif

    </div>

</div>

@if(in_array($suratKeluar->status, ['draft', 'ditolak'], true))
    <form
        action="{{ route('sekretaris.surat-keluar.ajukan', $suratKeluar) }}"
        method="POST"
        class="mt-3"
        data-confirm="Yakin ingin mengajukan surat ini kepada Ketua Umum? Setelah diajukan, draft tidak dapat diedit selama menunggu persetujuan."
    >
        @csrf

        <button
            type="submit"
            class="sk-detail-btn"
            style="background:#facc15;color:#101b4c;border:1px solid #eab308;cursor:pointer;"
        >
            <i class="bi bi-send-check"></i>
            Ajukan ke Ketua Umum
        </button>
    </form>
@endif

@if($suratKeluar->file_surat)
<div style="background:#fff;
            border:1px solid #e2e8f0;
            border-left:4px solid #facc15;
            border-radius:12px;
            padding:20px;
            margin:20px 0;">

    <h3 style="font-size:17px;
               font-weight:800;
               color:#101b4c;
               margin-bottom:8px;">
        <i class="bi bi-file-earmark-text me-2"></i>
        Dokumen Surat Keluar
    </h3>

    <p style="color:#64748b;font-size:14px;">
        Lihat atau unduh dokumen surat yang dilampirkan.
    </p>

    <div style="display:flex;flex-wrap:wrap;gap:10px;">

        @if(in_array(strtolower(pathinfo($suratKeluar->file_surat, PATHINFO_EXTENSION)), ['pdf','jpg','jpeg','png'], true))
        <a href="{{ route('surat-keluar.dokumen.lihat', $suratKeluar) }}"
           data-himsi-pdf-preview
           style="background:#101b4c;
                  color:#fff;
                  padding:11px 17px;
                  border-radius:9px;
                  text-decoration:none;
                  font-weight:700;">
            <i class="bi bi-eye me-1"></i>
            Lihat Dokumen
        </a>
        @endif

        <a href="{{ route('surat-keluar.dokumen.unduh', $suratKeluar) }}"
           style="background:#facc15;
                  color:#101b4c;
                  padding:11px 17px;
                  border-radius:9px;
                  text-decoration:none;
                  font-weight:700;">
            <i class="bi bi-download me-1"></i>
            Unduh Dokumen
        </a>

    </div>
</div>
@endif
@if(in_array($suratKeluar->status, ['draft', 'ditolak'], true)
    && $suratKeluar->file_surat
    && strtolower(pathinfo($suratKeluar->file_surat, PATHINFO_EXTENSION)) === 'pdf')
    <div class="alert alert-info mt-3">Penempatan tanda tangan dilakukan oleh pejabat penandatangan pada akunnya sendiri melalui menu persetujuan. Sekertaris tidak memasukkan tanda tangan pejabat lain.</div>
@endif
@if($suratKeluar->status === 'disetujui' && filled($suratKeluar->nomor_surat)
    && $suratKeluar->file_surat
    && strtolower(pathinfo($suratKeluar->file_surat, PATHINFO_EXTENSION)) === 'pdf')
<div class="card border-0 shadow-sm mt-4 rounded-4"><div class="card-body p-4">
    <h5 class="fw-bold mb-3" style="color:#101b4c"><i class="bi bi-patch-check me-2"></i>Pengesahan PDF dan Arsip</h5>
    <p class="text-secondary">Penandatangan: Ketua Umum{{ $suratKeluar->penandatanganPendamping ? ' + '.$suratKeluar->penandatanganPendamping->nama_lengkap : '' }}.</p>
    @if($suratKeluar->penandatanganPendamping)
        @foreach($suratKeluar->persetujuanTandaTangan as $izin)
        <div class="alert alert-info py-2">Persetujuan {{ $izin->user?->nama_lengkap }}: <strong>{{ ucfirst($izin->status) }}</strong></div>
        @endforeach
    @endif
    @if($suratKeluar->file_final)
        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>PDF final bertanda tangan tersedia, dokumen asli tidak ditimpa.</div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="{{ route('surat-keluar.dokumen-final.lihat',$suratKeluar) }}" data-himsi-pdf-preview>Pratinjau PDF Final</a>
            <a class="btn btn-warning" href="{{ route('surat-keluar.dokumen-final.unduh',$suratKeluar) }}">Unduh PDF Final</a>
        </div>
        @if(!$suratKeluar->diarsipkan_pada)
        <form method="POST" action="{{ route('sekretaris.surat-keluar.arsipkan',$suratKeluar) }}" class="mt-3">@csrf
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-archive me-1"></i>Masukkan ke Arsip</button>
        </form>
        @else
        <div class="alert alert-secondary mt-3">Surat ini telah diarsipkan pada {{ $suratKeluar->diarsipkan_pada->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i') }}.</div>
        @endif
    @else
        <p>PDF final belum diterbitkan. Jika tanda tangan telah disetujui dan gambarnya tersedia, tekan tombol berikut untuk menghasilkan PDF.</p>
        <form method="POST" action="{{ route('sekretaris.surat-keluar.dokumen-final.simpan',$suratKeluar) }}">@csrf
            <label class="d-block mb-3"><input type="checkbox" name="konfirmasi_pengesahan" value="1" required> Saya memahami PDF final dibuat otomatis dari surat asli dan tanda tangan yang disetujui.</label>
            <button type="submit" class="btn btn-warning fw-bold"><i class="bi bi-file-earmark-check me-1"></i>Terbitkan PDF Bertanda Tangan</button>
        </form>
    @endif
</div></div>
@endif
@if($suratKeluar->status === 'disetujui' && $suratKeluar->file_surat
    && strtolower(pathinfo($suratKeluar->file_surat, PATHINFO_EXTENSION)) !== 'pdf')
<div class="card border-0 shadow-sm mt-4 rounded-4"><div class="card-body p-4">
    <h5 class="fw-bold" style="color:#101b4c">Dokumen Disetujui</h5>
    <p>Dokumen asli disetujui tanpa konversi. Tidak ada PDF bertanda tangan otomatis untuk format ini.</p>
    <a class="btn btn-outline-primary" href="{{ route('surat-keluar.dokumen.unduh', $suratKeluar) }}">Unduh Dokumen Asli</a>
    @if(!$suratKeluar->diarsipkan_pada)
    <form method="POST" action="{{ route('sekretaris.surat-keluar.arsipkan',$suratKeluar) }}" class="mt-3">@csrf
        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-archive me-1"></i>Arsipkan Surat</button>
    </form>
    @else
    <p class="text-secondary mt-3 mb-0">Surat sudah diarsipkan.</p>
    @endif
</div></div>
@endif
@if($suratKeluar->file_surat && strtolower(pathinfo($suratKeluar->file_surat, PATHINFO_EXTENSION)) === 'pdf')
<div class="card border-0 shadow-sm mt-3 rounded-4"><div class="card-body p-4">
    <h5 class="fw-bold">Pratinjau Surat PDF Asli</h5>
    <iframe title="Pratinjau surat asli" src="{{ route('surat-keluar.dokumen.lihat',$suratKeluar) }}" style="width:100%;height:60vh;min-height:320px;border:1px solid #ddd;border-radius:12px"></iframe>
</div></div>
@endif
@include('components.himsi-pdf-preview-modal')
@endsection