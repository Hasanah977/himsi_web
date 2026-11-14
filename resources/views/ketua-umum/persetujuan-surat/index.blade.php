@extends('layouts.ketua-umum')

@section('title', 'Persetujuan Surat Keluar')

@section('content')
<style>
    .ps-page {
        padding: 8px 0 32px;
    }

    .ps-title {
        color: #101b4c;
        font-size: 26px;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .ps-subtitle {
        color: #64748b;
        font-size: 14px;
        margin-bottom: 24px;
    }

    .ps-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .ps-stat {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 22px;
        box-shadow: 0 5px 20px rgba(16,27,76,.04);
    }

    .ps-stat-label {
        color: #64748b;
        font-size: 13px;
        font-weight: 600;
    }

    .ps-stat-number {
        color: #101b4c;
        font-size: 30px;
        font-weight: 800;
        margin-top: 10px;
    }

    .ps-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(16,27,76,.04);
    }

    .ps-card-header {
        padding: 20px 22px;
        border-bottom: 1px solid #e2e8f0;
        color: #101b4c;
        font-size: 17px;
        font-weight: 800;
    }

    .ps-table-wrap {
        overflow-x: auto;
    }

    .ps-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 850px;
    }

    .ps-table th {
        background: #f8fafc;
        padding: 14px 16px;
        text-align: left;
        color: #475569;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .ps-table td {
        padding: 16px;
        border-top: 1px solid #f1f5f9;
        font-size: 13px;
        color: #334155;
        vertical-align: middle;
    }

    .ps-badge {
        display: inline-block;
        border-radius: 999px;
        padding: 6px 11px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .ps-waiting {
        background: #fef3c7;
        color: #92400e;
    }

    .ps-approved {
        background: #dcfce7;
        color: #166534;
    }

    .ps-rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    .ps-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 13px;
        border-radius: 9px;
        background: #101b4c;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        border: 0;
    }

    .ps-btn:hover {
        background: #facc15;
        color: #101b4c;
    }

    .ps-empty {
        text-align: center;
        padding: 42px 20px;
        color: #64748b;
    }

    .ps-pagination {
        padding: 18px 22px;
    }

    @media (max-width: 767px) {
        .ps-stats {
            grid-template-columns: 1fr;
        }

        .ps-title {
            font-size: 22px;
        }

        .ps-stat {
            padding: 18px;
        }
    }
</style>

<div class="ps-page">

    <h2 class="ps-title">
        Persetujuan Surat Keluar
    </h2>

    <p class="ps-subtitle">
        Tinjau surat keluar yang diajukan oleh Sekertaris HIMSI.
    </p>

    <div class="ps-stats">

        <div class="ps-stat">
            <div class="ps-stat-label">
                <i class="bi bi-hourglass-split me-1"></i>
                Menunggu Persetujuan
            </div>
            <div class="ps-stat-number">
                {{ $jumlahMenunggu }}
            </div>
        </div>

        <div class="ps-stat">
            <div class="ps-stat-label">
                <i class="bi bi-check-circle me-1"></i>
                Disetujui
            </div>
            <div class="ps-stat-number">
                {{ $jumlahDisetujui }}
            </div>
        </div>

        <div class="ps-stat">
            <div class="ps-stat-label">
                <i class="bi bi-x-circle me-1"></i>
                Ditolak
            </div>
            <div class="ps-stat-number">
                {{ $jumlahDitolak }}
            </div>
        </div>

    </div>

    <div class="ps-card">

        <div class="ps-card-header">
            <i class="bi bi-file-earmark-check me-2"></i>
            Daftar Pengajuan Surat
        </div>

        <div class="ps-table-wrap">

            <table class="ps-table">

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Jenis Surat</th>
                        <th>Perihal</th>
                        <th>Tujuan</th>
                        <th>Diajukan Oleh</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($suratKeluars as $surat)
                        <tr>
                            <td>
                                {{ $suratKeluars->firstItem() + $loop->index }}
                            </td>

                            <td>
                                <strong>{{ $surat->jenis_surat }}</strong>
                            </td>

                            <td>{{ $surat->perihal }}</td>

                            <td>{{ $surat->tujuan }}</td>

                            <td>
                                {{ $surat->pembuat?->nama_lengkap ?? '-' }}
                            </td>

                            <td>
                                {{ $surat->diajukan_pada?->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i') ?? '-' }}
                            </td>

                            <td>
                                @if($surat->status === 'menunggu_persetujuan')
                                    <span class="ps-badge ps-waiting">
                                        Menunggu Persetujuan
                                    </span>
                                @elseif($surat->status === 'disetujui')
                                    <span class="ps-badge ps-approved">
                                        Disetujui
                                    </span>
                                @else
                                    <span class="ps-badge ps-rejected">
                                        Ditolak
                                    </span>
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('ketua-umum.persetujuan-surat.show', $surat) }}"
                                   class="ps-btn">
                                    <i class="bi bi-eye"></i>
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="ps-empty">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Belum ada pengajuan surat keluar.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>

        </div>

        @if($suratKeluars->hasPages())
            <div class="ps-pagination">
                {{ $suratKeluars->links() }}
            </div>
        @endif

    </div>

</div>
@endsection