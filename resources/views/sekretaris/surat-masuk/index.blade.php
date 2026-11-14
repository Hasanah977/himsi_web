@extends('layouts.sekretaris')

@section('title', 'Surat Masuk')

@push('styles')
<style>
    .surat-page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
    }

    .surat-page-header h2 {
        color: #172454;
        font-size: 23px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .surat-page-header p {
        color: #64748b;
        font-size: 13px;
        margin-bottom: 0;
    }

    .surat-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #172454;
        color: #fff;
        border-radius: 10px;
        padding: 11px 17px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: background .2s, transform .2s;
    }

    .surat-btn-primary:hover {
        background: #263b82;
        color: #fff;
        transform: translateY(-2px);
    }

    .surat-table-wrap {
        overflow-x: auto;
    }

    .surat-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1050px;
        font-size: 13px;
    }

    .surat-table th {
        background: #f1f4fa;
        color: #172454;
        font-weight: 700;
        padding: 15px 12px;
        white-space: nowrap;
    }

    .surat-table td {
        padding: 15px 12px;
        border-bottom: 1px solid #edf0f7;
        vertical-align: middle;
        color: #334155;
    }

    .surat-table tbody tr:hover {
        background: #f8faff;
    }

    .surat-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .surat-action {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #fff;
        text-decoration: none;
        color: #172454;
        cursor: pointer;
    }

    .surat-action:hover {
        background: #edf2ff;
    }

    .surat-action.delete {
        color: #dc2626;
    }

    .surat-empty {
        padding: 55px 20px;
        text-align: center;
        color: #64748b;
    }

    .surat-empty i {
        display: block;
        font-size: 42px;
        color: #94a3b8;
        margin-bottom: 12px;
    }

    @media (max-width: 767px) {
        .surat-page-header h2 {
            font-size: 19px;
        }

        .surat-btn-primary {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush

@section('content')
<div class="surat-page-header">
    <div>
        <h2>Buku Kendali Surat Masuk</h2>
        <p>Pencatatan dan pengarsipan surat masuk HIMSI sesuai SOP organisasi.</p>
    </div>

    <a href="{{ route('sekretaris.surat-masuk.create') }}"
       class="surat-btn-primary">
        <i class="bi bi-plus-circle"></i>
        Tambah Surat Masuk
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Tutup"></button>
    </div>
@endif

<div class="sekretaris-panel">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-1">Daftar Surat Masuk</h5>
            <small class="text-secondary">
                Total {{ $suratMasuks->total() }} surat tercatat
            </small>
        </div>
        <span class="badge text-bg-light border px-3 py-2">
            <i class="bi bi-journal-text me-1"></i>
            Buku Kendali
        </span>
    </div>

    <div class="surat-table-wrap">
        <table class="surat-table">
            <thead>
                <tr>
                    <th>Nomor Surat</th>
                    <th>Tanggal Diterima</th>
                    <th>Pengirim</th>
                    <th>Perihal</th>
                    <th>Disposisi</th>
                    <th>Bidang Arsip</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suratMasuks as $surat)
                    <tr>
                        <td class="fw-semibold">{{ $surat->nomor_surat }}</td>
                        <td>{{ $surat->tanggal_diterima?->format('d/m/Y') }}</td>
                        <td>
                            <div class="fw-semibold">{{ $surat->pengirim }}</div>
                            <small class="text-secondary">
                                {{ $surat->alamat_pengirim }}
                            </small>
                        </td>
                        <td>{{ $surat->perihal }}</td>
                        <td>{{ Str::limit($surat->disposisi ?? '-', 50) }}</td>
                        <td>{{ $surat->bidang_arsip ?? '-' }}</td>
                        <td>
                            <div class="surat-actions">
                                <a class="surat-action"
                                   href="{{ route('sekretaris.surat-masuk.show', $surat) }}"
                                   title="Lihat detail"
                                   aria-label="Lihat detail">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a class="surat-action"
                                   href="{{ route('sekretaris.surat-masuk.edit', $surat) }}"
                                   title="Edit surat"
                                   aria-label="Edit surat">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form action="{{ route('sekretaris.surat-masuk.destroy', $surat) }}"
                                      method="POST"
                                      data-confirm="Yakin ingin menghapus surat ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="surat-action delete"
                                            title="Hapus surat"
                                            aria-label="Hapus surat">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="surat-empty">
                                <i class="bi bi-inbox"></i>
                                <strong>Belum ada surat masuk</strong>
                                <p class="mt-2 mb-0">
                                    Klik Tambah Surat Masuk untuk mulai mencatat surat.
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $suratMasuks->links() }}
    </div>
</div>
@endsection