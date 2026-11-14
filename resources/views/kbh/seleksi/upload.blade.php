@extends('layouts.kbh')
@section('title', 'Upload Berkas | KBH')
@section('page-title', 'Upload Berkas')
@section('page-subtitle', 'Pendaftaran calon pengurus HIMSI')
@section('content')
<div class="form-card">
  <span class="section-label">{{ $periode->nama }}</span><h2>Unggah Berkas Seleksi</h2>
  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
  <form action="{{ route('kbh.seleksi.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="form-group"><label for="khs">KHS <span class="required">*</span></label>
      <input id="khs" type="file" name="khs" class="form-control" accept=".pdf" required><small>PDF, maksimal 5 MB.</small></div>
    <div class="form-group"><label for="sertifikat">Sertifikat Pendukung (opsional)</label>
      <input id="sertifikat" type="file" name="sertifikat" class="form-control" accept=".pdf,.jpg,.jpeg,.png"><small>PDF/JPG/PNG, maksimal 5 MB.</small></div>
    <div class="button-group"><button type="submit" class="btn-primary">Kirim Pendaftaran</button>
      <a href="{{ route('kbh.seleksi.index') }}" class="btn-secondary">Batal</a></div>
  </form>
</div>
@endsection
