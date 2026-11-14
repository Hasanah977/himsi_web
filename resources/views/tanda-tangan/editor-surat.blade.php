@extends('administration.layout')
@section('title', 'Administrasi HIMSI')
@push('styles')
<style>
*{box-sizing:border-box}body{margin:0;background:#f3f6fc;color:#132353;font-family:Arial,Helvetica,sans-serif}header{background:#111e50;color:white;padding:20px 5%;display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;align-items:center}header h1{font-size:21px;margin:0}a{color:#1559c2}header a{color:white}main{max-width:1240px;margin:25px auto;padding:0 15px}.box{border-radius:16px;background:#fff;padding:22px;box-shadow:0 3px 14px #17245412;margin-bottom:20px}.grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(270px,1fr);gap:22px}@media(max-width:850px){.grid{grid-template-columns:1fr}}.viewer{overflow:auto;min-height:350px;border-radius:12px;background:#e5eaf1;padding:14px;text-align:center}.sheet{position:relative;display:inline-block;box-shadow:0 3px 15px #0003;background:#fff;touch-action:pan-y}canvas{display:block;max-width:none}#signature{position:absolute;display:none;cursor:grab;touch-action:none;user-select:none;border:2px dashed #1769de;background:#e5f2ff60;min-height:20px}#signature img{display:block;width:100%;height:auto;pointer-events:none}#signature:active{cursor:grabbing}button,.btn{background:#facc15;border:none;border-radius:8px;padding:11px 16px;font-weight:700;color:#101b4c;cursor:pointer}button:disabled{opacity:.45;cursor:not-allowed}.btn-secondary{background:#172454;color:white}.toolbar{display:flex;justify-content:center;align-items:center;gap:9px;flex-wrap:wrap;margin:8px 0 16px}label{display:block;font-size:14px;font-weight:700;margin:14px 0 7px}input[type=file],input[type=range]{width:100%}input[type=file]{padding:10px;border:1px solid #d5deeb;border-radius:8px}.tip{padding:13px;background:#e6f5ff;border-radius:8px;line-height:1.5;font-size:14px}.note{color:#63718c;font-size:13px;line-height:1.6}.err{color:#b42318;margin-top:12px}.success{padding:12px;border-radius:8px;background:#d8f8e7;color:#116a42}button:focus-visible,a:focus-visible{outline:3px solid #faad14;outline-offset:2px}h2{font-size:20px;margin:0 0 10px}
</style>
@endpush
@section('content')

 <div class="box"><h2>{{ $suratKeluar->perihal }}</h2>
 <p class="note">Akun penandatangan: <strong>{{ auth()->user()->nama_lengkap ?? auth()->user()->name ?? 'Pejabat HIMSI' }}</strong>. Anda hanya bisa mengunggah dan mengatur tanda tangan milik Anda sendiri. File surat asli tetap aman.</p>
 <div class="tip">Pilih gambar PNG/JPG dari File Explorer, geser menggunakan mouse atau sentuhan, atur ukuran, lalu klik <strong>Simpan Gambar & Posisi</strong>. Gambar benar-benar ditempel ke <strong>PDF final setelah seluruh persetujuan lengkap</strong>, bukan saat diunggah.</div>
 @if(session('success'))<p class="success">{{ session('success') }}</p>@endif
 @if($errors->any())<div class="err" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
 </div>
 <div class="grid">
 <section class="box">
 <h2>Pratinjau PDF & geser tanda tangan</h2>
 <div class="toolbar"><button type="button" class="btn-secondary" id="prev">‹ Sebelumnya</button><span id="info">Memuat PDF...</span><button type="button" class="btn-secondary" id="next">Berikutnya ›</button></div>
 <div class="viewer" id="viewer"><div class="sheet" id="sheet"><canvas id="pdfCanvas"></canvas><div id="signature" aria-label="Gambar tanda tangan yang bisa digeser"><img id="ttdPreview" alt="Pratinjau tanda tangan" draggable="false"></div></div></div>
 <p class="note">Gambar di atas adalah pratinjau posisi. Periksa halaman dan tanda tangan sebelum menyetujui. PDF final tidak dapat ditimpa setelah terbit.</p>
 </section>
 <section class="box">
 <h2>Gambar tanda tangan Anda</h2>
 @if($terkunci)<p class="tip">Posisi Anda sudah terkunci karena keputusan persetujuan telah diberikan.</p>@endif
 <form method="POST" action="{{ route('tanda-tangan.surat.simpan',$suratKeluar) }}" enctype="multipart/form-data" id="form-sign">
 @csrf
 <label for="fileTtd">Pilih gambar dari File Explorer (PNG/JPG, maks. 1 MB)</label>
 <input type="file" id="fileTtd" name="gambar" accept="image/png,image/jpeg,.png,.jpg,.jpeg" {{ $terkunci ? 'disabled' : '' }}>
 @if($penempatan)<p class="note">Gambar untuk surat ini sudah tersimpan. Kamu boleh mengubahnya sebelum memberikan keputusan.</p>@endif
 <label for="size">Lebar tanda tangan: <span id="sizeLabel">{{ $penempatan->lebar_mm ?? 48 }}</span> mm</label>
 <input type="range" id="size" min="15" max="80" value="{{ $penempatan->lebar_mm ?? 48 }}" {{ $terkunci ? 'disabled' : '' }}>
 <input type="hidden" name="halaman" id="halaman" value="{{ $penempatan->halaman ?? '' }}">
 <input type="hidden" name="x_mm" id="x" value="{{ $penempatan->x_mm ?? 50 }}">
 <input type="hidden" name="y_mm" id="y" value="{{ $penempatan->y_mm ?? 70 }}">
 <input type="hidden" name="lebar_mm" id="lebar" value="{{ $penempatan->lebar_mm ?? 48 }}">
 <label style="font-weight:normal"><input type="checkbox" name="konfirmasi_gambar" value="1" {{ $terkunci ? 'disabled' : 'required' }}> Saya menegaskan ini tanda tangan milik saya sendiri dan hanya boleh digunakan untuk surat ini setelah saya menyetujuinya.</label>
 <button style="width:100%;margin-top:15px" type="submit" id="save" disabled>Simpan Gambar & Posisi</button>
 <div id="msg" class="err" aria-live="polite"></div>
 </form>
 <p class="note">Surat Word (.doc/.docx) tetap dapat disetujui tanpa konversi, tetapi editor tempel gambar ini hanya untuk PDF.</p>
 </section>
 </div>

@endsection
@push('scripts')
<script src="{{ asset('vendor/himsi-pdf/pdf.min.js') }}"></script>
<script>
const pdfjsLib = window.pdfjsLib;
if (!pdfjsLib) {
    document.getElementById('info').textContent = 'PDF.js gagal dimuat';
    throw new Error('Pustaka PDF.js tidak tersedia.');
}
const el=id=>document.getElementById(id);
const canvas=el('pdfCanvas'),ctx=canvas.getContext('2d'),sheet=el('sheet'),sign=el('signature'),img=el('ttdPreview');
let pdf=null,pageNumber=Number(@json($penempatan->halaman ?? 1)),widthMm=210,heightMm=297,renderBusy=false,activeUrl=null;
let xMm=Number(el('x').value),yMm=Number(el('y').value),wMm=Number(el('lebar').value);
let ratio=0.40,hasImage=false;
const locked=@json($terkunci);
function showMessage(msg){el('msg').textContent=msg;}
function mmToPxX(v){return v/widthMm*canvas.width}function mmToPxY(v){return v/heightMm*canvas.height}
function reflow(){if(!hasImage)return;const h=wMm*ratio;xMm=Math.max(0,Math.min(xMm,widthMm-wMm));yMm=Math.max(0,Math.min(yMm,heightMm-h));sign.style.display='block';sign.style.left=mmToPxX(xMm)+'px';sign.style.top=mmToPxY(yMm)+'px';sign.style.width=mmToPxX(wMm)+'px';el('x').value=xMm.toFixed(2);el('y').value=yMm.toFixed(2);el('lebar').value=wMm;el('halaman').value=pageNumber;el('sizeLabel').textContent=wMm;}
function showImage(url){img.onload=()=>{ratio=img.naturalHeight/Math.max(1,img.naturalWidth);hasImage=true;sign.style.display='block';reflow();if(!locked && pdf)el('save').disabled=false;};img.src=url;}
@if($penempatan)
showImage(@json(route('tanda-tangan.surat.gambar',$suratKeluar)));
@endif
el('fileTtd').addEventListener('change',event=>{const file=event.target.files[0];if(!file)return;
 if(!['image/png','image/jpeg'].includes(file.type)){showMessage('Pilih PNG atau JPG.');return;}
 if(file.size>1048576){showMessage('Ukuran maksimal 1 MB.');return;}
 if(activeUrl)URL.revokeObjectURL(activeUrl);activeUrl=URL.createObjectURL(file);showImage(activeUrl);showMessage('');});
async function draw(n){if(!pdf || renderBusy)return;renderBusy=true;try{pageNumber=Math.max(1,Math.min(pdf.numPages,n));const page=await pdf.getPage(pageNumber);const base=page.getViewport({scale:1});const available=Math.max(220,el('viewer').clientWidth-32);const viewport=page.getViewport({scale:Math.min(1.6,available/base.width)});canvas.width=Math.round(viewport.width);canvas.height=Math.round(viewport.height);sheet.style.width=canvas.width+'px';sheet.style.height=canvas.height+'px';widthMm=base.width*25.4/72;heightMm=base.height*25.4/72;await page.render({canvasContext:ctx,viewport}).promise;el('info').textContent='Halaman '+pageNumber+' / '+pdf.numPages;reflow();}catch(e){showMessage('PDF gagal ditampilkan: '+e.message)}finally{renderBusy=false;}}
el('prev').addEventListener('click',()=>draw(pageNumber-1));el('next').addEventListener('click',()=>draw(pageNumber+1));
el('size').addEventListener('input',e=>{wMm=Number(e.target.value);reflow();});
let active=false,offsetX=0,offsetY=0;
sign.addEventListener('pointerdown',e=>{if(locked)return;const rect=sign.getBoundingClientRect();offsetX=e.clientX-rect.left;offsetY=e.clientY-rect.top;sign.setPointerCapture(e.pointerId);active=true;e.preventDefault();});
sign.addEventListener('pointermove',e=>{if(!active||!sign.hasPointerCapture(e.pointerId))return;const rect=canvas.getBoundingClientRect();xMm=(e.clientX-rect.left-offsetX)/rect.width*widthMm;yMm=(e.clientY-rect.top-offsetY)/rect.height*heightMm;reflow();});
sign.addEventListener('pointerup',e=>{active=false;if(sign.hasPointerCapture(e.pointerId))sign.releasePointerCapture(e.pointerId)});
el('form-sign').addEventListener('submit',e=>{if(locked||!pdf||!hasImage){e.preventDefault();showMessage('Unggah gambar dan tunggu sampai PDF selesai dimuat.');return;}reflow();});
async function start(){try{pdfjsLib.GlobalWorkerOptions.workerSrc=@json(asset('vendor/himsi-pdf/pdf.worker.min.js'));pdf=await pdfjsLib.getDocument({url:@json(route('surat-keluar.dokumen.lihat',$suratKeluar)),withCredentials:true}).promise;await draw(pageNumber);if(!locked && hasImage)el('save').disabled=false;}catch(e){el('info').textContent='PDF gagal dimuat';showMessage('Tidak dapat membuka PDF: '+e.message)}}start();
</script>
@endpush
