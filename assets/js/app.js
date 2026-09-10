document.addEventListener('DOMContentLoaded',()=>{
  const body=document.body;
  const drawer=document.querySelector('[data-mobile-drawer]');
  document.querySelectorAll('[data-menu-open]').forEach(btn=>btn.addEventListener('click',()=>{drawer?.classList.add('open');drawer?.setAttribute('aria-hidden','false');body.style.overflow='hidden';}));
  document.querySelectorAll('[data-menu-close]').forEach(btn=>btn.addEventListener('click',()=>{drawer?.classList.remove('open');drawer?.setAttribute('aria-hidden','true');body.style.overflow='';}));

  const filters=document.querySelector('[data-filters]');
  document.querySelectorAll('[data-filter-open]').forEach(btn=>btn.addEventListener('click',()=>{filters?.classList.add('open');body.style.overflow='hidden';}));
  document.querySelectorAll('[data-filter-close]').forEach(btn=>btn.addEventListener('click',()=>{filters?.classList.remove('open');body.style.overflow='';}));

  document.querySelectorAll('[data-qty]').forEach(box=>{
    const input=box.querySelector('input');
    box.querySelector('[data-minus]')?.addEventListener('click',()=>{const min=parseInt(input.min||'1',10);input.value=Math.max(min,(parseInt(input.value||'1',10)-1)).toString();input.dispatchEvent(new Event('change'));});
    box.querySelector('[data-plus]')?.addEventListener('click',()=>{const max=parseInt(input.max||'99',10);input.value=Math.min(max,(parseInt(input.value||'1',10)+1)).toString();input.dispatchEvent(new Event('change'));});
  });

  const mainImage=document.querySelector('[data-main-image]');
  document.querySelectorAll('[data-thumb]').forEach(btn=>btn.addEventListener('click',()=>{
    document.querySelectorAll('[data-thumb]').forEach(x=>x.classList.remove('active'));btn.classList.add('active');
    if(mainImage){mainImage.src=btn.dataset.src||mainImage.src;mainImage.alt=btn.dataset.alt||mainImage.alt;}
  }));

  document.querySelectorAll('[data-tab]').forEach(btn=>btn.addEventListener('click',()=>{
    const group=btn.closest('[data-tabs]');if(!group)return;
    group.querySelectorAll('[data-tab]').forEach(x=>x.classList.remove('active'));group.querySelectorAll('[data-panel]').forEach(x=>x.classList.remove('active'));
    btn.classList.add('active');group.querySelector(`[data-panel="${btn.dataset.tab}"]`)?.classList.add('active');
  }));

  const billingToggle=document.querySelector('[data-billing-toggle]');
  const billingFields=document.querySelector('[data-billing-fields]');
  const syncBilling=()=>{if(!billingToggle||!billingFields)return;billingFields.hidden=billingToggle.checked;};
  billingToggle?.addEventListener('change',syncBilling);syncBilling();

  document.querySelectorAll('[data-auto-submit]').forEach(el=>el.addEventListener('change',()=>el.form?.submit()));

  const copyBtns=document.querySelectorAll('[data-copy]');
  copyBtns.forEach(btn=>btn.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(btn.dataset.copy||'');const old=btn.textContent;btn.textContent='Kopyalandı';setTimeout(()=>btn.textContent=old,1200);}catch(e){}}));
});
