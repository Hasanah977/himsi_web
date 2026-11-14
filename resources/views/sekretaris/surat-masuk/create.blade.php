@extends('layouts.sekretaris')

@section('title', 'Tambah Surat Masuk')

@push('styles')
<style>
    .surat-form-page {
        max-width: 1100px;
        margin: 0 auto;
    }

    .surat-form-header {
        margin-bottom: 24px;
    }

    .surat-form-header h2 {
        font-size: 23px;
        font-weight: 700;
        color: #172454;
    }

    .surat-form-header p {
        color: #64748b;
        font-size: 13px;
    }

    .surat-form-card {
        background: #fff;
        border: 1px solid #e8edf5;
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 5px 20px rgba(16, 27, 76, .04);
    }

    .surat-form-section {
        font-size: 15px;
        font-weight: 700;
        color: #172454;
        padding-bottom: 12px;
        border-bottom: 1px solid #e8edf5;
        margin-bottom: 20px;
    }

    .surat-form-card .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #334155;
    }

    .surat-form-card .form-control,
    .surat-form-card .form-select {
        border-radius: 9px;
        padding: 11px 13px;
        font-size: 13px;
        border-color: #dce3ed;
    }

    .surat-form-card .form-control:focus,
    .surat-form-card .form-select:focus {
        border-color: #172454;
        box-shadow: 0 0 0 3px rgba(23, 36, 84, .08);
    }

    .surat-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 28px;
    }

    .surat-form-actions .btn {
        padding: 11px 24px;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 600;
    }

    .surat-save-btn {
        background: #172454;
        color: #fff;
        border: none;
    }

    .surat-save-btn:hover {
        background: #263b82;
        color: #fff;
    }

    @media (max-width: 767px) {
        .surat-form-header h2 {
            font-size: 19px;
        }

        .surat-form-card {
            padding: 18px;
        }

        .surat-form-actions {
            flex-direction: column-reverse;
        }

        .surat-form-actions .btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<div class="surat-form-page">
    <div class="surat-form-header">
        <h2>Tambah Surat Masuk</h2>
        <p>Isi data surat berdasarkan buku kendali persuratan HIMSI.</p>
    </div>

    <div class="surat-form-card">
        <form action="{{ route('sekretaris.surat-masuk.store') }}"
              method="POST"
              enctype="multipart/form-data">
            @csrf

            <div class="surat-form-section">
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
                           value="{{ old('nomor_surat') }}"
                           class="form-control @error('nomor_surat') is-invalid @enderror"
                           placeholder="Masukkan nomor surat dari pengirim">
                    @error('nomor_surat')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                    <input type="date"
                           name="tanggal_surat"
                           required
                           value="{{ old('tanggal_surat') }}"
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
                           value="{{ old('tanggal_diterima') }}"
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
                           value="{{ old('pengirim') }}"
                           class="form-control @error('pengirim') is-invalid @enderror"
                           placeholder="Nama instansi atau pengirim">
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
                           value="{{ old('alamat_pengirim') }}"
                           class="form-control @error('alamat_pengirim') is-invalid @enderror"
                           placeholder="Alamat instansi atau pengirim">
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
                           value="{{ old('perihal') }}"
                           class="form-control @error('perihal') is-invalid @enderror"
                           placeholder="Contoh: Undangan Rapat Koordinasi">
                    @error('perihal')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Keterangan Lampiran</label>
                    <input type="text"
                           name="lampiran"
                           maxlength="255"
                           value="{{ old('lampiran') }}"
                           class="form-control @error('lampiran') is-invalid @enderror"
                           placeholder="Contoh: 2 lembar">
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
                           value="{{ old('bidang_arsip') }}"
                           class="form-control @error('bidang_arsip') is-invalid @enderror"
                           placeholder="Contoh: Kesekretariatan">
                    @error('bidang_arsip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="surat-form-section mt-4">
                <i class="bi bi-archive me-2"></i>
                Disposisi dan Arsip
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Disposisi</label>
                    <textarea name="disposisi"
                              rows="3"
                              class="form-control @error('disposisi') is-invalid @enderror"
                              placeholder="Catatan arahan atau tindak lanjut surat">{{ old('disposisi') }}</textarea>
                    @error('disposisi')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan"
                              rows="3"
                              class="form-control @error('keterangan') is-invalid @enderror"
                              placeholder="Keterangan tambahan jika diperlukan">{{ old('keterangan') }}</textarea>
                    @error('keterangan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Unggah Dokumen Surat <span class="text-danger">*</span></label>
                    <input type="file"
                           name="file_surat"
                           required
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           class="form-control @error('file_surat') is-invalid @enderror">
                    <div class="form-text">
                        PDF, Word (.doc/.docx), JPG, atau PNG. Maksimal 10 MB. Word dikonversi untuk pratinjau.
                    </div>
                    @error('file_surat')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="surat-form-actions">
                <a href="{{ route('sekretaris.surat-masuk.index') }}"
                   class="btn btn-outline-secondary">
                    Batal
                </a>

                <button type="submit" class="btn surat-save-btn">
                    <i class="bi bi-save me-1"></i>
                    Simpan Surat Masuk
                </button>
            </div>
        </form>
    </div>
</div>
@endsection