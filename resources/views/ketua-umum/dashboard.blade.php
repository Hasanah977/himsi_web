@extends('administration.layout')
@section('title', 'Dashboard Ketua Umum')
@section('content')
<x-role-dashboard name="chair" eyebrow="Pusat Keputusan Organisasi" :title="'Selamat datang, '.auth()->user()->name" description="Pantau keputusan, progres departemen, dan kondisi organisasi dari data yang tercatat.">
    <x-dashboard-stats :items="$stats" :icons="['hammer', 'file-earmark-check', 'layers', 'exclamation-triangle']" />
    <div class="rd-section-heading"><h2>Ringkasan Kepemimpinan</h2><p>Data diperbarui dari sistem.</p></div>
    <div class="rd-grid">
        <x-dashboard-panel title="Kesehatan Program Kerja" icon="bar-chart-fill">
            @php
                $maximum = max(1, $departments->max(fn ($item) => $item['waiting'] + $item['running'] + $item['completed']));
            @endphp
            <div class="rd-horizontal-bars">
            @foreach($departments as $department)
                <div class="rd-bar-row"><span>{{ $department['label'] }}</span><div class="rd-bar-track">
                    @foreach(['waiting' => '#ffd51c', 'running' => '#193565', 'completed' => '#2aa260'] as $key => $color)
                        @if($department[$key])<span style="width:{{ $department[$key] / $maximum * 100 }}%;background:{{ $color }};color:{{ $key === 'waiting' ? '#182d50' : '#fff' }}" title="{{ $department['label'] }}: {{ $department[$key] }}">{{ $department[$key] }}</span>@endif
                    @endforeach
                </div><b>{{ $department['waiting'] + $department['running'] + $department['completed'] }}</b></div>
            @endforeach
            </div>
            <div class="rd-chart-key"><span><i style="background:#ffd51c"></i>Persiapan / menunggu</span><span><i style="background:#193565"></i>Berjalan</span><span><i style="background:#2aa260"></i>Selesai</span></div>
            <div class="rd-panel-footer"><a class="rd-action" href="{{ route('ketua-umum.monitoring-organisasi') }}">Lihat Monitoring →</a></div>
        </x-dashboard-panel>
        <x-dashboard-panel title="Perlu Keputusan Ketua" icon="hammer">
            @forelse($decisions as $decision)
                <div class="rd-list-row"><span class="rd-icon"><i class="bi bi-file-earmark-text"></i></span><div><strong>{{ $decision['title'] }}</strong><small>{{ Str::limit($decision['detail'], 65) }}</small></div><span class="rd-pill">{{ $decision['status'] }}</span><a class="rd-action solid" href="{{ $decision['url'] }}">Tinjau</a></div>
            @empty<p class="rd-empty">Tidak ada keputusan yang menunggu.</p>@endforelse
        </x-dashboard-panel>
        <x-dashboard-panel title="Eskalasi & Risiko" icon="exclamation-triangle">
            @forelse($escalations as $item)
                <div class="rd-list-row"><span class="rd-icon"><i class="bi bi-exclamation-triangle"></i></span><div><strong>{{ $item->laporan?->judul ?? 'Eskalasi pengawas' }}</strong><small>{{ Str::limit($item->alasan, 90) }}</small></div><span class="rd-pill red">Menunggu keputusan</span><a class="rd-action solid" href="{{ route('ketua-umum.eskalasi-pengawas.index') }}">Buka</a></div>
            @empty<p class="rd-empty">Tidak ada eskalasi aktif.</p>@endforelse
        </x-dashboard-panel>
        <x-dashboard-panel title="Persetujuan Surat Terbaru" icon="check-circle">
            @forelse($recentApprovals as $letter)
                <div class="rd-list-row"><span class="rd-icon"><i class="bi bi-file-earmark-check"></i></span><div><strong>{{ $letter->perihal }}</strong><small>{{ $letter->nomor_surat ?: 'Nomor belum tersedia' }} · {{ $letter->diputuskan_pada?->format('d M Y') }}</small></div><span class="rd-pill {{ $letter->status === 'disetujui' ? 'green' : 'red' }}">{{ ucfirst($letter->status) }}</span><a class="rd-action" href="{{ route('ketua-umum.persetujuan-surat.show', $letter) }}">Lihat</a></div>
            @empty<p class="rd-empty">Belum ada keputusan surat tercatat.</p>@endforelse
        </x-dashboard-panel>
    </div>
</x-role-dashboard>
@endsection
