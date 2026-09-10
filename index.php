<?php
require __DIR__.'/app/bootstrap.php';
page_view('home');
$pageTitle=seo_value('site_title','TOPLUCA | Kırtasiye, Kitap, Teknoloji ve Ofis Ürünleri');
$pageDescription=seo_value('site_description','Kırtasiye, kitap, teknoloji ve ofis ihtiyaçları TOPLUCA’da.');
$pageKeywords=seo_value('site_keywords','kırtasiye, kitap, ofis ürünleri, teknoloji');
$cats=main_categories();
$heroBanners=active_banners('home_hero');$stripBanners=active_banners('home_strip');$midLeft=active_banners('home_mid_left');$midRight=active_banners('home_mid_right');
$features=table_exists('homepage_feature_boxes')?db()->query("SELECT * FROM homepage_feature_boxes WHERE is_active=1 ORDER BY sort_order,id LIMIT 4")->fetchAll():[];
$products=db()->query("SELECT p.*,b.name brand_name,c.name category_name FROM products p LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1 ORDER BY p.is_bestseller DESC,p.sales_count DESC,p.id DESC LIMIT 10")->fetchAll();
$featured=db()->query("SELECT p.*,b.name brand_name,c.name category_name FROM products p LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1 ORDER BY p.is_featured DESC,p.id DESC LIMIT 10")->fetchAll();
$brands=db()->query("SELECT * FROM brands WHERE is_active=1 ORDER BY sort_order,name LIMIT 14")->fetchAll();
$sectionRows=active_home_sections();$sections=[];foreach($sectionRows as $r)$sections[$r['section_type']]=$r;
function hs(array $sections,string $key,string $field,string $fallback): string{return trim((string)($sections[$key][$field]??''))?:$fallback;}
require __DIR__.'/includes/header.php';
?>
<section class="home-hero-section">
<div class="container home-hero-grid">
  <div class="hero-slider" data-hero-slider data-autoplay="<?=h(setting('banner_autoplay','1'))?>" data-interval="<?=h(setting('banner_interval','5500'))?>">
    <div class="hero-slides">
    <?php if($heroBanners):foreach($heroBanners as $i=>$b):?>
      <article class="hero-slide <?=$i===0?'active':''?> align-<?=h($b['content_align']??'left')?>" style="<?=h(banner_style($b))?>" data-slide>
        <?php if($b['desktop_image']||$b['mobile_image']):?><picture class="hero-picture"><?php if($b['mobile_image']):?><source media="(max-width:700px)" srcset="<?=app_url($b['mobile_image'])?>"><?php endif;?><?php if($b['desktop_image']):?><img src="<?=app_url($b['desktop_image'])?>" alt="<?=h($b['title'])?>" <?=$i===0?'fetchpriority="high"':'loading="lazy"'?>><?php endif;?></picture><?php endif;?>
        <div class="hero-overlay"></div>
        <div class="hero-content">\          <?php if($b['eyebrow']):?><span class="hero-eyebrow"><?=h($b['eyebrow'])?></span><?php endif;?>
          <h1><?=h($b['title'])?></h1>
          <?php if($b['subtitle']):?><p><?=h($b['subtitle'])?></p><?php endif;?>
          <?php if($b['link_url']):?><a class="hero-cta" href="<?=h(strpos($b['link_url'],'http')===0?$b['link_url']:app_url($b['link_url']))?>" <?=$b['open_new_tab']?'target="_blank" rel="noopener"':''?>><?=h($b['button_text']?:'Şimdi İncele')?> <span>→</span></a><?php endif;?>
        </div>
      </article>
    <?php endforeach;else:?>
      <article class="hero-slide active fallback-hero" data-slide><div class="hero-content"><span class="hero-eyebrow">TOPLUCA.NET</span><h1>İhtiyacın olan ne varsa, <strong>Topluca.</strong></h1><p>Kırtasiye, kitap, teknoloji ve ofis ihtiyaçlarını tek sepette buluşturan profesyonel alışveriş deneyimi.</p><a class="hero-cta" href="<?=app_url('search.php')?>">Alışverişe Başla <span>→</span></a></div><div class="fallback-art"><i></i><i></i><i></i></div></article>
    <?php endif;?>
    </div>
    <?php if(count($heroBanners)>1):?><button class="hero-arrow prev" type="button" data-slider-prev aria-label="Önceki banner">‹</button><button class="hero-arrow next" type="button" data-slider-next aria-label="Sonraki banner">›</button><div class="hero-dots"><?php foreach($heroBanners as $i=>$b):?><button class="<?=$i===0?'active':''?>" type="button" data-slider-dot="<?=$i?>" aria-label="Banner <?=$i+1?>"></button><?php endforeach;?></div><?php endif;?>
  </div>
  <aside class="hero-benefits">
    <?php if($features):foreach($features as $f):?><a class="hero-benefit" href="<?=h($f['link_url']?app_url($f['link_url']):'#')?>"><span class="hero-benefit-icon"><?=h($f['icon'])?></span><span><b><?=h($f['title'])?></b><small><?=h($f['subtitle'])?></small></span><i>›</i></a><?php endforeach;else:?><div class="hero-benefit"><span class="hero-benefit-icon">⚡</span><span><b>Ankara’da Aynı Gün</b><small>Adres ve sepete göre otomatik uygunluk</small></span></div><div class="hero-benefit"><span class="hero-benefit-icon">▤</span><span><b>Toplu Alım Avantajı</b><small>Adet arttıkça kademeli fiyatlar</small></span></div><div class="hero-benefit"><span class="hero-benefit-icon">⌕</span><span><b>Akıllı Arama</b><small>Barkod, ISBN, marka ve kategori</small></span></div><?php endif;?>
  </aside>
