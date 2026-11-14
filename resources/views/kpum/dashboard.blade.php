@extends('kpum.layout')
@section('title', 'Dashboard Panitia KPUM')
@section('content')
@php
    $participation = [
        ['label' => 'Sudah Memilih', 'value' => $used, 'color' => '#153365'],
        ['label' => 'Belum Memilih', 'value' => max(0, $eligible - $used), 'color' => '#d6dce7'],
    ];
    $stage = match($election->status) {'published' => 4, 'certified' => 3, 'closed' => 2, 'open' => 2, default => 1};
    $stages = [
        ['Persiapan', 'Data & daftar pemilih'],
        ['Pendaftaran & Verifikasi', $election->registration_open ? 'Pendaftaran dibuka' : ($election->status === 'draft' ? 'Pendaftaran ditutup' : 'Tahap selesai')],
        ['Pemungutan Suara', $election->starts_at ? $election->starts_at->copy()->timezone('Asia/Makassar')->format('d M Y H:i').' – '.$election->ends_at?->copy()->timezone('Asia/Makassar')->format('d M Y H:i').' WITA' : 'Jadwal belum ditetapkan'],
        ['Pengesahan Hasil', $election->certified_at?->copy()->timezone('Asia/Makassar')->format('d M Y') ?? 'Belum disahkan'],
        ['Publikasi Hasil', $election->published_at?->copy()->timezone('Asia/Makassar')->format('d M Y') ?? 'Belum dipublikasikan'],
    ];
@endphp
<x-role-dashboard name="kpum" variant="election" eyebrow="Pusat Pemilihan HIMSI" :title="$election->title.' '.$election->period" description="Sistem informasi pelaksanaan pemilihan Ketua HIMSI berdasarkan data yang tercatat.">
    <x-dashboard-stats :items="['Status Pemilihan' => $election->label(), 'Kandidat Disetujui' => $tally->count(), 'Pemilih Terdaftar' => $eligible, 'Suara Masuk' => $total]" :icons="['calendar-check', 'person-check', 'people', 'box-seam']" />
    <div class="rd-notice"><i class="bi bi-exclamation-triangle"></i><strong>{{ $election->label() }}.</strong>
        @if($election->acceptingVotes())
            Pantau proses pemungutan suara hingga batas waktu yang ditetapkan.
        @elseif($election->status === 'published')
            Hasil pemilihan telah dipublikasikan.
        @else
            Hasil suara ditampilkan setelah hasil pemilihan dipublikasikan.
        @endif
    </div>
    <div class="rd-grid rd-grid-election">
        <x-dashboard-panel title="Partisipasi Pemilih" icon="people"><x-dashboard-donut :segments="$participation" :center="$percent.'%'" caption="Partisipasi" /><p class="rd-empty-note">Total {{ $eligible }} pemilih terdaftar.</p></x-dashboard-panel>
        <x-dashboard-panel title="Tahapan Pemilihan" icon="layers" :href="route('kpum.control')" link-label="Lihat tahapan">
            <ol class="rd-timeline">@foreach($stages as $item)<li class="{{ $loop->index < $stage || ($loop->index === 4 && $election->status === 'published') ? 'complete' : ($loop->index === $stage ? 'current' : '') }}"><span class="rd-step"><i class="bi bi-{{ $loop->index < $stage || $election->status === 'published' ? 'check-lg' : 'circle-fill' }}"></i></span><b>{{ $item[0] }}</b><small>{{ $item[1] }}</small></li>@endforeach</ol>
            @if($pending)<p class="rd-empty-note">{{ $pending }} kandidat menunggu verifikasi.</p>@endif
        </x-dashboard-panel>
    </div>
    <div class="rd-grid rd-grid-wide">
        <x-dashboard-panel title="Kandidat Ketua HIMSI" icon="people" :href="route('kpum.candidates')" link-label="Lihat Semua Kandidat">
            <div class="rd-scroll"><table class="rd-table"><thead><tr><th>No.</th><th>Nama Kandidat</th><th>NIM</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            @forelse($tally as $candidate)<tr><td>{{ $candidate->number }}</td><td><strong>{{ $candidate->name }}</strong></td><td>{{ $candidate->nim }}</td><td><span class="rd-pill green">Disetujui</span></td><td><a class="rd-action" href="{{ route('kpum.candidates.edit', $candidate) }}">Lihat Detail</a></td></tr>
            @empty<tr><td colspan="5" class="rd-empty">Belum ada kandidat yang disetujui.</td></tr>@endforelse
            </tbody></table></div>
        </x-dashboard-panel>
        <x-dashboard-panel title="Berita Pemilihan" icon="file-earmark-text" :href="route('kpum.announcement')" link-label="Lihat pengumuman">
            @forelse($news as $item)<div class="rd-list-row"><span class="rd-icon"><i class="bi bi-file-earmark-text"></i></span><div><strong>{{ $item->payload['title'] ?? 'Hasil pemilihan' }}</strong><small>{{ $item->payload['date'] ?? $item->created_at->format('d M Y') }}</small></div><span class="rd-pill green">Dipublikasikan</span><a class="rd-action" href="{{ route('kpum.announcement') }}">Buka</a></div>@empty<p class="rd-empty">Belum ada berita pemilihan yang dipublikasikan.</p>@endforelse
        </x-dashboard-panel>
    </div>
    @if($election->status === 'published')
    <x-dashboard-panel title="Hasil Pemilihan" icon="bar-chart">
        @foreach($tally as $candidate)<div class="rd-results"><div class="d-flex justify-content-between"><strong>{{ $candidate->name }}</strong><span>{{ $candidate->ballots_count }} suara ({{ $total ? round($candidate->ballots_count / $total * 100, 1) : 0 }}%)</span></div><progress value="{{ $candidate->ballots_count }}" max="{{ max(1, $total) }}" aria-label="Perolehan suara {{ $candidate->name }}"></progress></div>@endforeach
    </x-dashboard-panel>
    @endif
</x-role-dashboard>
@endsection
