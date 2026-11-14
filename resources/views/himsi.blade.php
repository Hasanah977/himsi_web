@if($page === 'login')
    @include('portal.login')
@else
@php
    $isAuth = in_array($page, ['login', 'forgot', 'reset'], true);
    /*
    |--------------------------------------------------------------------------
    | DATA PUBLIK DARI DATABASE
    |--------------------------------------------------------------------------
    */
    $programs = collect(
        \App\Models\PublicContent::items('programs')
    );
    $announcements = collect(
        \App\Models\PublicContent::items('announcements')
    );
    $gallery = \App\Models\PublicContent::items('gallery');
    $leaders = \App\Models\PublicContent::items('leaders');
    $departments = \App\Models\PublicContent::items('departments');
    $settings = \App\Models\PublicContent::settings();
    /*
    |--------------------------------------------------------------------------
    | GAMBAR LAMA DAN GAMBAR UPLOAD SUPER ADMIN
    |--------------------------------------------------------------------------
    */
    $image = fn ($filename) =>
        \App\Models\PublicContent::imageUrl($filename);
    /*
    |--------------------------------------------------------------------------
    | NAVIGASI
    |--------------------------------------------------------------------------
    */
    $navigation = [
        'home' => 'Beranda',
        'profile' => 'Profil',
        'structure' => 'Struktur',
        'programs' => 'Program Kerja',
        'announcements' => 'Pengumuman',
    ];
    /*
    |--------------------------------------------------------------------------
    | NILAI ORGANISASI
    |--------------------------------------------------------------------------
    */
    $coreValues = [
        ['bi-shield-check', 'Integritas', $settings['integrity']],
        ['bi-people', 'Kolaborasi', $settings['collaboration']],
        ['bi-lightbulb', 'Inovasi', $settings['innovation']],
        ['bi-heart', 'Dedikasi', $settings['dedication']],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('components.site-icon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta
        name="description"
        content="Website Himpunan Mahasiswa Sistem Informasi."
    >
    <meta name="theme-color" content="#1b234f">
    @if($isAuth || $page === 'dashboard')
        <meta name="robots" content="noindex">
    @endif
    <title>{{ $title }} — HIMSI</title>
    @vite($isAuth || $page === 'dashboard' ? 'resources/js/app.js' : 'resources/js/public.js')
    <link rel="stylesheet" href="{{ asset('css/himsi-portal.css') }}">
<link rel="stylesheet" href="{{ asset('css/himsi-revision.css') }}?v=20261110-4">
</head>
<body class="{{ $isAuth ? 'auth-page' : 'public-site' }}">
@if($isAuth)
    {{-- =========================================================
         LOGIN / LUPA PASSWORD / RESET PASSWORD
    ========================================================== --}}
    <main class="auth-card">
        <header class="auth-heading">
            @if($page === 'login')
                <img
                    class="auth-logo"
                    src="{{ $image($settings['logo']) }}"
                    alt="Logo HIMSI"
                >
                <h1>Portal Admin</h1>
                <p>Panel Kontrol Sistem HIMSI</p>
            @elseif($page === 'forgot')
                <div class="auth-symbol">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                </div>
                <h1>Lupa Kata Sandi?</h1>
                <p>
                    Masukkan email Anda untuk menerima instruksi
                    pengaturan ulang kata sandi.
                </p>
            @else
                <div class="auth-symbol">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                </div>
                <h1>Kata Sandi Baru</h1>
                <p>Gunakan minimal 8 karakter untuk kata sandi Anda.</p>
            @endif
        </header>
        @if(session('status'))
            <div class="alert alert-success" role="status">
                {{ session('status') }}
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if($page === 'login')
            <form
                method="POST"
                action="{{ route('login.submit') }}"
                data-submit
            >
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input
                        class="form-control"
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Masukkan email"
                        autocomplete="username"
                        required
                    >
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">
                        Kata Sandi
                    </label>
                    <div class="password-field">
                        <input
                            class="form-control"
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan kata sandi"
                            autocomplete="current-password"
                            required
                        >
                        <button
                            class="password-toggle"
                            type="button"
                            data-password-toggle="password"
                            aria-controls="password"
                            aria-pressed="false"
                            aria-label="Tampilkan kata sandi"
                        >
                            Lihat
                        </button>
                    </div>
                </div>
                <div class="login-options">
                    <label class="remember-label">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="remember"
                            value="1"
                        >
                        Ingat saya
                    </label>
                    <a
                        class="text-link"
                        href="{{ route('password.request') }}"
                    >
                        Lupa Password?
                    </a>
                </div>
                <button
                    class="btn btn-navy w-100"
                    type="submit"
                    data-label="Login"
                >
                    Login
                </button>
            </form>
        @elseif($page === 'forgot')
            <form
                method="POST"
                action="{{ route('password.email') }}"
                data-submit
            >
                @csrf
                <div class="mb-4">
                    <label class="form-label" for="email">Email</label>
                    <input
                        class="form-control"
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Masukkan email"
                        autocomplete="email"
                        required
                    >
                </div>
                <button
                    class="btn btn-navy w-100"
                    type="submit"
                    data-label="Kirim Tautan Reset"
                >
                    Kirim Tautan Reset
                </button>
            </form>
        @else
            <form
                method="POST"
                action="{{ route('password.update') }}"
                data-submit
            >
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input
                        class="form-control"
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $email) }}"
                        autocomplete="email"
                        required
                    >
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">
                        Kata sandi baru
                    </label>
                    <div class="password-field">
                        <input
                            class="form-control"
                            type="password"
                            id="password"
                            name="password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >
                        <button
                            class="password-toggle"
                            type="button"
                            data-password-toggle="password"
                            aria-controls="password"
                            aria-pressed="false"
                            aria-label="Tampilkan kata sandi"
                        >
                            Lihat
                        </button>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password_confirmation">
                        Ulangi kata sandi
                    </label>
                    <input
                        class="form-control"
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>
                <button
                    class="btn btn-navy w-100"
                    type="submit"
                    data-label="Simpan Kata Sandi"
                >
                    Simpan Kata Sandi
                </button>
            </form>
        @endif
        <div class="auth-links">
            @if($page !== 'login')
                <a href="{{ route('login') }}">← Kembali ke Login</a>
            @endif
            @if($page === 'reset')
                <a href="{{ route('password.request') }}">
                    Minta tautan reset baru
                </a>
            @endif
            <a href="{{ route('home') }}">← Kembali ke Beranda</a>
        </div>
    </main>
