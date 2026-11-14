{{-- Tampil hanya saat Draft/Ditolak; posisi terkunci ketika surat diajukan. --}}
<section class="himsi-posisi-card" id="himsi-editor-ttd">
    <h4><i class="bi bi-pen"></i> Atur Posisi Tanda Tangan</h4>
    <p class="small text-secondary">Geser kotak tanda tangan menggunakan mouse atau sentuhan. Ini <strong>penanda posisi</strong>, bukan gambar tanda tangan pejabat. Gambar asli hanya ditempel setelah pejabat menyetujui surat.</p>
    <div class="row g-3 align-items-start">
        <div class="col-lg-8">
            <div class="himsi-posisi-toolbar">
                <button type="button" id="himsi-halaman-sebelum" class="btn btn-outline-secondary btn-sm">‹ Sebelumnya</button>
                <span id="himsi-label-halaman" aria-live="polite">Memuat PDF...</span>
                <button type="button" id="himsi-halaman-setelah" class="btn btn-outline-secondary btn-sm">Berikutnya ›</button>
            </div>
            <div class="himsi-posisi-viewport">
                <div class="himsi-posisi-page" id="himsi-posisi-page">
                    <canvas id="himsi-posisi-canvas"></canvas>
                    <div id="himsi-drag-ketua" class="himsi-drag-tanda himsi-tanda-ketua" role="img" aria-label="Letak tanda tangan Ketua Umum">TTD Ketua Umum</div>
                    @if($suratKeluar->penandatangan_pendamping_id)
                    <div id="himsi-drag-pendamping" class="himsi-drag-tanda himsi-tanda-pendamping" role="img" aria-label="Letak tanda tangan pendamping">TTD Pendamping</div>
                    @endif
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="himsi-pindahkan-halaman">Tempatkan penanda terpilih di halaman ini</button>
            <div class="alert alert-warning mt-2 small mb-0">Periksa setiap halaman dokumen hasil konversi Word. Posisi dan hasil konversi akan dikunci saat kamu mengajukan surat.</div>
        </div>
        <div class="col-lg-4">
            <form id="himsi-posisi-form" action="{{ route('sekretaris.surat-keluar.posisi-ttd.simpan', $suratKeluar) }}" method="POST" class="himsi-posisi-form">
                @csrf
                <label class="form-label fw-semibold" for="himsi-pilih-pejabat">Pilih penanda yang digeser</label>
                <select id="himsi-pilih-pejabat" class="form-select mb-3">
                    <option value="ketua">Ketua Umum</option>
                    @if($suratKeluar->penandatangan_pendamping_id)<option value="pendamping">Penandatangan pendamping</option>@endif
                </select>
                <label class="form-label fw-semibold" for="himsi-lebar-tanda">Lebar tanda tangan (mm)</label>
                <input id="himsi-lebar-tanda" class="form-range" type="range" min="15" max="80" step="1" value="{{ $suratKeluar->ttd_lebar ?: 48 }}">
                <div class="small mb-3"><span id="himsi-lebar-label">48</span> mm</div>
                <input type="hidden" name="ttd_halaman" id="himsi-field-halaman-ketua" value="{{ $suratKeluar->ttd_halaman }}">
                <input type="hidden" name="ttd_posisi_x" id="himsi-field-x-ketua" value="{{ $suratKeluar->ttd_posisi_x ?? 135 }}">
                <input type="hidden" name="ttd_posisi_y" id="himsi-field-y-ketua" value="{{ $suratKeluar->ttd_posisi_y ?? 235 }}">
                <input type="hidden" name="ttd_lebar" id="himsi-field-lebar-ketua" value="{{ $suratKeluar->ttd_lebar ?: 48 }}">
                @if($suratKeluar->penandatangan_pendamping_id)
                <input type="hidden" name="ttd_halaman_pendamping" id="himsi-field-halaman-pendamping" value="{{ $suratKeluar->ttd_halaman_pendamping }}">
                <input type="hidden" name="ttd_posisi_x_pendamping" id="himsi-field-x-pendamping" value="{{ $suratKeluar->ttd_posisi_x_pendamping ?? 40 }}">
                <input type="hidden" name="ttd_posisi_y_pendamping" id="himsi-field-y-pendamping" value="{{ $suratKeluar->ttd_posisi_y_pendamping ?? 235 }}">
                <input type="hidden" name="ttd_lebar_pendamping" id="himsi-field-lebar-pendamping" value="{{ $suratKeluar->ttd_lebar_pendamping ?: 48 }}">
                @endif
                <p class="small text-secondary">Halaman dan koordinat tanda tangan tersimpan terpisah untuk setiap pejabat.</p>
                <button type="submit" class="btn w-100 fw-bold" id="himsi-simpan-posisi" style="background:#facc15;color:#101b4c" disabled>Simpan Posisi Tanda Tangan</button>
                <div id="himsi-editor-pesan" class="small text-danger mt-2" role="alert"></div>
            </form>
        </div>
    </div>
