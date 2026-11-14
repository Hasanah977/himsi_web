@extends('layouts.sekretaris')

@section('title', 'Surat Keluar')

@push('styles')
<style>
    .sk-heading {
        font-weight: 800;
        color: #101b4c;
    }

    .sk-card {
        background: #fff;
        border: 1px solid #e8ecf4;
        border-radius: 16px;
        padding: 22px;
        box-shadow: 0 5px 20px rgba(16,27,76,.04);
    }

    .sk-stat-number {
        font-size: 28px;
        font-weight: 800;
        color: #101b4c;
    }

    .sk-stat-label {
        font-size: 13px;
        color: #64748b;
    }

    .sk-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 10px;
        background: #101b4c;
        color: #fff;
        text-decoration: none;
        border: 0;
        font-weight: 600;
    }

    .sk-btn:hover {
        background: #facc15;
        color: #101b4c;
    }

    .sk-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 850px;
    }

    .sk-table th {
        background: #f5f7fc;
        color: #101b4c;
        padding: 14px;
        font-size: 13px;
        text-align: left;
    }

    .sk-table td {
        padding: 14px;
        border-bottom: 1px solid #eef1f6;
        font-size: 13px;
        vertical-align: middle;
    }

    .sk-status {
        display: inline-block;
        border-radius: 20px;
        padding: 6px 11px;
        font-size: 12px;
        font-weight: 700;
    }

    .sk-draft {
        background: #e2e8f0;
        color: #334155;
    }

    .sk-menunggu {
        background: #fef3c7;
        color: #92400e;
    }

    .sk-disetujui {
        background: #dcfce7;
        color: #166534;
    }

    .sk-ditolak {
        background: #fee2e2;
        color: #991b1b;
    }

    @media (max-width: 767px) {
        .sk-card {
            padding: 16px;
        }

        .sk-heading {
            font-size: 22px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="sk-heading mb-1">Surat Keluar</h2>
            <p class="text-secondary mb-0">
                Pengelolaan surat keluar HIMSI sesuai prosedur persuratan.
            </p>
        </div>

        <a href="{{ route('sekretaris.surat-keluar.create') }}" class="sk-btn">
            <i class="bi bi-plus-circle"></i>
            Tambah Surat Keluar
        </a>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Total Surat Keluar', $jumlahSuratKeluar, 'bi-envelope-paper'],
            ['Draft', $jumlahDraft, 'bi-file-earmark-text'],
            ['Menunggu Persetujuan', $jumlahMenunggu, 'bi-hourglass-split'],
            ['Disetujui', $jumlahDisetujui, 'bi-check-circle']
        ] as [$label, $jumlah, $icon])
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="sk-card h-100">
                    <i class="bi {{ $icon }} fs-4 text-warning"></i>
                    <div class="sk-stat-number mt-2">{{ $jumlah }}</div>
                    <div class="sk-stat-label">{{ $label }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="sk-card">
        <h5 class="fw-bold mb-3" style="color:#101b4c">
            Daftar Surat Keluar
        </h5>

        <div class="table-responsive">
            <table class="sk-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nomor Surat</th>
                        <th>Jenis Surat</th>
                        <th>Tujuan</th>
                        <th>Perihal</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suratKeluars as $surat)
                        <tr>
                            <td>{{ $suratKeluars->firstItem() + $loop->index }}</td>
                            <td>{{ $surat->nomor_surat ?? 'Belum diterbitkan' }}</td>
                            <td>{{ $surat->jenis_surat }}</td>
                            <td>{{ $surat->tujuan }}</td>
                            <td>{{ $surat->perihal }}</td>
                            <td>{{ $surat->tanggal_surat?->format('d/m/Y') }}</td>
                            <td>
                                @php
                                    $statusClass = match($surat->status) {
                                        'draft' => 'sk-draft',
                                        'menunggu_persetujuan' => 'sk-menunggu',
                                        'disetujui' => 'sk-disetujui',
                                        'ditolak' => 'sk-ditolak',
                                        default => 'sk-draft'
                                    };
                                @endphp

                                <span class="sk-status {{ $statusClass }}">
                                    {{ ucwords(str_replace('_', ' ', $surat->status)) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('sekretaris.surat-keluar.show', $surat) }}"
                                   class="sk-btn"
                                   style="padding:7px 12px;font-size:12px;">
                                    <i class="bi bi-eye"></i>
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-secondary">
                                <i class="bi bi-envelope-paper fs-1 d-block mb-2"></i>
                                Belum ada data Surat Keluar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $suratKeluars->links() }}
        </div>
    </div>

</div>
@endsection