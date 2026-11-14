@extends('layouts.kbh')
@section('title', 'Seleksi Pengurus | KBH')
@section('page-title', 'Seleksi Pengurus')
@section('page-subtitle', 'Pendaftaran calon pengurus HIMSI')
@section('content')
<x-chair-registration />
<x-selection-banner :period="$periode" />
<div id="selection-history">@if($pendaftaran)<section class="admin-panel"><h2 class="h5 fw-bold">Riwayat Seleksi</h2><x-selection-timeline :application="$pendaftaran" /></section>@endif</div>
<div class="content-card">
  <span class="section-label">OPEN RECRUITMENT</span><h2>Seleksi Pengurus HIMSI</h2>
  <p class="content-description">{{ $periode?->nama ?? 'Periode seleksi belum tersedia.' }}</p>
  @if($periode)<p>Jadwal: {{ $periode->mulai_pada->timezone('Asia/Makassar')->format('d/m/Y H:i') }} – {{ $periode->selesai_pada->timezone('Asia/Makassar')->format('d/m/Y H:i') }}</p>@endif
  <div class="requirement-box"><h3>Persyaratan</h3><ul>
    <li>Mahasiswa aktif Sistem Informasi.</li><li>Memiliki komitmen terhadap organisasi.</li>
    <li>Unggah KHS PDF (maks. 5 MB).</li><li>Sertifikat tambahan jika tersedia.</li>
  </ul></div>
  @if($pendaftaran)
    <div class="alert alert-success">Kamu telah mendaftar pada periode ini. Status: <strong>{{ strtoupper(str_replace('_',' ', $pendaftaran->status)) }}</strong>.</div>
    <a href="{{ route('kbh.seleksi.hasil') }}" class="btn-primary">Lihat Status Pendaftaran</a>
  @elseif($periode && $periode->sedangDibuka())
    <a href="{{ route('kbh.seleksi.upload') }}" class="btn-primary">Daftar Sekarang</a>
  @else
    <div class="alert alert-warning">Pendaftaran saat ini belum dibuka atau sudah ditutup.</div>
  @endif
</div>
@endsection
