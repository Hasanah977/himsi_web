@extends('layouts.koordinator-pengawas')
@section('title','Teguran & Rekomendasi')
@section('page-title','Teguran dan Rekomendasi')
@section('content')
<div class="pg-card"><h2 class="h5 fw-bold">Tindak Lanjut Rekomendasi</h2><p class="pg-muted">Perbarui hasil evaluasi dan tindak lanjut. Tindakan ini tidak otomatis mengubah data dari proyek Operations atau Keuangan.</p><div class="table-responsive"><table class="table pg-table"><thead><tr><th>Kegiatan</th><th>Rekomendasi</th><th>Tenggat</th><th>Status</th><th>Evaluasi ulang</th></tr></thead><tbody>
@forelse($rekomendasis as $r)<tr><td><a href="{{ route('pengawas.laporan.show',$r->laporan_id) }}">{{ $r->laporan?->nama_kegiatan }}</a></td><td>{{ $r->rekomendasi }}</td><td>{{ $r->tenggat?->format('d/m/Y') ?? '—' }}</td><td><span class="pg-status">{{ ucfirst($r->status) }}</span></td><td><form action="{{ route('pengawas.rekomendasi.tindak-lanjut',$r) }}" method="post">@csrf <select name="status" class="form-select form-select-sm mb-1"><option value="terbuka" @selected($r->status==='terbuka')>Terbuka</option><option value="ditindaklanjuti" @selected($r->status==='ditindaklanjuti')>Ditindaklanjuti</option><option value="selesai" @selected($r->status==='selesai')>Selesai</option></select><textarea name="catatan_tindak_lanjut" rows="2" class="form-control form-control-sm mb-1" required placeholder="Catatan evaluasi">{{ $r->catatan_tindak_lanjut }}</textarea><button type="submit" class="btn btn-sm btn-primary">Simpan</button></form></td></tr>
@empty<tr><td colspan="5" class="text-muted text-center">Belum ada rekomendasi.</td></tr>@endforelse
</tbody></table></div>{{ $rekomendasis->links() }}</div>
@endsection
