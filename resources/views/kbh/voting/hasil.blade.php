@extends('layouts.kbh')
@section('title', 'Hasil Voting | KBH')
@section('page-title', 'Hasil Voting')
@section('page-subtitle', 'Status pemilihan Ketua Umum HIMSI')
@section('content')
<div class="content-card">
    <span class="section-label">PEMILIHAN</span><h2>{{ $periode?->nama ?? 'Belum ada periode voting' }}</h2>
    @if(!$periode)<p>Periode voting belum dibuat.</p>
    @elseif($hasilTerbuka)
      <p>Pemungutan suara telah berakhir. Berikut hasil perolehan suara:</p>
      @php($total = $kandidats->sum('jumlah_suara'))
      @foreach($kandidats as $kandidat)
        @php($persen = $total > 0 ? round($kandidat->jumlah_suara / $total * 100, 1) : 0)
        <div class="vote-item" style="margin:16px 0">
          <div class="vote-info"><strong>{{ $kandidat->nama }}</strong><span>{{ $kandidat->jumlah_suara }} suara ({{ $persen }}%)</span></div>
          <div class="progress"><div class="progress-bar" style="width:{{ $persen }}%"></div></div>
        </div>
      @endforeach
      @if($kandidats->isEmpty())<p>Belum ada kandidat pada periode ini.</p>@endif
    @else
      <p>Hasil belum dipublikasikan sampai jadwal pemilihan berakhir.</p>
      @if($sudahMemilih)<p><strong>Suara kamu sudah tercatat.</strong> Terima kasih telah berpartisipasi.</p>@endif
    @endif
    <a href="{{ route('kbh.voting.index') }}" class="btn-secondary" style="margin-top:18px">Kembali ke Voting</a>
</div>
@endsection
