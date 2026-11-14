(() => {
    if (!document.body.classList.contains('cb-page')) return;
    const reviews = document.querySelectorAll('[data-cb-review]');
    const count = document.querySelector('[data-cb-valid-count]');
    const update = () => {
        if (count) count.textContent = [...reviews].filter(input => input.checked && input.value === 'valid').length;
    };
    reviews.forEach(input => input.addEventListener('change', update));
    update();
    document.querySelectorAll('[data-cb-print]').forEach(button => {
        button.addEventListener('click', () => window.print());
    });
    const kind = document.querySelector('#kind');
    const file = document.querySelector('#file');
    if (kind && file) {
        const updateAccept = () => { file.accept = kind.value === 'photo' ? 'image/jpeg,image/png,image/webp' : 'application/pdf'; };
        kind.addEventListener('change', updateAccept);
        updateAccept();
    }
})();
