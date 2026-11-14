@if(auth()->user()->canSwitchWorkspace() && auth()->user()->role !== 'kbh' || auth()->user()->isSuperAdmin())
<a class="admin-nav-link {{ request()->routeIs('governance.*') ? 'active' : '' }}" href="{{ route('governance.archive', ['type' => match(auth()->user()->role) { 'sekretaris_umum' => 'surat-keluar', 'bendahara_umum' => 'keuangan', default => 'program' }]) }}">Arsip Kepengurusan</a>
@endif
