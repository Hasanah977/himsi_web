@php
    $role = request()->attributes->get('administration_preview_role', auth()->user()->role);
    $menu = config('administration.menus.'.$role, []);
    $recruitmentEnabled = \App\Support\RecruitmentControl::enabled();
    $menu = array_filter($menu, fn ($entry) => $recruitmentEnabled || !in_array($entry[1], ['recruitment.index', 'kbh.voting.hasil', 'kbh.seleksi.index'], true));
    $menu = isset($menu[0]) ? [0 => $menu[0]] + $menu : $menu;
    $settings = \App\Models\PublicContent::settings();
    $label = config('superadmin.roles.'.$role, 'Administrasi HIMSI');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('components.site-icon')
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', $label) — HIMSI</title>
@vite('resources/js/app.js')
<link rel="stylesheet" href="{{ asset('css/administration/kbh.css') }}">
<link rel="stylesheet" href="{{ asset('css/administration/legacy.css') }}">
<link rel="stylesheet" href="{{ asset('css/himsi-kbh-iuran.css') }}">
@stack('styles')
<link rel="stylesheet" href="{{ asset('css/administration/theme.css') }}">
@if($role === 'kbh' || request()->routeIs('kbh.forum*'))
<link rel="stylesheet" href="{{ asset('css/himsi-department.css') }}">
<link rel="stylesheet" href="{{ asset('css/administration/member.css') }}?v=2">
<script src="{{ asset('js/himsi-member.js') }}" defer></script>
@endif
@if($role==='bendahara_umum')<link rel="stylesheet" href="{{ asset('css/himsi-finance.css') }}">@endif
    <link rel="stylesheet" href="{{ asset('css/himsi-sidebar.css') }}?v=3">
    <script src="{{ asset('js/himsi-sidebar.js') }}?v=3" defer></script>
    <link rel="stylesheet" href="{{ asset('css/himsi-workspace.css') }}?v=20261110-2">
<link rel="stylesheet" href="{{ asset('css/himsi-revision.css') }}?v=20261109">
<link rel="stylesheet" href="{{ asset('css/himsi-next.css') }}?v=20261110"><script src="{{ asset('js/himsi-next.js') }}?v=20261110" defer></script>
    <link rel="stylesheet" href="{{ asset('css/himsi-sidebar-theme.css') }}?v=20261111-compact-menus">
    @include('components.workspace-background')
</head>
<body class="admin-page administration-page @yield('workspace-class') {{ in_array($role, ['koordinator_departemen','sekretaris_umum']) ? 'compact-administration' : '' }} {{ $role === 'bendahara_umum' ? 'finance-role' : '' }} {{ $role === 'kbh' || request()->routeIs('kbh.forum*') ? 'member-page' : '' }}">
<div class="admin-shell">
<aside class="offcanvas-lg offcanvas-start admin-sidebar" id="administrationSidebar" tabindex="-1" aria-labelledby="administrationMenuTitle">
<div class="offcanvas-header"><h2 class="fs-5" id="administrationMenuTitle">Menu HIMSI</h2><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#administrationSidebar" aria-label="Tutup menu"></button></div>
<div class="offcanvas-body">
<a class="admin-brand" href="{{ route($menu[0][1] ?? 'dashboard') }}"><img src="{{ \App\Models\PublicContent::imageUrl($settings['logo'] ?? 'logo-himsi.png') }}" width="49" height="49" alt="Logo HIMSI"><span>HIMSI<small>UNSULBAR — FT</small></span></a>
<div class="admin-menu-label">{{ $label }}</div>
<x-sidebar-account />
<nav class="admin-navigation" aria-label="Menu {{ $label }}">

