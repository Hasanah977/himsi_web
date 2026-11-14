(() => {
    if (!document.body.classList.contains('department-page')) return;
    document.querySelectorAll('form[data-navigate]').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            if (form.reportValidity()) {
                const url = new URL(form.querySelector('[data-navigate-url]').value, location.origin);
                if (url.origin === location.origin) location.assign(url.href);
            }
        });
    });
    const budget = document.querySelector('[data-budget]');
    if (budget) {
        const rows = budget.querySelector('[data-budget-rows]');
        const format = value => new Intl.NumberFormat('id-ID', {style:'currency', currency:'IDR', maximumFractionDigits:0}).format(value);
        const update = () => {
            let total = 0;
            rows.querySelectorAll('[data-budget-row]').forEach((row, index) => {
                row.querySelectorAll('[data-field]').forEach(input => input.name = `items[${index}][${input.dataset.field}]`);
                const qty = Number(row.querySelector('[data-field="qty"]').value) || 0;
                const price = Number(row.querySelector('[data-field="price"]').value) || 0;
                const subtotal = Math.max(0, qty * price);
                row.querySelector('[data-row-total]').textContent = format(subtotal);
                total += subtotal;
                row.querySelector('[data-remove-row]').disabled = rows.children.length === 1;
            });
            budget.querySelector('[data-budget-total]').textContent = format(total);
            budget.querySelector('[data-add-row]').disabled = rows.children.length >= 100;
        };
        budget.addEventListener('input', update);
        budget.addEventListener('click', event => {
            const remove = event.target.closest('[data-remove-row]');
            if (remove && rows.children.length > 1) { remove.closest('[data-budget-row]').remove(); update(); }
        });
        budget.querySelector('[data-add-row]').addEventListener('click', () => {
            if (rows.children.length >= 100) return;
            const row = rows.firstElementChild.cloneNode(true);
            row.querySelectorAll('input').forEach(input => input.value = input.dataset.field === 'qty' ? 1 : (input.dataset.field === 'price' ? 0 : ''));
            rows.append(row); update(); row.querySelector('input').focus();
        });
        update();
    }
    const assign = document.querySelector('[data-task-assign]');
    if (assign) {
        const select = assign.querySelector('[data-task-program]');
        const all = assign.querySelector('[data-select-members]');
        const members = [...assign.querySelectorAll('[data-task-member]')];
        const refresh = () => {
            const allowed = JSON.parse(select.selectedOptions[0]?.dataset.members || '[]');
            members.forEach(input => { input.disabled = !allowed.includes(Number(input.value)); if (input.disabled) input.checked = false; });
            all.checked = false;
        };
        select.addEventListener('change', refresh);
        all.addEventListener('change', () => members.forEach(input => { if (!input.disabled) input.checked = all.checked; }));
        refresh();
    }
    const start = document.getElementById('starts_on');
    const end = document.getElementById('ends_on');
    if (start && end) { const update = () => end.min = start.value; start.addEventListener('change', update); update(); }
    const messages = document.querySelector('[data-messages]');
    if (messages) messages.scrollTop = messages.scrollHeight;
    const updates = document.querySelector('[data-forum-updates]');
    if (updates) {
        let loading = false;
        const poll = async () => {
            if (document.hidden || loading) return;
            loading = true;
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 8000);
            try {
                const response = await fetch(updates.dataset.forumUpdates, {headers:{Accept:'application/json'},credentials:'same-origin',signal:controller.signal});
                if (!response.ok) return;
                const data = await response.json();
                if (data.latest_id > Number(updates.dataset.latest)) updates.classList.remove('d-none');
            } catch { /* Keep the current conversation available during network interruption. */ }
            finally { clearTimeout(timeout); loading = false; }
        };
        setInterval(poll, 15000);
    }
})();
