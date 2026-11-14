import QRCode from 'qrcode';
import { Html5Qrcode } from 'html5-qrcode';

const live = document.querySelector('[data-meeting-live]');
if (live) {
    const canvas = document.getElementById('meeting-qr');
    const code = document.getElementById('meeting-code');
    const countdown = document.getElementById('meeting-countdown');
    const error = document.getElementById('meeting-live-error');
    let deadline = 0;
    let currentCode = '';
    let fetching = false;
    let stopped = false;
    document.querySelectorAll('[data-manual-attendance]').forEach((form) => {
        form.querySelector('select').addEventListener('change', () => { form.dataset.edited = 'true'; });
    });
    const refresh = async () => {
        if (fetching || stopped) return;
        fetching = true;
        const requestedAt = performance.now();
        try {
            const response = await fetch(live.dataset.meetingLive, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok) throw new Error('Pembaruan absensi gagal. Periksa koneksi atau muat ulang halaman.');
            const data = await response.json();
            if (data.status !== 'open') {
                stopped = true;
                canvas.hidden = true;
                code.textContent = 'Absensi ditutup';
                countdown.textContent = 'Muat ulang halaman untuk melihat hasil rapat.';
                document.querySelectorAll('[data-manual-attendance] button').forEach((button) => { button.disabled = true; });
                return;
            }
            deadline = requestedAt + data.token.remaining_seconds * 1000;
            if (currentCode !== data.token.code || canvas.hidden) {
                await QRCode.toCanvas(canvas, data.token.code, { width: 280, margin: 4, errorCorrectionLevel: 'M' });
                currentCode = data.token.code;
                code.textContent = currentCode;
                canvas.hidden = false;
            }
            data.attendances.forEach((attendance) => {
                const row = document.querySelector(`[data-attendance-id="${attendance.id}"]`);
                if (!row) return;
                row.querySelector('[data-attendance-status]').textContent = attendance.label;
                row.querySelector('[data-attendance-time]').textContent = attendance.attended_at ?? '—';
                const form = row.querySelector('[data-manual-attendance]');
                if (form && form.dataset.edited !== 'true') {
                    form.querySelector('[name="version"]').value = attendance.version;
                    form.querySelector('[name="status"]').value = attendance.status === 'pending' ? '' : attendance.status;
                }
            });
            error.textContent = '';
        } catch (failure) {
            error.textContent = failure.message;
        } finally {
            fetching = false;
        }
    };
    refresh();
    const refreshTimer = setInterval(refresh, 5000);
    const countdownTimer = setInterval(() => {
        if (stopped) return;
        const remaining = Math.max(0, Math.ceil((deadline - performance.now()) / 1000));
        countdown.textContent = remaining ? `Kode berganti dalam ${remaining} detik.` : 'Memperbarui kode…';
        if (remaining === 0) {
            canvas.hidden = true;
            code.textContent = 'Memperbarui…';
            refresh();
        }
    }, 1000);
    window.addEventListener('pagehide', () => { stopped = true; clearInterval(refreshTimer); clearInterval(countdownTimer); });
}

const start = document.getElementById('start-meeting-scanner');
if (start) {
    const stop = document.getElementById('stop-meeting-scanner');
    const message = document.getElementById('meeting-scanner-message');
    const form = document.getElementById('meeting-attend-form');
    let scanner;
    let submitted = false;
    const stopScanner = async () => {
        if (scanner?.isScanning) await scanner.stop();
        stop.hidden = true;
        start.disabled = false;
    };
    start.addEventListener('click', async () => {
        start.disabled = true;
        try {
            scanner ??= new Html5Qrcode('meeting-scanner');
            await scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 220, height: 220 } }, async (value) => {
                if (submitted || !/^[A-Fa-f0-9]{10}$/.test(value)) return;
                submitted = true;
                document.getElementById('attendance-code').value = value.toUpperCase();
                document.getElementById('attendance-source').value = 'qr';
                await stopScanner();
                form.requestSubmit();
            });
            stop.hidden = false;
            message.textContent = 'Arahkan kamera ke kode QR yang ditampilkan Sekertaris.';
        } catch {
            start.disabled = false;
            message.textContent = 'Kamera tidak dapat dibuka. Gunakan HTTPS dan izinkan kamera, atau masukkan kode kehadiran.';
        }
    });
    stop.addEventListener('click', () => { stopScanner().catch(() => { message.textContent = 'Tutup halaman untuk menghentikan kamera.'; }); });
    window.addEventListener('pagehide', () => { stopScanner().catch(() => {}); });
    document.getElementById('attendance-code').addEventListener('input', () => { document.getElementById('attendance-source').value = 'code'; });
}
