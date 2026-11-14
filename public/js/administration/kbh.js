document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const toggle = document.getElementById('sidebarToggle');
  const overlay = document.getElementById('sidebarOverlay');
  const close = () => { sidebar?.classList.remove('active'); overlay?.classList.remove('show'); };
  toggle?.addEventListener('click', () => {
    sidebar?.classList.toggle('active');
    overlay?.classList.toggle('show', !!sidebar?.classList.contains('active'));
  });
  overlay?.addEventListener('click', close);
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
  sidebar?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => { if (window.innerWidth <= 991) close(); }));

  const modal = document.getElementById('kbhAspirasiPreview');
  const modalFrame = document.getElementById('kbhAspirasiFrame');
  const modalNote = document.getElementById('kbhAspirasiNote');
  const hidePreview = () => {
    if (!modal) return;
    modal.hidden = true;
    if (modalFrame) modalFrame.removeAttribute('src');
  };
  document.querySelectorAll('[data-kbh-lampiran]').forEach(button => {
    button.addEventListener('click', () => {
      if (!modal || !modalFrame || !modalNote) return;
      const format = button.dataset.ext?.toLowerCase();
      const tampil = ['pdf', 'png', 'jpg', 'jpeg'].includes(format);
      modalFrame.hidden = !tampil;
      modalNote.hidden = tampil;
      modalNote.textContent = tampil ? '' : 'Pratinjau Word tidak didukung langsung oleh browser. Gunakan tombol Unduh dan buka file asli dengan Microsoft Word.';
      if (tampil) modalFrame.src = button.dataset.kbhLampiran;
      modal.hidden = false;
    });
  });
  document.querySelectorAll('[data-close-kbh-preview]').forEach(button => button.addEventListener('click', hidePreview));
  document.addEventListener('keydown', e => { if(e.key === 'Escape') hidePreview(); });
});
