@extends('kpum.layout')
@section('title', 'Hasil & Audit')
@section('content')
@include('kpum.heading')
@include('kpum.stats')
@include('kpum.tally')
@if($total > 0)
@php($leaders = $tally->where('ballots_count', $tally->max('ballots_count')))
<div class="k-callout"><i class="bi bi-trophy" aria-hidden="true"></i><span><strong>{{ $leaders->count() > 1 ? 'Perolehan tertinggi seri' : 'Perolehan suara tertinggi' }}: {{ $leaders->pluck('name')->join(', ') }}</strong><small>{{ in_array($election->status, ['certified', 'published']) ? 'Rekap telah disahkan.' : 'Hasil sementara, belum menjadi penetapan resmi.' }} {{ $leaders->count() > 1 ? 'Penetapan ketua mengikuti keputusan berdasarkan aturan pemilihan.' : '' }}</small></span></div>
@endif
<section class="k-panel" id="audit"><div class="k-panel-head"><h2><i class="bi bi-shield-check" aria-hidden="true"></i> Pemeriksaan Hasil</h2><a class="btn btn-outline-navy btn-sm" href="{{ route('kpum.export') }}"><i class="bi bi-download me-1" aria-hidden="true"></i>Unduh Rekap CSV</a></div>
@foreach($checks as $label => $passed)<div class="k-audit-check {{ $passed ? 'passed' : 'failed' }}"><i class="bi {{ $passed ? 'bi-check-circle-fill' : 'bi-exclamation-circle' }}" aria-hidden="true"></i><div><strong>{{ $label }}</strong><small>{{ $passed ? 'Sesuai berdasarkan data saat ini' : 'Belum memenuhi pemeriksaan' }}</small></div></div>@endforeach
<p class="small text-secondary">Pemeriksaan ini membandingkan konsistensi data aplikasi. Panitia tetap harus memeriksa proses pemilihan dan menyelesaikan keberatan sebelum pengesahan.</p>
@if(in_array($election->status, ['certified', 'published']))<div class="k-info"><i class="bi bi-patch-check" aria-hidden="true"></i><span><strong>Hasil telah disahkan</strong><br>{{ $election->audit_note }}</span></div><a class="btn btn-navy mt-3" href="{{ route('kpum.announcement') }}">Buka Pengumuman</a>
@elseif($election->finished())<form method="POST" action="{{ route('kpum.certify') }}" data-k-confirm="Sahkan hasil pemilihan ini? Pastikan seluruh pemeriksaan dan penanganan keberatan sudah selesai.">@csrf<label class="k-check my-3"><input type="checkbox" name="confirmed" value="1" required>Saya telah menyelesaikan pemeriksaan manual dan memastikan hasil dapat disahkan.</label><label class="form-label" for="audit-note">Catatan / ringkasan berita acara</label><textarea class="form-control" id="audit-note" name="note" rows="3" maxlength="2000" required>{{ old('note') }}</textarea><button class="btn btn-navy mt-3" type="submit"><i class="bi bi-patch-check me-2" aria-hidden="true"></i>Sahkan Hasil</button></form>
@else<div class="k-info"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Pengesahan tersedia setelah voting berakhir.</span></div>@endif
</section>
<section class="k-panel"><div class="k-panel-head"><h2>Riwayat Audit</h2><small>Perubahan oleh pengelola</small></div><div class="table-responsive"><table class="table k-table"><thead><tr><th>Aktivitas</th><th>Keterangan</th><th>Waktu (WITA)</th></tr></thead><tbody>@forelse($audits as $audit)<tr><td>{{ $audit->action }}</td><td>{{ $audit->detail ?: '—' }}</td><td class="text-nowrap">{{ \Carbon\Carbon::parse($audit->created_at)->timezone('Asia/Makassar')->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="3">Belum ada aktivitas.</td></tr>@endforelse</tbody></table></div><div class="k-pagination"><span>Halaman {{ $audits->currentPage() }} / {{ $audits->lastPage() }}</span><div>@if($audits->previousPageUrl())<a href="{{ $audits->previousPageUrl() }}#audit">Sebelumnya</a>@endif @if($audits->nextPageUrl())<a href="{{ $audits->nextPageUrl() }}#audit">Berikutnya</a>@endif</div></div></section>
@endsection
