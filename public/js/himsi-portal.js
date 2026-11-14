(() => {
'use strict';
document.querySelectorAll('[data-hp-password]').forEach(button => button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.hpPassword), reveal = input.type === 'password';
    input.type = reveal ? 'text' : 'password'; button.setAttribute('aria-pressed', String(reveal));
    button.setAttribute('aria-label', reveal ? 'Sembunyikan password' : 'Tampilkan password');
    button.querySelector('i').className = reveal ? 'bi bi-eye-slash' : 'bi bi-eye';
}));
const form = document.getElementById('hp-registration'), draftKey = 'himsi-registration-draft-v2';
if (document.querySelector('[data-registration-complete]')) { try { sessionStorage.removeItem(draftKey); } catch {} }
if (!form) return;
const panels = [...form.querySelectorAll('[data-hp-panel]')], first = panels[0];
const password = document.getElementById('hp-new-password'), confirmation = document.getElementById('hp-confirm-password');
const send = document.getElementById('hp-send'), review = document.getElementById('hp-review');
const uploads = [...form.querySelectorAll('input[type="file"]')];
let stage = 1, submitting = false;
form.noValidate = true;
const fields = [...first.querySelectorAll('input[name],select[name],textarea[name]')].filter(input => !['password','password_confirmation'].includes(input.name));
const draftStatus = document.getElementById('hp-draft-status');
try {
    const saved = JSON.parse(sessionStorage.getItem(draftKey) || 'null');
    if (saved && Date.now() - saved.time < 24 * 60 * 60 * 1000) {
        fields.forEach(input => { if (!input.value && typeof saved.data[input.name] === 'string') input.value = saved.data[input.name]; });
        draftStatus.textContent = 'Draf dipulihkan · ' + new Date(saved.time).toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'});
    } else sessionStorage.removeItem(draftKey);
} catch { draftStatus.textContent = 'Penyimpanan sementara tidak tersedia'; }
function saveDraft() {
    try {
        sessionStorage.setItem(draftKey, JSON.stringify({time:Date.now(), data:Object.fromEntries(fields.map(input => [input.name,input.value]))}));
        draftStatus.textContent = 'Draf tersimpan · ' + new Date().toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
    } catch { draftStatus.textContent = 'Draf belum tersimpan'; }
}
fields.forEach(input => input.addEventListener('change', saveDraft));
let draftTimer;
first.addEventListener('input', event => { if (!fields.includes(event.target)) return; clearTimeout(draftTimer); draftTimer = setTimeout(saveDraft, 700); });
const strength = document.createElement('div'); strength.className='hp-strength'; strength.setAttribute('aria-live','polite');
strength.append(document.createTextNode('Kekuatan kata sandi: '));
for(let i=0;i<4;i++) strength.append(document.createElement('span'));
password.closest('.hp-password').after(strength);
function passwordValidity() {
    confirmation.setCustomValidity(confirmation.value !== password.value ? 'Konfirmasi password tidak sama.' : '');
    const score = [password.value.length >= 8, /[A-Z]/.test(password.value) && /[a-z]/.test(password.value), /[0-9]/.test(password.value), /[^a-zA-Z0-9]/.test(password.value)].filter(Boolean).length;
    strength.querySelectorAll('span').forEach((bar,i) => bar.classList.toggle('filled',i < score));
    strength.setAttribute('aria-label','Kekuatan kata sandi: ' + ['belum diisi','lemah','cukup','baik','kuat'][password.value ? score : 0]);
}
password.addEventListener('input',passwordValidity); confirmation.addEventListener('input',passwordValidity);
function changeStage(next) {
    stage=next; panels.forEach(panel => {panel.hidden=Number(panel.dataset.hpPanel)!==next;});
    document.querySelectorAll('[data-hp-step]').forEach(item => {
        const n=Number(item.dataset.hpStep); item.classList.toggle('is-current',n===next);item.classList.toggle('is-done',n<next);
        if(n===next) item.setAttribute('aria-current','step'); else item.removeAttribute('aria-current');
    });
    panels[next-1].querySelector('h1').focus();
}
function validFirst() { passwordValidity(); const invalid=[...first.querySelectorAll('input,select,textarea')].find(input=>!input.checkValidity()); if(invalid){changeStage(1);invalid.reportValidity();return false;}return true; }
function validUploads() { const invalid=uploads.find(input=>!input.checkValidity()); if(invalid){changeStage(2);invalid.reportValidity();return false;}return true; }
function renderReview() {
    const list=document.getElementById('hp-review-data'); list.replaceChildren();
    [...fields,...uploads].forEach(input=>{const label=form.querySelector('label[for="'+input.id+'"]');const term=document.createElement('dt'), detail=document.createElement('dd');term.textContent=label?.textContent.replace(/\s*\*$/,'')||input.name;detail.textContent=input.type==='file'?(input.files[0]?.name||'—'):(input.value||'—');list.append(term,detail);});
}
document.getElementById('hp-next').addEventListener('click',()=>{if(validFirst())changeStage(2);});
document.getElementById('hp-previous').addEventListener('click',()=>changeStage(1));
document.getElementById('hp-back-upload').addEventListener('click',()=>changeStage(2));
review.addEventListener('click',()=>{if(validFirst()&&validUploads()){renderReview();changeStage(3);}});
function updateCount(){const count=uploads.filter(input=>input.files.length&&input.checkValidity()).length;document.getElementById('hp-upload-count').textContent=count+' dari 3 berkas dipilih';review.disabled=count!==3||submitting;}
uploads.forEach(input=>{
    const zone=input.closest('.hp-dropzone');
    input.addEventListener('change',()=>{const file=input.files[0];let error='';if(file&&file.size>5*1024*1024)error='Ukuran berkas maksimal 5 MB.';else if(file&&!/\.(jpe?g|png|pdf)$/i.test(file.name))error='Pilih berkas JPG, PNG, atau PDF.';input.setCustomValidity(error);zone.querySelector('[data-hp-filename]').textContent=file?file.name:'Tarik berkas ke sini atau klik untuk memilih';updateCount();if(error)input.reportValidity();});
    zone.addEventListener('dragover',event=>{event.preventDefault();zone.classList.add('is-over');});zone.addEventListener('dragleave',()=>zone.classList.remove('is-over'));
    zone.addEventListener('drop',event=>{event.preventDefault();zone.classList.remove('is-over');if(event.dataTransfer.files.length){const transfer=new DataTransfer();transfer.items.add(event.dataTransfer.files[0]);input.files=transfer.files;input.dispatchEvent(new Event('change'));}});
});
form.addEventListener('submit',event=>{
    if(submitting){event.preventDefault();return;}
    if(!validFirst()){event.preventDefault();return;}
    if(stage===1){event.preventDefault();changeStage(2);return;}
    if(!validUploads()){event.preventDefault();return;}
    if(stage!==3){event.preventDefault();renderReview();changeStage(3);return;}
    const confirmed=document.getElementById('hp-confirm-data');if(!confirmed.checkValidity()){event.preventDefault();confirmed.reportValidity();return;}
    submitting=true;send.disabled=true;send.textContent='Mengirim pendaftaran…';
});
window.addEventListener('pageshow',()=>{submitting=false;send.disabled=false;send.textContent='Kirim Pendaftaran';updateCount();});
})();
