<section class="k-panel" data-k-live="{{ route('kpum.stats') }}">
    <div class="k-panel-head"><h2><i class="bi bi-bar-chart" aria-hidden="true"></i> Perolehan suara {{ in_array($election->status, ['certified', 'published']) ? 'disahkan' : 'sementara' }}</h2><small data-k-live-status>Pembaruan tiap 30 detik</small></div>
    @forelse($tally as $candidate)
        @php($share = $total ? round($candidate->ballots_count / $total * 100, 1) : 0)
        <div class="k-tally" data-k-candidate="{{ $candidate->id }}"><div class="k-tally-label"><span><b class="k-number">{{ $candidate->number }}</b> {{ $candidate->name }}</span><strong><span data-k-votes>{{ $candidate->ballots_count }}</span> suara <small>(<span data-k-share>{{ $share }}</span>%)</small></strong></div><progress class="k-progress" value="{{ $share }}" max="100" aria-label="Persentase suara {{ $candidate->name }}"></progress></div>
    @empty
        <div class="k-empty"><i class="bi bi-bar-chart" aria-hidden="true"></i><h3>Belum ada kandidat terverifikasi</h3><p>Perolehan suara akan muncul setelah kandidat disetujui.</p></div>
    @endforelse
    <p class="k-total">Total <strong data-k-stat="total">{{ $total }}</strong> suara masuk · Pilihan masing-masing pemilih tidak ditampilkan.</p>
</section>
