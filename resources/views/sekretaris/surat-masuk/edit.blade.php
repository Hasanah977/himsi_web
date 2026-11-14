@extends('layouts.sekretaris')

@section('title', 'Edit Surat Masuk')

@push('styles')
<style>
    .surat-edit-page {
        max-width: 1100px;
        margin: 0 auto;
    }

    .surat-edit-header {
        margin-bottom: 24px;
    }

    .surat-edit-header h2 {
        font-size: 23px;
        font-weight: 700;
        color: #172454;
        margin-bottom: 6px;
    }

    .surat-edit-header p {
        color: #64748b;
        font-size: 13px;
        margin-bottom: 0;
    }

    .surat-edit-card {
        background: #fff;
        border: 1px solid #e8edf5;
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 5px 20px rgba(16, 27, 76, .04);
    }

    .surat-edit-section {
        font-size: 15px;
        font-weight: 700;
        color: #172454;
        padding-bottom: 12px;
        border-bottom: 1px solid #e8edf5;
        margin-bottom: 20px;
    }

    .surat-edit-card .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #334155;
    }

    .surat-edit-card .form-control {
        border-radius: 9px;
        padding: 11px 13px;
        font-size: 13px;
        border-color: #dce3ed;
    }

    .surat-edit-card .form-control:focus {
        border-color: #172454;
        box-shadow: 0 0 0 3px rgba(23, 36, 84, .08);
    }

    .surat-edit-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 28px;
    }

    .surat-edit-actions .btn {
        padding: 11px 24px;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 600;
    }

    .surat-edit-save {
        background: #172454;
        color: white;
        border: none;
    }

    .surat-edit-save:hover {
        background: #263b82;
        color: white;
    }

    .surat-existing-file {
        background: #f4f7fc;
        border-radius: 9px;
        padding: 14px;
        color: #334155;
        font-size: 13px;
        margin-bottom: 12px;
    }

    @media (max-width: 767px) {
        .surat-edit-header h2 {
            font-size: 19px;
        }

        .surat-edit-card {
            padding: 18px;
        }

        .surat-edit-actions {
            flex-direction: column-reverse;
        }

        .surat-edit-actions .btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<div class="surat-edit-page">
    <div class="surat-edit-header">
        <h2>Edit Surat Masuk</h2>
        <p>Perbarui informasi surat yang tercatat dalam buku kendali HIMSI.</p>
    </div>

    <div class="surat-edit-card">
        <form action="{{ route('sekretaris.surat-masuk.update', $suratMasuk) }}"
              method="POST"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="surat-edit-section">
                <i class="bi bi-envelope-paper me-2"></i>
                Informasi Surat
            </div>

            <div class="row g-3">

                <div class="col-md-12">
                    <label class="form-label">Nomor Surat <span class="text-danger">*</span></label>
                    <input type="text"
                           name="nomor_surat"
                           required
                           maxlength="100"
                           value="{{ old('nomor_surat', $suratMasuk->nomor_surat) }}"
                           class="form-control @error('nomor_surat') is-invalid @enderror">
                    @error('nomor_surat')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                    <input type="date"
                           name="tanggal_surat"
                           required
                           value="{{ old('tanggal_surat', $suratMasuk->tanggal_surat?->format('Y-m-d')) }}"
                           class="form-control @error('tanggal_surat') is-invalid @enderror">
                    @error('tanggal_surat')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tanggal Diterima <span class="text-danger">*</span></label>
                    <input type="date"
                           name="tanggal_diterima"
                           required
                           value="{{ old('tanggal_diterima', $suratMasuk->tanggal_diterima?->format('Y-m-d')) }}"
                           class="form-control @error('tanggal_diterima') is-invalid @enderror">
                    @error('tanggal_diterima')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Nama Pengirim <span class="text-danger">*</span></label>
                    <input type="text"
                           name="pengirim"
                           required
                           maxlength="150"
                           value="{{ old('pengirim', $suratMasuk->pengirim) }}"
                           class="form-control @error('pengirim') is-invalid @enderror">
                    @error('pengirim')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Alamat Pengirim <span class="text-danger">*</span></label>
                    <input type="text"
                           name="alamat_pengirim"
                           required
                           maxlength="255"
                           value="{{ old('alamat_pengirim', $suratMasuk->alamat_pengirim) }}"
                           class="form-control @error('alamat_pengirim') is-invalid @enderror">
                    @error('alamat_pengirim')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Perihal Surat <span class="text-danger">*</span></label>
                    <input type="text"
                           name="perihal"
                           required
                           maxlength="255"
                           value="{{ old('perihal', $suratMasuk->perihal) }}"
                           class="form-control @error('perihal') is-invalid @enderror">
                    @error('perihal')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Keterangan Lampiran</label>
                    <input type="text"
                           name="lampiran"
                           maxlength="255"
                           value="{{ old('lampiran', $suratMasuk->lampiran) }}"
                           class="form-control @error('lampiran') is-invalid @enderror">
                    @error('lampiran')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Bidang Arsip <span class="text-danger">*</span></label>
                    <input type="text"
                           name="bidang_arsip"
                           required
                           maxlength="100"
                           value="{{ old('bidang_arsip', $suratMasuk->bidang_arsip) }}"
                           class="form-control @error('bidang_arsip') is-invalid @enderror">
                    @error('bidang_arsip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="surat-edit-section mt-4">
                <i class="bi bi-archive me-2"></i>
                Disposisi dan Arsip
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Disposisi</label>
                    <textarea name="disposisi"
                              rows="3"
                              class="form-control @error('disposisi') is-invalid @enderror">{{ old('disposisi', $suratMasuk->disposisi) }}</textarea>
                    @error('disposisi')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan"
                              rows="3"
                              class="form-control @error('keterangan') is-invalid @enderror">{{ old('keterangan', $suratMasuk->keterangan) }}</textarea>
                    @error('keterangan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Dokumen Surat</label>

                    @if($suratMasuk->file_surat)
                        <div class="surat-existing-file">
                            <i class="bi bi-file-earmark-check me-2"></i>
                            Dokumen surat sudah tersimpan di arsip privat.
                        </div>
                    @endif

                    <input type="file"
                           name="file_surat"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           class="form-control @error('file_surat') is-invalid @enderror">

                    <div class="form-text">
                        Kosongkan jika tidak ingin mengganti dokumen.
                        PDF, Word (.doc/.docx), JPG, atau PNG. Maksimal 10 MB. Word dikonversi untuk pratinjau.
                    </div>

                    @error('file_surat')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="surat-edit-actions">
                <a href="{{ route('sekretaris.surat-masuk.show', $suratMasuk) }}"
                   class="btn btn-outline-secondary">
                    Batal
                </a>

                <button type="submit" class="btn surat-edit-save">
                    <i class="bi bi-save me-1"></i>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection