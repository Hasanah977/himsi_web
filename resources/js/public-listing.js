export function initializePublicListing(site, motion, reducedMotion) {
    const listing = site.querySelector('[data-public-listing]');
    if (!listing || !window.fetch || !window.AbortController) return;
    const pathname = window.location.pathname;
    const results = listing.querySelector('[data-list-results]');
    const status = listing.querySelector('[data-list-status]');
    let controller;
    let renderedUrl = window.location.href;
    let resizeTimer;
    const resetResults = () => {
        window.clearTimeout(resizeTimer);
        results.classList.remove('listing-fading', 'listing-changing');
        results.style.height = '';
    };

    const load = async (url, { history = true, focus = false, pagination = false } = {}) => {
        controller?.abort();
        resetResults();
        const request = new AbortController();
        controller = request;
        listing.setAttribute('aria-busy', 'true');
        status.textContent = 'Memuat informasi…';
        try {
            const response = await fetch(url, {
                signal: request.signal,
                headers: { Accept: 'text/html' },
                credentials: 'same-origin',
            });
            if (!response.ok || new URL(response.url).pathname !== pathname) throw new Error('Invalid listing response');
            const nextDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = nextDocument.querySelector('[data-public-listing]');
            const nextResults = next?.querySelector('[data-list-results]');
            const nextControls = next?.querySelector('[data-list-controls]');
            if (!nextResults || !nextControls) throw new Error('Missing listing content');
            if (request.signal.aborted) return;

            window.clearTimeout(resizeTimer);
            const oldHeight = results.getBoundingClientRect().height;
            results.style.height = `${oldHeight}px`;
            results.classList.add('listing-changing');
            results.classList.toggle('listing-fading', !reducedMotion.matches);
            if (!reducedMotion.matches) await new Promise(resolve => window.setTimeout(resolve, 120));
            if (request.signal.aborted) return;

            const focusedElement = window.document.activeElement;
            const searchFocus = focusedElement?.matches('input[type="search"]') ? {
                value: focusedElement.value,
                start: focusedElement.selectionStart,
                end: focusedElement.selectionEnd,
            } : null;
            const previousSummary = listing.querySelector('[data-list-summary]');
            const nextSummary = next.querySelector('[data-list-summary]');
            if (previousSummary && nextSummary && previousSummary.dataset.listSummary !== nextSummary.dataset.listSummary) {
                motion.finish(previousSummary);
                previousSummary.replaceWith(nextSummary);
                motion.refresh(listing);
            }
            motion.finish(results);
            results.innerHTML = nextResults.innerHTML;
            results.dataset.resultCount = nextResults.dataset.resultCount;
            listing.querySelector('[data-list-controls]').replaceWith(nextControls);
            const search = nextControls.querySelector('input[type="search"]');
            if (focus && searchFocus && search) {
                search.value = searchFocus.value;
                search.focus({ preventScroll: true });
                search.setSelectionRange(searchFocus.start, searchFocus.end);
            }
            else if (focus) {
                const activeFilter = nextControls.querySelector('[aria-current="page"]');
                (pagination ? results : activeFilter || results).focus({ preventScroll: true });
            }
            renderedUrl = response.url;
            if (history && renderedUrl !== window.location.href) {
                window.history.pushState(null, '', renderedUrl);
            }
            window.document.title = nextDocument.title;
            results.classList.remove('listing-fading');
            results.style.height = 'auto';
            const nextHeight = results.getBoundingClientRect().height;
            results.style.height = `${oldHeight}px`;
            // Flush the old height before transitioning to the measured result height.
            results.getBoundingClientRect();
            results.style.height = `${nextHeight}px`;
            motion.refresh(results, { enter: true });
            resizeTimer = window.setTimeout(() => {
                results.style.height = '';
                results.classList.remove('listing-changing');
            }, reducedMotion.matches ? 0 : 260);
            if (pagination && results.getBoundingClientRect().top < 0) {
                results.scrollIntoView({ behavior: reducedMotion.matches ? 'instant' : 'smooth', block: 'start' });
            }
            status.textContent = `${nextResults.dataset.resultCount} informasi ditemukan.`;
        } catch (error) {
            if (error.name !== 'AbortError' && !request.signal.aborted) window.location.assign(url);
        } finally {
            if (controller === request) {
                listing.removeAttribute('aria-busy');
                results.classList.remove('listing-fading');
            }
        }
    };

    listing.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const link = event.target.closest('.filter-tabs a, .public-pagination a, .empty-state a');
        if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin || url.pathname !== pathname) return;
        event.preventDefault();
        load(url.href, { focus: true, pagination: Boolean(link.closest('.public-pagination')) });
    });
    listing.addEventListener('submit', event => {
        const form = event.target.closest('.search-form');
        if (!form || !form.checkValidity()) return;
        event.preventDefault();
        const url = new URL(form.action, window.location.href);
        url.search = new URLSearchParams(new FormData(form)).toString();
        load(url.href, { focus: true });
    });
    window.addEventListener('popstate', () => {
        if (window.location.pathname !== pathname) return;
        controller?.abort();
        resetResults();
        if (renderedUrl !== window.location.href) {
            load(window.location.href, { history: false });
        } else {
            listing.removeAttribute('aria-busy');
            status.textContent = `${results.dataset.resultCount} informasi ditemukan.`;
        }
    });
    window.addEventListener('pagehide', () => {
        controller?.abort();
        resetResults();
        listing.removeAttribute('aria-busy');
    });
}
