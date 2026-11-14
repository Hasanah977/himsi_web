<footer class="site-footer public-footer {{ $page === 'home' ? 'public-footer--home' : '' }}">
    <svg class="footer-wave" viewBox="0 0 1680 100" preserveAspectRatio="none" aria-hidden="true">
        <defs>
            <linearGradient id="footer-wave-gold" x1="0" x2="1">
                <stop offset="0" stop-color="#20386b"/>
                <stop offset=".32" stop-color="#344d7a"/>
                <stop offset=".47" stop-color="#ffdf00"/>
                <stop offset="1" stop-color="#ffcf3a"/>
            </linearGradient>
        </defs>
        <path d="M0 44C280-30 420 92 710 82S1250-32 1680 43V100H0Z" fill="url(#footer-wave-gold)"/>
        <path d="M0 72C280 4 420 104 710 88S1250-15 1680 52V101H0Z" fill="#0b193f"/>
    </svg>
    <div class="container footer-container">
        <div class="footer-grid">
            <div class="footer-about">
                <a class="brand footer-brand" href="{{ route('home') }}">
                    <span>HIMSI<small>SISTEM INFORMASI</small></span>
                </a>
                <p>{{ $settings['footer_description'] }}</p>
            </div>
            <div class="footer-links">
                <h3>Tautan Cepat</h3>
                <ul>
                    @foreach(['profile' => 'Profil Himpunan', 'structure' => 'Struktur Organisasi', 'programs' => 'Program Kerja', 'announcements' => 'Pengumuman'] as $footerRoute => $footerLabel)
                        <li><a href="{{ route($footerRoute) }}">{{ $footerLabel }}<i class="bi bi-chevron-right" aria-hidden="true"></i></a></li>
                    @endforeach
                </ul>
            </div>
            <div class="footer-contact">
                <h3>Temui Kami</h3>
                <address>{{ $settings['address'] }}</address>
                <p>Informasi kegiatan tersedia melalui halaman pengumuman HIMSI.</p>
            </div>
            <div class="footer-emblem">
                <img class="footer-logo" src="{{ $image($settings['logo']) }}" alt="Logo HIMSI" loading="lazy" width="160" height="160">
            </div>
        </div>
        <div class="footer-bottom">
            <span>© {{ date('Y') }} HIMSI. Hak cipta dilindungi.</span>
            <span class="footer-motto">Bersama, bertumbuh, dan berdampak.</span>
        </div>
    </div>
</footer>
