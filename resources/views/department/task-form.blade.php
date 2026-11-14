@extends('department.layout')
@section('title', 'Assign Tugas Baru')
@section('content')
<div class="k-heading">
<div>
<div class="k-eyebrow">DISTRIBUSI TUGAS</div>
<h1>Assign Tugas Baru</h1>
<p>Setiap anggota yang dipilih memiliki hasil dan status tugas masing-masing.</p>
</div>
<a class="btn btn-outline-navy" href="{{ route('department.tasks',$departmentKey) }}">Kembali</a>
</div>
<form method="POST" action="{{ route('department.task.store',$departmentKey) }}" data-task-assign>@csrf
<section class="k-panel">
<h2 class="mb-3">1. Pilih Program Kerja</h2>
<label class="form-label" for="program_id">Program kerja terkait</label>
<select class="form-select" id="program_id" name="program_id" data-task-program required>
<option value="">Pilih proker yang disetujui / berjalan</option>@foreach($programs as $program)<option value="{{ $program->id }}" data-members="{{ json_encode($program->members->pluck('id')->all()) }}" @selected((string)old('program_id')===(string)$program->id)>{{ $program->title }}</option>@endforeach</select>@if($programs->isEmpty())<p class="mt-3 text-secondary">Belum ada proker yang disetujui. Selesaikan pengajuan kegiatan terlebih dahulu.</p>@endif</section>
<section class="k-panel">
<div class="k-panel-head">
<h2>2. Pilih Anggota</h2>
<label class="small d-flex gap-2 align-items-center">
<input class="form-check-input m-0" type="checkbox" data-select-members> Pilih semua yang tersedia</label>
</div>
<div class="o-people-grid">@forelse($people as $person)<label class="o-person">
<input class="form-check-input" type="checkbox" name="members[]" value="{{ $person->id }}" data-task-member @checked(in_array($person->id,old('members',[])))>
<span class="k-avatar">{{ mb_substr($person->name,0,1) }}</span>
<span>{{ $person->name }}<small>{{ $counts[$person->id]??0 }} tugas aktif</small>
</span>
</label>@empty<p>Belum ada akun anggota aktif di departemen ini. Hubungi Super Admin.</p>@endforelse</div>
<small>Anggota harus terdaftar sebagai anggota proker terpilih.</small>
</section>
<section class="k-panel">
<h2 class="mb-3">3. Detail Tugas</h2>
<div class="mb-3">
<label class="form-label" for="title">Nama tugas</label>
<input class="form-control" id="title" name="title" value="{{ old('title') }}" maxlength="160" required>
</div>
<div class="mb-3">
<label class="form-label" for="description">Deskripsi pekerjaan</label>
<textarea class="form-control" id="description" name="description" rows="5" maxlength="5000" required>{{ old('description') }}</textarea>
</div>
<label class="form-label" for="deadline">Deadline</label>
<input class="form-control" type="date" id="deadline" name="deadline" value="{{ old('deadline') }}" required>
<div class="o-actions">
<button class="btn o-btn-yellow" type="submit">Bagikan Tugas <i class="bi bi-send" aria-hidden="true">
</i>
</button>
</div>
</section>
</form>
@endsection
