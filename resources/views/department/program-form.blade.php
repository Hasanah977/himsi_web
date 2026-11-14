@extends('department.layout')
@section('title', $program->exists ? 'Edit Proker' : 'Buat Proker Baru')
@section('content')
<div class="k-heading">
<div>
<div class="k-eyebrow">LANGKAH 1 DARI 4</div>
<h1>{{ $program->exists ? 'Edit Informasi Proker' : 'Buat Program Kerja Baru' }}</h1>
<p>Rencanakan kegiatan dan tentukan anggota yang terlibat.</p>
</div>
<a class="btn btn-outline-navy" href="{{ route('department.programs', $departmentKey) }}">Kembali</a>
</div>
@include('department.steps', ['step' => 0])
<form class="k-panel" method="POST" action="{{ $program->exists ? route('department.program.update', [$departmentKey, $program->id]) : route('department.program.store', $departmentKey) }}">
@csrf @if($program->exists) @method('PUT') @endif
<input type="hidden" name="version" value="{{ $program->version ?? 0 }}">
<div class="o-form-grid">
    <div class="o-full">
<label class="form-label" for="title">Nama program kerja</label>
<input class="form-control" id="title" name="title" value="{{ old('title', $program->title) }}" maxlength="160" required>
</div>
    <div class="o-full">
<label class="form-label" for="description">Deskripsi dan tujuan</label>
<textarea class="form-control" id="description" name="description" rows="4" maxlength="8000" required>{{ old('description', $program->description) }}</textarea>
</div>
    @foreach(['period' => ['Periode', $program->period ?? config('department.period'), 'text'], 'starts_on' => ['Tanggal mulai', $program->starts_on?->format('Y-m-d'), 'date'], 'ends_on' => ['Tanggal selesai', $program->ends_on?->format('Y-m-d'), 'date'], 'location' => ['Lokasi / platform', $program->location, 'text'], 'target' => ['Target peserta', $program->target, 'text']] as $key => [$label, $value, $type])<div>
<label class="form-label" for="{{ $key }}">{{ $label }}</label>
<input class="form-control" id="{{ $key }}" name="{{ $key }}" type="{{ $type }}" value="{{ old($key, $value) }}" maxlength="{{ $key === 'period' ? 40 : 200 }}" required>
</div>@endforeach
    <div>
<label class="form-label" for="pic">Penanggung jawab / PIC</label>
<select class="form-select" id="pic" name="pic_id" required>
<option value="">Pilih akun aktif departemen</option>@foreach($people as $person)<option value="{{ $person->id }}" @selected((string)old('pic_id', $program->pic_id) === (string)$person->id)>{{ $person->name }}</option>@endforeach</select>
</div>
    <fieldset class="o-full">
<legend class="form-label">Anggota yang terlibat</legend>
<div class="o-people-grid">@foreach($people as $person)<label class="o-person">
<input class="form-check-input" type="checkbox" name="members[]" value="{{ $person->id }}" @checked(in_array($person->id, old('members', $program->exists ? $program->members->pluck('id')->all() : [])))>
<span class="k-avatar">{{ mb_substr($person->name, 0, 1) }}</span>
<span>{{ $person->name }}<small>{{ config('superadmin.roles')[$person->role] }}</small>
</span>
</label>@endforeach</div>
<small>PIC otomatis disertakan. Akun dan departemen dikelola oleh Super Admin.</small>
</fieldset>
</div>
<div class="o-actions">
<a class="btn btn-outline-navy" href="{{ route('department.programs', $departmentKey) }}">Batal</a>
<button class="btn o-btn-yellow" type="submit">Simpan Informasi Proker <i class="bi bi-arrow-right" aria-hidden="true">
</i>
</button>
</div>
</form>
@endsection
