document.addEventListener('DOMContentLoaded',()=>{
 const side=document.getElementById('adminSidebar'),overlay=document.getElementById('adminOverlay'),btn=document.getElementById('adminMenuBtn');
 const close=()=>{side&&side.classList.remove('open');overlay&&overlay.classList.remove('open');document.body.classList.remove('menu-open')};
 if(btn)btn.addEventListener('click',()=>{side&&side.classList.toggle('open');overlay&&overlay.classList.toggle('open');document.body.classList.toggle('menu-open')});
 if(overlay)overlay.addEventListener('click',close);
 document.querySelectorAll('[data-confirm]').forEach(el=>el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm||'Bu işlem yapılsın mı?'))e.preventDefault()}));
 document.querySelectorAll('[data-file-preview]').forEach(input=>input.addEventListener('change',()=>{
   const target=document.querySelector(input.dataset.filePreview);if(!target)return;target.innerHTML='';
   [...input.files].forEach(file=>{if(!file.type.startsWith('image/'))return;const r=new FileReader();r.onload=()=>{const wrap=document.createElement('div');wrap.className='live-preview-item';wrap.innerHTML='<img src="'+r.result+'"><small>'+file.name+'</small>';target.appendChild(wrap)};r.readAsDataURL(file)});
 }));
 document.querySelectorAll('[data-tab-target]').forEach(btn=>btn.addEventListener('click',()=>{
   const group=btn.closest('[data-tabs]');if(!group)return;group.querySelectorAll('[data-tab-target]').forEach(x=>x.classList.remove('active'));group.querySelectorAll('[data-tab-panel]').forEach(x=>x.hidden=true);btn.classList.add('active');const p=group.querySelector('[data-tab-panel="'+btn.dataset.tabTarget+'"]');if(p)p.hidden=false;
 }));
});