</section>

@once
@push('styles')
<style>
.himsi-posisi-card{padding:22px;background:#fff;border:1px solid #e5eaf2;border-radius:16px;margin:20px 0;box-shadow:0 4px 20px #101b4c0a}
.himsi-posisi-card h4{font-size:19px;font-weight:800;color:#101b4c}
.himsi-posisi-toolbar{display:flex;gap:12px;align-items:center;justify-content:center;flex-wrap:wrap;margin-bottom:10px}
.himsi-posisi-viewport{background:#e8ecf3;border-radius:12px;padding:12px;display:flex;justify-content:center;overflow-x:auto;min-height:200px}
.himsi-posisi-page{position:relative;display:inline-block;box-shadow:0 2px 14px #0003;touch-action:pan-y}
#himsi-posisi-canvas{display:block;max-width:none;background:#fff}
.himsi-drag-tanda{position:absolute;display:none;align-items:center;justify-content:center;border:2px dashed #2260d5;background:#e5f0ffb8;color:#101b4c;font-size:11px;font-weight:800;cursor:grab;user-select:none;touch-action:none;text-align:center;border-radius:4px;padding:2px;line-height:1.2}
.himsi-drag-tanda.himsi-tanda-pendamping{border-color:#078452;background:#d8fcebb8}
.himsi-drag-tanda.aktif{outline:2px solid #facc15;outline-offset:2px}
@media(max-width:767px){.himsi-posisi-card{padding:14px}.himsi-posisi-viewport{padding:6px}}
</style>
@endpush
@push('scripts')
<script type="module">
import * as pdfjsLib from "{{ asset('vendor/himsi-pdf/pdf.mjs') }}";
(function(){
    const root=document.getElementById('himsi-editor-ttd'); if(!root)return;
    const el=id=>document.getElementById(id), pageEl=el('himsi-posisi-page'),canvas=el('himsi-posisi-canvas'),ctx=canvas.getContext('2d');
    const pesan=el('himsi-editor-pesan'),save=el('himsi-simpan-posisi'),select=el('himsi-pilih-pejabat'),slider=el('himsi-lebar-tanda');
    const source=@json(route('surat-keluar.dokumen.lihat', $suratKeluar));
    const worker=@json(asset('vendor/himsi-pdf/pdf.worker.mjs'));
    const config={
      ketua:{node:el('himsi-drag-ketua'),hal:el('himsi-field-halaman-ketua'),x:el('himsi-field-x-ketua'),y:el('himsi-field-y-ketua'),w:el('himsi-field-lebar-ketua')},
      pendamping:el('himsi-drag-pendamping')?{node:el('himsi-drag-pendamping'),hal:el('himsi-field-halaman-pendamping'),x:el('himsi-field-x-pendamping'),y:el('himsi-field-y-pendamping'),w:el('himsi-field-lebar-pendamping')}:null
    };
    let pdf,current=1,pageWidthMm=210,pageHeightMm=297,scale=1,rendering=false;
    function mmToPxX(mm){return Number(mm)/pageWidthMm*canvas.width}
    function mmToPxY(mm){return Number(mm)/pageHeightMm*canvas.height}
    function pxToMmX(px){return px/canvas.width*pageWidthMm}
    function pxToMmY(px){return px/canvas.height*pageHeightMm}
    function visible(){
      for(const cfg of Object.values(config)){
        if(!cfg)continue;
        cfg.node.style.display=Number(cfg.hal.value)===current?'flex':'none';
        cfg.node.classList.toggle('aktif',cfg===config[select.value]);
        if(Number(cfg.hal.value)!==current)continue;
        cfg.node.style.width=mmToPxX(cfg.w.value)+'px';
        cfg.node.style.height=mmToPxY(20)+'px';
        cfg.node.style.left=mmToPxX(cfg.x.value)+'px';
        cfg.node.style.top=mmToPxY(cfg.y.value)+'px';
      }
      const selected=config[select.value];slider.value=selected.w.value;
      el('himsi-lebar-label').textContent=selected.w.value;
    }
    async function draw(pageNum){
      if(!pdf || rendering)return;
      rendering=true;
      try{
        current=Math.max(1,Math.min(pdf.numPages,pageNum));
        const page=await pdf.getPage(current);
        const base=page.getViewport({scale:1});
        const available=Math.max(250,root.querySelector('.himsi-posisi-viewport').clientWidth-32);
        scale=Math.min(1.7,available/base.width);
        const view=page.getViewport({scale});
        canvas.width=Math.round(view.width);canvas.height=Math.round(view.height);
        pageEl.style.width=canvas.width+'px';pageEl.style.height=canvas.height+'px';
        pageWidthMm=base.width*25.4/72;pageHeightMm=base.height*25.4/72;
        await page.render({canvasContext:ctx,viewport:view}).promise;
        el('himsi-label-halaman').textContent='Halaman '+current+' / '+pdf.numPages;
        visible();
      }catch(err){pesan.textContent='PDF gagal ditampilkan: '+err.message}
      finally{rendering=false;}
    }
    function reposition(cfg, clientX,clientY){
      const box=canvas.getBoundingClientRect();
      const w=Number(cfg.w.value),h=20;
      cfg.x.value=Math.max(0,Math.min(pageWidthMm-w,pxToMmX(clientX-box.left))).toFixed(1);
      cfg.y.value=Math.max(0,Math.min(pageHeightMm-h,pxToMmY(clientY-box.top))).toFixed(1);
      cfg.hal.value=current;visible();
    }
    Object.entries(config).forEach(([name,cfg])=>{
      if(!cfg)return;
      cfg.node.addEventListener('pointerdown',event=>{
        select.value=name;visible();cfg.node.setPointerCapture(event.pointerId);cfg.node.style.cursor='grabbing';event.preventDefault();
      });
      cfg.node.addEventListener('pointermove',event=>{
        if(cfg.node.hasPointerCapture(event.pointerId))reposition(cfg,event.clientX,event.clientY);
      });
      cfg.node.addEventListener('pointerup',event=>{cfg.node.style.cursor='grab';if(cfg.node.hasPointerCapture(event.pointerId))cfg.node.releasePointerCapture(event.pointerId)});
    });
    el('himsi-pindahkan-halaman').addEventListener('click',()=>{
      const cfg=config[select.value];cfg.hal.value=current;
      if(Number(cfg.x.value)+Number(cfg.w.value)>pageWidthMm)cfg.x.value=Math.max(0,pageWidthMm-Number(cfg.w.value));
      if(Number(cfg.y.value)+20>pageHeightMm)cfg.y.value=Math.max(0,pageHeightMm-20);
      visible();
    });
    el('himsi-halaman-sebelum').addEventListener('click',()=>draw(current-1));
    el('himsi-halaman-setelah').addEventListener('click',()=>draw(current+1));
    select.addEventListener('change',()=>{const cfg=config[select.value];const hal=Number(cfg.hal.value)||current;draw(hal).then(visible);});
    slider.addEventListener('input',()=>{
      const cfg=config[select.value];cfg.w.value=slider.value;
      if(Number(cfg.x.value)+Number(cfg.w.value)>pageWidthMm)cfg.x.value=Math.max(0,pageWidthMm-Number(cfg.w.value));
      visible();
    });
    el('himsi-posisi-form').addEventListener('submit',event=>{
      if(!pdf){event.preventDefault();pesan.textContent='Pratinjau PDF belum siap.';return;}
      for(const cfg of Object.values(config)){
        if(!cfg)continue;
        if(!cfg.hal.value){event.preventDefault();pesan.textContent='Tempatkan tanda tangan terlebih dahulu.';return;}
      }
    });
    async function init(){
      pdfjsLib.GlobalWorkerOptions.workerSrc=worker;
      try{
        pdf=await pdfjsLib.getDocument({url:source,withCredentials:true}).promise;
        for(const cfg of Object.values(config)){if(cfg && !cfg.hal.value)cfg.hal.value=pdf.numPages;}
        save.disabled=false;await draw(Number(config.ketua.hal.value)||pdf.numPages);
      }catch(error){pesan.textContent='Tidak dapat membaca PDF: '+error.message;}
    }
    init();
})();
</script>
@endpush
@endonce
