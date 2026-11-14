@extends('department.layout')
@section('title', 'Forum Diskusi')
@section('content')
<div class="k-heading">
<div>
<div class="k-eyebrow">RUANG KOLABORASI</div>
<h1>Forum Diskusi</h1>
<p>Koordinasikan program kerja dan berbagi informasi bersama tim.</p>
</div>@if($isCoordinator && $departmentKey !== 'umum')<button class="btn o-btn-yellow" type="button" data-bs-toggle="modal" data-bs-target="#newGroup">
<i class="bi bi-plus-lg" aria-hidden="true">
</i> Buat Grup</button>@endif</div>
@include('components.forum-workspace', ['forumIsKbh'=>false])
@if($isCoordinator && $departmentKey !== 'umum')<div class="modal fade" id="newGroup" tabindex="-1" aria-labelledby="newGroupTitle" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
<div class="modal-content">
<div class="modal-header">
<h2 class="modal-title fs-5" id="newGroupTitle">Buat Grup Departemen</h2>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
</button>
</div>
<form method="POST" action="{{ route('department.forum.create',$departmentKey) }}">@csrf<div class="modal-body">
<label class="form-label" for="group-name">Nama grup</label>
<input class="form-control mb-3" id="group-name" name="name" maxlength="120" required>
<label class="form-label" for="group-description">Deskripsi</label>
<textarea class="form-control mb-3" id="group-description" name="description" maxlength="1000" rows="2">
</textarea>
<fieldset>
<legend class="form-label">Anggota aktif departemen</legend>
<div class="o-group-members">@foreach($people as $person)<label class="d-flex gap-2 mb-2">
<input class="form-check-input" type="checkbox" name="members[]" value="{{ $person->id }}">{{ $person->name }}</label>@endforeach</div>
</fieldset>
<small>Pembuat grup otomatis menjadi anggota. Grup ini hanya dapat dibaca oleh anggota terpilih dan Super Admin.</small>
</div>
<div class="modal-footer">
<button class="btn btn-navy" type="submit">Buat Grup</button>
</div>
</form>
</div>
</div>
</div>@endif
@endsection
