<?php
require __DIR__.'/app/bootstrap.php';
$pageTitle='TOPLUCA | İhtiyacın olan ne varsa, Topluca.';
$pageDescription='Kırtasiye, kitap, bilgisayar, yazıcı, toner, kağıt, okul ve hobi ürünlerini avantajlı fiyatlarla TOPLUCA’dan alın.';
view_event('home');
$cats=main_categories();
$hero=db()->query("SELECT * FROM banners WHERE is_active=1 AND position_code='home_hero' AND (start_at IS NULL OR start_at<=NOW()) AND (end_at IS NULL OR end_at>=NOW()) ORDER BY sort_order,id LIMIT 1")->fetch();
$sections=db()->query("SELECT * FROM homepage_sections WHERE is_active=1 ORDER BY sort_order,id")->fetchAll();
function home_section_products(array $section): array {
    $limit=max(1,min(10,(int)$section['item_limit']));
    if($section['source_type']==='category' && $section['source_value']){
        $cat=category_by_slug($section['source_value']);
        if($cat){$ids=descendant_ids((int)$cat['id']);$ph=implode(',',array_fill(0,count($ids),'?'));return product_card_query("p.category_id IN ($ph)",$ids,'p.is_bestseller DESC,p.sales_count DESC,p.id DESC',$limit);}
    }
    if($section['source_type']==='new')return product_card_query('p.is_new=1',[],'p.id DESC',$limit);
    if($section['source_type']==='featured')return product_card_query('p.is_featured=1',[],'p.sales_count DESC,p.id DESC',$limit);
    return product_card_query('p.is_bestseller=1 OR p.sales_count>0',[],'p.is_bestseller DESC,p.sales_count DESC',$limit);
}
require __DIR__.'/includes/header.php';
?>
<section class="hero">
  <div class="shell hero-card">
    <div class="hero-card__inner">
      <div class="hero-copy">
        <small><?=h($hero['eyebrow']??'TOPLUCA.NET')?></small>
        <h1><?=h($hero['title']??'ARADIĞINIZ')?> <br>HER ŞEY <br><span>TOPLUCA’DA!</span></h1>
        <p><?=h($hero['subtitle']??'Geniş ürün yelpazesi, hızlı teslimat ve güvenli alışveriş deneyimi. İşin, okulun ve hobinin tüm ihtiyaçları tek sepette.')?></p>
        <a class="hero-cta" href="<?=h($hero['link_url']?:url('search.php'))?>"><?=h($hero['button_text']??'Alışverişe Başla')?> <span>→</span></a>
      </div>
      <div class="hero-visual">
        <div class="hero-visual__phrase">İşin, okulun,<br>hobinin yanında<br>her zaman!</div>
        <div class="box-stack">
          <div class="box box--1">TOPLUCA</div><div class="box box--2">TOPLUCA</div><div class="box box--3">TOPLUCA</div><div class="box box--4">TOPLUCA</div>
        </div>
      </div>
      <div class="hero-benefits">
        <div class="hero-benefit"><div class="hero-benefit__icon">🚚</div><div><b>Hızlı Teslimat</b><small>Ankara’da uygun siparişlerde aynı gün</small></div></div>
        <div class="hero-benefit"><div class="hero-benefit__icon">🏷</div><div><b>Binlerce Ürün</b><small>Tüm ihtiyaçların tek adreste</small></div></div>
        <div class="hero-benefit"><div class="hero-benefit__icon">🛡</div><div><b>Güvenli Alışveriş</b><small>Güvenli bağlantı ve sipariş altyapısı</small></div></div>
      </div>
    </div>
    <div class="hero-dots"><span></span><span></span><span></span></div>
  </div>
</section>
<section class="section">
  <div class="shell">
    <div class="category-showcase">
      <?php $arts=['✏️','📚','🖱️','🖨️','📄','🎒','🎨'];foreach($cats as $i=>$cat):?>
      <a class="category-tile" href="<?=url('category.php?slug='.urlencode($cat['slug']))?>"><div class="category-tile__art"><?=$arts[$i]??'📦'?></div><b><?=h($cat['name'])?></b><span>Tüm Ürünler →</span></a>
      <?php endforeach;?>
    </div>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="shell promo-row">
    <article class="promo-card promo-card--orange"><small>OKULA DÖNÜŞ</small><h3>Okul ve Kırtasiye Fırsatları Başladı!</h3><p>Kalemden deftere, çantadan okul ihtiyaçlarına seçili ürünlerde avantajlı fiyatlar.</p><a href="<?=url('category.php?slug=okul-urunleri')?>">Ürünleri İncele →</a></article>
    <article class="promo-card promo-card--dark"><small>OFİSTE YÜKSEK PERFORMANS</small><h3>Teknoloji & Ofis Ürünlerinde Güçlü Fiyatlar</h3><p>Bilgisayar aksesuarları, sarf malzemeleri ve ofis ihtiyaçları tek yerde.</p><a href="<?=url('category.php?slug=bilgisayar-sarf-malzemeleri')?>">Şimdi Keşfet →</a></article>
  </div>
</section>
<?php foreach($sections as $section):$products=home_section_products($section);if(!$products)continue;?>
<section class="section <?=$section['section_code']==='books'?'section--soft':''?>">
  <div class="shell">
    <div class="section-head"><div class="section-head__copy"><small><?=h($section['subtitle'])?></small><h2><?=h($section['title'])?></h2></div><a href="<?=($section['source_type']==='category'&&$section['source_value'])?url('category.php?slug='.urlencode($section['source_value'])):url('search.php')?>">Tümünü Gör →</a></div>
    <div class="products-grid"><?php foreach($products as $product)require __DIR__.'/includes/product-card.php';?></div>
  </div>
</section>
<?php endforeach;?>
<?php require __DIR__.'/includes/footer.php';?>
