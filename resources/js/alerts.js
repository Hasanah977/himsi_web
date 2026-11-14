import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import '../css/alerts.css';

const popup = Swal.mixin({
    confirmButtonColor: '#1b234f',
    cancelButtonColor: '#657087',
    confirmButtonText: 'Mengerti',
    customClass: { popup: 'himsi-alert' },
    heightAuto: false,
    returnFocus: true,
});
const approved = new WeakSet();
let confirming = false;

// Capture prevents loading-button handlers from disabling a cancelled form.
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (approved.has(form)) return;
    const destructive = form.hasAttribute('data-confirm-delete');
    const message = event.submitter?.dataset.confirm || form.dataset.confirm || form.dataset.kConfirm;
    if (!destructive && !message) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    if (confirming || !form.reportValidity()) return;
    const submitter = event.submitter;
    confirming = true;
    try {
        const result = await popup.fire({
            titleText: destructive ? 'Hapus data ini?' : 'Konfirmasi tindakan',
            text: message || 'Data yang dihapus tidak dapat dikembalikan.',
            icon: destructive ? 'warning' : 'question',
            showCancelButton: true,
            cancelButtonText: 'Batal',
            confirmButtonText: destructive ? 'Ya, hapus' : 'Ya, lanjutkan',
            confirmButtonColor: destructive ? '#b42335' : '#1b234f',
            focusCancel: true,
            allowOutsideClick: false,
        });
        if (result.isConfirmed && form.isConnected && (!submitter || !submitter.disabled)) {
            approved.add(form);
            try {
                // Keep the clicked button's name/value (draft, submit, approve, etc.).
                form.requestSubmit(submitter || undefined);
            } finally {
                approved.delete(form);
            }
        }
    } finally {
        confirming = false;
    }
}, true);

function showFlash() {
    const error = document.querySelector('.alert-danger[role="alert"]');
    const success = document.querySelector('.alert-success[role="status"], .alert-success[role="alert"]');
    const notice = error || success;
    if (!notice || !notice.textContent.trim()) return;
    popup.fire({
        titleText: error ? 'Periksa kembali' : 'Berhasil',
        text: notice.textContent.trim(),
        icon: error ? 'error' : 'success',
    });
    // Keep inline errors so users can consult them while correcting the form.
    if (!error) notice.hidden = true;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', showFlash, { once: true });
} else {
    showFlash();
}
