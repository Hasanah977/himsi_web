const revealSelector = [
    '[data-reveal]', '.page-hero > .container', '.section-heading', '.section-center', '.stat',
    '.news-card', '.mini-notice', '.gallery-grid .gallery-item', '.quote-content',
    '.vision-card', '.mission-card', '.value-card', '.person-card',
    '.department-card', '.program-overview > div:first-child',
    '.announcement-feature', '.announcement-row', '.article-detail', '.empty-state',
].join(', ');

export function initializePublicMotion(site, main, reducedMotion) {
    const pending = new Set();
    const running = new Map();
    const canObserve = 'IntersectionObserver' in window;
    const pageIsLeaving = () => main?.matches('.public-page-leaving');
    const formatNumber = new Intl.NumberFormat('id-ID');

    const finishReveal = element => {
        element.classList.remove('scroll-reveal-pending', 'scroll-reveal-entering');
        element.classList.add('scroll-reveal-done');
        pending.delete(element);
        revealObserver?.unobserve(element);
    };

    const reveal = (element, delay = 0, animate = true) => {
        if (!pending.has(element)) return;
        const tree = element.closest('.organization-tree');
        const chief = tree?.querySelector('.person-chief');
        if (tree && chief) {
            if (element === chief || !tree.dataset.hierarchyStart) {
                tree.dataset.hierarchyStart = String(performance.now());
                tree.classList.add('hierarchy-revealed');
            }
            if (element !== chief) {
                delay += Math.max(0, 920 - (performance.now() - Number(tree.dataset.hierarchyStart)));
            }
            if (!animate || reducedMotion.matches) tree.classList.add('hierarchy-complete');
        }
        element.style.setProperty('--reveal-delay', `${delay}ms`);
        element.classList.remove('scroll-reveal-pending');
        element.classList.add('scroll-reveal-done');
        if (animate && !reducedMotion.matches && !pageIsLeaving()) {
            element.classList.add('scroll-reveal-entering');
        }
        pending.delete(element);
        revealObserver?.unobserve(element);
    };

    const revealObserver = canObserve ? new IntersectionObserver(entries => {
        const groups = new Map();
        entries.filter(entry => entry.isIntersecting).sort((a, b) => {
            const position = a.target.compareDocumentPosition(b.target);
            return position & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
        }).forEach(({ target }) => {
            const group = target.closest('.news-grid, .program-grid, .achievement-grid, .notice-list, .department-grid, .organization-tree, .vision-grid, .stats, .row') || target.parentElement;
            const index = groups.get(group) || 0;
            groups.set(group, index + 1);
            const isHeading = target.matches('.section-heading, .section-center');
            reveal(target, isHeading ? 0 : 100 + Math.min(index, 5) * 70);
        });
    }, { threshold: 0, rootMargin: '0px 0px -24px 0px' }) : null;

    const animateNumber = element => {
        if (element.dataset.counted) return;
        const target = Number(element.dataset.countUp);
        if (!Number.isFinite(target) || target < 0) return;
        element.dataset.counted = 'true';
        const render = progress => {
            element.textContent = formatNumber.format(Math.round(target * progress));
        };
        animate(element, render);
    };

    const animateDonut = element => {
        if (element.dataset.counted) return;
        const values = JSON.parse(element.dataset.donutValues);
        const total = values.reduce((sum, value) => sum + value, 0);
        element.dataset.counted = 'true';
        const render = progress => {
            const upcoming = total ? values[0] / total * 100 * progress : 0;
            const running = total ? (values[0] + values[1]) / total * 100 * progress : 0;
            const end = total ? 100 * progress : 0;
            element.style.background = `conic-gradient(#ffda00 0% ${upcoming}%, #183773 ${upcoming}% ${running}%, #ac8bfa ${running}% ${end}%, #e8edf6 ${end}% 100%)`;
        };
        animate(element, render);
    };

    const animate = (element, render) => {
        metricObserver?.unobserve(element);
        if (reducedMotion.matches || document.hidden || !canObserve) {
            render(1);
            return;
        }
        const started = performance.now();
        const animation = { frame: 0, render };
        const tick = now => {
            if (!element.isConnected) {
                running.delete(element);
                return;
            }
            const progress = Math.min(1, (now - started) / 950);
            render(1 - (1 - progress) ** 3);
            if (progress < 1) animation.frame = requestAnimationFrame(tick);
            else running.delete(element);
        };
        render(0);
        animation.frame = requestAnimationFrame(tick);
        running.set(element, animation);
    };

    const metricObserver = canObserve ? new IntersectionObserver(entries => {
        entries.forEach(({ target, isIntersecting }) => {
            if (!isIntersecting) return;
            if (target.hasAttribute('data-donut-values')) animateDonut(target);
            else animateNumber(target);
        });
    }, { threshold: 0.25 }) : null;

    const refresh = (root = site, { enter = false } = {}) => {
        root.querySelectorAll('.organization-tree').forEach(tree => {
            if (!reducedMotion.matches && canObserve) tree.classList.add('hierarchy-motion');
        });
        root.querySelectorAll(revealSelector).forEach(element => {
            if (element.hasAttribute('data-scroll-reveal') || element.closest('.modal') || element.parentElement.closest('[data-scroll-reveal]')) return;
            element.setAttribute('data-scroll-reveal', '');
            if (!canObserve || reducedMotion.matches) {
                finishReveal(element);
                return;
            }
            pending.add(element);
            element.classList.add('scroll-reveal-pending');
            const bounds = element.getBoundingClientRect();
            if (bounds.bottom <= 0) {
                reveal(element, 0, false);
            } else {
                revealObserver.observe(element);
            }
        });
        root.querySelectorAll('[data-count-up], [data-donut-values]').forEach(element => {
            if (element.dataset.counted) return;
            if (canObserve && !reducedMotion.matches) metricObserver.observe(element);
            else if (element.hasAttribute('data-donut-values')) animateDonut(element);
            else animateNumber(element);
        });
    };

    const finish = (root = site) => {
        pending.forEach(element => {
            if (root.contains(element)) finishReveal(element);
        });
        root.querySelectorAll('.scroll-reveal-entering').forEach(finishReveal);
        root.querySelectorAll('.hierarchy-motion').forEach(tree => tree.classList.add('hierarchy-complete'));
        root.querySelectorAll('[data-count-up], [data-donut-values]').forEach(element => metricObserver?.unobserve(element));
        running.forEach((animation, element) => {
            if (!root.contains(element)) return;
            cancelAnimationFrame(animation.frame);
            animation.render(1);
            running.delete(element);
        });
    };

    site.addEventListener('animationend', event => {
        if (event.animationName === 'public-scroll-enter') finishReveal(event.target);
    });
    site.addEventListener('focusin', event => {
        const element = event.target.closest('[data-scroll-reveal]');
        if (element) {
            reveal(element, 0, false);
            finishReveal(element);
        }
    });
    reducedMotion.addEventListener('change', () => {
        if (reducedMotion.matches) {
            finish();
            refresh();
        }
    });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            running.forEach((animation, element) => {
                cancelAnimationFrame(animation.frame);
                animation.render(1);
                running.delete(element);
            });
        }
    });
    window.addEventListener('pagehide', () => finish());
    window.addEventListener('pageshow', event => {
        if (event.persisted) refresh();
    });
    refresh();
    return { refresh, finish };
}
