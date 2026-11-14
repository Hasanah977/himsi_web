@extends('layouts.ketua-umum')

@section('title', 'Approval Center')

@push('styles')
<style>
    .approval-header {
        background: linear-gradient(120deg, #172454, #304a98);
        color: white;
        border-radius: 18px;
        padding: 28px;
        margin-bottom: 24px;
    }

    .approval-header h2 {
        font-size: 23px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .approval-header p {
        margin: 0;
        color: #e1e8ff;
        font-size: 14px;
    }

    .approval-stat {
        background: #fff;
        border: 1px solid #e8edf5;
        border-radius: 16px;
        padding: 22px;
        height: 100%;
        box-shadow: 0 4px 18px rgba(16,27,76,.04);
    }

    .approval-stat-label {
        color: #64748b;
        font-size: 14px;
        margin-bottom: 8px;
    }

    .approval-stat-value {
        color: #172454;
        font-size: 30px;
        font-weight: 800;
    }

    .approval-panel {
        margin-top: 24px;
        background: #fff;
        border: 1px solid #e8edf5;
        border-radius: 16px;
        padding: 24px;
    }

    .approval-tabs {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        border-bottom: 1px solid #e8edf5;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }

    .approval-tab {
        border: none;
        border-radius: 10px;
        padding: 11px 16px;
        background: #edf1fa;
        color: #172454;
        font-weight: 600;
        cursor: pointer;
        transition: all .2s ease;
    }

    .approval-tab.active {
        background: #facc15;
        color: #172454;
    }

    .approval-content {
        display: none;
    }

    .approval-content.active {
        display: block;
    }

    .approval-item {
        border: 1px solid #e8edf5;
        border-radius: 12px;
        padding: 18px;
        margin-bottom: 14px;
    }

    .approval-item h4 {
        color: #172454;
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .approval-meta {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 10px;
    }

    .approval-description {
        font-size: 14px;
        color: #475569;
        line-height: 1.7;
    }

    .approval-empty {
        padding: 45px 15px;
        text-align: center;
        color: #64748b;
    }

    .approval-empty i {
        font-size: 36px;
        color: #94a3b8;
        display: block;
        margin-bottom: 12px;
    }

    @media (max-width: 767px) {
        .approval-header,
        .approval-panel {
            padding: 18px;
        }

        .approval-tab {
            flex: 1 1 100%;
        }
    }
</style>
@endpush

@section('content')

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Periksa kembali pengajuan:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif

<div class="approval-header">
    <h2>
        <i class="bi bi-clipboard-check me-2"></i>
        Approval Center
    </h2>
    <p>Kelola dan pantau persetujuan pengajuan organisasi HIMSI.</p>
</div>

<div class="row g-3">
    <div class="col-12 col-md-4">
        <div class="approval-stat">
            <div class="approval-stat-label">Menunggu Persetujuan</div>
            <div class="approval-stat-value">{{ $totalMenunggu }}</div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="approval-stat">
            <div class="approval-stat-label">Disetujui</div>
            <div class="approval-stat-value">{{ $totalDisetujui }}</div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="approval-stat">
            <div class="approval-stat-label">Ditolak</div>
            <div class="approval-stat-value">{{ $totalDitolak }}</div>
        </div>
    </div>
</div>

<div class="approval-panel">

    <div class="approval-tabs">
        <button type="button" class="approval-tab active" data-approval-tab="menunggu">
            Menunggu ({{ $totalMenunggu }})
        </button>

        <button type="button" class="approval-tab" data-approval-tab="disetujui">
            Disetujui ({{ $totalDisetujui }})
        </button>

        <button type="button" class="approval-tab" data-approval-tab="ditolak">
            Ditolak ({{ $totalDitolak }})
        </button>
    </div>

    <div class="d-flex align-items-center flex-wrap gap-2 mt-3 mb-3">
        <label for="approvalJenisFilter" class="fw-semibold" style="color:#101b4c;">
            Jenis Pengajuan:
        </label>
        <select id="approvalJenisFilter"
                class="form-select"
                style="width:auto;min-width:180px;">
            <option value="semua">Semua Pengajuan</option>
            <option value="dana">Pengajuan Dana</option>
            <option value="surat">Surat Keluar</option>
        </select>
    </div>
    @foreach (['menunggu' => $menunggu, 'disetujui' => $disetujui, 'ditolak' => $ditolak] as $status => $data)
        <div class="approval-content {{ $status === 'menunggu' ? 'active' : '' }}"
             id="approval-{{ $status }}">

            @foreach ($data as $pengajuan)
                <div class="approval-item" data-jenis-pengajuan="dana">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
    <h4 class="mb-0">{{ $pengajuan->judul }}</h4>

    <span class="badge rounded-pill px-3 py-2 {{ match($status) {
        'disetujui' => 'bg-success',
        'ditolak' => 'bg-danger',
        default => 'bg-warning text-dark'
    } }}">
        {{ match($status) {
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            default => 'Menunggu'
        } }}
    </span>
</div>

                    <div class="approval-meta">
                        <i class="bi bi-person me-1"></i>
                        {{ $pengajuan->pengaju?->nama_lengkap ?? 'Pengguna tidak tersedia' }}

                        <span class="mx-2">|</span>

                        {{ $pengajuan->jenis_pengajuan }}

                        <span class="mx-2">|</span>

                        {{ $pengajuan->created_at?->format('d/m/Y') }}
                    </div>

                    <div class="approval-description">
                        {{ $pengajuan->deskripsi ?? 'Tidak ada deskripsi.' }}
                    </div>

                    @if ($pengajuan->nominal !== null)
                        <div class="mt-2 fw-semibold">
                            Nominal: Rp {{ number_format((float) $pengajuan->nominal, 0, ',', '.') }}
                        </div>
                    @endif

                    @if ($status !== 'menunggu')
                        <div class="mt-3 small text-muted">
                            <div class="mb-1">
                                <i class="bi bi-person-check me-1"></i>
                                <strong>Diproses oleh:</strong>
                                {{ $pengajuan->pemroses?->nama_lengkap ?? 'Tidak diketahui' }}
                            </div>

                            <div>
                                <i class="bi bi-calendar-check me-1"></i>
                                <strong>Waktu keputusan:</strong>
                                {{ $pengajuan->diproses_pada?->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i') ?? 'Belum tercatat' }}
                            </div>
                        </div>
                    @endif
                    @if ($pengajuan->catatan_ketua)
                        <div class="mt-3 small">
                            <strong>Catatan Ketua:</strong>
                            {{ $pengajuan->catatan_ketua }}
                        </div>
                    @endif

                    @if ($status === 'menunggu')
                        <div class="mt-3">
                            <button type="button"
        class="btn btn-primary btn-sm mt-3 d-block"
        data-bs-toggle="modal"
        data-bs-target="#reviewPengajuan{{ $pengajuan->id }}">
    <i class="bi bi-eye me-1"></i>
    Review Pengajuan
</button>

                            <span class="badge bg-warning text-dark">
                                Menunggu Review Ketua Umum
                            </span>
                        </div>
                    @endif
                </div>
            @endforeach


            @php
                $daftarSurat = match ($status) {
                    'menunggu' => $suratMenunggu,
                    'disetujui' => $suratDisetujui,
                    'ditolak' => $suratDitolak,
                };
            @endphp

            @foreach ($daftarSurat as $surat)
                <div class="approval-item" data-jenis-pengajuan="surat">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h4 class="mb-0">
                            <i class="bi bi-envelope-paper me-2"></i>
                            {{ $surat->perihal }}
                        </h4>

                        <span class="badge rounded-pill px-3 py-2 {{
                            $status === 'disetujui' ? 'bg-success' :
                            ($status === 'ditolak' ? 'bg-danger' : 'bg-warning text-dark')
                        }}">
                            {{ $status === 'menunggu' ? 'Menunggu' : ucfirst($status) }}
                        </span>
                    </div>

                    <div class="approval-meta mb-2">
                        <i class="bi bi-person me-1"></i>
                        {{ $surat->pembuat?->nama_lengkap ?? 'Sekertaris' }}
                        <span class="mx-2">|</span>
                        Surat Keluar ({{ $surat->jenis_surat }})
                        <span class="mx-2">|</span>
                        {{ $surat->diajukan_pada?->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i') ?? '-' }}
                    </div>

                    <div class="approval-description">
                        <strong>Tujuan:</strong> {{ $surat->tujuan }}
                    </div>

                    @if ($status !== 'menunggu')
                        <div class="mt-2 small text-muted">
                            <strong>Diproses oleh:</strong>
                            {{ $surat->penyetuju?->nama_lengkap ?? 'Tidak diketahui' }}
                        </div>
                    @endif

                    <div class="mt-3">
                        <a href="{{ route('ketua-umum.persetujuan-surat.show', $surat) }}"
                           class="btn btn-sm fw-bold"
                           style="background:#facc15;color:#101b4c;border-radius:9px;padding:9px 16px;">
                            <i class="bi bi-eye me-1"></i>
                            {{ $status === 'menunggu' ? 'Review Surat' : 'Detail Surat' }}
                        </a>
                    </div>
                </div>
            @endforeach

            @if ($data->isEmpty() && $daftarSurat->isEmpty())
                <div class="approval-empty" data-approval-empty>
                    <i class="bi bi-inbox"></i>
                    Belum ada pengajuan dengan status {{ $status }}.
                </div>
            @endif

            <div class="approval-empty d-none" data-filter-empty>
                <i class="bi bi-inbox"></i>
                Tidak ada pengajuan untuk jenis yang dipilih.
            </div>
        </div>
    @endforeach

</div>

@foreach ($menunggu as $pengajuan)
<div class="modal fade" id="reviewPengajuan{{ $pengajuan->id }}" tabindex="-1"
     aria-labelledby="reviewTitle{{ $pengajuan->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark" id="reviewTitle{{ $pengajuan->id }}">
                    <i class="bi bi-clipboard-check me-2"></i>Review Pengajuan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
            </div>

            <div class="modal-body p-4">
                <h5 class="fw-bold mb-3">{{ $pengajuan->judul }}</h5>

                <dl class="row g-3 mb-0">
                    <dt class="col-sm-4">Nama Pengaju</dt>
                    <dd class="col-sm-8">{{ $pengajuan->pengaju?->nama_lengkap ?? 'Tidak tersedia' }}</dd>

                    <dt class="col-sm-4">Jenis Pengajuan</dt>
                    <dd class="col-sm-8">{{ $pengajuan->jenis_pengajuan }}</dd>

                    <dt class="col-sm-4">Tanggal Pengajuan</dt>
                    <dd class="col-sm-8">{{ $pengajuan->created_at?->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i') }}</dd>

                    <dt class="col-sm-4">Nominal</dt>
                    <dd class="col-sm-8">
                        {{ $pengajuan->nominal !== null ? 'Rp '.number_format((float) $pengajuan->nominal, 0, ',', '.') : '-' }}
                    </dd>

                    <dt class="col-sm-4">Deskripsi</dt>
                    <dd class="col-sm-8">{{ $pengajuan->deskripsi ?? '-' }}</dd>

                    <dt class="col-sm-4">Lampiran</dt>
                    <dd class="col-sm-8">
                        @if ($pengajuan->lampiran)
                            <a href="{{ asset('storage/'.$pengajuan->lampiran) }}"
                               target="_blank" rel="noopener noreferrer">Lihat Lampiran</a>
                        @else
                            Tidak ada lampiran
                        @endif
                    </dd>
                </dl>

                                <form method="POST"
                      action="{{ route('ketua-umum.approval-center.proses', $pengajuan->id) }}"
                      id="formKeputusan{{ $pengajuan->id }}">
                    @csrf

                    <div class="mt-4">
                        <label for="catatanKetua{{ $pengajuan->id }}"
                               class="form-label fw-semibold">
                            Catatan Ketua Umum
                        </label>

                        <textarea
                            class="form-control"
                            id="catatanKetua{{ $pengajuan->id }}"
                            name="catatan_ketua"
                            rows="3"
                            maxlength="2000"
                            placeholder="Tuliskan catatan atau alasan keputusan..."></textarea>

                        <small class="text-muted">
                            Catatan wajib diisi apabila pengajuan ditolak.
                        </small>
                    </div>

                    <div class="d-flex justify-content-end flex-wrap gap-2 mt-4">
                        <button type="button"
                                class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">
                            Tutup
                        </button>

                        <button type="submit"
                                name="keputusan"
                                value="ditolak"
                                class="btn btn-outline-danger"
                                onclick="return validasiTolak(this)">
                            <i class="bi bi-x-circle me-1"></i>
                            Tolak
                        </button>

                        <button type="submit"
                                name="keputusan"
                                value="disetujui"
                                class="btn btn-success"
                                data-confirm="Yakin ingin menyetujui pengajuan ini?">
                            <i class="bi bi-check-circle me-1"></i>
                            Setujui
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach


<div style="background:#ffffff;
            border:1px solid #e2e8f0;
            border-left:5px solid #facc15;
            border-radius:14px;
            padding:20px;
            margin:20px 0;
            display:flex;
            flex-wrap:wrap;
            align-items:center;
            justify-content:space-between;
            gap:16px;">

    <div>
        <h3 style="font-size:18px;
                   font-weight:800;
                   color:#101b4c;
                   margin:0 0 6px;">
            <i class="bi bi-envelope-paper me-2"></i>
            Persetujuan Surat Keluar
        </h3>

        <p style="color:#64748b;
                  font-size:14px;
                  margin:0;">
            Periksa, setujui, atau tolak surat keluar
            yang diajukan oleh Sekertaris HIMSI.
        </p>
    </div>

    <a href="{{ route('ketua-umum.persetujuan-surat.index') }}"
       style="display:inline-flex;
              align-items:center;
              gap:8px;
              padding:12px 18px;
              background:#facc15;
              color:#101b4c;
              border-radius:10px;
              font-size:14px;
              font-weight:800;
              text-decoration:none;">
        <i class="bi bi-arrow-right-circle"></i>
        Buka Persetujuan Surat
    </a>

</div>
@endsection

@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filter = document.getElementById('approvalJenisFilter');
    if (!filter) return;

    function terapkanFilter() {
        document.querySelectorAll('.approval-content').forEach(function (panel) {
            const items = panel.querySelectorAll('[data-jenis-pengajuan]');
            let terlihat = 0;

            items.forEach(function (item) {
                const cocok = filter.value === 'semua' ||
                    item.dataset.jenisPengajuan === filter.value;

                item.classList.toggle('d-none', !cocok);

                if (cocok) terlihat++;
            });

            const kosongAsli = panel.querySelector('[data-approval-empty]');
            const kosongFilter = panel.querySelector('[data-filter-empty]');

            if (kosongAsli) {
                kosongAsli.classList.toggle('d-none', filter.value !== 'semua');
            }

            if (kosongFilter) {
                kosongFilter.classList.toggle(
                    'd-none',
                    terlihat > 0 || (filter.value === 'semua' && !!kosongAsli)
                );
            }
        });
    }

    filter.addEventListener('change', terapkanFilter);
    terapkanFilter();
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('[data-approval-tab]');
    const contents = document.querySelectorAll('.approval-content');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const target = tab.dataset.approvalTab;

            tabs.forEach(function (item) {
                item.classList.toggle('active', item === tab);
            });

            contents.forEach(function (content) {
                content.classList.toggle(
                    'active',
                    content.id === 'approval-' + target
                );
            });
        });
    });
});
</script>
<script>
function validasiTolak(tombol) {
    const form = tombol.closest('form');

    if (!form) {
        return false;
    }

    const catatan = form.querySelector('[name="catatan_ketua"]');

    if (!catatan || catatan.value.trim() === '') {
        alert('Catatan Ketua Umum wajib diisi untuk menolak pengajuan.');

        if (catatan) {
            catatan.focus();
        }

        return false;
    }

    return true;
}
</script>
@endpush