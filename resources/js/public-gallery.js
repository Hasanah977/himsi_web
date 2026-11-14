const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

document.querySelectorAll('.gallery-marquee').forEach(gallery => {
    const rows = [...gallery.querySelectorAll('.gallery-marquee-row')].map(row => {
        const track = row.querySelector('.gallery-marquee-track');
        const group = row.querySelector('.gallery-marquee-group');
        return { row, track, group, originals: [...group.children] };
    });
    const clonePhoto = photo => {
        const clone = photo.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        clone.tabIndex = -1;
        return clone;
    };
    const rebuild = () => {
        rows.forEach(({ row, track, group, originals }) => {
            track.classList.remove('is-ready');
            track.querySelectorAll('[data-gallery-copy]').forEach(copy => copy.remove());
            group.replaceChildren(...originals);
            if (reducedMotion.matches || !originals.length || !row.clientWidth) return;
            while (group.getBoundingClientRect().width < row.clientWidth) {
                originals.forEach(photo => group.append(clonePhoto(photo)));
            }
            const copy = group.cloneNode(true);
            copy.dataset.galleryCopy = '';
            copy.setAttribute('aria-hidden', 'true');
            copy.querySelectorAll('button').forEach(button => { button.tabIndex = -1; });
            track.append(copy);
            track.style.setProperty('--gallery-duration', `${group.getBoundingClientRect().width / 32}s`);
            track.classList.add('is-ready');
        });
    };
    let width = 0;
    if ('ResizeObserver' in window) {
        new ResizeObserver(entries => {
            const nextWidth = Math.round(entries[0].contentRect.width);
            if (nextWidth === width) return;
            width = nextWidth;
            rebuild();
        }).observe(gallery);
    } else {
        window.addEventListener('resize', rebuild);
    }
    reducedMotion.addEventListener('change', rebuild);
    const modal = document.getElementById('galleryModal');
    modal?.addEventListener('show.bs.modal', () => gallery.classList.add('is-paused'));
    modal?.addEventListener('hidden.bs.modal', () => gallery.classList.remove('is-paused'));
    rebuild();
});
