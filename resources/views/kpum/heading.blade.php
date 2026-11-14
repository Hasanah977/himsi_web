<div class="k-heading">
    <div><div class="k-eyebrow">PANITIA KPUM · HIMSI UNSULBAR</div><h1>{{ $election->title }}</h1><p>Periode {{ $election->period ?: 'belum diatur' }} <span class="mx-1">·</span> <span data-k-stat="eligible">{{ $eligible }}</span> pemilih terdaftar</p></div>
    <span class="k-badge {{ $election->acceptingVotes() ? 'green' : '' }}" data-k-stat="label">{{ $election->label() }}</span>
</div>
<nav class="k-tabs" aria-label="Tahapan pemilihan">
    @foreach(['candidates' => ['bi-people', 'Manajemen Kandidat'], 'control' => ['bi-ui-checks', 'Kontrol Voting'], 'results' => ['bi-shield-check', 'Hasil & Audit'], 'announcement' => ['bi-megaphone', 'Pengumuman']] as $key => $tab)
        <a class="{{ request()->routeIs('kpum.'.$key.'*') || ($key === 'control' && request()->routeIs('kpum.voters')) ? 'active' : '' }}" href="{{ route('kpum.'.$key) }}" @if(request()->routeIs('kpum.'.$key)) aria-current="page" @endif><i class="bi {{ $tab[0] }}" aria-hidden="true"></i>{{ $tab[1] }}</a>
    @endforeach
</nav>
