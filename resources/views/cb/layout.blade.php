<!DOCTYPE html>
<html lang="id">
<head>
    @include('components.site-icon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Panitia CB') — HIMSI</title>
    @vite('resources/js/app.js')
    <link rel="stylesheet" href="{{ asset('css/himsi-kpum.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/himsi-cb.css') }}?v=1">
    <script src="{{ asset('js/himsi-cb.js') }}?v=1" defer></script>
    <link rel="stylesheet" href="{{ asset('css/himsi-sidebar.css') }}?v=3">
    <script src="{{ asset('js/himsi-sidebar.js') }}?v=3" defer></script>
    <link rel="stylesheet" href="{{ asset('css/himsi-workspace.css') }}?v=20261110-2">
<link rel="stylesheet" href="{{ asset('css/himsi-revision.css') }}?v=20261109">
    <link rel="stylesheet" href="{{ asset('css/himsi-sidebar-theme.css') }}?v=20261111-compact-menus">
    @include('components.workspace-background')
    @stack('styles')
</head>
<body class="kpum-page cb-page">
<div class="k-shell">
    <aside class="offcanvas-lg offcanvas-start k-sidebar" id="cbSidebar" tabindex="-1" aria-labelledby="cbSidebarTitle">
        <div class="offcanvas-header"><h2 id="cbSidebarTitle">Menu HIMSI</h2><button class="btn-close btn-close-white" type="button" data-bs-dismiss="offcanvas" data-bs-target="#cbSidebar" aria-label="Tutup menu"></button></div>
        <div class="offcanvas-body">
            <a class="k-brand" href="{{ route('cb.dashboard') }}"><img src="{{ $cbLogo }}" alt="Logo HIMSI" width="48" height="48"><span>HIMSI<small>Panitia Character Building</small></span></a>
            <x-sidebar-account />
<nav aria-label="Navigasi Panitia CB">
                <div class="k-nav-label">RUANG KADERISASI</div>
                <a class="k-nav {{ request()->routeIs('cb.dashboard') ? 'active' : '' }}" href="{{ route('cb.dashboard') }}"><i class="bi bi-grid" aria-hidden="true"></i>Dashboard</a>
                <a class="k-nav {{ request()->routeIs('cb.recruitment*') ? 'active' : '' }}" href="{{ route('cb.recruitment') }}"><i class="bi bi-clipboard-check" aria-hidden="true"></i>Rekrutmen KBH @if($cbPending)<b class="cb-nav-count">{{ $cbPending }}</b>@endif</a>
                <a class="k-nav {{ request()->routeIs('cb.activities*', 'cb.participants*') ? 'active' : '' }}" href="{{ route('cb.activities') }}"><i class="bi bi-calendar-event" aria-hidden="true"></i>Kegiatan CB</a>
            </nav>
            <div class="k-sidebar-foot">
                <div class="k-sidebar-note"><i class="bi bi-shield-check" aria-hidden="true"></i><span>Bertumbuh bersama.<br>Membangun karakter HIMSI.</span></div>
                
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="k-nav" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Keluar</button></form>
            </div>
        </div>
    </aside>
    <div class="k-main">
        <header class="k-topbar">
            <div class="d-flex align-items-center gap-3"><button class="btn k-icon-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#cbSidebar" aria-controls="cbSidebar" aria-label="Buka menu"><i class="bi bi-list" aria-hidden="true"></i></button><span class="k-breadcrumb">HIMSI <span>/</span> <strong>@yield('title', 'Panitia CB')</strong></span></div>
            <div class="d-flex align-items-center gap-3"><x-notification-bell /><div class="k-user"><span class="k-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span>{{ auth()->user()->name }}<small>Panitia CB</small></span></div></div>
        </header>
        <main class="k-content" id="main-content">
            @if(session('success'))<div class="alert alert-success" role="status"><i class="bi bi-check-circle me-2" aria-hidden="true"></i>{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger" role="alert"><strong>Periksa kembali isian berikut:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
            <footer class="k-footer">HIMSI UNSULBAR — FT <span>Character Building · Kaderisasi HIMSI</span></footer>
        </main>
    </div>
</div>
<div class="modal fade" id="kConfirm" tabindex="-1" aria-labelledby="kConfirmTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-5" id="kConfirmTitle">Konfirmasi tindakan</h2><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><p id="kConfirmMessage" class="mb-0"></p></div><div class="modal-footer"><button class="btn btn-outline-navy" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-navy" id="kConfirmSubmit" type="button">Ya, lanjutkan</button></div></div></div></div>
</body>
</html>
