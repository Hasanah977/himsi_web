@extends('administration.layout')
@section('title', 'Rekomendasi Pengawas')
@section('content')
@forelse($items as $item)<article class="admin-panel"><h2 class="h5">{{ $item->laporan->judul }}</h2><p>{{ $item->rekomendasi }}</p><p>Status: {{ $item->status }} · Tenggat: {{ $item->tenggat?->format('d M Y') ?? '—' }}</p>@if($item->status!=='selesai')<form method="POST" action="{{ route('administration.recommendations.respond',$item) }}">@csrf<label for="note-{{ $item->id }}" class="form-label">Tindak lanjut departemen</label><textarea id="note-{{ $item->id }}" class="form-control mb-3" name="catatan_tindak_lanjut" required maxlength="6000">{{ $item->catatan_tindak_lanjut }}</textarea><button class="btn btn-navy" type="submit">Kirim ke Pengawas</button></form>@else<p>{{ $item->catatan_tindak_lanjut }}</p>@endif</article>@empty<div class="admin-panel">Belum ada rekomendasi untuk departemen Anda.</div>@endforelse{{ $items->links() }}
@endsection
