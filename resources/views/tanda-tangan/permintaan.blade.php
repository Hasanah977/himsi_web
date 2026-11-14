@extends('administration.layout')
@section('title', 'Administrasi HIMSI')
@push('styles')

@endpush
@section('content')
<h2 class="fw-bold"><i class="bi bi-pen-fill me-2"></i>Permintaan Persetujuan Tanda Tangan</h2><p>Hanya surat yang telah disetujui Ketua Umum dan menunggu persetujuan Anda yang dapat diproses.</p><div class="card border-0 shadow-sm rounded-4"><div class="table-responsive"><table class="table mb-0 align-middle"><thead><tr><th>Perihal</th><th>Nomor</th><th>Keputusan Anda</th><th></th></tr></thead><tbody>@forelse($permintaan as $p)<tr><td>{{ $p->suratKeluar?->perihal }}</td><td>{{ $p->suratKeluar?->nomor_surat }}</td><td>{{ ucfirst($p->status) }}</td><td>@if($p->suratKeluar)<a class="btn btn-primary btn-sm" href="{{ route('tanda-tangan.persetujuan.show',$p->suratKeluar) }}">Detail</a>@endif</td></tr>@empty<tr><td colspan="4" class="text-center py-4">Belum ada permintaan.</td></tr>@endforelse</tbody></table></div></div><div class="mt-3">{{ $permintaan->links() }}</div>
@endsection
@push('scripts')

@endpush
