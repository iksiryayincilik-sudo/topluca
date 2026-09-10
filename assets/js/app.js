document.addEventListener('DOMContentLoaded',function(){
  var body=document.body;
  var mb=document.querySelector('[data-menu-button]'),menu=document.querySelector('[data-main-menu]');
  if(mb&&menu){mb.addEventListener('click',function(){var open=menu.classList.toggle('open');mb.setAttribute('aria-expanded',open?'true':'false');body.classList.toggle('nav-open',open);});}
  document.addEventListener('click',function(e){if(menu&&menu.classList.contains('open')&&!menu.contains(e.target)&&e.target!==mb){menu.classList.remove('open');body.classList.remove('nav-open');}});

  document.querySelectorAll('.qty-control').forEach(function(q){
    var input=q.querySelector('input'),minus=q.querySelector('[data-qty-minus]'),plus=q.querySelector('[data-qty-plus]');
    function set(v){var min=parseInt(input.min||'1',10),max=parseInt(input.max||'99',10);input.value=Math.min(max,Math.max(min,v||min));input.dispatchEvent(new Event('change',{bubbles:true}));}
    if(minus)minus.addEventListener('click',function(){set(parseInt(input.value||'1',10)-1);});
    if(plus)plus.addEventListener('click',function(){set(parseInt(input.value||'1',10)+1);});
    input.addEventListener('blur',function(){set(parseInt(input.value||'1',10));});
  });

  var fo=document.querySelector('[data-filter-open]'),fp=document.querySelector('[data-filter-panel]'),fc=document.querySelector('[data-filter-close]');
  function filterClose(){if(fp)fp.classList.remove('open');body.classList.remove('drawer-open');}
  if(fo&&fp)fo.addEventListener('click',function(){fp.classList.add('open');body.classList.add('drawer-open');});
  if(fc&&fp)fc.addEventListener('click',filterClose);

  document.querySelectorAll('[data-thumb]').forEach(function(t){t.addEventListener('click',function(){var src=t.getAttribute('data-src'),main=document.querySelector('[data-main-image]');if(main&&src){main.classList.add('changing');setTimeout(function(){main.src=src;main.classList.remove('changing');},80);}document.querySelectorAll('[data-thumb]').forEach(function(x){x.classList.remove('active');});t.classList.add('active');});});

  document.querySelectorAll('[data-tab]').forEach(function(btn){btn.addEventListener('click',function(){var key=btn.getAttribute('data-tab'),scope=btn.closest('.detail-tabs')||document;scope.querySelectorAll('[data-tab]').forEach(function(x){x.classList.remove('active');});scope.querySelectorAll('[data-panel]').forEach(function(x){x.classList.remove('active');});btn.classList.add('active');var p=scope.querySelector('[data-panel="'+key+'"]');if(p)p.classList.add('active');});});

  var slider=document.querySelector('[data-hero-slider]');
  if(slider){
    var slides=[].slice.call(slider.querySelectorAll('[data-slide]')),dots=[].slice.call(slider.querySelectorAll('[data-slider-dot]')),index=0,timer=null,interval=Math.max(2500,parseInt(slider.dataset.interval||'5500',10)),autoplay=slider.dataset.autoplay!=='0';
    function show(next){if(!slides.length)return;index=(next+slides.length)%slides.length;slides.forEach(function(s,i){s.classList.toggle('active',i===index);s.setAttribute('aria-hidden',i===index?'false':'true');});dots.forEach(function(d,i){d.classList.toggle('active',i===index);});}
    function stop(){if(timer){clearInterval(timer);timer=null;}}
    function start(){stop();if(autoplay&&slides.length>1)timer=setInterval(function(){show(index+1);},interval);}
    var prev=slider.querySelector('[data-slider-prev]'),next=slider.querySelector('[data-slider-next]');
    if(prev)prev.addEventListener('click',function(){show(index-1);start();});if(next)next.addEventListener('click',function(){show(index+1);start();});
    dots.forEach(function(d){d.addEventListener('click',function(){show(parseInt(d.dataset.sliderDot||'0',10));start();});});
    slider.addEventListener('mouseenter',stop);slider.addEventListener('mouseleave',start);slider.addEventListener('focusin',stop);slider.addEventListener('focusout',start);
    var touchX=null;slider.addEventListener('touchstart',function(e){touchX=e.touches[0].clientX;},{passive:true});slider.addEventListener('touchend',function(e){if(touchX===null)return;var dx=e.changedTouches[0].clientX-touchX;if(Math.abs(dx)>45){show(index+(dx<0?1:-1));start();}touchX=null;},{passive:true});
    show(0);start();
  }

  document.querySelectorAll('[data-image-input]').forEach(function(inp){inp.addEventListener('change',function(){var target=document.querySelector(inp.getAttribute('data-preview'));if(!target)return;target.innerHTML='';Array.from(inp.files||[]).slice(0,8).forEach(function(f){if(!f.type.startsWith('image/'))return;var img=document.createElement('img');img.src=URL.createObjectURL(f);img.alt='Önizleme';target.appendChild(img);});});});
});