document.addEventListener('DOMContentLoaded', () => {
    const cropForm = document.querySelector('[data-profile-crop-form]');
    if (cropForm) {
        const photo = cropForm.querySelector('[data-profile-photo]');
        const canvas = cropForm.querySelector('[data-profile-canvas]');
        const note = cropForm.querySelector('[data-photo-note]');
        const save = cropForm.querySelector('[data-photo-save]');
        const zoom = cropForm.querySelector('[data-crop-zoom]');
        const x = cropForm.querySelector('[data-crop-x]');
        const y = cropForm.querySelector('[data-crop-y]');
        let picture, photoUrl, ready = false, submitting = false;
        const draw = () => {
            if (!picture) return;
            const size = Math.min(picture.naturalWidth, picture.naturalHeight) / Number(zoom.value);
            const left = (picture.naturalWidth - size) * Number(x.value) / 100;
            const top = (picture.naturalHeight - size) * Number(y.value) / 100;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, 512, 512);
            ctx.drawImage(picture, left, top, size, size, 0, 0, 512, 512);
        };
        const load = source => {
            ready = false; save.disabled = true;
            const nextPicture = new Image();
            picture = nextPicture;
            nextPicture.onload = () => {
                if (picture !== nextPicture) return;
                zoom.value = '1'; x.value = y.value = '50';
                canvas.hidden = false; cropForm.querySelector('[data-profile-initial]').hidden = true;
                cropForm.querySelector('[data-crop-controls]').hidden = false;
                ready = true; save.disabled = false; draw();
                note.textContent = 'Atur posisi dan ukuran foto, lalu klik Simpan foto.';
            };
            picture.onerror = () => { note.textContent = 'Foto tidak dapat dibaca. Pilih gambar lain.'; };
            picture.src = source;
        };
        if (cropForm.dataset.existingPhoto) load(cropForm.dataset.existingPhoto);
        photo.addEventListener('change', () => {
            const file = photo.files[0]; ready = false; save.disabled = true;
            if (!file) {
                ready = Boolean(picture?.complete && picture.naturalWidth);
                save.disabled = !ready;
                return;
            }
            if (file.size > 5 * 1024 * 1024 || !['image/jpeg','image/png','image/webp'].includes(file.type)) {
                note.textContent = 'Pilih JPG, PNG, atau WEBP dengan ukuran maksimal 5 MB.'; return;
            }
            if (photoUrl) URL.revokeObjectURL(photoUrl);
            photoUrl = URL.createObjectURL(file); load(photoUrl);
        });
        [zoom, x, y].forEach(control => control.addEventListener('input', draw));
        cropForm.addEventListener('submit', event => {
            event.preventDefault(); if (!ready || submitting) return;
            submitting = true; save.disabled = true;
            canvas.toBlob(blob => {
                if (!blob) { submitting = false; save.disabled = false; return; }
                const transfer = new DataTransfer();
                transfer.items.add(new File([blob], 'foto-profil.jpg', {type: 'image/jpeg'}));
                photo.files = transfer.files;
                HTMLFormElement.prototype.submit.call(cropForm);
            }, 'image/jpeg', 0.92);
        });
        window.addEventListener('pageshow', () => { submitting = false; save.disabled = !ready; });
        window.addEventListener('pagehide', () => { if (photoUrl) URL.revokeObjectURL(photoUrl); });
    }
    document.querySelectorAll('[data-confirm-message]').forEach(form => form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirmMessage)) event.preventDefault();
    }));
    const messages = document.querySelector('[data-messages]');
    if (messages) messages.scrollTop = messages.scrollHeight;
});
