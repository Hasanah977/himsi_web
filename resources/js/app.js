import './alerts';
import '../css/admin.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'bootstrap/dist/css/bootstrap.min.css';
import '../css/app.css';
import { Modal } from 'bootstrap';

import './public-interactions';

// Departemen hanya berlaku untuk koordinator / anggota departemen.
const roleSelect = document.getElementById('role');
const departmentField = document.getElementById('departmentField');
const departmentSelect = document.getElementById('department');

if (roleSelect && departmentField && departmentSelect) {
    const updateDepartment = () => {
        const needsDepartment = [
            'koordinator_departemen',
            'anggota_departemen',
        ].includes(roleSelect.value);

        departmentField.hidden = !needsDepartment;
        departmentSelect.disabled = !needsDepartment;
        departmentSelect.required = needsDepartment;
    };

    roleSelect.addEventListener('change', updateDepartment);
    updateDepartment();
}
import './kpum';

const duesVerification = document.getElementById('duesVerification');
if (duesVerification) {
    const form = duesVerification.querySelector('form');
    const note = form.querySelector('[name="catatan"]');
    duesVerification.addEventListener('show.bs.modal', event => {
        const trigger = event.relatedTarget;
        if (!trigger) return;
        form.action = trigger.dataset.action;
        form.elements.modal_payment.value = trigger.dataset.payment;
        duesVerification.querySelectorAll('[data-payment-name]').forEach(name => name.textContent = trigger.dataset.name);
        duesVerification.querySelector('[data-payment-member]').textContent = trigger.dataset.member;
        duesVerification.querySelector('[data-payment-department]').textContent = trigger.dataset.department;
        duesVerification.querySelector('[data-payment-period]').textContent = `Iuran ${trigger.dataset.period}`;
        duesVerification.querySelector('[data-payment-proof]').href = trigger.dataset.proof;
        if (duesVerification.dataset.reopen !== trigger.dataset.payment) note.value = '';
        duesVerification.dataset.reopen = '';
        note.required = false;
    });
    form.querySelectorAll('button[name="keputusan"]').forEach(button => button.addEventListener('click', () => {
        note.required = button.value === 'ditolak';
    }));
    duesVerification.addEventListener('shown.bs.modal', () => note.focus());
    if (duesVerification.dataset.reopen) {
        const trigger = [...document.querySelectorAll('[data-payment]')].find(button => button.dataset.payment === duesVerification.dataset.reopen);
        if (trigger) Modal.getOrCreateInstance(duesVerification).show(trigger);
    }
}

document.querySelectorAll('[data-photo-upload]').forEach((container) => {
    const input = container.querySelector('[data-photo-input]');
    const preview = container.querySelector('[data-photo-preview]');
    const placeholder = container.querySelector('[data-photo-placeholder]');
    const remove = container.querySelector('[data-photo-remove]');
    const error = container.querySelector('[data-photo-error]');
    let objectUrl;
    const clearPreview = () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = undefined;
        preview.removeAttribute('src');
        preview.hidden = true;
        placeholder.hidden = false;
        remove.hidden = true;
        error.hidden = true;
    };
    input.addEventListener('change', () => {
        clearPreview();
        const file = input.files[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            error.textContent = 'Pilih foto JPG, PNG, atau WebP dengan ukuran maksimal 5 MB.';
            error.hidden = false;
            input.value = '';
            return;
        }
        objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        preview.hidden = false;
        placeholder.hidden = true;
        remove.hidden = false;
    });
    remove.addEventListener('click', () => {
        input.value = '';
        clearPreview();
        input.focus();
    });
    window.addEventListener('pagehide', () => { if (objectUrl) URL.revokeObjectURL(objectUrl); });
});

