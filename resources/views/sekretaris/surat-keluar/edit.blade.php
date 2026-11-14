@extends('layouts.sekretaris')

@section('title', 'Surat Keluar')

@push('styles')
<style>
    .sk-edit-page {
        max-width: 1050px;
        margin: 0 auto;
        padding: 16px 0 32px;
    }

    .sk-edit-title {
        color: #101b4c;
        font-size: 26px;
        font-weight: 800;
    }

    .sk-edit-card {
        background: #fff;
        border: 1px solid #e8ecf4;
        border-radius: 18px;
        padding: 28px;
        box-shadow: 0 8px 28px rgba(16,27,76,.05);
    }

    .sk-edit-section {
        color: #101b4c;
        font-weight: 800;
        padding-bottom: 12px;
        border-bottom: 2px solid #facc15;
        margin-bottom: 22px;
    }

    .sk-edit-label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 8px;
    }

    .sk-edit-control {
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

    .sk-edit-control:focus {
        outline: none;
        border-color: #eab308;
        box-shadow: 0 0 0 3px rgba(250,204,21,.18);
    }

    .sk-edit-hint {
        color: #64748b;
        font-size: 12px;
        margin-top: 6px;
    }

    .sk-edit-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 11px 18px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid transparent;
    }

    .sk-edit-primary {
        background: #101b4c;
        color: #fff;
    }

    .sk-edit-primary:hover {
        background: #facc15;
        color: #101b4c;
    }

    .sk-edit-outline {
        background: #fff;
        border-color: #cbd5e1;
        color: #334155;
    }

    .sk-edit-outline:hover {
        border-color: #facc15;
        color: #101b4c;
    }

    @media (max-width: 767px) {
        .sk-edit-card {
            padding: 18px;
        }

        .sk-edit-title {
            font-size: 22px;
        }

        .sk-edit-actions {
            flex-direction: column-reverse;
        }

        .sk-edit-actions > * {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<div class="sk-edit-page">

    <a href="{{ route('sekretaris.surat-keluar.show', $suratKeluar) }}"
       class="text-secondary text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i>
        Kembali ke Detail Surat
    </a>

    <h2 class="sk-edit-title mt-3 mb-1">
        {{ $suratKeluar->status === 'ditolak' ? 'Revisi Surat Keluar' : 'Edit Draft Surat Keluar' }}
    </h2>

    <p class="text-secondary mb-4">
        Perbarui informasi surat sebelum diajukan kepada Ketua Umum.
    </p>

    <div class="sk-edit-card">

        @if($errors->any())
            <div class="alert alert-danger">
                <strong>Periksa kembali data berikut:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="formEditSuratKeluar"
              action="{{ route('sekretaris.surat-keluar.update', $suratKeluar) }}"
              method="POST"
              enctype="multipart/form-data">

            @csrf
            @method('PUT')

            <h5 class="sk-edit-section">
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
                            class="sk-edit-control"
                            required>
                        <option value="">Pilih Kategori</option>

                        <option value="keputusan"
                            @selected(old('kategori_penomoran', $suratKeluar->kategori_penomoran) === 'keputusan')>
                            Surat Keputusan (1.xxx)
                        </option>

                        <option value="reguler"
                            @selected(old('kategori_penomoran', $suratKeluar->kategori_penomoran) === 'reguler')>
                            Surat Reguler (2.xxx)
                        </option>

                        <option value="kepanitiaan"
                            @selected(old('kategori_penomoran', $suratKeluar->kategori_penomoran) === 'kepanitiaan')>
                            Surat Kepanitiaan (3.xxx)
                        </option>
                    </select>

                    @error('kategori_penomoran')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>


                <div class="col-md-4">
                    <label class="sk-edit-label" for="jenis_surat">
                        Jenis Surat *
                    </label>

                    <select name="jenis_surat"
                            id="jenis_surat"
                            class="sk-edit-control"
                            required>
                        @foreach($jenisSurat as $jenis)
                            <option value="{{ $jenis }}"
                                @selected(old('jenis_surat', $suratKeluar->jenis_surat) === $jenis)>
                                {{ $jenis }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="sk-edit-label" for="kode_lingkup">
                        Kode Lingkup
                    </label>

                    <select name="kode_lingkup"
                            id="kode_lingkup"
                            class="sk-edit-control">
                        <option value="">Pilih Lingkup</option>
                        @foreach($kodeLingkup as $kode)
                            <option value="{{ $kode }}"
                                @selected(old('kode_lingkup', $suratKeluar->kode_lingkup) === $kode)>
                                {{ $kode }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="sk-edit-label" for="kode_penerbit">
                        Kode Penerbit
                    </label>

                    <select name="kode_penerbit"
                            id="kode_penerbit"
                            class="sk-edit-control">
                        <option value="">Pilih Penerbit</option>
                        @foreach($kodePenerbit as $kode)
                            <option value="{{ $kode }}"
                                @selected(old('kode_penerbit', $suratKeluar->kode_penerbit) === $kode)>
                                {{ $kode }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="sk-edit-label">
                        Nomor Surat
                    </label>

                    <input type="text"
                           class="sk-edit-control"
                           value="{{ $suratKeluar->nomor_surat ?? 'Belum diterbitkan' }}"
                           readonly>

                    <div class="sk-edit-hint">
                        Nomor surat tidak dapat diubah melalui form draft.
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="sk-edit-label" for="tanggal_surat">
                        Tanggal Surat *
                    </label>

                    <input type="date"
                           name="tanggal_surat"
                           id="tanggal_surat"
                           class="sk-edit-control"
                           value="{{ old('tanggal_surat', $suratKeluar->tanggal_surat?->format('Y-m-d')) }}"
                           required>
                </div>

                <div class="col-md-6">
                    <label class="sk-edit-label" for="tujuan">
                        Tujuan Surat *
                    </label>

                    <input type="text"
                           name="tujuan"
                           id="tujuan"
                           class="sk-edit-control"
                           value="{{ old('tujuan', $suratKeluar->tujuan) }}"
                           required>
                </div>

                <div class="col-md-6">
                    <label class="sk-edit-label" for="perihal">
                        Perihal Surat *
                    </label>

                    <input type="text"
                           name="perihal"
                           id="perihal"
                           class="sk-edit-control"
                           value="{{ old('perihal', $suratKeluar->perihal) }}"
                           required>
                </div>

                <div class="col-12">
                    <label class="sk-edit-label" for="lampiran">
                        Keterangan Lampiran
                    </label>

                    <input type="text"
                           name="lampiran"
                           id="lampiran"
                           class="sk-edit-control"
                           value="{{ old('lampiran', $suratKeluar->lampiran) }}">
                </div>

            </div>

            <h5 class="sk-edit-section mt-4">
                <i class="bi bi-pencil-square me-2"></i>
                Isi dan Dokumen Surat
            </h5>

            <div class="row g-3">

                


                <div class="col-12">
                    <label class="sk-edit-label" for="isi_surat">
                        Isi Surat
                    </label>

                    <textarea name="isi_surat"
                              id="isi_surat"
                              class="sk-edit-control"
                              rows="7">{{ old('isi_surat', $suratKeluar->isi_surat) }}</textarea>
                </div>

                <div class="col-12">
                    <label class="sk-edit-label" for="file_surat">
                        Ganti Dokumen Surat
                    </label>

                    <input type="file"
                           name="file_surat"
                           id="file_surat"
                           class="sk-edit-control"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">

                    <div class="sk-edit-hint">
                        PDF, Word (.doc, .docx), atau gambar (.jpg, .jpeg, .png), maksimal 10 MB. Tanpa konversi.
                        Jika tidak memilih file baru, dokumen lama tetap digunakan.
                    </div>

                    @if($suratKeluar->file_surat)
                        <p class="text-success small mt-2 mb-0">
                            <i class="bi bi-check-circle me-1"></i>
                            Dokumen sebelumnya sudah tersimpan.
                        </p>
                    @endif
                </div>

            </div>


            <div class="card border-0 bg-light rounded-3 p-3 mt-4">
                <h5 class="fw-bold" style="color:#101b4c"><i class="bi bi-pen-fill me-2"></i>Pengajuan dan Penandatangan</h5>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="jenis_pengajuan">Jenis Pengajuan</label>
                        <select name="jenis_pengajuan" id="jenis_pengajuan" class="form-select" required>
                            <option value="surat_baru" @selected(old('jenis_pengajuan', $suratKeluar->jenis_pengajuan) === 'surat_baru')>Pengajuan Surat Baru</option>
                            <option value="revisi_surat" @selected(old('jenis_pengajuan', $suratKeluar->jenis_pengajuan) === 'revisi_surat')>Pengajuan Revisi Surat</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="penandatangan_pendamping_id">Pilihan Penandatangan</label>
                        <select name="penandatangan_pendamping_id" id="penandatangan_pendamping_id" class="form-select">
                            <option value="">Ketua Umum saja</option>
                            @foreach($calonPenandatangan as $calon)
                                <option value="{{ $calon->id }}" @selected((string)old('penandatangan_pendamping_id', $suratKeluar->penandatangan_pendamping_id)===(string)$calon->id)>
                                    Ketua Umum + {{ $calon->nama_lengkap }} ({{ $calon->administration_role }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Penandatangan pendamping harus menyetujui secara mandiri. Koordinator dipilih dari nama akun departemen.</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold" for="ttd_posisi_x">Posisi awal Ketua X (mm; dapat digeser setelah draft disimpan)</label>
                        <input type="number" min="10" max="190" id="ttd_posisi_x" name="ttd_posisi_x" class="form-control" value="{{ old('ttd_posisi_x', $suratKeluar->ttd_posisi_x ?? 135) }}">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold" for="ttd_posisi_y">Posisi awal Ketua Y (mm; dapat digeser setelah draft disimpan)</label>
                        <input type="number" min="15" max="275" id="ttd_posisi_y" name="ttd_posisi_y" class="form-control" value="{{ old('ttd_posisi_y', $suratKeluar->ttd_posisi_y ?? 235) }}">
                    </div>
                </div>
                <p class="small text-secondary mt-3 mb-0">Buat dokumen secara manual. Upload Word, PDF, atau gambar lalu simpan draf dan ajukan persetujuan tanpa konversi. Pengaturan posisi tanda tangan otomatis hanya tersedia jika file asli PDF.</p>
                @if($errors->any())
                    <div class="alert alert-danger mt-3"><strong>Periksa formulir:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
            </div>

            <div class="alert alert-warning mt-4 mb-0">
                <i class="bi bi-shield-check me-2"></i>
                Surat berstatus Draft atau Ditolak dapat diperbarui sebelum diajukan kepada Ketua Umum.
                Persetujuan Ketua Umum dilakukan melalui proses terpisah.
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 sk-edit-actions">

                <a href="{{ route('sekretaris.surat-keluar.show', $suratKeluar) }}"
                   class="sk-edit-btn sk-edit-outline">
                    Batal
                </a>

                <button type="submit"
                        class="sk-edit-btn sk-edit-primary">
                    <i class="bi bi-save"></i>
                    Simpan Perubahan
                </button>

            </div>

        </form>

    </div>
</div>
@endsection