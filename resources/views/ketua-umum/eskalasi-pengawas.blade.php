@extends('layouts.ketua-umum')
@section('title','Eskalasi Pengawas')
@section('content')
<div class="ketua-panel"><h2 class="h5 fw-bold">Eskalasi Koordinator Pengawas</h2><p class="text-secondary">Ketua Umum memeriksa kasus yang dieskalasikan dan memberikan keputusan final.</p><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Kegiatan</th><th>Pengaju</th><th>Alasan</th><th>Status</th><th>Keputusan</th></tr></thead><tbody>
@forelse($eskalasis as $e)<tr><td>{{ $e->laporan?->nama_kegiatan }}</td><td>{{ $e->pengaju?->nama_lengkap }}</td><td>{{ $e->alasan }}</td><td>{{ ucfirst($e->status) }}</td><td>@if($e->status==='menunggu')<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#keputusan-{{ $e->id }}">Beri Keputusan</button>@else{{ $e->keputusan }}@endif</td></tr>@empty<tr><td colspan="5" class="text-center text-secondary">Belum ada eskalasi dari pengawas.</td></tr>@endforelse
</tbody></table></div>{{ $eskalasis->links() }}</div>
@foreach($eskalasis as $e)
@if($e->status === 'menunggu')
<div class="modal fade" id="keputusan-{{ $e->id }}" tabindex="-1" aria-labelledby="keputusan-title-{{ $e->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h2 class="modal-title fs-5" id="keputusan-title-{{ $e->id }}">Keputusan Eskalasi</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
        <div class="modal-body"><p class="fw-semibold">{{ $e->laporan?->nama_kegiatan }}</p><p class="text-secondary">{{ $e->alasan }}</p>
        <form method="post" action="{{ route('ketua-umum.eskalasi-pengawas.putuskan',$e) }}">@csrf<label class="form-label" for="status-{{ $e->id }}">Status keputusan</label><select id="status-{{ $e->id }}" required name="status" class="form-select form-select-sm mb-2"><option value="ditindaklanjuti">Perlu ditindaklanjuti</option><option value="selesai">Selesai</option></select><label class="form-label" for="note-{{ $e->id }}">Keputusan Ketua Umum</label><textarea id="note-{{ $e->id }}" name="keputusan" required class="form-control form-control-sm mb-2" rows="3" placeholder="Keputusan Ketua Umum">{{ old('keputusan') }}</textarea><button type="submit" class="btn btn-sm btn-primary">Simpan Keputusan</button></form>
        </div>
    </div></div>
</div>
@endif
@endforeach
@endsection
