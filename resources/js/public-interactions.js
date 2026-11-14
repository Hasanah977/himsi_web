import { initializePublicMotion } from './public-motion';
import { initializePublicListing } from './public-listing';

/*
|--------------------------------------------------------------------------
| DETAIL GALERI
|--------------------------------------------------------------------------
*/

const galleryModal = document.getElementById('galleryModal');

if (galleryModal) {
    const image = document.getElementById('galleryModalImage');
    const title = document.getElementById('galleryModalTitle');
    const description = document.getElementById(
        'galleryModalDescription'
    );

    galleryModal.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;

        if (!trigger) return;

        image.src = trigger.dataset.image;
        image.alt = trigger.dataset.title;

        title.textContent = trigger.dataset.title;
        description.textContent = trigger.dataset.description;
    });

    galleryModal.addEventListener('hidden.bs.modal', () => {
        image.removeAttribute('src');
        image.alt = '';
    });
}

/*
|--------------------------------------------------------------------------
| TAMPILKAN / SEMBUNYIKAN PASSWORD
|--------------------------------------------------------------------------
*/

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(
            button.dataset.passwordToggle
        );

        if (!input) return;

        const showPassword = input.type === 'password';

        input.type = showPassword ? 'text' : 'password';

        button.textContent = showPassword
            ? 'Sembunyikan'
            : 'Lihat';

        button.setAttribute(
            'aria-pressed',
            String(showPassword)
        );

        button.setAttribute(
            'aria-label',
            showPassword
                ? 'Sembunyikan kata sandi'
                : 'Tampilkan kata sandi'
        );
    });
});

/*
|--------------------------------------------------------------------------
| JEDA RUNNING TEXT
|--------------------------------------------------------------------------
*/

const tickerButton = document.querySelector('[data-ticker-toggle]');
const ticker = document.querySelector('.ticker');

if (tickerButton && ticker) {
    tickerButton.addEventListener('click', () => {
        const paused = ticker.classList.toggle('paused');

        tickerButton.textContent = paused ? 'Lanjutkan' : 'Jeda';
        tickerButton.setAttribute('aria-pressed', String(paused));
    });
}

/*
|--------------------------------------------------------------------------
| ANIMASI SAAT KONTEN MASUK LAYAR
|--------------------------------------------------------------------------
*/

const publicSite = document.querySelector('.public-site');
const publicMain = publicSite?.querySelector('#main-content[data-public-pages]');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

if (publicMain) {
    const transitionKey = 'himsi-public-page-transition';
    const publicPaths = JSON.parse(publicMain.dataset.publicPages).map(page => new URL(page).pathname);
    let navigating = false;
    let navigationTimer;
    let recoveryTimer;
    const resetPage = () => {
        window.clearTimeout(navigationTimer);
        window.clearTimeout(recoveryTimer);
        navigating = false;
        publicMain.classList.remove('public-page-leaving', 'public-page-entering');
    };
    const enterPage = () => {
        if (!reducedMotion.matches) publicMain.classList.add('public-page-entering');
    };
    try {
        const previous = JSON.parse(sessionStorage.getItem(transitionKey) || 'null');
        sessionStorage.removeItem(transitionKey);
        if (previous && previous.url === window.location.href && Date.now() - previous.time < 15000) {
            enterPage();
        }
    } catch {
        // Navigation still works when storage is unavailable.
    }
    publicMain.addEventListener('animationend', event => {
        if (event.target === publicMain && event.animationName === 'public-page-in') {
            publicMain.classList.remove('public-page-entering');
        }
    });
    publicSite.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || reducedMotion.matches) return;
        const link = event.target.closest('a[href]');
        if (!link || link.hasAttribute('download') || link.hasAttribute('data-bs-toggle') || (link.target && link.target !== '_self')) return;
        const destination = new URL(link.href, window.location.href);
        if (destination.origin !== window.location.origin || !['http:', 'https:'].includes(destination.protocol)) return;
        if (destination.pathname === window.location.pathname && destination.search === window.location.search) return;
        if (!publicPaths.some(path => destination.pathname === path || (path !== '/' && destination.pathname.startsWith(path + '/')))) return;
        event.preventDefault();
        if (navigating) return;
        navigating = true;
        publicMain.classList.remove('public-page-entering');
        publicMain.classList.add('public-page-leaving');
        navigationTimer = window.setTimeout(() => {
            try {
                sessionStorage.setItem(transitionKey, JSON.stringify({ url: destination.href, time: Date.now() }));
            } catch {
                // The exit animation does not depend on storage.
            }
            recoveryTimer = window.setTimeout(resetPage, 1500);
            window.location.assign(destination.href);
        }, 140);
    });
    window.addEventListener('pagehide', resetPage);
    window.addEventListener('pageshow', event => {
        if (event.persisted) {
            resetPage();
            enterPage();
        }
    });
    reducedMotion.addEventListener('change', () => {
        if (reducedMotion.matches) publicMain.classList.remove('public-page-entering', 'public-page-leaving');
    });
}

if (publicSite) {
    const motion = initializePublicMotion(publicSite, publicMain, reducedMotion);
    initializePublicListing(publicSite, motion, reducedMotion);
}

/*
|--------------------------------------------------------------------------
| STATUS TOMBOL SAAT FORM DIKIRIM
|--------------------------------------------------------------------------
*/

document.querySelectorAll('form[data-submit]').forEach((form) => {
    form.addEventListener('submit', () => {
        if (!form.checkValidity()) return;

        const button = form.querySelector('[type="submit"]');

        if (!button) return;

        button.disabled = true;
        button.textContent = 'Memproses…';
    });
});

/*
|--------------------------------------------------------------------------
| PULIHKAN TOMBOL SAAT KEMBALI DENGAN TOMBOL BACK BROWSER
|--------------------------------------------------------------------------
*/

window.addEventListener('pageshow', () => {
    document
        .querySelectorAll('form[data-submit] [type="submit"]')
        .forEach((button) => {
            button.disabled = false;
            button.textContent = button.dataset.label;
        });
});


const heroCarousel = document.querySelector('[data-hero-carousel]');
if (heroCarousel) {
    const slides = [...heroCarousel.querySelectorAll('.hero-slide')];
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let current = 0, paused = motion.matches, timer;
    const show = index => {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            slide.classList.toggle('is-active', i === current);
            slide.setAttribute('aria-hidden', String(i !== current));
        });
    };
    const stop = () => { window.clearInterval(timer); };
    const start = () => {
        stop();
        if (!paused && !document.hidden && slides.length > 1) timer = window.setInterval(() => show(current + 1), 6500);
    };
    document.addEventListener('visibilitychange', start);
    motion.addEventListener('change', () => { paused = motion.matches; start(); });
    start();
}
