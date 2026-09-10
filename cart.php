<?php
require __DIR__.'/app/bootstrap.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();$action=(string)($_POST['action']??'');$cid=cart_id();
  if($action==='add'){
    $ok=add_to_cart((int)($_POST['product_id']??0),max(1,(int)($_POST['quantity']??1)));
    flash($ok?'success':'error',$ok?'Ürün sepetinize eklendi.':'Ürün şu anda sepete eklenemiyor.');
  }elseif($action==='update'){
    foreach($_POST['qty']??[] as $itemId=>$qty){
      $qty=max(0,min(99,(int)$qty));$s=db()->prepare('SELECT ci.id,ci.product_id,p.stock,p.reserved_stock,p.min_order_qty FROM cart_items ci JOIN products p ON p.id=ci.product_id WHERE ci.id=? AND ci.cart_id=?');$s->execute([(int)$itemId,$cid]);$row=$s->fetch();if(!$row)continue;
      if($qty<=0){db()->prepare('DELETE FROM cart_items WHERE id=? AND cart_id=?')->execute([(int)$itemId,$cid]);continue;}
      $available=max(0,(int)$row['stock']-(int)$row['reserved_stock']);$qty=max((int)$row['min_order_qty'],min($qty,$available));db()->prepare('UPDATE cart_items SET quantity=?,updated_at=NOW() WHERE id=? AND cart_id=?')->execute([$qty,(int)$itemId,$cid]);
    }
    sync_cart_total();flash('success','Sepetiniz güncellendi.');
  }elseif($action==='remove'){
    db()->prepare('DELETE FROM cart_items WHERE id=? AND cart_id=?')->execute([(int)($_POST['item_id']??0),$cid]);sync_cart_total();flash('success','Ürün sepetten çıkarıldı.');
  }
  redirect('cart.php');
}
$items=cart_items();$subtotal=cart_subtotal();$freeLimit=(float)setting('free_shipping_limit',750);$remaining=max(0,$freeLimit-$subtotal);$progress=$freeLimit>0?min(100,($subtotal/$freeLimit)*100):100;
$pageTitle='Sepetim | TOPLUCA';view_event('cart');require __DIR__.'/includes/header.php';
?>
<section class="cart-page"><div class="shell"><div class="cart-header"><div><div class="breadcrumbs"><a href="<?=url()?>">Anasayfa</a><span>›</span><span>Sepetim</span></div><h1>Sepetim</h1></div><span style="font-size:11px;color:#7c838b"><?=cart_count()?> ürün</span></div>
<?php if(!$items):?><div class="empty-state"><div class="empty-state__icon">🛒</div><h2>Sepetiniz henüz boş</h2><p>Binlerce ürün arasından ihtiyacınız olanları keşfedin.</p><a class="primary-btn" href="<?=url()?>">Alışverişe Başla</a></div>
<?php else:?><div class="cart-layout"><div>
<form method="post"><?=csrf_field()?><input type="hidden" name="action" value="update"><div class="cart-panel">
<?php foreach($items as $item):?><div class="cart-item">
  <a class="cart-item__img" href="<?=url('product.php?slug='.urlencode($item['slug']))?>"><img src="<?=url($item['image']?:'assets/img/demo/box.svg')?>" alt="<?=h($item['name'])?>"></a>
  <div><a class="cart-item__name" href="<?=url('product.php?slug='.urlencode($item['slug']))?>"><?=h($item['name'])?></a><div class="cart-item__meta"><?=h($item['brand_name'])?> · <?=h($item['sku'])?><?php if((int)$item['same_day_delivery']===1):?> · ⚡ Ankara aynı gün uygun<?php endif;?></div></div>
  <div class="cart-item__price"><?=money($item['effective_price'])?></div>
  <div class="cart-qty" data-qty><button type="button" data-minus>−</button><input type="number" min="<?=max(1,(int)$item['min_order_qty'])?>" max="<?=max(1,(int)$item['stock']-(int)$item['reserved_stock'])?>" name="qty[<?=(int)$item['cart_item_id']?>]" value="<?=(int)$item['quantity']?>"><button type="button" data-plus>+</button></div>
  <div class="cart-line-total"><?=money($item['line_total'])?></div>
  <button class="cart-remove" type="submit" name="remove_preview" value="0" onclick="this.form.action='<?=url('cart.php')?>';this.form.querySelector('[name=action]').value='update'" aria-label="Sepetten çıkar">×</button>
</div><?php endforeach;?>
</div><div class="cart-actions"><a class="secondary-btn" href="<?=url()?>">← Alışverişe Devam Et</a><button class="secondary-btn" type="submit">Sepeti Güncelle</button></div></form>
</div>
<aside class="summary-card"><h3>Sipariş Özeti</h3><div class="summary-row"><span>Ürünler</span><strong><?=money($subtotal)?></strong></div><div class="summary-row"><span>Kargo</span><strong>Adres sonrası hesaplanır</strong></div><div class="summary-row summary-total"><span>Ara Toplam</span><strong><?=money($subtotal)?></strong></div>
<div class="progress-card"><?php if($remaining>0):?>🚚 <b><?=money($remaining)?></b> daha ekleyin, standart ücretsiz kargo limitine ulaşın.<?php else:?>✓ Standart ücretsiz kargo limitine ulaştınız.<?php endif;?><div class="progress-track"><span style="width:<?=$progress?>%"></span></div></div>
<div style="background:#fff5ee;border-radius:9px;padding:11px;font-size:10px;color:#7b4b2d;margin-bottom:12px">📍 Teslimat seçenekleri <b>adres bilgilerinizi girdikten sonra</b> hesaplanır. Ankara adreslerinde aynı gün teslimat uygunluğu ilçe, saat, ürünler ve sepet tutarına göre kontrol edilir.</div>
<a class="primary-btn" href="<?=url('checkout.php?step=address')?>">Adres ve Teslimata Geç →</a><p style="font-size:9px;color:#8b9299;text-align:center;margin:10px 0 0">Sipariş oluşturulmadan önce fiyat ve stok tekrar doğrulanır.</p></aside>
</div><?php endif;?></div></section>
<?php require __DIR__.'/includes/footer.php';?>
