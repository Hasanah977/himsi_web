@extends('administration.layout')
@section('title', 'Pendaftaran Ketua Umum')
@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4"><div><h2>Pendaftaran Ketua Umum</h2><p class="text-secondary mb-0">Nomor urut ditetapkan panitia KPUM setelah Anda lolos verifikasi.</p></div><a class="btn btn-outline-navy" href="{{ route('kbh.seleksi.index') }}">Kembali ke Seleksi</a></div>
<form class="admin-panel" method="POST" enctype="multipart/form-data" action="{{ route('kpum.registration.store') }}">
    @csrf
    <h3 class="h5 mb-4">01 · Identitas Kandidat</h3>
    <div class="row g-4 mb-5">
        <div class="col-md-6"><label class="form-label" for="candidate-name">Nama lengkap</label><input class="form-control" id="candidate-name" name="name" value="{{ old('name', $candidate?->name ?? auth()->user()->name) }}" maxlength="120" required></div>
        <div class="col-md-6"><label class="form-label" for="candidate-nim">NIM</label><input class="form-control" id="candidate-nim" name="nim" value="{{ old('nim', auth()->user()->nim ?? $candidate?->nim) }}" maxlength="40" @readonly(auth()->user()->nim) required></div>
        <div class="col-md-6"><label class="form-label" for="candidate-year">Angkatan</label><input class="form-control" id="candidate-year" name="angkatan" type="number" min="2000" max="{{ now()->year }}" value="{{ old('angkatan', $candidate?->angkatan ?? auth()->user()->angkatan_kelas) }}" required></div>
        <div class="col-md-6"><label class="form-label" for="candidate-ipk">IPK (syarat lolos minimal 3,00)</label><input class="form-control" id="candidate-ipk" name="ipk" type="number" min="0" max="4" step="0.01" value="{{ old('ipk', $candidate?->ipk) }}" required></div>
        <div class="col-md-6"><label class="form-label" for="candidate-photo">Foto kandidat</label><input class="form-control" id="candidate-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required><small class="text-secondary">JPG, PNG, atau WebP · maksimal 3 MB.</small></div>
    </div>
    <h3 class="h5 mb-4">02 · Visi & Misi</h3>
    @foreach(['vision' => ['Visi', 200], 'mission' => ['Misi', 500]] as $key => [$label, $limit])
        <div class="mb-4"><label class="form-label" for="candidate-{{ $key }}">{{ $label }}</label><textarea class="form-control" id="candidate-{{ $key }}" name="{{ $key }}" rows="4" maxlength="{{ $limit }}" required>{{ old($key, $candidate?->$key) }}</textarea><small class="text-secondary">Maksimal {{ $limit }} karakter.</small></div>
    @endforeach
    <h3 class="h5 mt-5 mb-2">03 · Berkas Persyaratan</h3><p class="text-secondary">PDF, JPG, atau PNG · maksimal 3 MB per berkas. Semua berkas wajib diunggah, termasuk saat mengirim perbaikan.</p>
    <div class="row g-4">@foreach(\App\Models\KpumCandidate::documentLabels() as $key => $label)<div class="col-md-6"><label class="form-label" for="candidate-{{ $key }}">{{ $label }}</label><input class="form-control" id="candidate-{{ $key }}" name="documents[{{ $key }}]" type="file" accept="application/pdf,image/jpeg,image/png" required></div>@endforeach</div>
    <div class="mt-5"><button class="btn btn-navy" type="submit"><i class="bi bi-send me-2"></i>Kirim Pendaftaran ke KPUM</button></div>
</form>
@endsection
