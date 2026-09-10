<?php
require __DIR__.'/app/bootstrap.php';
$slug=trim($_GET['slug']??'');
$product=product_by_slug($slug);
if(!$product){http_response_code(404);exit('Ürün bulunamadı.');}
db()->prepare('UPDATE products SET view_count=view_count+1 WHERE id=?')->execute([(int)$product['id']]);
page_view('product',(int)$product['id']);
$images=product_images((int)$product['id']);
$attrs=product_attributes((int)$product['id']);
$tiers=product_tiers((int)$product['id']);
$price=unit_price($product,1);
$s=db()->prepare("SELECT p.*,b.name brand_name,c.name category_name FROM products p LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id WHERE p.category_id=? AND p.id<>? AND p.is_active=1 ORDER BY p.sales_count DESC,p.id DESC LIMIT 5");
$s->execute([(int)$product['category_id'],(int)$product['id']]);
$related=$s->fetchAll();
$pageTitle=trim((string)($product['meta_title']??''))!==''?$product['meta_title']:$product['name'].' | TOPLUCA';
$pageDescription=trim((string)($product['meta_description']??''))!==''?$product['meta_description']:mb_substr(strip_tags((string)($product['short_description']?:$product['description'])),0,155);
$pageKeywords=trim((string)($product['meta_keywords']??''))!==''?$product['meta_keywords']:trim(($product['brand_name']??'').' '.$product['name'].' '.$product['sku']);
$pageCanonical=app_url('product.php?slug='.urlencode($product['slug']));
require __DIR__.'/includes/header.php';
$schema=['@context'=>'https://schema.org','@type'=>'Product','name'=>$product['name'],'sku'=>$product['sku'],'description'=>$product['short_description']?:$product['description'],'brand'=>['@type'=>'Brand','name'=>$product['brand_name']?:'TOPLUCA'],'offers'=>['@type'=>'Offer','priceCurrency'=>'TRY','price'=>number_format($price,2,'.',''),'availability'=>(int)$product['stock']>0?'https://schema.org/InStock':'https://schema.org/OutOfStock','url'=>$pageCanonical]];
if($images)$schema['image']=array_map(fn($x)=>app_url($x['image_path']),$images);
?>
<script type="application/ld+json"><?=json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?></script>
<section class="product-page"><div class="container">
<div class="breadcrumbs"><a href="<?=app_url()?>">Ana Sayfa</a><span>›</span><a href="<?=app_url('category.php?slug='.urlencode($product['category_slug']))?>"><?=h($product['category_name'])?></a><span>›</span><span><?=h($product['name'])?></span></div>
<div class="product-detail-grid">
  <div class="gallery-layout">
    <div class="thumbs"><?php foreach($images as $i=>$im):?><button class="thumb <?=$i===0?'active':''?>" type="button" data-thumb data-src="<?=app_url($im['image_path'])?>"><img src="<?=app_url($im['image_path'])?>" alt="<?=h($im['alt_text']?:$product['name'])?>"></button><?php endforeach;?></div>
    <div class="main-image"><?php if($images):?><img data-main-image src="<?=app_url($images[0]['image_path'])?>" alt="<?=h($product['name'])?>"><?php else:?><div class="main-placeholder"><b><?=h(mb_substr($product['name'],0,1))?></b><span>TOPLUCA</span></div><?php endif;?></div>
  </div>
  <div class="product-info-card">
    <?php if(!empty($product['brand_name'])):?><a class="brand-link" href="<?=app_url('brand.php?slug='.urlencode($product['brand_slug']??''))?>"><?=h($product['brand_name'])?></a><?php endif;?>
    <h1><?=h($product['name'])?></h1>
    <div class="product-info-meta"><span class="stars">★★★★★</span><span>Ürün Kodu: <?=h($product['sku'])?></span><?php if($product['barcode']):?><span>Barkod: <?=h($product['barcode'])?></span><?php endif;?><span><?=((int)$product['stock']>0)?'✓ Stokta':'Stokta yok'?></span></div>
    <?php if(trim((string)$product['short_description'])!==''):?><p class="product-short-description"><?=h($product['short_description'])?></p><?php endif;?>
    <div class="product-price-box"><?php if($product['compare_price']!==null&&(float)$product['compare_price']>$price):?><del><?=money($product['compare_price'])?></del><?php endif;?><strong><?=money($price)?></strong><small>KDV dahil</small></div>
    <?php if($tiers):?><div class="price-tiers"><h4>Toplu alım fiyatları</h4><?php foreach($tiers as $t):?><div class="price-tier-row"><span><?=(int)$t['min_qty']?><?= $t['max_qty']!==null?'–'.(int)$t['max_qty']:'+'?> adet</span><b><?=money($t['unit_price'])?> / adet</b></div><?php endforeach;?></div><?php endif;?>
    <div class="delivery-cards"><div class="delivery-card"><span>🚚</span><div><b><?=$product['shipping_policy']==='free'?'Ücretsiz Kargo':'Kargo adresinize göre hesaplanır'?></b><span>Sepette adresinizi girdikten sonra kullanılabilir kargo firmaları ve ücretleri gösterilir.</span></div></div><?php if((int)$product['same_day_delivery']):?><div class="delivery-card"><span>⚡</span><div><b>Ankara Aynı Gün Teslimata Uygun</b><span>İl, ilçe, saat, kapasite ve sepet tutarı checkout sırasında otomatik kontrol edilir.</span></div></div><?php endif;?></div>
    <?php if((int)$product['stock']>0):?><form class="detail-cart" action="<?=app_url('cart.php')?>" method="post"><?=csrf_field()?><input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?=(int)$product['id']?>"><div class="qty-control"><button type="button" data-qty-minus>−</button><input type="number" name="quantity" min="1" max="<?=max(1,min(99,(int)$product['stock']))?>" value="1"><button type="button" data-qty-plus>+</button></div><button class="cart-button" type="submit">Sepete Ekle</button><a class="favorite-button" href="<?=app_url('favorites.php?action=toggle&product='.(int)$product['id'])?>">♡</a></form><?php else:?><div class="stock-out-box">Bu ürün şu anda stokta bulunmuyor.</div><?php endif;?>
  </div>
