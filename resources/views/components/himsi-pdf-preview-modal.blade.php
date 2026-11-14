{{-- Pratinjau PDF pada halaman yang sama, tanpa membuka tab baru. --}}
<style>
    #himsiPdfModal[hidden] { display: none !important; }
    #himsiPdfModal {
        position: fixed; inset: 0; z-index: 2147483000;
        display: flex; align-items: center; justify-content: center;
        padding: 14px; background: rgba(9, 19, 52, .72);
    }
    #himsiPdfModal .himsi-pdf-panel {
        display: flex; flex-direction: column; width: min(1100px, 100%);
        height: min(90vh, 920px); max-height: 90vh; min-height: 0;
        border-radius: 15px; overflow: hidden; background: #fff;
        box-shadow: 0 28px 100px rgba(0, 0, 0, .26);
    }
    #himsiPdfModal .himsi-pdf-header {
        display: flex; gap: 12px; align-items: center; justify-content: space-between;
        padding: 12px 16px; background: #101b4c; color: #fff;
    }
    #himsiPdfModal .himsi-pdf-header strong { font-size: 16px; line-height: 1.3; }
    #himsiPdfModal .himsi-pdf-close {
        background: #facc15; border: 0; border-radius: 8px;
        color: #101b4c; font-weight: 800; padding: 8px 12px; cursor: pointer;
    }
    #himsiPdfModal iframe {
        flex: 1; min-height: 0; width: 100%; border: 0; background: #e7ebf2;
    }
    @media (max-width: 767px) {
        #himsiPdfModal { padding: 6px; }
        #himsiPdfModal .himsi-pdf-panel { height: 94dvh; max-height: 94dvh; border-radius: 9px; }
        #himsiPdfModal .himsi-pdf-header { padding: 10px; }
        #himsiPdfModal .himsi-pdf-header strong { font-size: 14px; }
    }
</style>

<div id="himsiPdfModal" hidden role="dialog" aria-modal="true" aria-label="Pratinjau PDF surat">
    <div class="himsi-pdf-panel">
        <div class="himsi-pdf-header">
            <strong><i class="bi bi-file-earmark-pdf me-1"></i> Pratinjau Dokumen — HIMSI</strong>
            <button type="button" class="himsi-pdf-close" id="himsiPdfClose" aria-label="Tutup pratinjau">Tutup ✕</button>
        </div>
        <iframe id="himsiPdfFrame" title="Dokumen PDF HIMSI" referrerpolicy="same-origin"></iframe>
    </div>
</div>
<script>
(function () {
    const modal = document.getElementById('himsiPdfModal');
    const frame = document.getElementById('himsiPdfFrame');
    const closeButton = document.getElementById('himsiPdfClose');
    if (!modal || !frame || !closeButton) return;
    let previousFocus = null;
    let originalOverflow = '';

    function closePreview() {
        if (modal.hidden) return;
        frame.removeAttribute('src');
        modal.hidden = true;
        document.body.style.overflow = originalOverflow;
        if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
    }
    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[data-himsi-pdf-preview]');
        if (!link) return;
        event.preventDefault();
        previousFocus = link;
        originalOverflow = document.body.style.overflow;
        frame.src = link.href;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        closeButton.focus();
    });
    closeButton.addEventListener('click', closePreview);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closePreview();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            event.preventDefault();
            closePreview();
        }
        if (event.key === 'Tab' && !modal.hidden && event.target === closeButton) {
            event.preventDefault();
            closeButton.focus();
        }
    });
})();
</script>
