@php
    $portalSettings = \App\Models\PublicContent::settings();
    $portalLogo = \App\Models\PublicContent::imageUrl($portalSettings['logo'] ?? 'logo-himsi.png');
    $portalBackgroundImage = $portalSettings['auth_background'] ?? null;
    $portalBackground = \App\Models\PublicContent::imageUrl($portalBackgroundImage && $portalBackgroundImage !== 'placeholder.svg' ? $portalBackgroundImage : (\App\Models\PublicContent::heroSlides()[0]['image'] ?? null));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('components.site-icon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('portal-title', 'Ruang Anggota') — HIMSI</title>
    @vite('resources/js/app.js')
    <link rel="stylesheet" href="{{ asset('css/himsi-portal.css') }}">
    <script src="{{ asset('js/himsi-portal.js') }}?v=20261110" defer></script>
<link rel="stylesheet" href="{{ asset('css/himsi-revision.css') }}?v=20261110">
<link rel="stylesheet" href="{{ asset('css/himsi-next.css') }}?v=20261111-portal-compact">
</head>
<body class="hp-page @yield('portal-body-class')" style="--portal-photo:url('{{ $portalBackground }}')">
    <main class="hp-container @yield('portal-width')">
        <a class="hp-back" href="{{ route('home') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke Beranda</a>
        @if($errors->any())
            <div class="hp-alert" role="alert">
                <strong>Periksa kembali data berikut:</strong>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                @if(request()->routeIs('registration.*'))<p>Isi ulang password dan pilih kembali berkas sebelum mengirim.</p>@endif
            </div>
        @endif
        @if(session('status'))<div class="hp-alert" role="status">{{ session('status') }}</div>@endif
        @yield('portal-content')
    </main>
</body>
</html>
