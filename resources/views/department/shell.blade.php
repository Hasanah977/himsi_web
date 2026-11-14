@php($navigationDepartment = in_array(auth()->user()->role, ['koordinator_departemen', 'anggota_departemen'], true) ? auth()->user()->department : $departmentKey)
<!DOCTYPE html>
<html lang="id">
<head>
    @include('components.site-icon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Departemen') — HIMSI</title>
    @vite('resources/js/app.js')
    <link rel="stylesheet" href="{{ asset('css/himsi-kpum.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/himsi-department.css') }}?v=1">
    <script src="{{ asset('js/himsi-department.js') }}?v=1" defer>
</script>
<link rel="stylesheet" href="{{ asset('css/forum-messages.css') }}">
<script src="{{ asset('js/himsi-member.js') }}" defer></script>
    <link rel="stylesheet" href="{{ asset('css/himsi-sidebar.css') }}?v=3">
    <script src="{{ asset('js/himsi-sidebar.js') }}?v=3" defer></script>
    <link rel="stylesheet" href="{{ asset('css/himsi-workspace.css') }}?v=20261110-2">
@stack('styles')
<link rel="stylesheet" href="{{ asset('css/himsi-revision.css') }}?v=20261109">
<link rel="stylesheet" href="{{ asset('css/himsi-next.css') }}?v=20261110"><script src="{{ asset('js/himsi-next.js') }}?v=20261110" defer></script>
    <link rel="stylesheet" href="{{ asset('css/himsi-sidebar-theme.css') }}?v=20261111-compact-menus">
    @include('components.workspace-background')
</head>
<body class="kpum-page department-page compact-department">
<a class="o-skip" href="#main-content">Lewati ke konten</a>
<div class="k-shell">
    <aside class="offcanvas-lg offcanvas-start k-sidebar" id="departmentSidebar" tabindex="-1" aria-labelledby="departmentSidebarTitle">
        <div class="offcanvas-header">
<h2 id="departmentSidebarTitle">Menu HIMSI</h2>
<button class="btn-close btn-close-white" type="button" data-bs-dismiss="offcanvas" data-bs-target="#departmentSidebar" aria-label="Tutup menu">
</button>
</div>
        <div class="offcanvas-body">
            <a class="k-brand" href="{{ $navigationDepartment === 'umum' ? route('department.forum', 'umum') : ($navigationDepartment ? route($isCoordinator ? 'department.dashboard' : 'department.member.dashboard', $navigationDepartment) : route('department.reviews')) }}">
                <img src="{{ $departmentLogo }}" alt="Logo HIMSI" width="49" height="49">
<span>HIMSI<small>UNSULBAR — FT</small>
</span>
            </a>
            <div class="o-department">
<span>{{ $isMemberPortal ? 'PORTAL ANGGOTA' : 'OPERATIONS & COMMUNICATION' }}</span>
<strong>{{ config('superadmin.departments.'.$navigationDepartment, $departmentLabel) }}</strong>
</div>
            <x-sidebar-account />
