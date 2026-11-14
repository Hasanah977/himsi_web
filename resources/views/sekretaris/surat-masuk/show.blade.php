@extends('layouts.sekretaris')

@section('title', 'Detail Surat Masuk')

@push('styles')
<style>
    .surat-detail-page {
        max-width: 1100px;
        margin: 0 auto;
    }

    .surat-detail-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
    }

    .surat-detail-header h2 {
        color: #172454;
        font-size: 23px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .surat-detail-header p {
        color: #64748b;
        font-size: 13px;
        margin-bottom: 0;
    }

    .surat-detail-card {
        background: #fff;
        border: 1px solid #e8edf5;
        border-radius: 16px;
        padding: 28px;
        margin-bottom: 20px;
        box-shadow: 0 5px 20px rgba(16, 27, 76, .04);
    }

    .surat-detail-section {
        font-size: 15px;
        font-weight: 700;
        color: #172454;
        border-bottom: 1px solid #e8edf5;
        padding-bottom: 13px;
        margin-bottom: 20px;
    }

    .surat-detail-label {
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .surat-detail-value {
        color: #334155;
        font-size: 14px;
        overflow-wrap: anywhere;
        white-space: pre-wrap;
    }

    .surat-detail-item {
        margin-bottom: 22px;
    }

    .surat-detail-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .surat-detail-actions .btn {
        border-radius: 9px;
        padding: 10px 18px;
        font-size: 13px;
        font-weight: 600;
    }

    .surat-detail-edit {
        background: #172454;
        color: #fff;
        border: none;
    }

    .surat-detail-edit:hover {
        background: #263b82;
        color: #fff;
    }

    .surat-file-status {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background: #f4f7fc;
        border-radius: 10px;
        color: #172454;
        font-size: 13px;
    }

    .surat-file-status i {
        font-size: 25px;
    }

    @media (max-width: 767px) {
        .surat-detail-header h2 {
            font-size: 19px;
        }

        .surat-detail-card {
            padding: 18px;
        }

        .surat-detail-actions {
            flex-direction: column;
        }

        .surat-detail-actions .btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<div class="surat-detail-page">
    <div class="surat-detail-header">
        <div>
            <h2>Detail Surat Masuk</h2>
            <p>Informasi lengkap surat yang tercatat dalam buku kendali HIMSI.</p>
        </div>

        <div class="surat-detail-actions">
            <a href="{{ route('sekretaris.surat-masuk.index') }}"
               class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>
                Kembali
            </a>

            <a href="{{ route('sekretaris.surat-masuk.edit', $suratMasuk) }}"
               class="btn surat-detail-edit">
                <i class="bi bi-pencil-square me-1"></i>
                Edit Surat
            </a>
        </div>
    </div>

    <div class="surat-detail-card">
        <div class="surat-detail-section">
            <i class="bi bi-envelope-paper me-2"></i>
            Identitas Surat
        </div>

        <div class="row">

            <div class="col-md-12 surat-detail-item">
                <div class="surat-detail-label">Nomor Surat</div>
                <div class="surat-detail-value fw-semibold">
                    {{ $suratMasuk->nomor_surat }}
                </div>
            </div>

            <div class="col-md-6 surat-detail-item">
                <div class="surat-detail-label">Tanggal Surat</div>
                <div class="surat-detail-value">
                    {{ $suratMasuk->tanggal_surat?->format('d F Y') ?? '-' }}
                </div>
            </div>

            <div class="col-md-6 surat-detail-item">
                <div class="surat-detail-label">Tanggal Diterima</div>
                <div class="surat-detail-value">
                    {{ $suratMasuk->tanggal_diterima?->format('d F Y') ?? '-' }}
                </div>
            </div>

            <div class="col-md-6 surat-detail-item">
                <div class="surat-detail-label">Pengirim</div>
                <div class="surat-detail-value">
                    {{ $suratMasuk->pengirim }}
                </div>
            </div>

            <div class="col-md-6 surat-detail-item">
                <div class="surat-detail-label">Alamat Pengirim</div>
                <div class="surat-detail-value">
                    {{ $suratMasuk->alamat_pengirim ?? '-' }}
                </div>
            </div>

            <div class="col-12 surat-detail-item">
                <div class="surat-detail-label">Perihal</div>
                <div class="surat-detail-value">
                    {{ $suratMasuk->perihal }}
                </div>
            </div>

            <div class="col-md-6 surat-detail-item">
                <div class="surat-detail-label">Lampiran</div>
                <div class="surat-detail-value">
                    {{ $suratMasuk->lampiran ?? '-' }}
                </div>
            </div>

            <div class="col-md-6 surat-detail-item">
                <div class="surat-detail-label">Bidang Arsip</div>
                <div class="surat-detail-value">
                    {{ $suratMasuk->bidang_arsip ?? '-' }}
                </div>
            </div>
        </div>
    </div>

    <div class="surat-detail-card">
        <div class="surat-detail-section">
            <i class="bi bi-journal-check me-2"></i>
            Disposisi dan Keterangan
        </div>

        <div class="surat-detail-item">
            <div class="surat-detail-label">Disposisi</div>
            <div class="surat-detail-value">
                {{ $suratMasuk->disposisi ?: 'Belum ada disposisi.' }}
            </div>
        </div>

        <div class="surat-detail-item mb-0">
            <div class="surat-detail-label">Keterangan</div>
            <div class="surat-detail-value">
                {{ $suratMasuk->keterangan ?: 'Tidak ada keterangan tambahan.' }}
            </div>
        </div>
    </div>

    <div class="surat-detail-card">
        <div class="surat-detail-section">
            <i class="bi bi-file-earmark-lock me-2"></i>
            Dokumen Arsip
        </div>

        @if($suratMasuk->file_surat)
            <div class="surat-file-status">
                <i class="bi bi-file-earmark-check"></i>
                <div>
                    <strong>Dokumen telah tercatat</strong>
                    <div class="text-secondary mt-1">
                        File tersimpan di penyimpanan privat sistem.
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <a href="{{ route('sekretaris.surat-masuk.lihat-dokumen', $suratMasuk) }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn btn-primary">
                    <i class="bi bi-eye me-1"></i>
                    Lihat Dokumen
                </a>

                <a href="{{ route('sekretaris.surat-masuk.unduh-dokumen', $suratMasuk) }}"
                   class="btn btn-outline-primary">
                    <i class="bi bi-download me-1"></i>
                    Unduh Dokumen
                </a>
            </div>
        @else
            <p class="text-secondary mb-0">
                Belum ada dokumen yang diunggah.
            </p>
        @endif
    </div>
</div>
@endsection