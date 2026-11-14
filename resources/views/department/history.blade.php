<section class="k-panel">
<div class="k-panel-head">
<h2>
<i class="bi bi-clock-history" aria-hidden="true">
</i>Riwayat Proses</h2>
</div>
<div class="o-timeline">@forelse($history as $entry)<article>
<span class="o-timeline-dot">
</span>
<div>
<strong>{{ $entry->action }}</strong>
<small>{{ $entry->actor ?? 'Akun dihapus' }} · {{ \Carbon\Carbon::parse($entry->created_at)->timezone('Asia/Makassar')->translatedFormat('d M Y H:i') }} WITA</small>@if($entry->note)<p>{{ $entry->note }}</p>@endif</div>
</article>@empty<p class="text-secondary">Belum ada riwayat proses.</p>@endforelse</div>@include('department.pagination', ['items' => $history])</section>