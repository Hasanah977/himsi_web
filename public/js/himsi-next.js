document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-submit]').forEach(input => input.addEventListener('change', () => input.form.requestSubmit()));
    const forum = document.querySelector('[data-forum-workspace]');
    if(forum){
        const infoButtons = forum.querySelectorAll('[data-forum-info]');
        function toggleInfo(){const hidden=forum.classList.toggle('info-hidden');infoButtons.forEach(button=>button.setAttribute('aria-expanded',String(!hidden)));}
        infoButtons.forEach(button=>button.addEventListener('click',toggleInfo));
        if(window.matchMedia('(max-width:1200px)').matches) toggleInfo();
        forum.querySelectorAll('[data-forum-search]').forEach(button=>button.addEventListener('click',()=>{const search=forum.querySelector('.forum-message-search');search.hidden=false;search.querySelector('[name="search"]').focus();if(window.matchMedia('(max-width:1200px)').matches&&!forum.classList.contains('info-hidden'))toggleInfo();}));
        forum.querySelector('[data-forum-media]')?.addEventListener('click',()=>{const media=forum.querySelector('.forum-media');media.hidden=!media.hidden;});
        const file=forum.querySelector('#chat-file'), preview=forum.querySelector('[data-forum-file-preview]'), composer=forum.querySelector('.forum-compose'), body=forum.querySelector('#chat-body');
        if(file){
            file.addEventListener('change',()=>{body.setCustomValidity('');const selected=file.files[0];file.setCustomValidity(selected&&selected.size>10*1024*1024?'Ukuran lampiran maksimal 10 MB.':'');preview.hidden=!selected;preview.querySelector('span').textContent=selected?selected.name+' · '+(selected.size/1024).toFixed(0)+' KB':'';file.reportValidity();});
            forum.querySelector('[data-forum-remove-file]').addEventListener('click',()=>{file.value='';file.setCustomValidity('');preview.hidden=true;});
            composer.addEventListener('submit',event=>{body.setCustomValidity(!body.value.trim()&&!file.files.length?'Tulis pesan atau pilih lampiran.':'');if(!composer.reportValidity())event.preventDefault();});
            body.addEventListener('input',()=>body.setCustomValidity(''));
            forum.querySelectorAll('[data-forum-emoji]').forEach(button=>button.addEventListener('click',()=>{body.setRangeText(button.dataset.forumEmoji,body.selectionStart,body.selectionEnd,'end');body.focus();body.dispatchEvent(new Event('input'));}));
        }
        const messages=forum.querySelector('[data-messages]');if(messages&&!new URLSearchParams(location.search).has('search'))messages.scrollTop=messages.scrollHeight;
    }
    const duesForm=document.querySelector('[data-dues-upload]');
    if(duesForm){
        const month=duesForm.querySelector('[data-dues-month]');
        const syncAction=()=>{duesForm.action=month.selectedOptions[0].dataset.action;};syncAction();month.addEventListener('change',syncAction);
        const input=duesForm.querySelector('[data-upload-preview]'), result=duesForm.querySelector('[data-upload-result]'), zone=duesForm.querySelector('[data-upload-zone]');let previewUrl;
        input.addEventListener('change',()=>{
            if(previewUrl)URL.revokeObjectURL(previewUrl);previewUrl=null;
            result.replaceChildren();const file=input.files[0];let error='';
            if(file&&file.size>5*1024*1024)error='Ukuran berkas maksimal 5 MB.';else if(file&&!/\.(jpe?g|png|pdf)$/i.test(file.name))error='Gunakan JPG, PNG, atau PDF.';
            input.setCustomValidity(error);
            if(file&&!error&&['image/jpeg','image/png'].includes(file.type)){const img=document.createElement('img');previewUrl=URL.createObjectURL(file);img.src=previewUrl;img.alt='Pratinjau bukti pembayaran';result.append(img);}else{const icon=document.createElement('i');icon.className='bi bi-file-earmark'+(file?.type==='application/pdf'?'-pdf':'');result.append(icon);}
            const label=document.createElement('small');label.textContent=error||(file?file.name:'Belum ada berkas dipilih');result.append(label);if(error)input.reportValidity();
        });
        zone.addEventListener('dragover',event=>{event.preventDefault();zone.classList.add('is-over');});zone.addEventListener('dragleave',()=>zone.classList.remove('is-over'));
        zone.addEventListener('drop',event=>{event.preventDefault();zone.classList.remove('is-over');if(event.dataTransfer.files.length){const transfer=new DataTransfer();transfer.items.add(event.dataTransfer.files[0]);input.files=transfer.files;input.dispatchEvent(new Event('change'));}});
        window.addEventListener('pagehide',()=>{if(previewUrl)URL.revokeObjectURL(previewUrl);});
    }
});
