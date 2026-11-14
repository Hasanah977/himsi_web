@extends('administration.layout')
@section('title', 'Voting')
@section('content')
<section class="admin-panel d-flex justify-content-between align-items-center gap-3 flex-wrap"><div class="vote-heading"><span class="workspace-eyebrow">PEMILIHAN KETUA UMUM</span><h2 class="h4 fw-bold mt-2">{{ $election->title }}</h2><p class="text-secondary mb-0">{{ $results ? 'Rekap suara resmi yang telah dipublikasikan oleh KPUM.' : 'Kenali kandidat, gunakan hak pilih, dan pantau hasilnya di halaman ini.' }}</p></div><span class="workspace-badge {{ $results ? 'success' : 'info' }}"><i class="bi bi-{{ $results ? 'check-circle-fill' : 'clock' }} me-2"></i>{{ $election->label() }}</span></section>
@if($results)
    @php
        $totalVotes = $candidates->sum('ballots_count');
        $highestVotes = $candidates->max('ballots_count') ?? 0;
        $leaders = $candidates->where('ballots_count', $highestVotes);
        $ranked = $candidates->sortByDesc('ballots_count')->values();
    @endphp
    <div class="vote-overview">
        <section class="admin-panel"><h2><i class="bi bi-bar-chart me-2"></i>Grafik Hasil Voting Resmi</h2><div class="vote-chart" role="img" aria-label="Hasil resmi: @foreach($candidates as $candidate){{ $candidate->name }} {{ $candidate->ballots_count }} suara; @endforeach">
            @foreach($candidates as $candidate)<div class="vote-column" style="--height:{{ $highestVotes ? ($candidate->ballots_count / $highestVotes * 85) : 0 }}%;--bar:{{ ['#192c65','#ffdc32','#9d8ae8','#3da6af'][$loop->index % 4] }}"><b>{{ $candidate->ballots_count }} suara ({{ $totalVotes ? round($candidate->ballots_count / $totalVotes * 100, 1) : 0 }}%)</b><div class="bar"></div><span>{{ $candidate->name }}</span></div>@endforeach
        </div><div class="vote-chart-legend">@foreach($candidates as $candidate)<span><i style="background:{{ ['#192c65','#ffdc32','#9d8ae8','#3da6af'][$loop->index % 4] }}"></i>{{ $candidate->name }} · {{ $candidate->ballots_count }} suara</span>@endforeach</div><p class="text-secondary small mb-0">Jumlah suara sah per kandidat · {{ $totalVotes }} total suara</p></section>
        <section class="admin-panel vote-summary"><h2><i class="bi bi-award me-2"></i>Ringkasan Hasil Voting</h2><div class="vote-summary-body"><div class="vote-winner"><i class="bi bi-trophy"></i><small>{{ !$totalVotes ? 'Belum ada suara sah' : ($leaders->count() > 1 ? 'Perolehan tertinggi sama' : 'Pemenang / Perolehan Tertinggi') }}</small><h3>{{ $totalVotes ? $leaders->pluck('name')->join(' & ') : 'Belum ada pemenang' }}</h3>@if($totalVotes)<span class="workspace-badge success">{{ $highestVotes }} suara ({{ round($highestVotes / $totalVotes * 100,1) }}%)</span>@endif</div><div><small>Status</small><p><span class="workspace-badge success">Hasil dipublikasikan</span></p><small>Total Suara Sah</small><p class="fs-3 fw-bold mb-2">{{ $totalVotes }}</p><strong class="small">Peringkat / Perolehan</strong><ol class="vote-ranking">@foreach($ranked as $candidate)<li><b>{{ $ranked->search(fn ($rank) => $rank->ballots_count === $candidate->ballots_count) + 1 }}</b>{{ $candidate->name }} — {{ $candidate->ballots_count }} suara</li>@endforeach</ol></div></div></section>
    </div>
