@extends('layouts.koordinator-pengawas')
@section('title','Eskalasi ke Ketua')
@section('page-title','Eskalasi ke Ketua / Forum')
@section('content')
<div class="pg-card"><h2 class="h5 fw-bold">Daftar Eskalasi</h2><p class="pg-muted">Kasus yang disampaikan kepada Ketua Umum untuk diputuskan.</p><div class="table-responsive"><table class="table pg-table"><thead><tr><th>Tanggal</th><th>Kegiatan</th><th>Alasan</th><th>Status</th><th>Keputusan Ketua</th></tr></thead><tbody>
@forelse($eskalasis as $e)<tr><td>{{ $e->created_at->format('d/m/Y') }}</td><td><a href="{{ route('pengawas.laporan.show',$e->laporan_id) }}">{{ $e->laporan?->nama_kegiatan }}</a></td><td>{{ $e->alasan }}</td><td><span class="pg-status">{{ ucfirst($e->status) }}</span></td><td>{{ $e->keputusan ?: 'Belum ada keputusan' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">Belum ada eskalasi.</td></tr>@endforelse
</tbody></table></div>{{ $eskalasis->links() }}</div>
@endsection