</div>
<div class="detail-tabs"><div class="tab-nav"><button class="active" data-tab="desc">Ürün Açıklaması</button><button data-tab="spec">Teknik Özellikler</button><button data-tab="delivery">Teslimat & İade</button></div><div class="tab-panel active" data-panel="desc"><?=nl2br(h($product['description']?:$product['short_description']))?></div><div class="tab-panel" data-panel="spec"><table class="spec-table"><tr><th>Marka</th><td><?=h($product['brand_name'])?></td></tr><tr><th>Ürün Kodu</th><td><?=h($product['sku'])?></td></tr><?php if($product['model_number']):?><tr><th>Model</th><td><?=h($product['model_number'])?></td></tr><?php endif;?><?php if($product['barcode']):?><tr><th>Barkod</th><td><?=h($product['barcode'])?></td></tr><?php endif;?><?php if($product['isbn']):?><tr><th>ISBN</th><td><?=h($product['isbn'])?></td></tr><?php endif;?><?php foreach($attrs as $a):?><tr><th><?=h($a['attribute_name'])?></th><td><?=h($a['attribute_value'])?></td></tr><?php endforeach;?></table></div><div class="tab-panel" data-panel="delivery">Teslimat seçenekleri adres ve sepet içeriğine göre sipariş aşamasında hesaplanır. Ankara aynı gün teslimat yalnızca uygun Ankara adreslerinde, uygun ürünlerde ve mevcut kapasite dahilinde sunulur.</div></div>
</div></section>
<?php if($related):?><section class="section soft"><div class="container"><div class="section-head"><div><small>Benzer ürünler</small><h2>Bunlar da ilginizi çekebilir</h2></div></div><div class="products-grid"><?php foreach($related as $p){require __DIR__.'/includes/product-card.php';}?></div></div></section><?php endif;?>
<?php require __DIR__.'/includes/footer.php';?>