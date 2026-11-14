@extends('layouts.koordinator-pengawas')
@section('title','Hasil Audit')
@section('page-title','Hasil Audit')
@section('content')
<div class="pg-card"><h2 class="h5 fw-bold mb-1">Riwayat Hasil Audit</h2><p class="pg-muted">Setiap audit tercatat bersama nama auditor dan waktu pemeriksaan.</p><div class="table-responsive"><table class="table pg-table"><thead><tr><th>Waktu</th><th>Kegiatan</th><th>Hasil</th><th>Auditor</th><th>Catatan</th><th>Aksi</th></tr></thead><tbody>
@forelse($audits as $audit)<tr><td>{{ $audit->created_at->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i') }}</td><td>{{ $audit->laporan?->nama_kegiatan }}</td><td><span class="pg-status">{{ ucfirst(str_replace('_',' ',$audit->hasil)) }}</span></td><td>{{ $audit->auditor?->nama_lengkap }}</td><td>{{ \Illuminate\Support\Str::limit($audit->catatan,100) }}</td><td><a href="{{ route('pengawas.laporan.show',$audit->laporan_id) }}">Detail</a></td></tr>
@empty<tr><td colspan="6" class="text-center text-muted">Belum ada audit tersimpan.</td></tr>@endforelse
</tbody></table></div>{{ $audits->links() }}</div>
@endsection
