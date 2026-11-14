<a class="workspace-notification" href="{{ route('administration.notifications') }}" aria-label="Notifikasi, {{ $headerUnreadCount }} belum dibaca">
    <i class="bi bi-bell" aria-hidden="true"></i>
    @if($headerUnreadCount)
        <span class="workspace-notification-count">{{ $headerUnreadCount > 99 ? '99+' : $headerUnreadCount }}</span>
    @endif
</a>
