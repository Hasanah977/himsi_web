<!DOCTYPE html>
<html lang="id">

<head>
    @include('components.site-icon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Panitia KPUM') — HIMSI</title>
    @vite('resources/js/app.js')
    <link rel="stylesheet" href="{{ asset('css/himsi-kpum.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/himsi-sidebar.css') }}?v=3">
    <script src="{{ asset('js/himsi-sidebar.js') }}?v=3" defer></script>
    <link rel="stylesheet" href="{{ asset('css/himsi-workspace.css') }}?v=20261110-2">
<link rel="stylesheet" href="{{ asset('css/himsi-revision.css') }}?v=20261109">
    <link rel="stylesheet" href="{{ asset('css/himsi-sidebar-theme.css') }}?v=20261111-compact-menus">
    @include('components.workspace-background')
    @stack('styles')
</head>

<body class="kpum-page">
    @php($isManager = auth()->user()->isSuperAdmin() || auth()->user()->role === 'panitia_kpum')
    <div class="k-shell">
        <aside class="offcanvas-lg offcanvas-start k-sidebar" id="kpumSidebar" tabindex="-1"
            aria-labelledby="kpumSidebarTitle">
            <div class="offcanvas-header">
                <h2 id="kpumSidebarTitle">Menu HIMSI</h2><button class="btn-close btn-close-white" type="button"
                    data-bs-dismiss="offcanvas" data-bs-target="#kpumSidebar" aria-label="Tutup menu"></button>
            </div>
            <div class="offcanvas-body">
                <a class="k-brand" href="{{ route($isManager ? 'kpum.dashboard' : 'kpum.vote') }}"><img
                        src="{{ $kpumLogo }}" alt="Logo HIMSI" width="48"
                        height="48"><span>HIMSI<small>{{ $isManager ? 'Panitia KPUM' : 'Ruang Pemilih' }}</small></span></a>
                <x-sidebar-account />
<nav aria-label="Navigasi KPUM">
                    <div class="k-nav-label">RUANG PEMILIHAN</div>
                    @if ($isManager)
                        <a class="k-nav {{ request()->routeIs('kpum.dashboard') ? 'active' : '' }}"
                            href="{{ route('kpum.dashboard') }}"><i class="bi bi-grid" aria-hidden="true"></i>
                            Dashboard</a>
                        <a class="k-nav {{ request()->routeIs('kpum.candidates*', 'kpum.control', 'kpum.voters*', 'kpum.results', 'kpum.announcement') ? 'active' : '' }}"
                            href="{{ route('kpum.candidates') }}"><i class="bi bi-ui-checks" aria-hidden="true"></i>
                            Pemilihan Ketua (KPUM)</a>
                    @else
                        <a class="k-nav" href="{{ route('dashboard') }}"><i class="bi bi-grid" aria-hidden="true"></i>
                            Dashboard akun</a>
                        <a class="k-nav active" href="{{ route('kpum.vote') }}"><i class="bi bi-check2-square"
                                aria-hidden="true"></i> Bilik Suara</a>
                    @endif
                </nav>
                <div class="k-sidebar-foot">
                    <div class="k-sidebar-note"><i class="bi bi-shield-check" aria-hidden="true"></i><span>Satu
                            suara.<br>Untuk masa depan HIMSI.</span></div>
                    <a class="k-nav" href="{{ route('home') }}"><i class="bi bi-box-arrow-up-right"
                            aria-hidden="true"></i> Website Publik</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="k-nav" type="submit"><i
                                class="bi bi-box-arrow-right" aria-hidden="true"></i> Keluar</button></form>
                </div>
            </div>
        </aside>
        <div class="k-main">
            <header class="k-topbar">
                <div class="d-flex align-items-center gap-3"><button class="btn k-icon-btn d-lg-none" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#kpumSidebar" aria-controls="kpumSidebar"
                        aria-label="Buka menu"><i class="bi bi-list" aria-hidden="true"></i></button><span
                        class="k-breadcrumb">HIMSI <span>/</span> <strong>@yield('title', 'KPUM')</strong></span></div>
                <div class="workspace-header-actions"><x-notification-bell /><div class="k-user"><span
                        class="k-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span>{{ auth()->user()->name }}<small>{{ auth()->user()->isSuperAdmin() ? 'Super Admin' : ($isManager ? 'Panitia KPUM' : 'Pemilih') }}</small></span>
                </div>
                </div>
            </header>
            <main class="k-content" id="main-content">
                @if (session('success'))
                    <div class="alert alert-success" role="status"><i class="bi bi-check-circle me-2"
                            aria-hidden="true"></i>{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert"><strong>Periksa kembali isian berikut:</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
                <footer class="k-footer">HIMSI UNSULBAR — FT <span>Komisi Pemilihan Umum Mahasiswa</span></footer>
            </main>
        </div>
    </div>
    <div class="modal fade" id="kConfirm" tabindex="-1" aria-labelledby="kConfirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="kConfirmTitle">Konfirmasi tindakan</h2><button class="btn-close"
                        type="button" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p id="kConfirmMessage" class="mb-0"></p>
                </div>
                <div class="modal-footer"><button class="btn btn-outline-navy" type="button"
                        data-bs-dismiss="modal">Batal</button><button class="btn btn-navy" id="kConfirmSubmit"
                        type="button">Ya, lanjutkan</button></div>
            </div>
        </div>
    </div>
</body>

</html>
