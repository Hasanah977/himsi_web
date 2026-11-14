<script type="application/json" id="sidebar-unread-urls">@json($sidebarUnreadUrls)</script>
@if(auth()->user()->canSwitchWorkspace())
<form class="workspace-switch" method="POST" action="{{ route('workspace.update') }}">
    @csrf
    <span class="workspace-switch-caption">RUANG KERJA</span>
    <div class="workspace-switch-options"><span>KBH</span><span>{{ config('superadmin.roles.'.auth()->user()->assignedRole()) }}</span></div>
    <input type="hidden" name="role" value="{{ auth()->user()->role === 'kbh' ? auth()->user()->assignedRole() : 'kbh' }}">
    <button type="submit" class="workspace-switch-control" role="switch" aria-checked="{{ auth()->user()->role === 'kbh' ? 'false' : 'true' }}" aria-label="Beralih antara KBH dan {{ config('superadmin.roles.'.auth()->user()->assignedRole()) }}"><span class="workspace-switch-track"><span></span></span><span>{{ auth()->user()->role === 'kbh' ? 'Mode KBH' : config('superadmin.roles.'.auth()->user()->assignedRole()) }}</span></button>
</form>
@endif

