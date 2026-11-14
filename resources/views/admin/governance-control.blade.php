@extends('admin.layout')
@section('title', 'Kontrol Kepengurusan')
@section('content')
<div class="admin-toolbar"><div><h2>Kontrol Kepengurusan</h2><p>Atur akses rekrutmen dan pemilihan untuk seluruh anggota.</p></div></div>
<section class="admin-panel governance-control">
    <span class="workspace-icon"><i class="bi bi-sliders"></i></span>
    <h3>Rekrutmen & pemilihan pengurus</h3>
    <span class="badge {{ $enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $enabled ? 'Aktif' : 'Nonaktif' }}</span>
    <p class="mt-3">Satu pengaturan untuk menu berikut:</p>
    <ul><li><strong>Open Rekrutmen</strong> — Ketua Umum, Sekertaris Umum, dan Bendahara Umum.</li><li><strong>Voting dan Seleksi Pengurus</strong> — KBH.</li></ul>
    <p class="text-secondary">Saat nonaktif, menu disembunyikan dan halaman terkait tidak dapat diakses. Seluruh data, hasil seleksi, dan suara tetap tersimpan.</p>
    <form method="POST" action="{{ route('admin.governance-control.update') }}">@csrf @method('PUT')
        <input type="hidden" name="enabled" value="{{ $enabled ? '0' : '1' }}">
        <button class="btn {{ $enabled ? 'btn-outline-danger' : 'btn-navy' }}" type="submit"><i class="bi bi-power me-2"></i>{{ $enabled ? 'Nonaktifkan akses kepengurusan' : 'Aktifkan akses kepengurusan' }}</button>
    </form>
</section>
@endsection
