document.addEventListener('DOMContentLoaded', () => {
    const desktop = window.matchMedia('(min-width: 992px)');
    const storageKey = 'himsi.sidebar.collapsed';
    let savedCollapsed = false;
    try { savedCollapsed = localStorage.getItem(storageKey) === 'true'; } catch {}

    document.querySelectorAll('aside.admin-sidebar, aside.k-sidebar').forEach((sidebar, index) => {
        const shell = sidebar.closest('.admin-shell, .k-shell');
        const brand = sidebar.querySelector('.admin-brand, .k-brand');
        if (!shell || !brand) return;
        shell.classList.add('sidebar-shell');
        sidebar.classList.add('collapsible-sidebar');

        const heading = document.createElement('div');
        heading.className = 'sidebar-heading';
        brand.before(heading);
        heading.append(brand);
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'sidebar-toggle';
        toggle.setAttribute('aria-controls', sidebar.id);
        toggle.innerHTML = '<i class="bi bi-chevron-double-left" aria-hidden="true"></i>';
        heading.append(toggle);

        const tooltip = document.createElement('div');
        tooltip.id = 'sidebar-tooltip-' + index;
        tooltip.className = 'sidebar-tooltip';
        tooltip.setAttribute('role', 'tooltip');
        tooltip.hidden = true;
        document.body.append(tooltip);
        let tooltipTarget = null;

        const hideTooltip = () => {
            tooltip.hidden = true;
            if (tooltipTarget) {
                const ids = (tooltipTarget.getAttribute('aria-describedby') || '').split(' ').filter(id => id && id !== tooltip.id);
                if (ids.length) tooltipTarget.setAttribute('aria-describedby', ids.join(' '));
                else tooltipTarget.removeAttribute('aria-describedby');
            }
            tooltipTarget = null;
        };
        const applyState = () => {
            const collapsed = desktop.matches && savedCollapsed;
            shell.classList.toggle('sidebar-collapsed', collapsed);
            toggle.setAttribute('aria-expanded', String(!collapsed));
            toggle.setAttribute('aria-label', collapsed ? 'Lebarkan sidebar' : 'Ciutkan sidebar');
            toggle.title = collapsed ? 'Lebarkan sidebar' : 'Ciutkan sidebar';
            toggle.firstElementChild.className = 'bi bi-chevron-double-' + (collapsed ? 'right' : 'left');
            hideTooltip();
        };
        const setCollapsed = value => {
            savedCollapsed = value;
            try { localStorage.setItem(storageKey, String(value)); } catch {}
            applyState();
        };
        toggle.addEventListener('click', () => setCollapsed(!savedCollapsed));

        const items = sidebar.querySelectorAll('nav a, nav summary, .admin-sidebar-bottom a, .admin-sidebar-bottom button, .k-sidebar-foot a, .k-sidebar-foot button');
        items.forEach(item => {
            const labelSource = item.cloneNode(true);
            labelSource.querySelectorAll('.badge, .cb-nav-count, [role="img"]').forEach(badge => badge.remove());
            const label = labelSource.textContent.replace(/\s+/g, ' ').trim();
            if (!label) return;
            item.classList.add('sidebar-item');
            if (!item.hasAttribute('aria-label')) item.setAttribute('aria-label', label);
            let icon = item.querySelector('i.bi');
            if (!icon) {
                icon = document.createElement('i');
                const iconLabels = [
                    [/arsip/i, 'archive'], [/periode/i, 'calendar2-range'],
                    [/dashboard/i, 'grid'], [/akun|profil/i, 'person-vcard'],
                    [/role|jabatan/i, 'person-badge'], [/pengumuman/i, 'megaphone'],
                    [/hasil.*voting/i, 'bar-chart'], [/voting|pemilihan|kpum/i, 'check2-square'],
                    [/seleksi|rekrutmen/i, 'person-check'], [/program|kegiatan/i, 'calendar-event'],
                    [/iuran|keuangan|kas/i, 'wallet2'], [/surat/i, 'envelope-paper'],
                    [/laporan|lpj/i, 'file-earmark-bar-graph'], [/tugas/i, 'list-task'],
                    [/forum/i, 'chat-dots'], [/aspirasi/i, 'chat-square-text'],
                    [/log|aktivitas/i, 'clock-history'], [/keamanan|darurat|admin/i, 'shield-lock'],
                    [/publik/i, 'globe2'], [/keluar/i, 'box-arrow-right'],
                ];
                const name = iconLabels.find(([pattern]) => pattern.test(label))?.[1] ?? 'grid';
                icon.className = 'bi bi-' + name;
                icon.setAttribute('aria-hidden', 'true');
            }
            icon.classList.add('sidebar-item-icon');
            item.prepend(icon);
            Array.from(item.childNodes).forEach(node => {
                if (node.nodeType !== Node.TEXT_NODE || !node.textContent.trim()) return;
                const text = document.createElement('span');
                text.className = 'sidebar-item-text';
                node.replaceWith(text);
                text.append(node);
            });
            const showTooltip = () => {
                if (!shell.classList.contains('sidebar-collapsed')) return;
                hideTooltip();
                tooltipTarget = item;
                tooltip.textContent = label;
                tooltip.hidden = false;
                const bounds = item.getBoundingClientRect();
                tooltip.style.left = (sidebar.getBoundingClientRect().right + 10) + 'px';
                tooltip.style.top = Math.max(8, Math.min(bounds.top + (bounds.height - tooltip.offsetHeight) / 2, window.innerHeight - tooltip.offsetHeight - 8)) + 'px';
                item.setAttribute('aria-describedby', [item.getAttribute('aria-describedby'), tooltip.id].filter(Boolean).join(' '));
            };
            item.addEventListener('mouseenter', showTooltip);
            item.addEventListener('mouseleave', hideTooltip);
            item.addEventListener('focus', showTooltip);
            item.addEventListener('blur', hideTooltip);
            if (item.tagName === 'SUMMARY') {
                item.addEventListener('click', event => {
                    if (!shell.classList.contains('sidebar-collapsed')) return;
                    event.preventDefault();
                    setCollapsed(false);
                    item.parentElement.open = true;
                });
            }
        });
        document.addEventListener('keydown', event => { if (event.key === 'Escape') hideTooltip(); });
        document.addEventListener('scroll', hideTooltip, true);
        window.addEventListener('resize', hideTooltip);
        desktop.addEventListener('change', applyState);
        applyState();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const source = document.getElementById('sidebar-unread-urls');
    if (!source) return;
    let notifications = [];
    try { notifications = JSON.parse(source.textContent).map(path => new URL(path, window.location.origin)); } catch { return; }
    document.querySelectorAll('aside nav a[href]').forEach(link => {
        if (link.classList.contains('active') || link.getAttribute('aria-current') === 'page') return;
        const menu = new URL(link.href, window.location.origin);
        if (menu.origin !== window.location.origin || menu.pathname === '/') return;
        const path = menu.pathname.replace(/\/$/, '');
        const canonicalPath = value => value.replace(/^\/rekrutmen\/(hasil|daftar)/, '/kbh/seleksi').replace(/^\/kbh\/seleksi\/ketua-umum/, '/kbh/seleksi');
        const hasUnread = notifications.some(item => item.origin === menu.origin && (canonicalPath(item.pathname) === canonicalPath(path) || canonicalPath(item.pathname).startsWith(canonicalPath(path) + '/')) && [...menu.searchParams].every(([key, value]) => item.searchParams.get(key) === value));
        if (!hasUnread || link.querySelector('[aria-label="Aktivitas baru"], .sidebar-notification-dot')) return;
        const dot = document.createElement('span');
        dot.className = 'sidebar-notification-dot';
        dot.setAttribute('role', 'img');
        dot.setAttribute('aria-label', 'Informasi belum dibaca');
        link.append(dot);
    });
});
