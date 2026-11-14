@extends('layouts.kbh')
@section('title', 'Hasil Seleksi | KBH')
@section('page-title', 'Hasil Seleksi')
@section('page-subtitle', 'Pantau status pendaftaran pengurus HIMSI')
@section('content')
@if($pendaftaran?->interview_at)<div class="admin-panel"><h2 class="h5">Jadwal Wawancara</h2><p>{{ $pendaftaran->interview_at->copy()->timezone('Asia/Makassar')->format('d M Y H:i') }} · {{ $pendaftaran->interview_location }}</p></div>@endif
<div class="content-card">
  <span class="section-label">STATUS SELEKSI</span><h2>{{ $periode?->nama ?? 'Seleksi Pengurus' }}</h2>
  @if(!$pendaftaran)
    <p>Belum ada pendaftaran kamu pada periode ini.</p>
    <a href="{{ route('kbh.seleksi.index') }}" class="btn-secondary">Kembali</a>
  @else
    <p>Terdaftar: {{ $pendaftaran->created_at->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i') }}</p>
    <div class="info-banner" style="margin-top:16px"><strong>Status: {{ strtoupper(str_replace('_', ' ', $pendaftaran->status)) }}</strong>
      @if($pendaftaran->status === 'menunggu')<p>Berkas diterima. Silakan menunggu pemeriksaan dan pengumuman hasil.</p>@endif
      @if($pendaftaran->status === 'lolos')<p>Selamat, kamu dinyatakan lolos seleksi pengurus.</p>@endif
      @if($pendaftaran->status === 'tidak_lolos')<p>Hasil seleksi telah diumumkan. Terima kasih atas partisipasimu.</p>@endif
    </div>
    @if($pendaftaran->catatan)<p><strong>Catatan:</strong> {{ $pendaftaran->catatan }}</p>@endif
    <p>Kamu tidak dapat mengajukan pendaftaran ulang untuk periode ini.</p>
    <a href="{{ route('kbh.seleksi.index') }}" class="btn-secondary">Kembali ke Seleksi</a>
  @endif
</div>
@endsection