@foreach($menu as $menuItem)
@php([$text,$route,$icon]=$menuItem)
@php($active = (request()->routeIs($route, str_ends_with($route, '.index') ? substr($route, 0, -6).'.*' : $route.'.*') || ($route === 'kbh.voting.hasil' && request()->routeIs('kbh.voting.*')) || ($route === 'department.reviews' && request()->routeIs('department.review')) || ($route === 'kbh.seleksi.index' && request()->routeIs('recruitment.apply', 'recruitment.result', 'kpum.registration.*')) || ($route === 'recruitment.index' && request()->routeIs('recruitment.*')) || ($route === 'finance.billing' && request()->routeIs('bendahara.iuran.*'))) && (!isset($menuItem[3]) || (is_array($menuItem[3]) ? (request('type') === ($menuItem[3]['type'] ?? null) || (isset($submission) && $submission->type === ($menuItem[3]['type'] ?? null))) : request()->route('kind') === $menuItem[3])))
@php($financeActivityKey = $route.(isset($menuItem[3]) && is_string($menuItem[3]) ? ':'.$menuItem[3] : ''))
<a class="admin-nav-link {{ $active ? 'active' : '' }}" href="{{ route($route, $menuItem[3] ?? []) }}" @if($active) aria-current="page" @endif><i class="bi bi-{{ $icon }}" aria-hidden="true"></i>{{ $text }}@if($role === 'kbh' && !request()->routeIs($route) && ($activityRoutes[$route] ?? false))<span class="rounded-circle bg-warning ms-auto" style="width:8px;height:8px" role="img" aria-label="Aktivitas baru"></span>@endif @if($role === 'bendahara_umum' && ($financeActivity[$financeActivityKey] ?? false))<span class="finance-activity-dot rounded-circle bg-warning ms-auto" style="width:8px;height:8px" role="img" aria-label="Aktivitas baru"></span>@endif</a>
@endforeach
@if(in_array(auth()->user()->role, \App\Models\User::OFFICER_ROLES, true))<a class="admin-nav-link {{ request()->routeIs('meetings.*') ? 'active' : '' }}" href="{{ route('meetings.index') }}"><i class="bi bi-calendar-check" aria-hidden="true"></i>Rapat Himpunan</a>@endif
@include('components.governance-archive-link')
@if(auth()->user()->isSuperAdmin())<a class="admin-nav-link" href="{{ route('kbh.forum') }}">Forum KBH</a>@endif
@if($role === 'koordinator_departemen' && auth()->user()->department === 'humas')<a class="admin-nav-link" href="{{ route('administration.aspirations') }}">Aspirasi KBH</a>@endif
</nav>
<div class="admin-sidebar-bottom">
@if(auth()->user()->isSuperAdmin())<a href="{{ route('admin.dashboard') }}">Kembali ke Super Admin</a>@endif
<a href="{{ route('home') }}">Lihat Website Publik</a>
<form action="{{ route('logout') }}" method="POST">@csrf<button type="submit"><i class="bi bi-box-arrow-right"></i>Keluar</button></form>
</div>
</div>
</aside>
<div class="admin-main">
<header class="admin-topbar"><div class="d-flex align-items-center gap-3"><button class="btn btn-outline-navy d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#administrationSidebar" aria-controls="administrationSidebar" aria-label="Buka menu"><i class="bi bi-list"></i></button><div><small>{{ $label }}</small><h1>@yield('title', 'Dashboard')</h1></div></div><div class="d-flex align-items-center gap-4"><a class="position-relative fs-4" href="{{ route($role === 'kbh' ? 'kbh.notifikasi.index' : 'administration.notifications') }}" aria-label="Notifikasi, {{ $unreadCount }} belum dibaca"><i class="bi bi-bell"></i>@if($unreadCount)<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:10px">{{ $unreadCount }}</span>@endif</a><a class="admin-user text-decoration-none" href="{{ $role === 'kbh' ? route('kbh.profil') : '#' }}"><span class="admin-avatar overflow-hidden">@if($role==='kbh' && auth()->user()->foto_profile)<img src="{{ route('kbh.profil.photo') }}" alt="Foto profil" style="width:100%;height:100%;object-fit:cover">@else<i class="bi bi-person"></i>@endif</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ $label }}</small></div></a></div></header>
<main class="admin-content">
@if(auth()->user()->isSuperAdmin() && $role !== 'super_admin')<div class="alert alert-info">Pratinjau Super Admin. Persetujuan dan tanda tangan dilakukan oleh akun pejabat terkait.</div>@endif
@foreach(['success','error','info'] as $flash)@if(session($flash))<div class="alert alert-{{ $flash === 'error' ? 'danger' : ($flash === 'success' ? 'success' : 'info') }}" role="status">{{ session($flash) }}</div>@endif @endforeach
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main>
</div></div>
<script src="{{ asset('js/administration/kbh.js') }}" defer></script>
@stack('scripts')
</body></html>
