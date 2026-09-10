<?php
$unitPrice=effective_price($product,1);
$oldPrice=(float)($product['compare_price']?:$product['sale_price']);
$discount=$oldPrice>$unitPrice?(int)round((1-$unitPrice/$oldPrice)*100):0;
?>
<article class="product-card">
  <div class="product-card__media">
    <?php if((int)$product['is_bestseller']===1):?><span class="product-badge product-badge--red">Çok Satan</span><?php elseif($discount>0):?><span class="product-badge">Fırsat Ürünü</span><?php elseif((int)$product['is_new']===1):?><span class="product-badge product-badge--green">Yeni</span><?php endif;?>
    <a href="<?=url('product.php?slug='.urlencode($product['slug']))?>"><img src="<?=url($product['image']?:'assets/img/demo/box.svg')?>" alt="<?=h($product['name'])?>" loading="lazy" width="330" height="330"></a>
    <form class="favorite-quick" method="post" action="<?=url('favorites.php')?>"><?=csrf_field()?><input type="hidden" name="action" value="toggle"><input type="hidden" name="product_id" value="<?=(int)$product['id']?>"><button type="submit" aria-label="Favorilere ekle">♡</button></form>
  </div>
  <div class="product-card__body">
    <div class="product-card__brand"><?=h($product['brand_name']??'TOPLUCA')?></div>
    <a class="product-card__name" href="<?=url('product.php?slug='.urlencode($product['slug']))?>"><?=h($product['name'])?></a>
    <div class="product-rating" aria-label="<?=h((string)$product['rating_avg'])?> puan"><span>★★★★★</span><small>(<?=(int)$product['review_count']?>)</small></div>
    <div class="product-price-row">
      <div class="product-price"><?php if($oldPrice>$unitPrice):?><del><?=money($oldPrice)?></del><?php endif;?><strong><?=money($unitPrice)?></strong></div>
      <?php if($discount>0):?><span class="discount-chip">%<?=$discount?></span><?php endif;?>
    </div>
    <form method="post" action="<?=url('cart.php')?>" class="product-add-form"><?=csrf_field()?><input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?=(int)$product['id']?>"><button class="product-add" type="submit">🛒 Sepete Ekle</button></form>
    <div class="product-logistics"><?php if(($product['shipping_policy']??'')==='free'):?><span>● Ücretsiz Kargo</span><?php elseif((int)$product['same_day_delivery']===1):?><span>⚡ Ankara Aynı Gün</span><?php else:?><span>🚚 Hızlı Gönderim</span><?php endif;?></div>
  </div>
</article>
