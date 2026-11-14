@extends('administration.layout')
@section('title', 'LPJ')
@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div><h2>Laporan Pertanggungjawaban</h2><p class="text-secondary">Unggah LPJ pengurus dan pantau verifikasi Koordinator Pengawas Himpunan.</p></div>
    @if(in_array(auth()->user()->role, \App\Models\OfficerReport::SubmitterRoles, true))
        <button class="btn btn-warning" data-bs-toggle="collapse" data-bs-target="#uploadLpj" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}" aria-controls="uploadLpj"><i class="bi bi-plus-lg"></i> Upload LPJ</button>
    @endif
</div>
@if(auth()->user()->role === 'koordinator_pengawas')
    <div class="admin-panel d-flex justify-content-between flex-wrap gap-3"><div><strong>LPJ Departemen</strong><p class="text-secondary mb-0">{{ $departmentPending }} LPJ departemen menunggu verifikasi Anda.</p></div><a class="btn btn-outline-navy align-self-center" href="{{ route('department.reviews') }}">Periksa LPJ Departemen</a></div>
@endif
<a class="btn btn-outline-navy mb-4" href="{{ route('officer-reports.template') }}"><i class="bi bi-download me-2"></i>Unduh template LPJ</a>
@if(in_array(auth()->user()->role, \App\Models\OfficerReport::SubmitterRoles, true))
<div class="collapse {{ $errors->any() ? 'show' : '' }}" id="uploadLpj"><form class="admin-panel" method="POST" enctype="multipart/form-data" action="{{ route('officer-reports.store') }}">
    @csrf
    <h3 class="h5 mb-3">Upload LPJ</h3>
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label" for="title">Judul laporan / kegiatan</label><input class="form-control" id="title" name="title" value="{{ old('title') }}" maxlength="160" required></div>
        <div class="col-md-4"><label class="form-label" for="period">Periode</label><input class="form-control" id="period" name="period" value="{{ old('period', config('department.period')) }}" maxlength="40" required></div>
        <div class="col-12"><label class="form-label" for="file">Dokumen LPJ</label><input class="form-control" type="file" id="file" name="file" accept=".pdf,.doc,.docx" aria-describedby="fileHelp" required><p class="form-text" id="fileHelp">PDF atau Word, maksimal 25 MB.</p></div>
    </div>
    <button class="btn btn-warning mt-3">Kirim untuk Verifikasi</button>
</form></div>
@endif
<form method="GET" class="d-flex flex-wrap gap-2 align-items-end mb-4"><div><label class="form-label" for="status">Status LPJ</label><select id="status" name="status" class="form-select"><option value="">Semua Status</option>@foreach(\App\Models\OfficerReport::Statuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div><button class="btn btn-outline-navy">Terapkan</button></form>
<section class="admin-panel"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Laporan / Kegiatan</th><th>Pengirim</th><th>Dokumen</th><th>Status</th><th>Diperbarui</th><th>Aksi</th></tr></thead><tbody>
@forelse($items as $item)
<tr><td>{{ $item->title }}<small class="d-block text-secondary">{{ $item->period }}</small></td><td>{{ $item->user->name }}<small class="d-block text-secondary">{{ config('superadmin.roles.'.$item->role) }}</small></td><td><a href="{{ route('officer-reports.file', $item) }}">{{ $item->file_name }}</a></td><td>{{ \App\Models\OfficerReport::Statuses[$item->status] }}</td><td>{{ $item->updated_at->format('d M Y H:i') }}</td><td><a class="btn btn-sm btn-outline-navy" href="{{ route('officer-reports.show', $item) }}">Detail</a></td></tr>
@empty
<tr><td colspan="6" class="text-center text-secondary py-5"><i class="bi bi-inbox fs-1 d-block mb-3"></i>Belum ada LPJ sesuai filter.</td></tr>
@endforelse
</tbody></table></div>{{ $items->links() }}</section>
@endsection
