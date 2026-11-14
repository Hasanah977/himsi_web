@extends('layouts.kbh')
@section('title', 'Dashboard KBH')
@section('content')
<div class="member-dashboard revision-dashboard">
<section class="member-hero">
    <div><span class="member-hero-kicker">RUANG ANGGOTA HIMSI</span><h2>Halo, {{ auth()->user()->name }}!</h2><p>KBH HIMSI Aktif <span>·</span> Angkatan {{ auth()->user()->angkatan_kelas ?? '—' }} <span>·</span> Bergabung sejak {{ auth()->user()->created_at->translatedFormat('d M Y') }}</p><span class="member-hero-note">Langkah kecilmu, cerita besar kita.</span></div>
    <x-member-art kind="community" class="member-hero-art" />
</section>
<div class="member-metrics">
    <article class="member-feature"><div class="member-feature-copy"><span class="workspace-eyebrow">KEANGGOTAAN</span><h2>Iuran Bulan Ini</h2><p>{{ now()->translatedFormat('F Y') }}</p><span class="workspace-badge {{ $currentPayment?->status === 'lunas' ? 'success' : ($currentPayment?->status === 'menunggu_verifikasi' ? 'warning' : 'danger') }}">{{ ['lunas' => 'Lunas', 'menunggu_verifikasi' => 'Menunggu Verifikasi', 'ditolak' => 'Bukti Ditolak'][$currentPayment?->status ?? ''] ?? 'Belum Dibayar' }}</span><p class="member-feature-detail">Nominal <strong>Rp {{ number_format($currentPayment?->nominal ?? config('himsi_iuran.nominal_per_bulan'), 0, ',', '.') }}</strong></p><a href="{{ route('kbh.iuran.index') }}">Lihat informasi iuran <i class="bi bi-arrow-right"></i></a></div><x-member-art kind="calendar" /></article>
    <article class="member-feature metric-paid"><div class="member-feature-copy"><span class="workspace-eyebrow">KEUANGAN</span><h2>Iuran Terbayar {{ now()->year }}</h2><strong class="metric-total">Rp {{ number_format($paid, 0, ',', '.') }}</strong><p>Total pembayaran tahun {{ now()->year }}.</p></div><x-member-art kind="wallet" /></article>
    <article class="member-feature"><div class="member-feature-copy"><span class="metric-symbol"><i class="bi bi-chat-dots"></i></span><p class="mt-4">Aspirasi Terkirim ke Humas</p><strong class="metric-total">{{ $aspirationCount }} aspirasi</strong><a href="{{ route('kbh.aspirasi.index') }}">Lihat aspirasi <i class="bi bi-arrow-right"></i></a></div><x-member-art kind="aspiration" /></article>
</div>
<section class="admin-panel member-news"><div class="d-flex justify-content-between align-items-center gap-3 mb-4"><div><span class="workspace-eyebrow">KABAR KAMPUS</span><h2 class="h5 mb-0 mt-1">Pengumuman Terbaru</h2></div><a href="{{ route('announcements') }}">Lihat semua <i class="bi bi-arrow-right"></i></a></div>
@forelse($announcements as $item)
    <a class="member-news-item" href="{{ route('announcement.detail', $item['slug']) }}"><span class="workspace-icon"><i class="bi bi-megaphone"></i></span><div><strong>{{ $item['title'] }}</strong><p>{{ $item['date'] ?? '' }} · {{ $item['summary'] ?? '' }}</p></div><i class="bi bi-chevron-right ms-auto"></i></a>
@empty<p class="text-secondary mb-0">Belum ada pengumuman terbaru. Kabar dari HIMSI akan tampil di sini.</p>@endforelse
</section>
</div>
@endsection
