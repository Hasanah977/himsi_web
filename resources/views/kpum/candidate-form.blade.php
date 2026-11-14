@extends('kpum.layout')
@section('title', $candidate->exists ? 'Edit Kandidat' : 'Daftarkan Kandidat')
@section('content')
<div class="k-heading"><div><div class="k-eyebrow">MANAJEMEN KANDIDAT</div><h1>{{ $candidate->exists ? 'Edit Kandidat' : 'Daftarkan Kandidat' }}</h1><p>Lengkapi identitas dan persyaratan calon Ketua Umum HIMSI.</p></div><a class="btn btn-outline-navy" href="{{ route('kpum.candidates') }}">Kembali ke Kandidat</a></div>
<form class="k-panel k-form" method="POST" enctype="multipart/form-data" action="{{ $candidate->exists ? route('kpum.candidates.update', $candidate) : route('kpum.candidates.store') }}">
@csrf @if($candidate->exists) @method('PUT') @endif
<div class="k-form-section"><span>01</span><div><h2>Identitas Kandidat</h2><p>Data ini akan ditampilkan pada kartu kandidat.</p></div></div>
<div class="row g-4">
    @foreach(['name' => ['Nama lengkap', 'text', 120], 'nim' => ['NIM', 'text', 40], 'number' => ['Nomor urut', 'number', 99], 'angkatan' => ['Angkatan', 'number', now()->year], 'ipk' => ['IPK (syarat lolos minimal 3,00)', 'number', 4]] as $key => $input)
    <div class="col-md-6"><label class="form-label" for="{{ $key }}">{{ $input[0] }} <span class="text-danger">*</span></label><input class="form-control" id="{{ $key }}" name="{{ $key }}" type="{{ $input[1] }}" value="{{ old($key, $candidate->$key) }}" @if($input[1] === 'number') min="{{ $key === 'angkatan' ? 2000 : ($key === 'number' ? 1 : 0) }}" max="{{ $input[2] }}" step="{{ $key === 'ipk' ? '0.01' : '1' }}" @else maxlength="{{ $input[2] }}" @endif required></div>
    @endforeach
    <div class="col-md-6"><label class="form-label" for="photo">Foto kandidat</label><input class="form-control" type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/webp" data-k-preview-input><small>JPG, PNG, WebP · maksimal 3 MB</small><img class="k-upload-preview {{ $candidate->photo ? '' : 'd-none' }}" data-k-preview @if($candidate->photo) src="{{ route('kpum.photo', $candidate) }}" @endif alt="Pratinjau foto kandidat"></div>
</div>
<div class="k-form-section"><span>02</span><div><h2>Visi & Misi</h2><p>Sampaikan arah dan rencana kandidat secara singkat.</p></div></div>
@foreach(['vision' => ['Visi', 200], 'mission' => ['Misi', 500]] as $key => $input)<div class="mb-4"><label class="form-label" for="{{ $key }}">{{ $input[0] }} <span class="text-danger">*</span></label><textarea class="form-control" id="{{ $key }}" name="{{ $key }}" rows="{{ $key === 'vision' ? 3 : 5 }}" maxlength="{{ $input[1] }}" data-k-count required>{{ old($key, $candidate->$key) }}</textarea><small class="k-counter" data-k-count-for="{{ $key }}">Maksimal {{ $input[1] }} karakter</small></div>@endforeach
<div class="k-form-section"><span>03</span><div><h2>Berkas Persyaratan</h2><p>PDF, JPG, atau PNG · maksimal 3 MB per berkas. Berkas lama dipertahankan jika tidak diganti.</p></div></div>
<div class="row g-4">@foreach(\App\Models\KpumCandidate::documentLabels() as $key => $label)<div class="col-md-6"><label class="form-label" for="doc-{{ $key }}">{{ $label }}</label><input class="form-control" type="file" id="doc-{{ $key }}" name="documents[{{ $key }}]" accept="application/pdf,image/jpeg,image/png">@if(!empty($candidate->documents[$key]))<a class="small" href="{{ route('kpum.candidates.document', [$candidate, $key]) }}"><i class="bi bi-paperclip" aria-hidden="true"></i>Unduh berkas sebelumnya</a>@endif</div>@endforeach</div>
<div class="k-info mt-4"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Data dapat disimpan sebelum berkas lengkap. Setiap perubahan mengembalikan status kandidat menjadi <strong>Menunggu verifikasi</strong>.</span></div>
<div class="k-form-actions"><a class="btn btn-outline-navy" href="{{ route('kpum.candidates') }}">Batal</a><button class="btn btn-navy" type="submit"><i class="bi bi-check2 me-2" aria-hidden="true"></i>Simpan Kandidat</button></div>
</form>
@endsection
