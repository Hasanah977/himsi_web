<nav class="navbar navbar-expand-lg site-nav" aria-label="Navigasi utama">
    <div class="container">
        <a class="brand" href="{{ route('home') }}">
            <img
                src="{{ $image($settings['logo']) }}"
                alt="Logo HIMSI"
            >
            <span>
                HIMSI
                <small>SISTEM INFORMASI</small>
            </span>
        </a>
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNav"
            aria-controls="mainNav"
            aria-expanded="false"
            aria-label="Buka menu navigasi"
        >
            <i class="bi bi-list" aria-hidden="true"></i>
            <span>Menu</span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto">
                @foreach($navigation as $routeName => $label)
                    @php
                        $active = request()->routeIs($routeName)
                            || (
                                $routeName === 'programs'
                                && request()->routeIs('program.detail')
                            )
                            || (
                                $routeName === 'announcements'
                                && request()->routeIs('announcement.detail')
                            );
                    @endphp
                    <li class="nav-item">
                        <a
                            class="nav-link {{ $active ? 'active' : '' }}"
                            href="{{ route($routeName) }}"
                            @if($active) aria-current="page" @endif
                        >
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
            <a
                class="hp-member-link"
                href="{{ auth()->check() ? route('dashboard') : route('login') }}"
            >
                <img src="{{ $image($settings['logo']) }}" alt="" width="44" height="44">
                <span><strong>Ruang Anggota</strong><small>HIMSI UNSULBAR — FT</small></span>
            </a>
        </div>
    </div>
</nav>
