@extends('layouts.ketua-umum')

@section('title', 'Detail Persetujuan Surat')

@section('content')
<style>
    .ps-detail {
        max-width: 1050px;
        margin: 0 auto;
        padding: 10px 0 35px;
    }

    .ps-detail-title {
        color: #101b4c;
        font-size: 26px;
        font-weight: 800;
        margin: 18px 0 6px;
    }

    .ps-detail-subtitle {
        color: #64748b;
        font-size: 14px;
        margin-bottom: 24px;
    }

    .ps-detail-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 26px;
        margin-bottom: 20px;
        box-shadow: 0 5px 20px rgba(16,27,76,.04);
    }

    .ps-detail-heading {
        color: #101b4c;
        font-size: 17px;
        font-weight: 800;
        padding-bottom: 13px;
        border-bottom: 2px solid #facc15;
        margin-bottom: 20px;
    }

    .ps-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 22px;
    }

    .ps-detail-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .ps-detail-value {
        color: #1e293b;
        font-size: 14px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .ps-detail-content {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        color: #334155;
        font-size: 14px;
        line-height: 1.8;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    .ps-detail-badge {
        display: inline-block;
        padding: 7px 13px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
    }

    .ps-detail-waiting {
        background: #fef3c7;
        color: #92400e;
    }

    .ps-detail-approved {
        background: #dcfce7;
        color: #166534;
    }

    .ps-detail-rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    .ps-detail-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 17px;
        border-radius: 10px;
        background: #101b4c;
        color: #fff;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        border: 0;
    }

    .ps-detail-btn:hover {
        background: #facc15;
        color: #101b4c;
    }

    @media (max-width: 767px) {
        .ps-detail-grid {
            grid-template-columns: 1fr;
        }

        .ps-detail-card {
            padding: 18px;
        }

        .ps-detail-title {
            font-size: 22px;
        }
    }
</style>

<div class="ps-detail">

    <a href="{{ route('ketua-umum.persetujuan-surat.index') }}"
       class="text-secondary text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i>
        Kembali ke Daftar Persetujuan
    </a>

    <h2 class="ps-detail-title">
        Detail Persetujuan Surat Keluar
    </h2>

    <p class="ps-detail-subtitle">
        Periksa informasi surat sebelum memberikan keputusan.
    </p>

    @if($suratKeluar->file_surat && strtolower(pathinfo($suratKeluar->file_surat, PATHINFO_EXTENSION)) === 'pdf')
<div class="ps-detail-card">
    <h5 class="ps-detail-heading"><i class="bi bi-file-earmark-pdf me-2"></i>Pratinjau Surat (dalam halaman)</h5>
    <iframe title="Pratinjau PDF surat" src="{{ route('surat-keluar.dokumen.lihat',$suratKeluar) }}"
        style="width:100%;height:60vh;min-height:340px;border:1px solid #e2e8f0;border-radius:12px" loading="lazy"></iframe>
    <a class="btn btn-outline-primary mt-2" href="{{ route('surat-keluar.dokumen.unduh',$suratKeluar) }}"><i class="bi bi-download"></i> Unduh PDF Asli</a>
</div>
@endif
<div class="ps-detail-card">

        <h5 class="ps-detail-heading">
            <i class="bi bi-file-earmark-text me-2"></i>
            Identitas Surat
        </h5>

        <div class="ps-detail-grid">

            <div>
                <div class="ps-detail-label">Nomor Surat</div>
                <div class="ps-detail-value">
                    {{ $suratKeluar->nomor_surat ?? 'Belum diterbitkan' }}
                </div>
            </div>

            <div>
                <div class="ps-detail-label">Jenis Surat</div>
                <div class="ps-detail-value">
                    {{ $suratKeluar->jenis_surat }}
                </div>
            </div>

            <div>
                <div class="ps-detail-label">Kode Lingkup</div>
                <div class="ps-detail-value">
                    {{ $suratKeluar->kode_lingkup ?? '-' }}
                </div>
            </div>

            <div>
                <div class="ps-detail-label">Kode Penerbit</div>
                <div class="ps-detail-value">
                    {{ $suratKeluar->kode_penerbit ?? '-' }}
                </div>
            </div>

            <div>
                <div class="ps-detail-label">Tujuan Surat</div>
                <div class="ps-detail-value">
                    {{ $suratKeluar->tujuan }}
                </div>
            </div>

            <div>
                <div class="ps-detail-label">Tanggal Surat</div>
                <div class="ps-detail-value">
                    {{ $suratKeluar->tanggal_surat?->format('d/m/Y') ?? '-' }}
                </div>
            </div>

            <div>
                <div class="ps-detail-label">Perihal</div>
                <div class="ps-detail-value">
                    {{ $suratKeluar->perihal }}
                </div>
            </div>

            <div>
                <div class="ps-detail-label">Lampiran</div>
                <div class="ps-detail-value">
                    {{ $suratKeluar->lampiran ?? '-' }}
                </div>
            </div>

        </div>

    </div>

    <div class="ps-detail-card">

        <h5 class="ps-detail-heading">
            <i class="bi bi-file-text me-2"></i>
            Isi dan Dokumen Surat
        </h5>

        <div class="ps-detail-label mb-2">
            Isi Surat
        </div>

        <div class="ps-detail-content">{{ $suratKeluar->isi_surat ?: 'Isi surat tidak tersedia dalam bentuk teks.' }}</div>

        <div class="mt-4">
            <div class="ps-detail-label">Dokumen Terlampir</div>

            <div class="ps-detail-value">
                @if($suratKeluar->file_surat)
                    <i class="bi bi-file-earmark-check text-success me-1"></i>
                    Dokumen surat sudah tersimpan.
                @else
                    Tidak ada dokumen terlampir.
                @endif
            </div>

            @if($suratKeluar->file_surat)
                <p class="text-secondary small mt-2 mb-0">
                    Periksa PDF asli pada pratinjau di halaman ini.
                </p>
            @endif
        </div>

    </div>

    <div class="ps-detail-card">

        <h5 class="ps-detail-heading">
            <i class="bi bi-shield-check me-2"></i>
            Informasi Persetujuan
        </h5>

        <div class="ps-detail-grid">

            <div>
                <div class="ps-detail-label">Diajukan Oleh</div>
                <div class="ps-detail-value">
                    {{ $suratKeluar->pembuat?->nama_lengkap ?? '-' }}
                </div>
            </div>

            <div>
                <div class="ps-detail-label">Tanggal Pengajuan</div>
                <div class="ps-detail-value">
                    {{ $suratKeluar->diajukan_pada?->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i') ?? '-' }}
                </div>
            </div>




            <div>
                <div class="ps-detail-label">Status Persetujuan</div>

                @if($suratKeluar->status === 'menunggu_persetujuan')
                    <span class="ps-detail-badge ps-detail-waiting">
                        Menunggu Persetujuan
                    </span>
                @elseif($suratKeluar->status === 'disetujui')
                    <span class="ps-detail-badge ps-detail-approved">
                        Disetujui
                    </span>
                @else
                    <span class="ps-detail-badge ps-detail-rejected">
                        Ditolak
                    </span>
                @endif
            </div>

            <div>
                <div class="ps-detail-label">Diputuskan Oleh</div>
                <div class="ps-detail-value">
                    {{ $suratKeluar->penyetuju?->nama_lengkap ?? '-' }}
                </div>
            </div>

            <div>
                <div class="ps-detail-label">Tanggal Keputusan</div>
                <div class="ps-detail-value">
                    {{ $suratKeluar->diputuskan_pada?->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i') ?? '-' }}
                </div>
            </div>

            <div>
                <div class="ps-detail-label">Catatan Persetujuan</div>
                <div class="ps-detail-value">
                    {{ $suratKeluar->catatan_persetujuan ?? '-' }}
                </div>
            </div>

        </div>

    </div>

    @if($riwayatPersetujuan->isNotEmpty())
        <div class="ps-detail-card">
            <h5 class="ps-detail-heading">
                <i class="bi bi-clock-history me-2"></i>
                Riwayat Persetujuan
            </h5>

            <div class="d-flex flex-column gap-3">
                @foreach($riwayatPersetujuan as $riwayat)
                    <div class="border rounded-3 p-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <strong>Pengajuan ke-{{ $riwayat->pengajuan_ke }}</strong>

                            @if($riwayat->status_keputusan === 'disetujui')
                                <span class="badge bg-success">Disetujui</span>
                            @elseif($riwayat->status_keputusan === 'ditolak')
                                <span class="badge bg-danger">Ditolak</span>
                            @endif
                        </div>

                        <div class="ps-detail-label">Tanggal Keputusan</div>
                        <div class="ps-detail-value mb-2">
                            {{ $riwayat->diputuskan_pada ? \Carbon\Carbon::parse($riwayat->diputuskan_pada)->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i') : '-' }}
                        </div>

                        <div class="ps-detail-label">Catatan Ketua Umum</div>
                        <div class="ps-detail-value">
                            {{ $riwayat->catatan ?: 'Tidak ada catatan.' }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($suratKeluar->status === 'menunggu_persetujuan')
        <div class="alert alert-warning">
            <i class="bi bi-info-circle me-2"></i>
            Surat ini sedang menunggu keputusan Ketua Umum. Silakan periksa PDF asli sebelum menyetujui.
        </div>
    @endif

    <a href="{{ route('ketua-umum.persetujuan-surat.index') }}"
       class="ps-detail-btn">
        <i class="bi bi-arrow-left"></i>
        Kembali ke Daftar
    </a>

</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if($suratKeluar->file_surat && strtolower(pathinfo($suratKeluar->file_surat, PATHINFO_EXTENSION)) === 'pdf' && in_array($suratKeluar->status, ['menunggu_persetujuan','disetujui'], true))
<div class="ps-detail-card">
  <h5 class="ps-detail-heading">Upload, Geser & Tempel Tanda Tangan Saya</h5>
  <p>Ketua Umum mengambil gambar TTD sendiri dari File Explorer, mengatur posisi dengan mouse, lalu menyetujuinya. TTD baru ditempel setelah persetujuan lengkap.</p>
  <a class="btn btn-warning fw-bold" href="{{ route('tanda-tangan.surat.editor',$suratKeluar) }}">Upload & Atur Posisi TTD Saya</a>
</div>
@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach</div>@endif

@if($suratKeluar->status === 'menunggu_persetujuan')
    <div class="ps-detail-card">
        <h5 class="ps-detail-heading">
            <i class="bi bi-clipboard-check me-2"></i>
            Keputusan Ketua Umum
        </h5>

        <form id="formKeputusanSurat"
              method="POST"
              action="{{ route('ketua-umum.persetujuan-surat.putuskan', $suratKeluar) }}">
            @csrf

            <label for="catatan_persetujuan" class="ps-detail-label">
                Catatan Keputusan
            </label>

            <textarea
                id="catatan_persetujuan"
                name="catatan_persetujuan"
                class="form-control mb-2"
                rows="4"
                maxlength="2000"
                placeholder="Wajib diisi"
                required
            >{{ old('catatan_persetujuan') }}</textarea>

            @error('catatan_persetujuan')
                <div class="text-danger small mb-2">{{ $message }}</div>
            @enderror

            <div class="form-check mt-3 mb-3">
                <input type="checkbox" class="form-check-input" id="konfirmasi_tanda_tangan" name="konfirmasi_tanda_tangan" value="1">
                <label class="form-check-label" for="konfirmasi_tanda_tangan">Jika saya menyetujui, saya mengizinkan sistem menggunakan gambar tanda tangan saya hanya untuk PDF surat ini. Centang sebelum memilih Setujui.</label>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <button
                    type="submit"
                    name="keputusan"
                    value="disetujui"
                    class="btn btn-success"
                    data-confirm="Setujui surat ini?">
                    <i class="bi bi-check-circle"></i>
                    Setujui
                </button>

                <button
                    type="submit"
                    name="keputusan"
                    value="ditolak"
                    class="btn btn-danger"
                    data-confirm="Tolak surat ini?">
                    <i class="bi bi-x-circle"></i>
                    Tolak
                </button>
            </div>
        </form>
    </div>
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

        <a href="{{ route('surat-keluar.dokumen.lihat', $suratKeluar) }}"
           data-himsi-pdf-preview
           rel="noopener noreferrer"
           style="background:#101b4c;
                  color:#fff;
                  padding:11px 17px;
                  border-radius:9px;
                  text-decoration:none;
                  font-weight:700;">
            <i class="bi bi-eye me-1"></i>
            Lihat Dokumen
        </a>

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
@if($suratKeluar->status === 'disetujui' && filled($suratKeluar->file_final))
<div style="background:#fff;
            border:1px solid #e2e8f0;
            border-left:4px solid #16a34a;
            border-radius:12px;
            padding:20px;
            margin:20px 0;">

    <h3 style="font-size:17px;
               font-weight:800;
               color:#101b4c;
               margin-bottom:8px;">
        <i class="bi bi-file-earmark-check me-2"></i>
        Arsip Dokumen Final
    </h3>

    <p style="color:#64748b;font-size:14px;">
        Dokumen final telah diarsipkan oleh Sekertaris
        setelah pengesahan sesuai SOP HIMSI.
    </p>

    <div style="display:flex;flex-wrap:wrap;gap:10px;">

        <a href="{{ route('surat-keluar.dokumen-final.lihat', $suratKeluar) }}"
           data-himsi-pdf-preview
           rel="noopener noreferrer"
           style="background:#101b4c;
                  color:#fff;
                  padding:11px 17px;
                  border-radius:9px;
                  text-decoration:none;
                  font-weight:700;">
            <i class="bi bi-eye me-1"></i>
            Lihat PDF Final
        </a>

        <a href="{{ route('surat-keluar.dokumen-final.unduh', $suratKeluar) }}"
           style="background:#facc15;
                  color:#101b4c;
                  padding:11px 17px;
                  border-radius:9px;
                  text-decoration:none;
                  font-weight:700;">
            <i class="bi bi-download me-1"></i>
            Unduh PDF Final
        </a>

    </div>
</div>
@endif

@include('components.himsi-pdf-preview-modal')
@endsection