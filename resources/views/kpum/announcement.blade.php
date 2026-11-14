@extends('kpum.layout')
@section('title', 'Pengumuman')
@section('content')
@include('kpum.heading')
<section class="k-panel">
@if($announcement)
<div class="k-empty"><span class="k-success-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span><h2>Hasil pemilihan telah dipublikasikan</h2><p>{{ $announcement->payload['title'] }}</p>@if(!empty($announcement->payload['image']))<img class="published-winner-photo" src="{{ \App\Models\PublicContent::imageUrl($announcement->payload['image']) }}" alt="Foto pemenang pemilihan">@endif<a class="btn btn-navy" href="{{ route('announcement.detail', $announcement->slug) }}">Lihat Pengumuman Publik</a></div>
@elseif($election->status === 'certified')
<div class="k-toolbar"><div><h2>Publikasikan Hasil Pemilihan</h2><p>Pengumuman akan tampil pada halaman Pengumuman di website HIMSI.</p></div><span class="k-badge green">Hasil sudah disahkan</span></div>
<form method="POST" action="{{ route('kpum.publish') }}" enctype="multipart/form-data" class="k-form" data-k-confirm="Publikasikan pengumuman hasil ini ke website publik HIMSI?">@csrf<div class="mb-4"><label class="form-label" for="announcement-title">Judul pengumuman</label><input class="form-control" name="title" id="announcement-title" value="{{ old('title', 'Hasil Pemilihan Ketua Umum HIMSI '.$election->period) }}" maxlength="160" required></div><div class="mb-4"><label class="form-label" for="announcement-body">Isi pengumuman</label><textarea class="form-control" name="body" id="announcement-body" rows="7" maxlength="5000" required>{{ old('body') }}</textarea><small>Rekap per kandidat dan keterangan hasil seri (jika ada) ditambahkan otomatis dari hasil yang disahkan.</small></div>@php($winners = $tally->where('ballots_count', $tally->max('ballots_count')))
@if($winners->count()===1 && !$winners->first()->user_id)<div class="mb-4"><label class="form-label" for="winner-account">Akun milik pemenang: {{ $winners->first()->name }}</label><select id="winner-account" name="winner_user_id" class="form-select" required><option value="">Pilih akun KBH kandidat pemenang</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected(old('winner_user_id')==$account->id)>{{ $account->name }} · {{ $account->email }}</option>@endforeach</select><small>Akun ini akan menjadi Ketua Umum setelah hasil dipublikasikan.</small></div>@endif
<section class="winner-photo-upload mb-4" data-photo-upload>
<div class="winner-photo-placeholder" data-photo-placeholder><i class="bi bi-person-bounding-box" aria-hidden="true"></i></div>
<img class="winner-photo-preview" data-photo-preview hidden alt="Pratinjau foto pemenang">
<div><label class="form-label" for="winner-photo">Foto pemenang <span class="text-secondary fw-normal">(opsional)</span></label><p class="small text-secondary mb-3" id="winner-photo-help">Sertakan potret pemenang pada pengumuman. JPG, PNG, atau WebP, maksimal 5 MB.</p>
<input class="form-control" type="file" id="winner-photo" name="winner_photo" accept="image/jpeg,image/png,image/webp" aria-describedby="winner-photo-help" data-photo-input>
<p class="small text-danger mt-2 mb-0" data-photo-error role="alert" hidden></p><button class="btn btn-sm btn-outline-secondary mt-2" type="button" data-photo-remove hidden>Hapus foto</button></div>
</section>
<label class="k-check mb-4"><input type="checkbox" name="confirmed" value="1" required>Saya sudah memeriksa isi dan menyetujui publikasi ke website.</label><button class="btn btn-navy" type="submit"><i class="bi bi-megaphone me-2" aria-hidden="true"></i>Publikasikan Pengumuman</button></form>
@else<div class="k-empty"><i class="bi bi-megaphone" aria-hidden="true"></i><h2>Pengumuman belum dapat diterbitkan</h2><p>Selesaikan voting, periksa hasil, lalu lakukan pengesahan.</p><a class="btn btn-navy" href="{{ route('kpum.results') }}">Ke Hasil & Audit</a></div>@endif
</section>
@endsection
