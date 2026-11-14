@extends('layouts.sekretaris')

@section('title', 'Tambah Surat Keluar')

@push('styles')
<style>
    .sk-form-page {
        max-width: 1050px;
        margin: 0 auto;
        padding: 16px 0 32px;
    }

    .sk-form-title {
        font-size: 26px;
        font-weight: 800;
        color: #101b4c;
    }

    .sk-form-card {
        background: #fff;
        border: 1px solid #e8ecf4;
        border-radius: 18px;
        padding: 28px;
        box-shadow: 0 8px 28px rgba(16,27,76,.05);
    }

    .sk-form-section {
        font-weight: 800;
        color: #101b4c;
        border-bottom: 2px solid #facc15;
        padding-bottom: 12px;
        margin-bottom: 22px;
    }

    .sk-form-label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 8px;
    }

    .sk-form-control {
        display: block;
        width: 100%;
        min-height: 44px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 10px 13px;
        background: #fff;
        color: #1e293b;
        font-size: 14px;
    }

    .sk-form-control:focus {
        outline: none;
        border-color: #eab308;
        box-shadow: 0 0 0 3px rgba(250,204,21,.18);
    }

    .sk-form-control[readonly] {
        background: #f1f5f9;
    }

    .sk-form-hint {
        color: #64748b;
        font-size: 12px;
        margin-top: 6px;
    }

    .sk-form-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 10px;
        padding: 11px 18px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
    }

    .sk-form-btn-primary {
        background: #101b4c;
        color: #fff;
    }

    .sk-form-btn-primary:hover {
        background: #facc15;
        color: #101b4c;
    }

    .sk-form-btn-outline {
        border-color: #cbd5e1;
        color: #334155;
        background: #fff;
    }

    .sk-form-btn-outline:hover {
        border-color: #facc15;
        color: #101b4c;
    }

    @media (max-width: 767px) {
        .sk-form-card {
            padding: 18px;
        }

        .sk-form-title {
            font-size: 22px;
        }

        .sk-form-actions {
            flex-direction: column-reverse;
        }

        .sk-form-actions > * {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<div class="sk-form-page">

    <div class="mb-4">
        <a href="{{ route('sekretaris.surat-keluar.index') }}"
           class="text-decoration-none text-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Kembali ke Surat Keluar
        </a>

        <h2 class="sk-form-title mt-3 mb-1">
            Tambah Surat Keluar
        </h2>

        <p class="text-secondary mb-0">
            Lengkapi data untuk membuat draft surat keluar HIMSI.
        </p>
    </div>

    <div class="sk-form-card">

        <form id="formSuratKeluar"
              action="{{ route('sekretaris.surat-keluar.store') }}"
              method="POST"
              enctype="multipart/form-data">

            @csrf
@if(request()->query('ops_submission_id'))<input type="hidden" name="ops_submission_id" value="{{ request()->query('ops_submission_id') }}"><div class="alert alert-info">Surat ini terhubung dengan pengajuan departemen. Gunakan PDF agar dapat ditandatangani setelah persetujuan.</div>@endif

            <h5 class="sk-form-section">
                <i class="bi bi-file-earmark-text me-2"></i>
                Identitas Surat
            </h5>

            <div class="row g-3">

                <div class="col-md-4">
                    <label for="kategori_penomoran" class="fw-semibold mb-2">
                        Kategori Penomoran Surat *
                    </label>

                    <select name="kategori_penomoran"
                            id="kategori_penomoran"
                            class="sk-form-control"
                            required>
                        <option value="">Pilih Kategori</option>

                        <option value="keputusan"
                            @selected(old('kategori_penomoran') === 'keputusan')>
                            Surat Keputusan (1.xxx)
                        </option>

                        <option value="reguler"
                            @selected(old('kategori_penomoran') === 'reguler')>
                            Surat Reguler (2.xxx)
                        </option>

                        <option value="kepanitiaan"
                            @selected(old('kategori_penomoran') === 'kepanitiaan')>
                            Surat Kepanitiaan (3.xxx)
                        </option>
                    </select>

                    @error('kategori_penomoran')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>


                <div class="col-md-4">
                    <label class="sk-form-label" for="jenis_surat">
                        Jenis Surat *
                    </label>

                    <select name="jenis_surat"
                            id="jenis_surat"
                            class="sk-form-control"
                            required>
                        <option value="">Pilih Jenis Surat</option>
                        @foreach($jenisSurat as $jenis)
                            <option value="{{ $jenis }}">
                                {{ $jenis }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="sk-form-label" for="kode_lingkup">
                        Kode Lingkup
                    </label>

                    <select name="kode_lingkup"
                            id="kode_lingkup"
                            class="sk-form-control">
                        <option value="">Pilih Lingkup</option>
                        @foreach($kodeLingkup as $kode)
                            <option value="{{ $kode }}">
                                {{ $kode }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="sk-form-label" for="kode_penerbit">
                        Kode Penerbit
                    </label>

                    <select name="kode_penerbit"
                            id="kode_penerbit"
                            class="sk-form-control">
                        <option value="">Pilih Penerbit</option>
                        @foreach($kodePenerbit as $kode)
                            <option value="{{ $kode }}">
                                {{ $kode }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="sk-form-label" for="nomor_surat">
                        Nomor Surat
                    </label>

                    <input type="text"
                           id="nomor_surat"
                           class="sk-form-control"
                           value="Akan dibuat sesuai ketentuan SOP"
                           readonly>

                    <div class="sk-form-hint">
                        Nomor surat belum diterbitkan pada tahap draft.
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="sk-form-label" for="tanggal_surat">
                        Tanggal Surat *
                    </label>

                    <input type="date"
                           name="tanggal_surat"
                           id="tanggal_surat"
                           class="sk-form-control"
                           required>
                </div>

                <div class="col-md-6">
                    <label class="sk-form-label" for="tujuan">
                        Tujuan Surat *
                    </label>

                    <input type="text"
                           name="tujuan"
                           id="tujuan"
                           class="sk-form-control"
                           placeholder="Masukkan tujuan surat"
                           required>
                </div>

                <div class="col-md-6">
                    <label class="sk-form-label" for="perihal">
                        Perihal Surat *
                    </label>

                    <input type="text"
                           name="perihal"
                           id="perihal"
                           class="sk-form-control"
                           placeholder="Masukkan perihal surat"
                           required>
                </div>

                <div class="col-12">
                    <label class="sk-form-label" for="lampiran">
                        Keterangan Lampiran
                    </label>

                    <input type="text"
                           name="lampiran"
                           id="lampiran"
                           class="sk-form-control"
                           placeholder="Contoh: 1 berkas">
                </div>

            </div>

            <h5 class="sk-form-section mt-4">
                <i class="bi bi-pencil-square me-2"></i>
                Isi dan Dokumen Surat
            </h5>

            <div class="row g-3">




                <div class="col-12">
                    <label class="sk-form-label" for="isi_surat">
                        Isi Surat
                    </label>

                    <textarea name="isi_surat"
                              id="isi_surat"
                              class="sk-form-control"
                              rows="7"
                              placeholder="Tuliskan isi surat..."></textarea>
                </div>

                <div class="col-12">
                    <label class="sk-form-label" for="file_surat">
                        Unggah Dokumen Surat
                    </label>

                    <input type="file"
                           name="file_surat"
                           id="file_surat"
                           class="sk-form-control"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">

                    <div class="sk-form-hint">
                        Unggah PDF, Word (.doc, .docx), atau gambar (.jpg, .jpeg, .png), maksimal 10 MB. File disimpan asli tanpa konversi.
                    </div>
                </div>

            </div>


            <div class="card border-0 bg-light rounded-3 p-3 mt-4">
                <h5 class="fw-bold" style="color:#101b4c"><i class="bi bi-pen-fill me-2"></i>Pengajuan dan Penandatangan</h5>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="jenis_pengajuan">Jenis Pengajuan</label>
                        <select name="jenis_pengajuan" id="jenis_pengajuan" class="form-select" required>
                            <option value="surat_baru" @selected(old('jenis_pengajuan', 'surat_baru') === 'surat_baru')>Pengajuan Surat Baru</option>
                            <option value="revisi_surat" @selected(old('jenis_pengajuan', 'surat_baru') === 'revisi_surat')>Pengajuan Revisi Surat</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="penandatangan_pendamping_id">Pilihan Penandatangan</label>
                        <select name="penandatangan_pendamping_id" id="penandatangan_pendamping_id" class="form-select">
                            <option value="">Ketua Umum saja</option>
                            @foreach($calonPenandatangan as $calon)
                                <option value="{{ $calon->id }}" @selected((string)old('penandatangan_pendamping_id', '')===(string)$calon->id)>
                                    Ketua Umum + {{ $calon->nama_lengkap }} ({{ $calon->administration_role }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Penandatangan pendamping harus menyetujui secara mandiri. Koordinator dipilih dari nama akun departemen.</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold" for="ttd_posisi_x">Posisi awal Ketua X (mm; dapat digeser setelah draft disimpan)</label>
                        <input type="number" min="10" max="190" id="ttd_posisi_x" name="ttd_posisi_x" class="form-control" value="{{ old('ttd_posisi_x', 135) }}">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold" for="ttd_posisi_y">Posisi awal Ketua Y (mm; dapat digeser setelah draft disimpan)</label>
                        <input type="number" min="15" max="275" id="ttd_posisi_y" name="ttd_posisi_y" class="form-control" value="{{ old('ttd_posisi_y', 235) }}">
                    </div>
                </div>
                <p class="small text-secondary mt-3 mb-0">Buat dokumen secara manual. Upload Word, PDF, atau gambar lalu simpan draf dan ajukan persetujuan tanpa konversi. Pengaturan posisi tanda tangan otomatis hanya tersedia jika file asli PDF.</p>
                @if($errors->any())
                    <div class="alert alert-danger mt-3"><strong>Periksa formulir:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
            </div>

            <div class="alert alert-warning mt-4 mb-0">
                <i class="bi bi-shield-check me-2"></i>
                Surat harus mendapatkan persetujuan Ketua Umum
                sebelum tanda tangan Ketua Umum digunakan.
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 sk-form-actions">

                <a href="{{ route('sekretaris.surat-keluar.index') }}"
                   class="sk-form-btn sk-form-btn-outline">
                    Batal
                </a>

                <button type="submit"
                        class="sk-form-btn sk-form-btn-primary">
                    <i class="bi bi-save"></i>
                    Simpan Draft
                </button>

            </div>

        </form>

    </div>

</div>
@endsection