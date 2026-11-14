@extends('layouts.kbh')
@section('title', 'Edit Profil')
@section('content')
<section class="admin-panel" style="max-width:850px"><h2 class="h5 mb-4">Informasi Profil</h2>
<form method="POST" enctype="multipart/form-data" action="{{ route('kbh.profil.update') }}">@csrf @method('PUT')
<div class="row g-4">
<div class="col-md-6"><label class="form-label" for="profile-name">Nama Lengkap</label><input id="profile-name" class="form-control" value="{{ auth()->user()->name }}" disabled></div>
<div class="col-md-6"><label class="form-label" for="profile-nim">NIM</label><input id="profile-nim" class="form-control" value="{{ auth()->user()->nim }}" disabled></div>
<div class="col-md-6"><label class="form-label" for="profile-email">Email</label><input id="profile-email" class="form-control" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required></div>
<div class="col-md-6"><label class="form-label" for="profile-phone">Nomor HP</label><input id="profile-phone" class="form-control" name="no_hp" value="{{ old('no_hp', auth()->user()->no_hp) }}" required></div>
<div class="col-12"><label class="form-label" for="profile-photo">Foto Profil</label><input id="profile-photo" class="form-control" type="file" name="foto_profile" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG, WEBP · maksimal 5 MB. Nama dan NIM tidak dapat diubah.</small></div>
</div><button class="btn btn-navy mt-4">Simpan Perubahan</button><a class="btn btn-outline-navy mt-4 ms-2" href="{{ route('kbh.profil') }}">Batal</a></form></section>
@endsection
