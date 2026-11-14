const quotes = document.querySelector('[data-quote-carousel]');

if (quotes) {
    const slides = [...quotes.querySelectorAll('[data-quote-slide]')];
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let current = 0;
    let timer;
    let visible = false;
    let hovered = false;
    let focused = false;
    let pageActive = true;

    const schedule = () => {
        window.clearTimeout(timer);
        if (slides.length < 2 || !visible || document.hidden || hovered || focused || !pageActive) return;
        timer = window.setTimeout(() => {
            slides[current].classList.remove('is-active');
            slides[current].setAttribute('aria-hidden', 'true');
            current = (current + 1) % slides.length;
            slides[current].classList.add('is-active');
            slides[current].setAttribute('aria-hidden', 'false');
            schedule();
        }, Math.max(7000, slides[current].innerText.trim().split(/\s+/).length * 280));
    };

    // Keep automatic changes readable; reduced motion removes the fade in CSS.
    quotes.addEventListener('mouseenter', () => { hovered = true; schedule(); });
    quotes.addEventListener('mouseleave', () => { hovered = false; schedule(); });
    quotes.addEventListener('focusin', () => { focused = true; schedule(); });
    quotes.addEventListener('focusout', () => { focused = false; schedule(); });
    document.addEventListener('visibilitychange', schedule);
    reducedMotion.addEventListener('change', schedule);
    window.addEventListener('pagehide', () => { pageActive = false; schedule(); });
    window.addEventListener('pageshow', () => { pageActive = true; schedule(); });
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(entries => {
            visible = entries[0].isIntersecting;
            schedule();
        }, { threshold: 0.2 }).observe(quotes);
    } else {
        visible = true;
        schedule();
    }
}
