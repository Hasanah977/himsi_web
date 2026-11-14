@extends('cb.layout')
@section('title', 'Dashboard Panitia CB')
@section('content')
@php
    $review = (int) ($counts['pending'] ?? 0);
    $returned = (int) ($counts['returned'] ?? 0);
    $approved = (int) ($counts['approved'] ?? 0);
    $rejected = (int) ($counts['rejected'] ?? 0);
    $segments = [
        ['label' => 'Perlu Review', 'value' => $review, 'color' => '#ffd51c'],
        ['label' => 'Dikembalikan', 'value' => $returned, 'color' => '#ef6464'],
        ['label' => 'Lolos KBH', 'value' => $approved, 'color' => '#3bb574'],
        ['label' => 'Ditolak', 'value' => $rejected, 'color' => '#a9b1bf'],
    ];
@endphp
<x-role-dashboard name="cb" eyebrow="Pusat Seleksi Anggota HIMSI" title="Seleksi Anggota HIMSI" description="Pantau pendaftaran, verifikasi berkas, dan hasil seleksi dari data yang tercatat.">
    <x-dashboard-stats :items="['Pendaftar Masuk' => $total, 'Berkas Perlu Ditinjau' => $review, 'Lolos Seleksi' => $approved, 'Dikembalikan untuk Perbaikan' => $returned]" :icons="['people', 'file-earmark-search', 'check-circle', 'arrow-repeat']" />
    <div class="rd-section-heading"><h2>Ringkasan Seleksi</h2><p>Seluruh pendaftaran · Data diperbarui dari sistem.</p></div>
    <div class="rd-grid rd-dark-panels">
        <x-dashboard-panel title="Status Pendaftaran" icon="list-ul"><x-dashboard-donut :segments="$segments" caption="Pendaftar" /></x-dashboard-panel>
        <x-dashboard-panel title="Tahapan Seleksi" icon="diagram-3">
            <ol class="rd-timeline rd-timeline-vertical">
                <li class="{{ $total ? 'complete' : '' }}"><span class="rd-step"><i class="bi bi-person-plus"></i></span><b>Pendaftaran Tercatat</b><span class="rd-pill">{{ $total }} pendaftar</span></li>
                <li class="{{ $review ? 'current' : '' }}"><span class="rd-step"><i class="bi bi-file-earmark-search"></i></span><b>Verifikasi Berkas</b><span class="rd-pill">{{ $review }} perlu review</span></li>
                <li class="{{ $returned ? 'current' : '' }}"><span class="rd-step"><i class="bi bi-arrow-repeat"></i></span><b>Perbaikan Berkas</b><span class="rd-pill red">{{ $returned }} dikembalikan</span></li>
                <li class="{{ $approved + $rejected ? 'complete' : '' }}"><span class="rd-step"><i class="bi bi-check-lg"></i></span><b>Keputusan Akhir</b><span class="rd-pill green">{{ $approved + $rejected }} diputuskan</span></li>
            </ol>
            <p class="rd-empty-note">Jumlah menunjukkan posisi pendaftar pada proses seleksi.</p>
        </x-dashboard-panel>
        <x-dashboard-panel title="Pendaftar Perlu Ditinjau" icon="list-ul" :href="route('cb.recruitment', ['status' => 'pending'])">
            <div class="rd-scroll"><table class="rd-table"><thead><tr><th>No.</th><th>Nama Pendaftar</th><th>NIM</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                @forelse($pendingRegistrations as $registration)<tr><td>{{ $loop->iteration }}</td><td>{{ $registration->user?->name ?? 'Akun tidak tersedia' }}</td><td>{{ $registration->nim }}</td><td><span class="rd-pill">Perlu Review</span></td><td><a class="rd-action solid" href="{{ route('cb.recruitment.show', $registration) }}">Tinjau</a></td></tr>
                @empty<tr><td colspan="5" class="rd-empty">Tidak ada pendaftar menunggu review.</td></tr>@endforelse
            </tbody></table></div>
        </x-dashboard-panel>
        <x-dashboard-panel title="Berkas & Hasil Seleksi" icon="check-circle">
            <div class="rd-list-row"><span class="rd-icon"><i class="bi bi-file-earmark-check"></i></span><div><strong>Tiga berkas telah diunggah</strong><small>Kelengkapan unggahan, belum tentu lolos verifikasi.</small></div><b>{{ $completeDocuments }}</b><a class="rd-action" href="{{ route('cb.recruitment') }}">Lihat</a></div>
            <div class="rd-list-row"><span class="rd-icon"><i class="bi bi-file-earmark-exclamation"></i></span><div><strong>Berkas perlu perbaikan</strong></div><b>{{ $returned }}</b><a class="rd-action" href="{{ route('cb.recruitment', ['status' => 'returned']) }}">Lihat</a></div>
            <div class="rd-list-row"><span class="rd-icon"><i class="bi bi-trophy"></i></span><div><strong>Hasil seleksi disetujui</strong></div><b>{{ $approved }}</b><a class="rd-action" href="{{ route('cb.recruitment', ['status' => 'approved']) }}">Lihat</a></div>
        </x-dashboard-panel>
    </div>
</x-role-dashboard>
@endsection
