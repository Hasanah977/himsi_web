@extends('layouts.ketua-umum')

@section('title', 'Monitoring Organisasi')

@push('styles')
<style>
    .monitoring-header {
        background: linear-gradient(120deg, #172454, #304a98);
        border-radius: 18px;
        padding: 28px;
        color: white;
        margin-bottom: 24px;
    }

    .monitoring-header h2 {
        font-size: 23px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .monitoring-header p {
        margin: 0;
        color: #e1e8ff;
        font-size: 14px;
    }

    .monitoring-card {
        background: white;
        border: 1px solid #e8edf5;
        border-radius: 16px;
        padding: 24px;
        height: 100%;
        box-shadow: 0 4px 18px rgba(16,27,76,.04);
        transition: transform .2s ease;
    }

    .monitoring-card:hover {
        transform: translateY(-4px);
    }

    .monitoring-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: #e9eefc;
        color: #172454;
        font-size: 23px;
        margin-bottom: 16px;
    }

    .monitoring-label {
        color: #64748b;
        font-size: 14px;
    }

    .monitoring-value {
        font-size: 30px;
        font-weight: 800;
        color: #172454;
        margin-top: 8px;
    }

    .monitoring-section {
        background: white;
        border: 1px solid #e8edf5;
        border-radius: 16px;
        padding: 24px;
        margin-top: 24px;
    }

    .monitoring-section h3 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 20px;
    }

    .role-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 15px 0;
        border-bottom: 1px solid #edf0f7;
    }

    .role-row:last-child {
        border-bottom: none;
    }

    .role-name {
        font-size: 14px;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .role-count {
        background: #e9eefc;
        color: #172454;
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 13px;
        flex-shrink: 0;
    }

    .monitoring-note {
        margin-top: 24px;
        background: #fff8df;
        border: 1px solid #f5df9a;
        border-radius: 12px;
        padding: 18px;
        color: #735600;
        font-size: 14px;
        line-height: 1.7;
    }

    @media (max-width: 767px) {
        .monitoring-header,
        .monitoring-card,
        .monitoring-section {
            padding: 18px;
        }

        .monitoring-header h2 {
            font-size: 20px;
        }

        .monitoring-value {
            font-size: 26px;
        }
    }
</style>
@endpush

@section('content')

<div class="monitoring-header">
    <h2>
        <i class="bi bi-bar-chart-line me-2"></i>
        Monitoring Organisasi
    </h2>
    <p>
        Pantau data akun dan distribusi pengguna berdasarkan
        role dalam Sistem Informasi HIMSI.
    </p>
</div>

<div class="row g-3">

    <div class="col-12 col-md-6">
        <div class="monitoring-card">
            <div class="monitoring-icon">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="monitoring-label">Total Akun Terdaftar</div>
            <div class="monitoring-value">
                {{ number_format($totalAkun, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="monitoring-card">
            <div class="monitoring-icon">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <div class="monitoring-label">Total Akun Aktif</div>
            <div class="monitoring-value">
                {{ number_format($totalAkunAktif, 0, ',', '.') }}
            </div>
        </div>
    </div>

</div>

<section class="monitoring-section">
    <h3>
        <i class="bi bi-diagram-3 me-2"></i>
        Distribusi Akun Berdasarkan Role
    </h3>

    @forelse ($statistikRole as $role)
        <div class="role-row">
            <span class="role-name">
                {{ $role->nama_role }}
            </span>

            <span class="role-count">
                {{ $role->users_count }} Akun
            </span>
        </div>
    @empty
        <p class="text-muted mb-0">
            Belum ada data role yang tersedia.
        </p>
    @endforelse
</section>

<div class="monitoring-note">
    <i class="bi bi-info-circle me-2"></i>
    Monitoring kegiatan, laporan departemen, dan perkembangan
    program kerja akan ditambahkan setelah modul dan tabel
    terkait tersedia. Data yang ditampilkan saat ini berasal
    dari database pengguna HIMSI.
</div>

@endsection
