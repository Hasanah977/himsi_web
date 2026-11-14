@extends('department.layout')
@section('title', 'Periksa Pengajuan')
@section('content')
<div class="k-heading">
<div>
<div class="k-eyebrow">{{ config('department.types')[$submission->type] }}</div>
<h1>{{ $submission->program->title }}</h1>
<p>Departemen {{ config('superadmin.departments')[$submission->program->department] }}</p>
</div>
<a class="btn btn-outline-navy" href="{{ route('department.reviews') }}">Kembali</a>
</div>
<section class="k-panel">@include('department.status',['status'=>$submission->status,'kind'=>'submission'])<div class="mt-4">@include('department.submission-details')</div>@if($submission->note)<p class="mt-3">Catatan terakhir: {{ $submission->note }}</p>@endif</section>
@if($submission->type==='letter')<div class="k-panel mb-4">
@if($linkedLetter)<p>Surat terhubung: {{ $linkedLetter->nomor_surat ?? 'Draft' }} — {{ $linkedLetter->status }}</p>
@if(auth()->user()->role==='sekretaris_umum')<a class="btn btn-navy" href="{{ route('sekretaris.surat-keluar.show',$linkedLetter) }}">Buka Administrasi Surat</a>@elseif(auth()->user()->role==='ketua_umum')<a class="btn btn-navy" href="{{ route('ketua-umum.persetujuan-surat.show',$linkedLetter) }}">Periksa & Tandatangani Surat</a>@endif
@endif</div>@endif
@php($expectedRole = match($submission->status) {'pending_supervisor'=>'koordinator_pengawas','pending_archive'=>'sekretaris_umum','pending_secretary'=>'sekretaris_umum','pending_chair'=>'ketua_umum','pending_treasurer'=>'bendahara_umum','approved'=>$submission->type==='letter' ? 'sekretaris_umum':($submission->type==='report' && $submission->program->status==='running' ? 'koordinator_pengawas':($submission->type==='budget' && !$submission->finance_approved_by ? 'ketua_umum':null)),default=>null})
@if($submission->type === 'rpk')
@php($expectedRole = \App\Support\RpkWorkflow::canReview($submission, auth()->user()) ? auth()->user()->role : null)
@endif
@if(auth()->user()->role === $expectedRole && (!$linkedLetter || $submission->status === 'approved'))
<form class="k-panel" method="POST" enctype="multipart/form-data" action="{{ route('department.review.save',$submission->id) }}" data-k-confirm="Simpan keputusan ini untuk versi pengajuan yang sedang ditampilkan?">@csrf<input type="hidden" name="version" value="{{ $submission->version }}">
<h2 class="mb-3">Keputusan Pemeriksaan</h2>
<label class="form-label" for="action">Tindakan</label>
<select class="form-select mb-3" id="action" name="action" required>
<option value="">Pilih tindakan</option>@if($submission->status==='pending_archive')<option value="archive">{{ $submission->type === 'rpk' ? 'Arsipkan PPRK' : 'Arsipkan LPJ dan selesaikan proker' }}</option>@elseif($submission->status==='approved' && $submission->type==='letter')<option value="sent">Tandai surat sudah dikirim</option>@elseif($submission->type === 'rpk')<option value="approve">Verifikasi PPRK</option><option value="revision">Kembalikan untuk revisi</option><option value="reject">Tolak PPRK</option>@else<option value="{{ in_array($expectedRole,['sekretaris_umum','bendahara_umum'])?'forward':'approve' }}">{{ in_array($expectedRole,['sekretaris_umum','bendahara_umum'])?'Teruskan ke Ketua Umum':'Setujui pengajuan ini' }}</option>
<option value="revision">Kembalikan untuk revisi</option>
<option value="reject">Tolak pengajuan</option>@endif</select>
<label class="form-label" for="note">Catatan keputusan</label>
<textarea class="form-control" id="note" name="note" rows="4" maxlength="3000" required>{{ old('note') }}</textarea>
<button class="btn o-btn-yellow mt-3" type="submit">Simpan Keputusan</button>
</form>
@else<div class="k-info mb-4">
<i class="bi bi-info-circle" aria-hidden="true">
</i>
<span>Pengajuan ini tidak sedang menunggu tindakan dari role akun Anda.</span>
</div>@endif
@if($submission->type==='budget' && in_array(auth()->user()->role,['bendahara_umum','super_admin']))<a class="btn btn-navy mb-4" href="{{ route('finance.funding.show',$submission) }}">Buka Pencairan Dana</a>@endif
@include('department.history')
@endsection
