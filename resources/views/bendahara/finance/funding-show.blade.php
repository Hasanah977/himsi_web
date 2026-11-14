@extends('administration.layout')
@section('title', 'Detail Pengajuan Dana')
@push('styles')<link rel="stylesheet" href="{{ asset('css/himsi-finance.css') }}">@endpush
@section('content')
<div class="finance-page">
<div class="finance-heading"><div><span class="finance-eyebrow text-secondary">{{ config('superadmin.departments.'.$submission->program->department) }}</span><h2>{{ $submission->program->title }}</h2><p>{{ config('department.submission_statuses.'.$submission->status) }}</p></div><a href="{{ route('finance.funding') }}" class="btn btn-outline-navy">Kembali</a></div>
<div class="finance-columns"><section class="finance-panel"><h2>Rincian RAB</h2>@include('department.submission-details')<p class="mt-3">Catatan pemeriksaan: {{ $submission->note ?? 'Belum ada catatan' }}</p>
@if(!$submission->finance_approved_by)<div class="finance-note">Belum ada persetujuan Ketua Umum untuk pencairan. RAB lama yang sebelumnya disetujui Bendahara perlu diperiksa ulang oleh Ketua Umum.</div>@endif</section>
<section class="finance-panel"><h2>{{ $submission->status==='disbursed'?'Pengembalian Dana':'Pencairan Dana' }}</h2><p>Sudah dicairkan: <strong>Rp {{ number_format($paid,0,',','.') }}</strong></p><p>Sudah dikembalikan: <strong>Rp {{ number_format($returned,0,',','.') }}</strong></p>
@if(auth()->user()->role==='bendahara_umum' && in_array($submission->status,['ready_funding','disbursed']))
<form method="POST" enctype="multipart/form-data" action="{{ route($submission->status==='disbursed'?'finance.funding.refund':'finance.funding.pay',$submission) }}" data-confirm="Catat transaksi dana kegiatan ini?">@csrf<input type="hidden" name="version" value="{{ $submission->version }}"><input type="hidden" name="reference" value="{{ old('reference',(string) Illuminate\Support\Str::uuid()) }}">
@if($submission->status==='disbursed')<label for="refund-amount" class="form-label">Nominal pengembalian (Rp)</label><input class="form-control mb-3" id="refund-amount" name="amount" type="number" min="1" max="{{ $paid-$returned }}" value="{{ old('amount') }}" required>@else<div class="finance-note mb-3">Pencairan penuh sesuai RAB: <strong>Rp {{ number_format($submission->total,0,',','.') }}</strong></div>@endif
<label for="date" class="form-label">Tanggal transaksi</label><input class="form-control mb-3" id="date" name="occurred_on" type="date" value="{{ old('occurred_on',date('Y-m-d')) }}" max="{{ min(date('Y-m-d'),config('finance.end')) }}" required>
<label for="note" class="form-label">Catatan</label><textarea class="form-control mb-3" id="note" name="note" maxlength="2000">{{ old('note') }}</textarea><label for="proof" class="form-label">Bukti transfer (JPG/PNG/WebP/PDF, maks. 5 MB)</label><input class="form-control mb-3" id="proof" name="receipt" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required>
<button type="submit" class="btn btn-navy">{{ $submission->status==='disbursed'?'Catat Pengembalian':'Konfirmasi Pencairan' }}</button></form>
@else<div class="finance-note">Pencairan dilakukan akun Bendahara setelah pengajuan disetujui Ketua Umum.</div>@endif</section></div>
@if($submission->status === 'pending_treasurer' && auth()->user()->role === 'bendahara_umum')
<section class="finance-panel mt-4"><h2>Review Pengajuan Masuk</h2><p>Periksa kelengkapan RAB sebelum diteruskan kepada Ketua Umum.</p><form method="POST" action="{{ route('department.review.save', $submission->id) }}">@csrf<input type="hidden" name="version" value="{{ $submission->version }}"><label for="review-note" class="form-label">Catatan pemeriksaan</label><textarea id="review-note" class="form-control mb-3" name="note" maxlength="3000" required></textarea><div class="d-flex gap-2 flex-wrap"><button class="btn btn-navy" name="action" value="forward">Teruskan ke Ketua</button><button class="btn btn-outline-navy" name="action" value="revision">Minta revisi</button><button class="btn btn-outline-danger" name="action" value="reject">Tolak</button></div></form></section>
@endif
<section class="finance-panel mt-4"><h2>Riwayat Dana Kegiatan</h2>@include('bendahara.finance.table')</section>
</div>
@endsection
