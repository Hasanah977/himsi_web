@extends('admin.layout')
@section('title', 'Periode Kepengurusan')
@section('content')
<section class="admin-panel governance-heading">
    <span class="workspace-icon"><i class="bi bi-calendar2-range" aria-hidden="true"></i></span>
    <div><span class="workspace-eyebrow">REGENERASI HIMSI</span><h2>Periode Kepengurusan</h2><p class="mb-0">Kelola masa bakti pengurus dan simpan perjalanan organisasi dalam arsip.</p></div>
</section>
@if($active)
<section class="admin-panel mt-4">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4"><div><span class="workspace-eyebrow">PERIODE BERJALAN</span><h2 class="mt-2">{{ $active->name }}</h2></div><span class="workspace-badge success"><i class="bi bi-circle-fill" aria-hidden="true"></i> Aktif</span></div>
    <div class="governance-facts">
        <div><small>Mulai masa bakti</small><strong>{{ \Illuminate\Support\Carbon::parse($active->starts_at)->translatedFormat('d F Y') }}</strong></div>
        <div><small>Akhir masa bakti</small><strong>{{ \Illuminate\Support\Carbon::parse($active->ends_at)->translatedFormat('d F Y') }}</strong></div>
        <div><small>Jabatan terisi</small><strong>{{ $officerCount }} pengurus</strong></div>
    </div>
    <div class="governance-end mt-4">
        <h3 class="h5"><i class="bi bi-calendar2-check me-2" aria-hidden="true"></i>Akhiri masa kepengurusan</h3>
        <p>Seluruh akses jabatan pengurus akan dicabut dan kembali menjadi KBH. Akun anggota tetap tersedia. Data periode ini dikunci sebagai arsip yang hanya dapat dibaca.</p>
        <form method="POST" action="{{ route('admin.governance.end', $active->id) }}" data-confirm="Akhiri periode {{ $active->name }}? Seluruh akses jabatan pengurus akan dicabut dan data menjadi arsip baca. Tindakan ini tidak dapat dibatalkan.">
            @csrf
            <label class="d-flex align-items-start gap-2 mb-3"><input class="form-check-input" type="checkbox" name="confirmed" value="1" required><span>Saya memahami dan menyetujui pengakhiran periode ini.</span></label>
            <button class="btn btn-outline-danger" type="submit"><i class="bi bi-calendar2-x me-2" aria-hidden="true"></i>Akhiri Kepengurusan</button>
        </form>
    </div>
</section>
@else
<section class="admin-panel mt-4">
    <h2 class="h5">Tetapkan periode kepengurusan</h2><p>Masa jabatan berlangsung satu tahun. Gunakan tanggal mulai resmi kepengurusan yang sedang berjalan.</p>
    <form method="POST" action="{{ route('admin.governance.store') }}">@csrf
        <div class="row g-4 mb-4"><div class="col-md-6"><label class="form-label" for="period-name">Nama periode</label><input id="period-name" class="form-control" name="name" value="{{ old('name') }}" maxlength="80" placeholder="Contoh: 2026/2027" required></div>
        <div class="col-md-6"><label class="form-label" for="period-start">Tanggal mulai resmi</label><input id="period-start" class="form-control" type="date" name="starts_at" value="{{ old('starts_at') }}" max="{{ now()->toDateString() }}" required></div></div>
        <button class="btn btn-navy" type="submit"><i class="bi bi-calendar2-plus me-2" aria-hidden="true"></i>Tetapkan Periode</button>
    </form>
</section>
@endif
<section class="admin-panel mt-4"><h2 class="h5 mb-4">Riwayat kepengurusan</h2>
@forelse($periods as $item)
    <div class="governance-history"><span class="workspace-icon"><i class="bi bi-{{ $item->closed_at ? 'archive' : 'calendar2-range' }}" aria-hidden="true"></i></span><div><strong>{{ $item->name }}</strong><small>{{ \Illuminate\Support\Carbon::parse($item->starts_at)->translatedFormat('d M Y') }} — {{ \Illuminate\Support\Carbon::parse($item->ends_at)->translatedFormat('d M Y') }}</small></div><span class="workspace-badge {{ $item->closed_at ? '' : 'success' }}">{{ $item->closed_at ? 'Selesai · Hanya baca' : 'Aktif' }}</span></div>
@empty<p class="text-secondary mb-0">Belum ada periode. Mulai dengan menetapkan masa kepengurusan pertama.</p>@endforelse
</section>
@endsection
