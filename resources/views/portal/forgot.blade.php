@extends('portal.layout')
@section('portal-title', 'Lupa Kata Sandi')
@section('portal-body-class', 'hp-login-page hp-forgot-page')
@section('portal-width', 'hp-login-width')
@section('portal-content')
@php($portalLogo = \App\Models\PublicContent::imageUrl(\App\Models\PublicContent::settings()['logo'] ?? 'logo-himsi.png'))
<header class="hp-heading"><img src="{{ $portalLogo }}" alt="Logo HIMSI" width="108" height="108"><h1>Portal Keanggotaan HIMSI</h1><p>Himpunan Mahasiswa Sistem Informasi Universitas Sulawesi Barat</p></header>
<section class="hp-card hp-login-card hp-forgot-card">
    <span class="hp-lock-icon"><i class="bi bi-lock"></i></span><h2>Lupa Kata Sandi?</h2><p class="hp-muted hp-center">Masukkan email yang terdaftar untuk menerima tautan pengaturan ulang kata sandi.</p>
    <form method="POST" action="{{ route('password.email') }}">@csrf
        <label for="hp-email">Email</label><div class="hp-input-icon"><i class="bi bi-envelope"></i><input id="hp-email" type="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email Anda" autocomplete="email" required></div>
        <div class="hp-notice"><i class="bi bi-info-circle"></i><span>Tautan reset hanya akan dikirim melalui email yang terdaftar di sistem HIMSI.</span></div>
        <button class="hp-button hp-full" type="submit">Kirim Tautan Reset <i class="bi bi-arrow-right"></i></button>
    </form>
    <div class="hp-divider">atau</div><nav class="hp-return-links"><a href="{{ route('login') }}"><i class="bi bi-person-lock"></i> Kembali ke Login</a><a href="{{ route('home') }}"><i class="bi bi-house"></i> Kembali ke Beranda</a></nav>
</section>
@endsection