<nav aria-label="Navigasi departemen">
@if(in_array(auth()->user()->role,['sekretaris_umum','ketua_umum','koordinator_pengawas','bendahara_umum']))<a class="k-nav" href="{{ route('dashboard') }}"><i class="bi bi-house"></i>Dashboard Role</a>@endif
            @if($navigationDepartment)
                @if($isCoordinator && $navigationDepartment !== 'umum')
                    @foreach([
                        ['department.dashboard', 'bi-grid', 'Dashboard', [$navigationDepartment]],
                        ['department.programs', 'bi-calendar-event', 'Program Kerja', [$navigationDepartment]],
                        ['department.submissions', 'bi-envelope-paper', 'Persuratan', [$navigationDepartment, 'letter']],
                        ['department.submissions', 'bi-wallet2', 'RAB & Anggaran', [$navigationDepartment, 'budget']],
                        ['department.submissions', 'bi-file-earmark-check', 'PPRK', [$navigationDepartment, 'rpk']],
                        ['department.tasks', 'bi-person-plus', 'Distribusi Tugas', [$navigationDepartment]],
                        ['department.monitoring', 'bi-clipboard-check', 'Monitoring Anggota', [$navigationDepartment]],
                        ['department.submissions', 'bi-file-earmark-text', 'LPJ', [$navigationDepartment, 'report']],
                    ] as [$route, $icon, $label, $params])
                        @php($active = $route === 'department.submissions' ? request()->routeIs('department.submissions', 'department.submission.*') && request()->route('type') === $params[1] : (request()->routeIs($route) || ($route === 'department.programs' && request()->routeIs('department.program.*')) || ($route === 'department.tasks' && request()->routeIs('department.task.create'))))
                        <a class="k-nav {{ $active ? 'active' : '' }}" href="{{ route($route, $params) }}" @if($active) aria-current="page" @endif>
<i class="bi {{ $icon }}" aria-hidden="true">
</i>{{ $label }}</a>
                    @endforeach
                @elseif($navigationDepartment !== 'umum')
                    <a class="k-nav {{ request()->routeIs('department.member.dashboard') ? 'active' : '' }}" href="{{ route('department.member.dashboard', $navigationDepartment) }}"><i class="bi bi-grid" aria-hidden="true"></i>Dashboard</a>
                    <a class="k-nav {{ request()->routeIs('department.my-tasks') ? 'active' : '' }}" href="{{ route('department.my-tasks', $navigationDepartment) }}">
<i class="bi bi-list-task" aria-hidden="true">
</i>Tugas Saya</a>
                    <a class="k-nav {{ request()->routeIs('department.member.program*') ? 'active' : '' }}" href="{{ route('department.member.programs', $navigationDepartment) }}"><i class="bi bi-calendar-event" aria-hidden="true"></i>Program Kerja</a>
                @endif
                <a class="k-nav {{ request()->routeIs('department.forum') ? 'active' : '' }}" href="{{ route('department.forum', ['department' => $navigationDepartment, 'portal' => $isMemberPortal ? 'member' : null]) }}">
<i class="bi bi-chat-dots" aria-hidden="true">
</i>Forum Diskusi</a>
            @else
                <a class="k-nav" href="{{ route('department.pprk') }}"><i class="bi bi-file-earmark-check"></i>Pengajuan RPK</a>
                <a class="k-nav active" href="{{ route('department.reviews') }}">
<i class="bi bi-inbox" aria-hidden="true">
</i>Tinjauan Pengajuan</a>
                <a class="k-nav" href="{{ route('department.forum', 'umum') }}"><i class="bi bi-chat-dots" aria-hidden="true"></i>Forum Umum HIMSI</a>
            @endif
            @if($navigationDepartment === 'umum' && in_array(auth()->user()->role, ['sekretaris_umum', 'ketua_umum', 'bendahara_umum', 'super_admin'], true))<a class="k-nav" href="{{ route('department.reviews') }}"><i class="bi bi-inbox" aria-hidden="true"></i>Tinjauan Pengajuan</a>@endif
            @if(auth()->user()->role==='koordinator_departemen')<a class="k-nav {{ request()->routeIs('department.pprk*') ? 'active' : '' }}" href="{{ route('department.pprk') }}"><i class="bi bi-file-earmark-check"></i>Pengajuan RPK</a>
@if(auth()->user()->department==='humas')<a class="k-nav" href="{{ route('administration.aspirations') }}"><i class="bi bi-chat-dots"></i>Aspirasi KBH</a>@endif @endif
@if(auth()->user()->role==='bendahara_umum')<a class="k-nav" href="{{ route('bendahara.iuran.index') }}"><i class="bi bi-wallet2"></i>Verifikasi Iuran KBH</a>@endif

