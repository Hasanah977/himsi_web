@extends('portal.layout')
@section('portal-title', 'Login')
@section('portal-body-class', 'hp-login-page')
@section('portal-width', 'hp-login-width')
@section('portal-content')
    @php
        $portalLogo = \App\Models\PublicContent::imageUrl(\App\Models\PublicContent::settings()['logo'] ?? 'logo-himsi.png');
    @endphp
    <header class="hp-heading">
        <img src="{{ $portalLogo }}" alt="Logo HIMSI" width="108" height="108">
        <h1>Portal Keanggotaan <em>HIMSI</em></h1>
        <p>Himpunan Mahasiswa Sistem Informasi Universitas Sulawesi Barat</p>
    </header>
    <section class="hp-card hp-login-card">
        <h2>Selamat Datang di HIMSI</h2>
        <p class="hp-muted hp-center">Masuk untuk mengakses ruang anggota dan pengelolaan organisasi.</p>
        <form action="{{ route('login.submit') }}" method="POST">
            @csrf
            <label for="hp-email">Email</label>
            <div class="hp-input-icon"><i class="bi bi-envelope"></i><input id="hp-email" type="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email" autocomplete="username" required></div>
            <label for="hp-password">Password</label>
            <div class="hp-password hp-input-icon"><i class="bi bi-lock"></i><input id="hp-password" type="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
                <button type="button" data-hp-password="hp-password" aria-label="Tampilkan password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
            <div class="hp-login-options"><label class="hp-check"><input type="checkbox" name="remember" value="1"> Ingat saya</label><a href="{{ route('password.request') }}">Lupa Password?</a></div>
            <button class="hp-button hp-full" type="submit">Login <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </form>
        <p class="hp-login-bottom">Belum punya akun? <a href="{{ route('registration.create') }}">Daftar Sekarang</a></p>
        <p class="hp-muted hp-center">Pendaftar baru dapat login untuk memeriksa status berkas.</p>
    </section>
@endsection
