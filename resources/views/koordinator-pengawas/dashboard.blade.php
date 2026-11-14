@extends('administration.layout')
@section('title', 'Dashboard Koordinator Pengawas')
@section('content')
<x-role-dashboard name="supervisor" eyebrow="Pusat Pengawasan Organisasi" :title="'Selamat datang, '.auth()->user()->name" description="Pantau laporan, rekomendasi, dan tindak lanjut organisasi dari data yang tercatat.">
    <x-dashboard-stats :items="$stats" :icons="['file-earmark-text', 'award', 'box-arrow-up', 'check-circle']" />
    <div class="rd-section-heading"><h2>Ringkasan Pengawasan</h2><p>Data diperbarui dari sistem.</p></div>
    <div class="rd-grid">
        <x-dashboard-panel title="Status Laporan Pengawasan" icon="file-earmark-text"><x-dashboard-donut :segments="$reportSegments" caption="Laporan" /></x-dashboard-panel>
        <x-dashboard-panel title="Rekomendasi Aktif" icon="award">
            <div class="rd-scroll"><table class="rd-table"><thead><tr><th>Rekomendasi</th><th>Departemen</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                @forelse($recommendations as $item)<tr><td>{{ Str::limit($item->rekomendasi, 75) }}</td><td>{{ config('superadmin.departments.'.$item->laporan?->departemen, $item->laporan?->departemen ?? '—') }}</td><td><span class="rd-pill {{ $item->status === 'ditindaklanjuti' ? 'blue' : '' }}">{{ $item->status === 'ditindaklanjuti' ? 'Ditindaklanjuti' : 'Menunggu tindak lanjut' }}</span></td><td><a class="rd-action" href="{{ route('pengawas.laporan.show', $item->laporan_id) }}">Buka</a></td></tr>
                @empty<tr><td colspan="4" class="rd-empty">Tidak ada rekomendasi aktif.</td></tr>@endforelse
            </tbody></table></div>
        </x-dashboard-panel>
        <x-dashboard-panel title="Eskalasi ke Ketua Umum" icon="box-arrow-up">
            <div class="rd-scroll"><table class="rd-table"><thead><tr><th>Perihal Eskalasi</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                @forelse($escalations as $item)<tr><td>{{ $item->laporan?->judul ?? Str::limit($item->alasan, 65) }}</td><td><span class="rd-pill">Menunggu keputusan Ketua</span></td><td><a class="rd-action" href="{{ route('pengawas.eskalasi.index') }}">Lihat</a></td></tr>
                @empty<tr><td colspan="3" class="rd-empty">Tidak ada eskalasi menunggu.</td></tr>@endforelse
            </tbody></table></div>
        </x-dashboard-panel>
        <x-dashboard-panel title="Monitoring Program" icon="layers">
            <div class="rd-mini-stats"><div>Program dipantau<strong>{{ $monitored }}</strong></div><div>Program perlu perbaikan<strong>{{ $needsAttention }}</strong></div><div>Program selesai dipantau<strong>{{ $completedPrograms }}</strong></div></div>
            <div class="rd-panel-footer"><a class="rd-action" href="{{ route('administration.programs') }}">Lihat Monitoring →</a></div>
        </x-dashboard-panel>
    </div>
    <x-dashboard-panel title="Laporan Pengawasan Terbaru" icon="file-earmark-text" :href="route('pengawas.laporan.index')">
        <div class="rd-scroll"><table class="rd-table"><thead><tr><th>Kegiatan</th><th>Departemen</th><th>Status</th><th>Dibuat</th><th>Aksi</th></tr></thead><tbody>
            @forelse($reports as $report)<tr><td><strong>{{ $report->judul }}</strong><small>{{ $report->nama_kegiatan }}</small></td><td>{{ config('superadmin.departments.'.$report->departemen, $report->departemen ?? '—') }}</td><td><span class="rd-pill {{ $report->status === 'selesai' ? 'green' : 'blue' }}">{{ ['masuk' => 'Menunggu Tinjauan', 'proses' => 'Dalam Tindak Lanjut', 'perlu_perbaikan' => 'Perlu Perbaikan', 'selesai' => 'Selesai'][$report->status] ?? $report->status }}</span></td><td>{{ $report->created_at->timezone('Asia/Makassar')->format('d M Y H:i') }}</td><td><a class="rd-action" href="{{ route('pengawas.laporan.show', $report) }}">Buka</a></td></tr>
            @empty<tr><td colspan="5" class="rd-empty">Belum ada laporan pengawasan tercatat.</td></tr>@endforelse
        </tbody></table></div>
    </x-dashboard-panel>
</x-role-dashboard>
@endsection