</div>
</section>
<?php if($stripBanners):$b=$stripBanners[0];?><section class="home-strip-section"><div class="container"><a class="home-strip-banner" href="<?=h($b['link_url']?app_url($b['link_url']):'#')?>"><?php if($b['desktop_image']):?><picture><?php if($b['mobile_image']):?><source media="(max-width:700px)" srcset="<?=app_url($b['mobile_image'])?>"><?php endif;?><img src="<?=app_url($b['desktop_image'])?>" alt="<?=h($b['title'])?>" loading="lazy"></picture><?php endif;?><span class="strip-copy"><b><?=h($b['title'])?></b><small><?=h($b['subtitle'])?></small></span></a></div></section><?php endif;?>
<section class="section home-categories"><div class="container"><div class="section-head"><div><small><?=h(hs($sections,'categories','title','Kategoriler'))?></small><h2><?=h(hs($sections,'categories','subtitle','Ne arıyorsanız burada'))?></h2></div><a href="<?=app_url('search.php')?>">Tüm ürünleri gör →</a></div><div class="category-grid"><?php foreach($cats as $c):?><a class="category-card" href="<?=app_url('category.php?slug='.urlencode($c['slug']))?>"><?php if($c['image']):?><span class="cat-image"><img src="<?=app_url($c['image'])?>" alt="<?=h($c['name'])?>" loading="lazy"></span><?php else:?><span class="cat-icon"><?=h($c['icon']?:'•')?></span><?php endif;?><strong><?=h($c['name'])?></strong><span class="cat-link">Ürünleri İncele <i>→</i></span></a><?php endforeach;?></div></div></section>
<section class="section soft"><div class="container"><div class="section-head"><div><small><?=h(hs($sections,'bestsellers','title','Popüler'))?></small><h2><?=h(hs($sections,'bestsellers','subtitle','Çok Satanlar'))?></h2></div><a href="<?=app_url('search.php?sort=popular')?>">Tüm ürünler →</a></div><div class="products-grid"><?php foreach($products as $p){require __DIR__.'/includes/product-card.php';}?></div></div></section>
<?php if($midLeft||$midRight):?><section class="section home-promos"><div class="container promo-grid"><?php foreach([($midLeft[0]??null),($midRight[0]??null)] as $idx=>$b):if(!$b)continue;?><a class="promo-banner <?=$idx?'dark':''?>" href="<?=h($b['link_url']?app_url($b['link_url']):'#')?>" style="<?=h(banner_style($b))?>"><?php if($b['desktop_image']):?><picture><?php if($b['mobile_image']):?><source media="(max-width:700px)" srcset="<?=app_url($b['mobile_image'])?>"><?php endif;?><img src="<?=app_url($b['desktop_image'])?>" alt="<?=h($b['title'])?>" loading="lazy"></picture><?php endif;?><span class="promo-overlay"></span><span class="promo-copy"><?php if($b['eyebrow']):?><small><?=h($b['eyebrow'])?></small><?php endif;?><b><?=h($b['title'])?></b><em><?=h($b['button_text']?:'Şimdi İncele')?> →</em></span></a><?php endforeach;?></div></section><?php endif;?>
<section class="section soft"><div class="container"><div class="section-head"><div><small><?=h(hs($sections,'featured','title','Sizin İçin'))?></small><h2><?=h(hs($sections,'featured','subtitle','Öne Çıkan Ürünler'))?></h2></div></div><div class="products-grid"><?php foreach($featured as $p){require __DIR__.'/includes/product-card.php';}?></div></div></section>
<section class="section brand-section"><div class="container"><div class="section-head"><div><small><?=h(hs($sections,'brands','title','Markalar'))?></small><h2><?=h(hs($sections,'brands','subtitle','Popüler Markalar'))?></h2></div></div><div class="brand-strip"><?php foreach($brands as $b):?><a class="brand-box" href="<?=app_url('brand.php?slug='.urlencode($b['slug']))?>"><?php if($b['logo']):?><img src="<?=app_url($b['logo'])?>" alt="<?=h($b['name'])?>" loading="lazy"><?php else:?><span><?=h($b['name'])?></span><?php endif;?></a><?php endforeach;?></div></div></section>
<?php require __DIR__.'/includes/footer.php';?>