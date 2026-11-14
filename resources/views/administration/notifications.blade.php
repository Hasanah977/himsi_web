@extends(match(auth()->user()->isSuperAdmin() ? 'super_admin' : auth()->user()->role) {
    'super_admin' => 'admin.layout',
    'panitia_cb' => 'cb.layout',
    'panitia_kpum' => 'kpum.layout',
    'koordinator_departemen', 'anggota_departemen' => 'department.layout',
    default => 'administration.layout',
})
@section('title', 'Notifikasi')
@section('content')
<div class="notification-toolbar">
    <nav class="notification-tabs" aria-label="Filter notifikasi">
        @foreach(['' => ['Semua Notifikasi', $totalCount], 'unread' => ['Belum Dibaca', $unreadCount], 'read' => ['Sudah Dibaca', $totalCount - $unreadCount]] as $filter => [$label, $count])
        <a class="{{ request('filter', '') === $filter ? 'active' : '' }}" href="{{ request()->url().($filter ? '?filter='.$filter : '') }}" @if(request('filter', '') === $filter) aria-current="page" @endif>{{ $label }} <span>({{ $count }})</span></a>
        @endforeach
    </nav>
    <div class="d-flex flex-wrap gap-2"><form method="POST" action="{{ route('notifikasi-himsi.read-all') }}">@csrf<button class="btn btn-outline-navy" @disabled(!$unreadCount)><i class="bi bi-check2 me-1"></i>Tandai Semua Dibaca</button></form><button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAllNotifications" @disabled(!$totalCount)><i class="bi bi-trash me-1"></i>Hapus Semua</button></div>
</div>
<div class="notification-feed">
@forelse($items as $item)
<article class="notification-card {{ $item->dibaca_pada ? 'is-read' : 'is-unread' }}">
    <span class="notification-state-dot" aria-hidden="true"></span>
    <div class="notification-symbol"><i class="bi bi-bell" aria-hidden="true"></i></div>
    <div class="notification-body">
        <div class="notification-heading"><h2>{{ $item->judul }}</h2><span class="badge {{ $item->dibaca_pada ? 'text-bg-secondary' : 'text-bg-primary' }}">{{ $item->dibaca_pada ? 'Sudah dibaca' : 'Belum dibaca' }}</span></div>
        <p>{{ $item->pesan }}</p>
        <time datetime="{{ $item->created_at->toIso8601String() }}">{{ $item->created_at->copy()->timezone('Asia/Makassar')->format('d M Y · H:i') }} WITA</time>
        <div class="notification-actions">
            @if($item->url)<form method="POST" action="{{ route('notifikasi-himsi.baca', $item) }}">@csrf<button class="btn btn-navy"><i class="bi bi-box-arrow-up-right me-1"></i>Buka Detail</button></form>@endif
            @unless($item->dibaca_pada)<form method="POST" action="{{ route('notifikasi-himsi.baca', $item) }}">@csrf<input type="hidden" name="mark_only" value="1"><button class="btn btn-outline-navy"><i class="bi bi-check-circle me-1"></i>Tandai Dibaca</button></form>@endunless
            <form method="POST" action="{{ route('notifikasi-himsi.destroy', $item) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger" aria-label="Hapus notifikasi {{ $item->judul }}"><i class="bi bi-trash me-1"></i>Hapus</button></form>
        </div>
    </div>
</article>
@empty
<div class="admin-panel k-panel workspace-empty notification-empty"><i class="bi bi-bell" aria-hidden="true"></i><h2>{{ request('filter') ? 'Tidak ada notifikasi '.(request('filter') === 'unread' ? 'yang belum dibaca' : 'yang sudah dibaca') : 'Belum Ada Notifikasi' }}</h2><p>Notifikasi baru akan muncul di sini ketika ada informasi<br>atau aktivitas terbaru.</p></div>
@endforelse
</div>
{{ $items->links() }}
<div class="modal fade" id="deleteAllNotifications" tabindex="-1" aria-labelledby="deleteAllTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content notification-confirm"><div class="modal-header border-0"><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body text-center"><span class="notification-delete-icon"><i class="bi bi-trash"></i></span><h2 class="h4 mt-3" id="deleteAllTitle">Hapus Semua Notifikasi?</h2><p>Apakah Anda yakin ingin menghapus seluruh {{ $totalCount }} notifikasi?<br>Notifikasi yang dihapus tidak dapat dikembalikan melalui halaman ini.</p></div><form class="modal-footer border-0 justify-content-center pb-4" action="{{ route('notifikasi-himsi.destroy-all') }}" method="POST">@csrf @method('DELETE')<input type="hidden" name="confirmed" value="1"><button type="button" class="btn btn-outline-navy" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger"><i class="bi bi-trash me-1"></i>Ya, Hapus Semua</button></form></div></div></div>
@endsection