@else
    @if($election->finished())<div class="alert alert-info">Hasil belum dipublikasikan oleh KPUM. Pengumuman akan tersedia di halaman ini setelah disahkan.</div>@endif
    <div class="alert {{ $elector?->has_voted ? 'alert-success' : 'alert-info' }}">{{ !$elector ? 'Akun belum terdaftar sebagai pemilih. Hubungi Panitia KPUM.' : ($elector->has_voted ? 'Terima kasih. Hak pilih Anda sudah digunakan.' : 'Setiap pemilih hanya dapat memilih satu kali.') }}</div>
@endif
<div class="vote-candidate-grid">
@forelse($candidates as $candidate)
    <article class="admin-panel vote-candidate"><header><span class="workspace-icon">{{ str_pad($candidate->number,2,'0',STR_PAD_LEFT) }}</span><h2>{{ $candidate->name }}</h2></header><div class="vote-candidate-body">
        @if($candidate->photo)<img class="candidate-photo" src="{{ route('kpum.photo',$candidate) }}" alt="Foto {{ $candidate->name }}">@else<div class="candidate-photo candidate-placeholder"><i class="bi bi-person"></i></div>@endif
        <div><section><h3><i class="bi bi-eye me-2"></i>Visi</h3><p>{{ $candidate->vision }}</p></section><section><h3><i class="bi bi-list-check me-2"></i>Misi</h3><p>{{ $candidate->mission }}</p></section></div></div>
        @if($results)<div class="vote-result"><div><small>Perolehan suara resmi</small><strong>{{ $candidate->ballots_count }} suara</strong></div><b>{{ $totalVotes ? round($candidate->ballots_count/$totalVotes*100,1) : 0 }}%</b></div>@endif
        <button class="btn btn-outline-navy w-100 mt-2" type="button" data-bs-toggle="modal" data-bs-target="#candidate-{{ $candidate->id }}">Lihat Visi &amp; Misi <i class="bi bi-arrow-right ms-2"></i></button>
        @if(!$results && $elector && !$elector->has_voted && $election->acceptingVotes())<form class="border-top pt-3 mt-3" method="POST" action="{{ route('kbh.voting.vote') }}" data-confirm="Kirim pilihan? Suara tidak dapat diubah.">@csrf<input type="hidden" name="candidate_id" value="{{ $candidate->id }}"><label class="d-flex gap-2 mb-3"><input class="form-check-input" type="checkbox" name="confirmed" value="1" required> Saya yakin dengan pilihan ini.</label><button type="submit" class="btn btn-navy w-100">Pilih Kandidat</button></form>@endif
    </article>
    <div class="modal fade" id="candidate-{{ $candidate->id }}" tabindex="-1" aria-labelledby="candidate-title-{{ $candidate->id }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-5" id="candidate-title-{{ $candidate->id }}">Visi &amp; Misi Calon Ketua Umum</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body p-4"><div class="d-flex gap-3 align-items-center mb-4">@if($candidate->photo)<img class="candidate-photo" style="width:100px;height:115px" src="{{ route('kpum.photo',$candidate) }}" alt="Foto {{ $candidate->name }}">@endif<div><span class="workspace-icon mb-2">{{ str_pad($candidate->number,2,'0',STR_PAD_LEFT) }}</span><h3 class="h4 fw-bold">{{ $candidate->name }}</h3><small>Calon Ketua Umum HIMSI</small></div></div><h4 class="h6 fw-bold"><i class="bi bi-eye me-2"></i>Visi</h4><p class="vote-modal-body">{{ $candidate->vision }}</p><h4 class="h6 fw-bold"><i class="bi bi-list-check me-2"></i>Misi</h4><p class="vote-modal-body mb-0">{{ $candidate->mission }}</p></div></div></div></div>
@empty
    <div class="admin-panel text-center py-5"><h2 class="h5">Belum ada kandidat terverifikasi.</h2><p class="text-secondary mb-0">Informasi kandidat akan tersedia setelah verifikasi oleh KPUM.</p></div>
@endforelse
</div>
@endsection