@else
    <a class="skip-link" href="#main-content">
        Langsung ke konten
    </a>
    {{-- =========================================================
         NAVBAR
    ========================================================== --}}
    @include('components.public-navbar')
    <main id="main-content" data-public-pages="{{ json_encode(array_map(fn ($route) => route($route), array_merge(array_keys($navigation), ['achievements']))) }}">
        @if($page === 'home')
            {{-- =================================================
                 HERO
            ================================================== --}}
            @include('components.public-hero')
            <div class="section-soft">
                {{-- STATISTIK --}}
                <div class="container stats-wrap">
                    <div class="stats">
                        <div class="stat">
                            <span class="stat-icon">
                                <i class="bi bi-people" aria-hidden="true"></i>
                            </span>
                            <strong data-count-up="{{ $statistics['members_count'] }}" aria-label="{{ $statistics['members_count'] }}">{{ $statistics['members_count'] }}</strong>
                            <small>Total Anggota Aktif</small>
                        </div>
                        <div class="stat">
                            <span class="stat-icon">
                                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                            </span>
                            <strong data-count-up="{{ $statistics['programs_count'] }}" aria-label="{{ $statistics['programs_count'] }}">{{ $statistics['programs_count'] }}</strong>
                            <small>Program Kerja Berjalan</small>
                        </div>
                        <div class="stat"><span class="stat-icon"><i class="bi bi-clipboard-data"></i></span><strong data-count-up="{{ $programs->count() }}" aria-label="{{ $programs->count() }}">{{ $programs->count() }}</strong><small>Total Program Kerja</small></div>
                        <div class="stat">
                            <span class="stat-icon">
                                <i class="bi bi-trophy" aria-hidden="true"></i>
                            </span>
                            <strong data-count-up="{{ $statistics['achievements_count'] }}" aria-label="{{ $statistics['achievements_count'] }}">{{ $statistics['achievements_count'] }}</strong>
                            <small>Prestasi Diraih</small>
                        </div>
                    </div>
                </div>
                {{-- PROGRAM KERJA --}}
                <section class="section">
                    <div class="container">
                        <div class="section-heading">
                            <div>
                                <span class="eyebrow">— Kegiatan Kami</span>
                                <h2>Program Kerja Terbaru</h2>
                            </div>
                            <a class="text-link" href="{{ route('programs') }}">
                                Lihat semua program →
                            </a>
                        </div>
                        @if($programs->isEmpty())
                            <p class="text-center py-4">
                                Belum ada program kerja yang ditampilkan.
                            </p>
                        @else
                            <div class="news-grid">
                                @foreach($programs->take(3) as $program)
                                    <article
                                        class="news-card {{ $loop->first ? 'news-feature' : 'news-small' }}"
                                        data-reveal
                                    >
                                        <a
                                            class="news-image"
                                            href="{{ route('program.detail', $program['slug']) }}"
                                            tabindex="-1"
                                            aria-hidden="true"
                                        >
                                            <img
                                                src="{{ $image($program['image']) }}"
                                                alt=""
                                                loading="lazy"
                                            >
                                        </a>
                                        <div class="news-body">
                                            <span class="tag">
                                                {{ $program['status'] }}
                                            </span>
                                            <div class="news-meta">
                                                {{ $program['category'] }}
                                                · {{ $program['date'] }}
                                            </div>
                                            <h3>
                                                <a href="{{ route('program.detail', $program['slug']) }}">
                                                    {{ $program['title'] }}
                                                </a>
                                            </h3>
                                            <p>{{ $program['summary'] }}</p>
                                            <a
                                                class="text-link"
                                                href="{{ route('program.detail', $program['slug']) }}"
                                            >
                                                Baca selengkapnya ↗
                                                <span class="visually-hidden">
                                                    : {{ $program['title'] }}
                                                </span>
                                            </a>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            </div>
            {{-- =================================================
                 PENGUMUMAN DAN RUNNING TEXT
            ================================================== --}}
            <section class="section">
                <div class="container section-center">
                    <h2>Informasi & Pengumuman</h2>
                    <p>
                        Pembaruan terkini seputar kegiatan akademik
                        dan kemahasiswaan HIMSI.
                    </p>
                </div>
                @if($announcements->isNotEmpty())
                    <div class="ticker">
                        <span class="ticker-label">UPDATE TERKINI</span>
                        <div class="ticker-window">
                            <div class="ticker-track">
                                <div class="ticker-group">
                                    @foreach($announcements->take(3) as $announcement)
                                        <a href="{{ route('announcement.detail', $announcement['slug']) }}">
                                            {{ $announcement['title'] }}
                                        </a>
                                    @endforeach
                                </div>
                                <div class="ticker-group" aria-hidden="true">
                                    @foreach($announcements->take(3) as $announcement)
                                        <a
                                            tabindex="-1"
                                            href="{{ route('announcement.detail', $announcement['slug']) }}"
                                        >
                                            {{ $announcement['title'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <button
                            type="button"
                            data-ticker-toggle
                            aria-pressed="false"
                        >
                            Jeda
                        </button>
                    </div>
                @endif
                <div class="container">
                    <div class="row g-3">
                        @forelse($announcements->take(3) as $announcement)
                            <div class="col-md-4">
                                <a
                                    class="mini-notice"
                                    href="{{ route('announcement.detail', $announcement['slug']) }}"
                                >
                                    <small>
                                        {{ $announcement['category'] }}
                                        · {{ $announcement['date'] }}
                                    </small>
                                    <h3>{{ $announcement['title'] }}</h3>
                                </a>
                            </div>
                        @empty
                            <div class="col-12 text-center">
                                <p>Belum ada pengumuman yang ditampilkan.</p>
                            </div>
                        @endforelse
                    </div>
                    <div class="text-center mt-4">
                        <a
                            class="btn btn-outline-navy"
                            href="{{ route('announcements') }}"
                        >
                            Lihat Semua Pengumuman
                        </a>
                    </div>
                </div>
            </section>
            {{-- =================================================
                 GALERI
            ================================================== --}}
            <section class="section section-soft">
                <div class="container">
                    <div class="section-center">
                        <h2>Galeri Kegiatan</h2>
                        <p>
                            Momen-momen berkesan kebersamaan keluarga besar HIMSI.
                            <br>Klik foto untuk melihat lebih dekat.
                        </p>
                    </div>
                    @if(empty($gallery))
                        <p class="text-center">Belum ada foto yang ditampilkan.</p>
                    @else
                        <div class="gallery-marquee" aria-label="Galeri foto kegiatan HIMSI">
                            @foreach([0, 1] as $galleryRow)
                                <div class="gallery-marquee-row" data-gallery-direction="{{ $galleryRow === 0 ? 'right' : 'left' }}">
                                    <div class="gallery-marquee-track">
                                        <div class="gallery-marquee-group">
                            @foreach(count($gallery) === 1 ? $gallery : array_values(array_filter($gallery, fn ($key) => $key % 2 === $galleryRow, ARRAY_FILTER_USE_KEY)) as $photo)
                                <button
                                    class="gallery-item"
                                    type="button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#galleryModal"
                                    data-image="{{ $image($photo['image']) }}"
                                    data-title="{{ $photo['title'] }}"
                                    data-description="{{ $photo['description'] }}"
                                    aria-label="Perbesar foto {{ $photo['title'] }}"
                                >
                                    <img
                                        src="{{ $image($photo['image']) }}"
                                        alt="{{ $photo['title'] }}"
                                        loading="lazy"
                                    >
                                    <span>{{ $photo['title'] }} ↗</span>
                                </button>
                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
            {{-- =================================================
                 KUTIPAN
            ================================================== --}}
            @include('components.public-quotes')
        @elseif($page === 'profile')
            @include('components.public-profile')
        @elseif($page === 'structure')
            @include('components.public-structure')
        @elseif(in_array($page, ['programs', 'announcements', 'achievements'], true))
            @include('components.public-listing')
        @elseif($page === 'detail')
            {{-- =================================================
                 DETAIL PROGRAM / PENGUMUMAN
            ================================================== --}}
            <section class="section section-soft">
                <div class="container">
                    <article class="panel article-detail">
                        <a class="text-link" href="{{ route($backRoute) }}">
                            ← Kembali ke {{ $backLabel }}
                        </a>
                        <div class="notice-meta mt-4">
                            <span class="tag">{{ $item['category'] }}</span>
                            <small>{{ $item['date'] }}</small>
                        </div>
                        <h1>{{ $item['title'] }}</h1>
                        @if(request()->routeIs('announcement.detail') && !empty($item['summary']))
                            <p class="article-lead">{{ $item['summary'] }}</p>
                        @endif
                        @if(isset($item['image']) && (!request()->routeIs('announcement.detail') || $item['image'] !== 'placeholder.svg'))
                            <img
                                class="article-cover {{ request()->routeIs('announcement.detail') ? 'announcement-cover' : '' }}"
                                src="{{ $image($item['image']) }}"
                                alt="{{ $item['title'] }}"
                            >
                        @endif
                        @if(isset($item['status'], $item['location']))
                            <p class="mt-3">
                                <strong>{{ $item['status'] }}</strong>
                                · {{ $item['location'] }}
                            </p>
                        @endif
                        <div class="article-text">
                            @foreach($item['body'] as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                        <a
                            class="btn btn-outline-navy"
                            href="{{ route($backRoute) }}"
                        >
                            Lihat {{ $backLabel }} Lainnya
                        </a>
                    </article>
                </div>
            </section>
        @elseif($page === 'dashboard')
            {{-- =================================================
                 DASHBOARD SAMBUTAN AKUN SELAIN SUPER ADMIN
            ================================================== --}}
            <section class="section section-soft dashboard-section">
                <div class="container">
                    <div class="panel">
                        <span class="eyebrow">Portal HIMSI</span>
                        <h1>
                            Selamat datang, {{ auth()->user()->name }}.
                        </h1>
                        <p>
                            Anda berhasil masuk. Modul sesuai role akun
                            akan tersedia setelah dihubungkan oleh pengelola.
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            @if($canVoteKpum ?? false)
                                <a class="btn btn-navy" href="{{ route('kpum.vote') }}">Pemilihan Ketua HIMSI</a>
                            @endif
                            <a
                                class="btn btn-outline-navy"
                                href="{{ route('home') }}"
                            >
                                Kembali ke Beranda
                            </a>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="btn btn-navy" type="submit">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </main>
    {{-- =========================================================
         FOOTER
    ========================================================== --}}
    @include('components.public-footer')
    {{-- =========================================================
         MODAL FOTO GALERI
    ========================================================== --}}
    @if($page === 'home')
        <div
            class="modal fade"
            id="galleryModal"
            tabindex="-1"
            aria-labelledby="galleryModalTitle"
            aria-describedby="galleryModalDescription"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="galleryModalTitle">
                            Detail Galeri
                        </h2>
                        <button
                            class="btn-close"
                            type="button"
                            data-bs-dismiss="modal"
                            aria-label="Tutup foto"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <img id="galleryModalImage" alt="">
                        <p
                            class="mt-3 mb-1"
                            id="galleryModalDescription"
                        ></p>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif
</body>
</html>
@endif
