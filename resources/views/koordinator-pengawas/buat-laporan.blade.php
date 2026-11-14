@extends('layouts.koordinator-pengawas')
@section('title','Catat Laporan')
@section('page-title','Catat Laporan Pengawasan')
@section('content')
<div class="pg-card" style="max-width:880px"><h2 class="h5 fw-bold">Form Laporan Masuk</h2><p class="pg-muted">Input manual sementara. Hanya masukkan laporan kegiatan yang benar-benar ada. Lampiran disimpan privat.</p>
<form action="{{ route('pengawas.laporan.store') }}" method="post" enctype="multipart/form-data">@csrf
<div class="mb-3"><label class="form-label" for="ops-program">Hubungkan Program Departemen</label><select class="form-select" id="ops-program" name="ops_program_id"><option value="">Laporan umum / manual</option>@foreach($programs as $program)<option value="{{ $program->id }}" @selected((string)old('ops_program_id',request('ops_program_id'))===(string)$program->id)>{{ $program->title }} — {{ config('superadmin.departments.'.$program->department) }}</option>@endforeach</select></div>

<div class="row g-3"><div class="col-md-6"><label class="form-label">Nama Kegiatan *</label><input required maxlength="180" class="form-control" name="nama_kegiatan" value="{{ old('nama_kegiatan') }}"></div>
<div class="col-md-6"><label class="form-label">Departemen</label><select name="departemen" class="form-select"><option value="">Pilih (opsional)</option>@foreach(['Kaderisasi','Akademik','Advokasi','Humas','Kesekretariatan','DANUS','Lainnya'] as $d)<option value="{{ $d }}" @selected(old('departemen')===$d)>{{ $d }}</option>@endforeach</select></div>
<div class="col-12"><label class="form-label">Judul Laporan *</label><input required maxlength="180" class="form-control" name="judul" value="{{ old('judul') }}"></div>
<div class="col-12"><label class="form-label">Isi Laporan *</label><textarea required class="form-control" name="isi" rows="6">{{ old('isi') }}</textarea></div>
<div class="col-12"><label class="form-label">Lampiran (opsional)</label><input type="file" class="form-control" name="lampiran" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"><div class="pg-note mt-1">Word/PDF/gambar, maksimal 10 MB. Word tidak dikonversi.</div></div>
<div class="col-12"><button class="pg-btn" type="submit">Simpan Laporan</button></div></div></form></div>
@endsection
