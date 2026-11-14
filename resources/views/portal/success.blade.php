@extends('portal.layout')
@section('portal-title', 'Pendaftaran Terkirim')
@section('portal-content')
<span data-registration-complete hidden></span>
    @include('portal.steps', ['step'=>3])
    <section class="hp-card hp-success">
        <div class="hp-success-icon"><i class="bi bi-envelope-check" aria-hidden="true"></i></div>
        <h1>Pendaftaran Berhasil Dikirim</h1>
        <p class="hp-muted">Data dan berkas kamu telah disimpan untuk proses verifikasi.</p>
        <ul class="hp-results">
            <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Ketiga berkas berhasil dikirim</li>
            <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Validasi awal sistem berhasil</li>
            <li><i class="bi bi-hourglass-split" aria-hidden="true"></i> Menunggu verifikasi pengurus / Panitia CB</li>
        </ul>
        <p class="hp-muted">Silakan login untuk memantau pemeriksaan berkas dan mengirim revisi.</p>
        <a class="hp-button" href="{{ route('home') }}">Kembali ke Beranda</a>
        <a class="hp-success-login" href="{{ route('login') }}">Kembali ke Login</a>
    </section>
@endsection
