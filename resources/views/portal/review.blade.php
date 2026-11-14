@extends('portal.layout')
@section('portal-width', 'hp-review-width')
@section('portal-title', 'Pemeriksaan Berkas')
@section('portal-content')
<section class="bg-white rounded-4 p-4 p-md-5 mb-4">
<div class="d-flex justify-content-between gap-3 flex-wrap"><div><h1 class="h3">Pemeriksaan Berkas</h1><p>{{ auth()->user()->name }} · {{ $registration->nim }}</p></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-secondary">Keluar</button></form></div>
<span class="badge {{ $registration->status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark' }}">{{ ['pending'=>'Menunggu Review','returned'=>'Revisi','rejected'=>'Ditolak'][$registration->status] ?? $registration->status }}</span>
<p class="mt-3">Akses KBH akan terbuka setelah Panitia CB memverifikasi kelulusan Anda.</p>
@if($registration->review_note)<div class="alert alert-info"><strong>Catatan Panitia CB</strong><p class="mb-0" style="white-space:pre-wrap">{{ $registration->review_note }}</p></div>@endif
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
</section>
<form method="POST" enctype="multipart/form-data" action="{{ route('registration.revise') }}">@csrf<input type="hidden" name="version" value="{{ $registration->review_version }}">
<div class="row g-4">
@foreach(\App\Models\HimsiRegistration::documents() as $key => $label)
<div class="col-lg-4"><section class="bg-white rounded-4 p-3 h-100"><h2 class="h6">{{ $label }}</h2><p class="small">{{ ['valid'=>'Valid','invalid'=>'Ditolak','clarify'=>'Revisi'][$registration->document_reviews[$key] ?? ''] ?? 'Menunggu Review' }}</p>
@if($registration->{$key.'_path'})
@if(strtolower(pathinfo($registration->{$key.'_path'}, PATHINFO_EXTENSION)) === 'pdf')<iframe title="{{ $label }}" src="{{ route('registration.document', $key) }}" style="width:100%;height:280px;border:0"></iframe>@else<img alt="{{ $label }}" src="{{ route('registration.document', $key) }}" style="width:100%;height:280px;object-fit:contain">@endif
<div class="d-flex gap-2 my-3"><a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="{{ route('registration.document', $key) }}">Buka</a><a class="btn btn-sm btn-outline-secondary" href="{{ route('registration.document', [$key, 'download'=>1]) }}">Unduh</a></div>
@else<p>Berkas belum tersedia.</p>@endif
@if($registration->status === 'returned')<label for="{{ $key }}" class="form-label">Berkas pengganti</label><input id="{{ $key }}" class="form-control" name="{{ $key }}" type="file" accept="application/pdf,image/jpeg,image/png"><small>JPG, PNG, PDF · maksimal 5 MB</small>@endif
</section></div>
@endforeach
</div>
@if($registration->status === 'returned')<button class="btn btn-light mt-4" type="submit">Kirim Ulang Berkas untuk Review</button>@endif
</form>
@endsection