@if(auth()->user()->isSuperAdmin())<a class="k-nav" href="{{ route('admin.dashboard') }}"><i class="bi bi-shield-lock"></i>Super Admin</a>@endif

@if(in_array(auth()->user()->role, \App\Models\User::OFFICER_ROLES, true))<a class="k-nav {{ request()->routeIs('meetings.*') ? 'active' : '' }}" href="{{ route('meetings.index') }}"><i class="bi bi-calendar-check" aria-hidden="true"></i>Rapat Himpunan</a>@endif
@include('components.governance-archive-link')
</nav>
            <div class="k-sidebar-foot">
<div class="k-sidebar-note">
<i class="bi bi-people" aria-hidden="true">
</i>
<span>Satu tujuan.<br>Bertumbuh dalam kolaborasi.</span>
</div>
<form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="k-nav">
<i class="bi bi-box-arrow-right" aria-hidden="true">
</i>Keluar</button>
</form>
</div>
        </div>
    </aside>
    <div class="k-main">
        <header class="k-topbar">
<div class="d-flex align-items-center gap-3">
<button class="btn k-icon-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#departmentSidebar" aria-controls="departmentSidebar" aria-label="Buka menu">
<i class="bi bi-list" aria-hidden="true">
</i>
</button>
<span class="k-breadcrumb">HIMSI <span>/</span> <strong>@yield('title')</strong>
</span>
</div>
<div class="k-user">
@if(auth()->check())
<a class="btn k-icon-btn position-relative" href="{{ $isMemberPortal && $navigationDepartment !== 'umum' ? route('department.member.notifications', $navigationDepartment) : route('administration.notifications') }}" aria-label="Notifikasi, {{ $unreadNotifications }} belum dibaca"><i class="bi bi-bell" aria-hidden="true"></i>@if($unreadNotifications)<span class="badge bg-danger rounded-pill">{{ $unreadNotifications }}</span>@endif</a>
@endif
<span class="k-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
<span>{{ auth()->user()->name }}<small>{{ config('superadmin.roles')[auth()->user()->role] ?? 'Pengurus' }}</small>
</span>
</div>
</header>
        <main class="k-content" id="main-content">
            @if($isMemberPortal && auth()->user()->isSuperAdmin())
                <div class="alert alert-info">Pratinjau Super Admin: ringkasan dan tugas seluruh anggota {{ $departmentLabel }}. Pengerjaan tugas dilakukan melalui akun anggota masing-masing. Notifikasi yang tampil tetap milik akun Anda.</div>
            @endif
            @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger" role="alert">
<strong>Periksa kembali:</strong>
<ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>@endif
            @if(auth()->user()->role === 'koordinator_departemen' && !request()->routeIs('department.pprk*') && !$pprkApproved)
            <div class="k-info mb-4"><i class="bi bi-file-earmark-check"></i><span><strong>Lengkapi RPK periode {{ config('department.period') }}</strong><br>Persetujuan RPK diperlukan sebelum membuat program kerja. <a href="{{ route('department.pprk') }}">Buka pengajuan RPK</a></span></div>
            @endif
            @yield('content')
            <footer class="k-footer">
<span>HIMSI UNSULBAR — FT</span>
<span>Operations & Communication</span>
</footer>
        </main>
    </div>
</div>
<div class="modal fade" id="kConfirm" tabindex="-1" aria-labelledby="kConfirmTitle" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
<div class="modal-header">
<h2 class="modal-title fs-5" id="kConfirmTitle">Konfirmasi tindakan</h2>
<button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup">
</button>
</div>
<div class="modal-body">
<p id="kConfirmMessage" class="mb-0">
</p>
</div>
<div class="modal-footer">
<button class="btn btn-outline-navy" type="button" data-bs-dismiss="modal">Batal</button>
<button class="btn btn-navy" id="kConfirmSubmit" type="button">Ya, lanjutkan</button>
</div>
</div>
</div>
</div>
@stack('scripts')
</body>
</html>
