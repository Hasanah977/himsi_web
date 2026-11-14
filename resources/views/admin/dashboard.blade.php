@extends('admin.layout')

@section('title', 'Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/himsi-analytics.css') }}?v=2">
@endpush
@section('content')
    @php($publicIsClosed = app(\App\Support\EmergencyState::class)->read()['maintenance'])
    <section class="admin-panel security-quickbar">
        <div><strong><i class="bi bi-shield-lock me-2" aria-hidden="true"></i>Kendali darurat</strong><p class="small text-secondary mb-0">Tindakan langsung saat terjadi insiden.</p></div>
        <div class="d-flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.security.maintenance') }}">@csrf<input type="hidden" name="enabled" value="{{ $publicIsClosed ? '0' : '1' }}"><button class="btn {{ $publicIsClosed ? 'btn-success' : 'btn-danger' }}" type="submit"><i class="bi bi-{{ $publicIsClosed ? 'globe2' : 'power' }} me-2" aria-hidden="true"></i>{{ $publicIsClosed ? 'Buka publik' : 'Tutup publik' }}</button></form>
            <form method="POST" action="{{ route('admin.security.freeze') }}">@csrf<button class="btn btn-outline-danger" type="submit"><i class="bi bi-lock me-2" aria-hidden="true"></i>Bekukan role lain</button></form>
            <a class="btn btn-outline-navy" href="{{ route('admin.security.index') }}">Backup & pemulihan</a>
        </div>
    </section>
    <section class="admin-welcome">
        <div>
            <span class="admin-eyebrow">SELAMAT DATANG</span>
            <h2>Kelola HIMSI dari satu tempat.</h2>
            <p>
                Perbarui informasi publik, atur akun pengguna,
                dan hubungkan modul setiap kelompok.
            </p>
        </div>

        <a
            class="btn btn-yellow"
            href="{{ route('home') }}"
            target="_blank"
            rel="noopener"
        >
            Lihat Website
            <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
        </a>
    </section>

    <div class="admin-stat-grid">
        @foreach([
            ['bi-people', $usersCount, 'Total Akun'],
            ['bi-check2-circle', $publishedCount, 'Konten Tayang'],
            ['bi-file-earmark', $draftCount, 'Konten Draft'],
            ['bi-person-exclamation', $pendingCount, 'Role Belum Ditentukan'],
        ] as $stat)
            <article class="admin-stat-card">
                <span class="admin-stat-icon">
                    <i class="bi {{ $stat[0] }}" aria-hidden="true"></i>
                </span>
                <strong>{{ $stat[1] }}</strong>
                <p>{{ $stat[2] }}</p>
            </article>
        @endforeach
    </div>

    @include('admin.analytics')
@endsection
