if (document.body.classList.contains('kpum-page')) {
    document.querySelectorAll('textarea[data-k-count]').forEach((input) => {
        const counter = document.querySelector(`[data-k-count-for="${input.id}"]`);
        const update = () => { if (counter) counter.textContent = `${input.value.length} / ${input.maxLength} karakter`; };
        input.addEventListener('input', update);
        update();
    });
    const upload = document.querySelector('[data-k-preview-input]');
    const preview = document.querySelector('[data-k-preview]');
    let previewUrl;
    upload?.addEventListener('change', () => {
        const file = upload.files?.[0];
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        if (!file || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) return;
        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        preview.classList.remove('d-none');
    });
    const timer = document.querySelector('[data-k-countdown]');
    if (timer) {
        const tick = () => {
            const seconds = Math.max(0, Math.floor((Date.parse(timer.dataset.kCountdown) - Date.now()) / 1000));
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            timer.textContent = seconds ? `Sisa ${days ? `${days} hari ` : ''}${hours} jam ${minutes} menit` : 'Jadwal voting telah berakhir';
        };
        tick();
        setInterval(tick, 1000);
    }
    const live = document.querySelector('[data-k-live]');
    if (live) {
        const status = live.querySelector('[data-k-live-status]');
        let loading = false;
        const refresh = async () => {
            if (document.hidden || loading) return;
            loading = true;
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 10000);
            try {
                const response = await fetch(live.dataset.kLive, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal });
                if (!response.ok) throw new Error('Gagal memperbarui');
                const data = await response.json();
                document.querySelectorAll('[data-k-stat]').forEach((el) => {
                    if (Object.hasOwn(data, el.dataset.kStat)) el.textContent = data[el.dataset.kStat];
                });
                document.querySelectorAll('[data-k-participation]').forEach((el) => { el.value = data.percent; });
                data.candidates.forEach((candidate) => {
                    const row = document.querySelector(`[data-k-candidate="${candidate.id}"]`);
                    if (!row) return;
                    row.querySelector('[data-k-votes]').textContent = candidate.votes;
                    row.querySelector('[data-k-share]').textContent = candidate.percent;
                    row.querySelector('progress').value = candidate.percent;
                });
                if (status) status.textContent = `Diperbarui ${new Date().toLocaleTimeString('id-ID')}`;
            } catch {
                if (status) status.textContent = 'Pembaruan tertunda. Akan dicoba kembali.';
            } finally {
                loading = false;
                clearTimeout(timeout);
            }
        };
        setInterval(refresh, 30000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    }
}
