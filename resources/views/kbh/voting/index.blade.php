@extends('layouts.kbh')
@section('title', 'Voting Ketua Umum | KBH')
@section('page-title', 'Voting Ketua Umum')
@section('page-subtitle', 'Pemilihan Ketua Umum HIMSI sesuai periode resmi')
@section('content')
<div class="info-banner">
    <strong>{{ $periode?->nama ?? 'Belum ada periode voting' }}</strong>
    @if($periode)
       <p>Jadwal: {{ $periode->mulai_pada->timezone('Asia/Makassar')->format('d/m/Y H:i') }} – {{ $periode->selesai_pada->timezone('Asia/Makassar')->format('d/m/Y H:i') }}</p>
       <p><strong>Status: {{ $periode->sedangDibuka() ? 'Voting Dibuka' : 'Voting Ditutup / Belum Dimulai' }}</strong></p>
    @else<p>Voting belum dijadwalkan oleh pengelola.</p>@endif
</div>
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
@if($sudahMemilih)<div class="alert alert-success">Suaramu sudah tercatat untuk periode ini. Kamu tidak dapat memilih lagi.</div>@endif
@if($periode && $periode->sedangDibuka() && !$sudahMemilih && $kandidats->isEmpty())<div class="alert alert-warning">Periode aktif, tetapi belum ada kandidat resmi. Hubungi panitia.</div>@endif
<div class="candidate-grid">
@foreach($kandidats as $kandidat)
  <article class="candidate-card">
    <div class="candidate-number">{{ str_pad((string)$loop->iteration,2,'0',STR_PAD_LEFT) }}</div>
    <div class="candidate-photo"><i class="bi bi-person-fill" aria-hidden="true"></i></div>
    <h3>{{ $kandidat->nama }}</h3><p>{{ $kandidat->nim }}</p>
    <a href="{{ route('kbh.voting.visi-misi', $kandidat->id) }}" class="btn-secondary">Lihat Visi & Misi</a>
    @if($periode->sedangDibuka() && !$sudahMemilih)
      <form action="{{ route('kbh.voting.vote') }}" method="POST" onsubmit="return confirm('Yakin memilih kandidat ini? Pilihan tidak dapat diubah.');">
        @csrf<input name="kandidat_id" type="hidden" value="{{ $kandidat->id }}">
        <button class="btn-primary" type="submit">Pilih Kandidat</button>
      </form>
    @else
      <p class="kbh-revisi-muted">{{ $sudahMemilih ? 'Sudah memilih' : 'Voting tidak aktif' }}</p>
    @endif
  </article>
@endforeach
</div>
<div class="center-action"><a href="{{ route('kbh.voting.hasil') }}" class="btn-secondary">Lihat Status / Hasil Voting</a></div>
@endsection
