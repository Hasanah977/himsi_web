@php
    $publicSettings = request()->routeIs('admin.security.*')
        ? config('superadmin.defaults', [])
        : \App\Models\PublicContent::settings();
    $adminLogo = \App\Models\PublicContent::imageUrl(
        $publicSettings['logo'] ?? 'logo-himsi.png'
    );
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    @include('components.site-icon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">

    <title>@yield('title', 'Super Admin') — HIMSI</title>

    @vite('resources/js/app.js')
    <link rel="stylesheet" href="{{ asset('css/himsi-super-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/himsi-sidebar.css') }}?v=3">
    <script src="{{ asset('js/himsi-sidebar.js') }}?v=3" defer></script>
    <link rel="stylesheet" href="{{ asset('css/himsi-workspace.css') }}?v=20261110-2">
    <link rel="stylesheet" href="{{ asset('css/himsi-security.css') }}?v=2">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/himsi-department.css') }}">
    <link rel="stylesheet" href="{{ asset('css/administration/member.css') }}">
    <script src="{{ asset('js/himsi-member.js') }}" defer></script>
<link rel="stylesheet" href="{{ asset('css/himsi-revision.css') }}?v=20261109">
<link rel="stylesheet" href="{{ asset('css/himsi-next.css') }}?v=20261110"><script src="{{ asset('js/himsi-next.js') }}?v=20261110" defer></script>
    <link rel="stylesheet" href="{{ asset('css/himsi-sidebar-theme.css') }}?v=20261111-compact-menus">
    @include('components.workspace-background')
</head>

<body class="admin-page super-admin-page">
<div class="admin-shell">

    <aside
        class="offcanvas-lg offcanvas-start admin-sidebar"
        tabindex="-1"
        id="adminSidebar"
        aria-labelledby="adminSidebarTitle"
    >
        <div class="offcanvas-header">
            <h2 class="fs-5 mb-0" id="adminSidebarTitle">
                Menu HIMSI
            </h2>

            <button
                class="btn-close btn-close-white"
                type="button"
                data-bs-dismiss="offcanvas"
                data-bs-target="#adminSidebar"
                aria-label="Tutup menu"
            ></button>
        </div>

        <div class="offcanvas-body">
            <a
                class="admin-brand"
                href="{{ route('admin.dashboard') }}"
                aria-label="Dashboard Super Admin HIMSI"
            >
                <img
                    class="admin-brand-logo"
                    src="{{ $adminLogo }}"
                    alt="Logo HIMSI"
                    width="48"
                    height="48"
                >

                <span class="admin-brand-text">
                    <span class="admin-brand-name">HIMSI</span>
                    <small>Super Admin</small>
                </span>
            </a>

            <x-sidebar-account />
<nav class="admin-navigation" aria-label="Menu Super Admin">
                <a
                    class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                    href="{{ route('admin.dashboard') }}"
                >
                    <i class="bi bi-speedometer2" aria-hidden="true"></i>
                    Dashboard
                </a>

                <div class="admin-menu-label">WEBSITE & AKUN</div>

                <details
                    class="admin-menu-group"
                    @if(request()->routeIs('admin.content.*')) open @endif
                >
                    <summary>
                        <span>
                            <i class="bi bi-globe2" aria-hidden="true"></i>
                            Kelola Publik
                        </span>

                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </summary>

                    <div class="admin-submenu">
                        @foreach(config('superadmin.sections', []) as $key => $schema)
                            <a
                                class="{{ request()->route('section') === $key ? 'active' : '' }}"
                                href="{{ route('admin.content.index', $key) }}"
                            >
                                <i
                                    class="bi {{ $schema['icon'] }}"
                                    aria-hidden="true"
                                ></i>

                                {{ $schema['label'] }}
                            </a>
                        @endforeach
                    </div>
                </details>

                <a
                    class="admin-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                    href="{{ route('admin.users.index') }}"
                >
                    <i class="bi bi-people" aria-hidden="true"></i>
                    Kelola Akun & Role
                </a>

                <a class="admin-nav-link {{ request()->routeIs('admin.governance.*') ? 'active' : '' }}" href="{{ route('admin.governance.index') }}"><i class="bi bi-calendar2-range" aria-hidden="true"></i>Periode Kepengurusan</a>
<a class="admin-nav-link {{ request()->routeIs('admin.governance-control.*') ? 'active' : '' }}" href="{{ route('admin.governance-control.index') }}"><i class="bi bi-sliders"></i>Kontrol Kepengurusan</a>
<div class="admin-menu-label">KEAMANAN SISTEM</div>
                <a class="admin-nav-link {{ request()->routeIs('admin.security.index', 'admin.security.restore.*') ? 'active' : '' }}" href="{{ route('admin.security.index') }}"><i class="bi bi-shield-lock" aria-hidden="true"></i>Pusat Kendali Darurat</a>
                <a class="admin-nav-link {{ request()->routeIs('admin.security.logs') ? 'active' : '' }}" href="{{ route('admin.security.logs') }}"><i class="bi bi-clock-history" aria-hidden="true"></i>Log Aktivitas</a>
                @include('components.governance-archive-link')
            </nav>

            <div class="admin-sidebar-bottom"><a href="{{ route('kbh.forum') }}">Forum KBH</a>
                <a href="{{ route('home') }}">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                    Lihat Website Publik
                </a>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf

                    <button type="submit">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-3">
                <button
                    class="btn btn-outline-navy d-lg-none"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#adminSidebar"
                    aria-controls="adminSidebar"
                    aria-label="Buka menu"
                >
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>

                <div>
                    <h1 class="h5 mb-0">@yield('title', 'Dashboard')</h1>
                </div>
            </div>

            <div class="workspace-header-actions">
                <x-notification-bell />
            <div class="admin-user">
                <span class="admin-avatar">
                    <i class="bi bi-person-fill" aria-hidden="true"></i>
                </span>

                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>Super Admin</small>
                </div>
            </div>
            </div>
        </header>

        <main class="admin-content">
            @if($emergencyState['maintenance'] || $emergencyState['frozen'])
                <div class="security-status-banner" role="status"><i class="bi bi-shield-exclamation" aria-hidden="true"></i><strong>Mode darurat aktif</strong><span>{{ $emergencyState['maintenance'] ? 'Publik ditutup.' : '' }} {{ $emergencyState['frozen'] ? 'Login role lain dibekukan.' : '' }}</span><a href="{{ route('admin.security.index') }}">Kelola akses</a></div>
            @endif
            @if(session('success'))
                <div class="alert alert-success" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

</div>
</body>
</html>
