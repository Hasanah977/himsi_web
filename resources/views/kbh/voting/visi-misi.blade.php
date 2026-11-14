@extends('layouts.kbh')
@section('title', 'Visi & Misi | KBH')
@section('page-title', 'Visi & Misi Kandidat')
@section('page-subtitle', 'Profil calon Ketua Umum HIMSI')
@section('content')
<a href="{{ route('kbh.voting.index') }}" class="back-link"><i class="bi bi-arrow-left"></i> Kembali</a>
<div class="content-card">
    <span class="section-label">{{ $periode->nama }}</span>
    <h2>{{ $kandidat->nama }}</h2><p>NIM: {{ $kandidat->nim }}</p>
    <div class="vision-section" style="margin-top:20px">
       <h3>Visi</h3><p style="white-space:pre-line">{{ $kandidat->visi ?: 'Belum diisi oleh panitia.' }}</p>
       <h3>Misi</h3><p style="white-space:pre-line">{{ $kandidat->misi ?: 'Belum diisi oleh panitia.' }}</p>
    </div>
</div>
@endsection
