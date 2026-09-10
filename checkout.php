<?php
require __DIR__.'/app/bootstrap.php';
$items=cart_items();if(!$items)redirect('cart.php');$subtotal=cart_subtotal();$state=checkout_state();$error='';
$step=(string)($_GET['step']??'address');$validSteps=['address','shipping','payment','review'];if(!in_array($step,$validSteps,true))$step='address';
if(!$state['address']&&$step!=='address')redirect('checkout.php?step=address');
$shippingOptions=$state['address']?shipping_options_for($state['address'],$items,$subtotal):[];
if(!$state['shipping_code']&&in_array($step,['payment','review'],true))redirect('checkout.php?step=shipping');
if(!$state['payment_method']&&$step==='review')redirect('checkout.php?step=payment');

if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();$action=(string)($_POST['action']??'');
  if($action==='save_address'){
    [$address,$errors]=checkout_validate_address($_POST);if($errors){$error=implode(' ',$errors);$step='address';}
    else{$state['address']=$address;$state['shipping_code']=null;$state['payment_method']=null;checkout_save($state);redirect('checkout.php?step=shipping');}
  }elseif($action==='select_shipping'){
    if(!$state['address'])redirect('checkout.php?step=address');$code=(string)($_POST['shipping_code']??'');$option=find_shipping_option($code,$state['address'],$items,$subtotal);
    if(!$option){$error='Seçtiğiniz teslimat seçeneği kullanılamıyor. Lütfen uygun bir seçenek seçin.';$step='shipping';}
    else{$state['shipping_code']=$code;$state['payment_method']=null;checkout_save($state);redirect('checkout.php?step=payment');}
  }elseif($action==='select_payment'){
    $method=(string)($_POST['payment_method']??'');$allowed=['bank_transfer'];if((int)setting('card_payment_enabled',0)===1)$allowed[]='credit_card';
    if(!in_array($method,$allowed,true)){$error='Ödeme yöntemi kullanılamıyor.';$step='payment';}
    else{$state['payment_method']=$method;$state['note']=trim((string)($_POST['note']??''));checkout_save($state);redirect('checkout.php?step=review');}
  }elseif($action==='place_order'){
    if(!$state['address']||!$state['shipping_code']||!$state['payment_method'])redirect('checkout.php?step=address');
    $option=find_shipping_option($state['shipping_code'],$state['address'],$items,$subtotal);if(!$option){$error='Teslimat koşulları değişti. Lütfen teslimat adımını tekrar kontrol edin.';$step='shipping';}
    else{try{$order=place_order($state['address'],$option,$state['payment_method'],$state['note']??'');redirect('order-success.php');}catch(Throwable $e){$error=$e->getMessage();$step='review';}}
  }
}

$state=checkout_state();$address=$state['address']?:checkout_prefill_address();$shippingOptions=$state['address']?shipping_options_for($state['address'],$items,$subtotal):[];$selectedShipping=$state['shipping_code']&&$state['address']?find_shipping_option($state['shipping_code'],$state['address'],$items,$subtotal):null;$shippingFee=(float)($selectedShipping['fee']??0);$grand=$subtotal+$shippingFee;
$stepIndex=array_search($step,$validSteps,true);$pageTitle='Güvenli Ödeme | TOPLUCA';require __DIR__.'/includes/header.php';
?>
<section class="checkout-page"><div class="checkout-shell">
<div class="checkout-steps"><?php foreach(['address'=>'Adres','shipping'=>'Teslimat','payment'=>'Ödeme','review'=>'Son Kontrol'] as $key=>$label):$idx=array_search($key,$validSteps,true);?><div class="checkout-step <?=$idx<$stepIndex?'done':($key===$step?'active':'')?>"><b><?=$idx<$stepIndex?'✓':$idx+1?></b><span><?=$label?></span></div><?php endforeach;?></div>
<?php if($error):?><div class="flash flash--error"><?=h($error)?></div><?php endif;?>
<div class="checkout-layout"><div class="checkout-card">
<?php if($step==='address'):?>
  <h2>Teslimat Adresi</h2><p class="lead">Teslimat yöntemlerini doğru hesaplayabilmemiz için önce adresinizi girin. Aynı gün teslimat seçeneği bu adımdan sonra kontrol edilir.</p>
  <form method="post"><input type="hidden" name="action" value="save_address"><?=csrf_field()?>
    <div class="form-grid"><label class="field">Ad *<input name="first_name" value="<?=h($address['first_name'])?>" autocomplete="given-name" required></label><label class="field">Soyad *<input name="last_name" value="<?=h($address['last_name'])?>" autocomplete="family-name" required></label><label class="field">E-posta *<input type="email" name="email" value="<?=h($address['email'])?>" autocomplete="email" required></label><label class="field">Telefon *<input name="phone" value="<?=h($address['phone'])?>" autocomplete="tel" required></label><label class="field">İl *<input name="city" value="<?=h($address['city'])?>" placeholder="Örn. Ankara / Adana" autocomplete="address-level1" required></label><label class="field">İlçe *<input name="district" value="<?=h($address['district'])?>" placeholder="Örn. Çankaya / Seyhan" autocomplete="address-level2" required></label><label class="field">Mahalle<input name="neighborhood" value="<?=h($address['neighborhood'])?>"></label><label class="field">Posta Kodu<input name="postal_code" value="<?=h($address['postal_code'])?>" inputmode="numeric"></label><label class="field field--full">Açık Adres *<textarea name="address" autocomplete="street-address" required><?=h($address['address'])?></textarea></label></div>
    <label class="billing-toggle"><input type="checkbox" name="billing_same_as_shipping" value="1" data-billing-toggle <?=!empty($address['billing_same_as_shipping'])?'checked':''?>> Fatura adresim teslimat adresimle aynı</label>
    <div class="form-grid" data-billing-fields><label class="field field--full">Firma / Kurum<input name="billing_company" value="<?=h($address['billing_company'])?>"></label><label class="field">Vergi Dairesi<input name="billing_tax_office" value="<?=h($address['billing_tax_office'])?>"></label><label class="field">Vergi / T.C. No<input name="billing_tax_no" value="<?=h($address['billing_tax_no'])?>"></label></div>
    <div class="checkout-nav"><a class="secondary-btn" href="<?=url('cart.php')?>">← Sepete Dön</a><button class="primary-btn">Teslimat Seçeneklerini Göster →</button></div>
  </form>
<?php elseif($step==='shipping'):?>
  <h2>Teslimat Yöntemi</h2><p class="lead"><b><?=h($state['address']['city'])?> / <?=h($state['address']['district'])?></b> adresi için uygun seçenekler hesaplandı. Ankara aynı gün teslimat yalnızca tüm koşullar sağlanıyorsa seçilebilir.</p>
  <form method="post"><input type="hidden" name="action" value="select_shipping"><?=csrf_field()?>
  <?php foreach($shippingOptions as $option):?>
    <?php if($option['eligible']):?><label class="shipping-choice"><input type="radio" name="shipping_code" value="<?=h($option['code'])?>" <?=$state['shipping_code']===$option['code']?'checked':''?> required><div class="shipping-choice__head"><b><?=$option['type']==='same_day'?'⚡':'🚚'?> <?=h($option['name'])?></b><span class="shipping-choice__price"><?=$option['fee']<=0?'Ücretsiz':money($option['fee'])?></span></div><small><?=h($option['estimated_days']??'')?><?php if($option['type']==='cargo'&&!empty($option['meta']['remaining_to_free'])):?> · <?=money($option['meta']['remaining_to_free'])?> daha ekleyin, bu kargo ücretsiz olsun.<?php endif;?></small><?php if($option['type']==='same_day'):?><div class="shipping-choice__note"><?php if(($option['meta']['remaining_to_free']??0)>0):?><b><?=money($option['meta']['remaining_to_free'])?></b> daha ekleyin, Ankara aynı gün teslimat ücretsiz olsun. Şimdi seçerseniz <?=money($option['fee'])?>.<?php else:?>Aynı gün teslimat için ücretsiz limite ulaştınız. Son sipariş saati <?=h($option['meta']['cutoff']??'')?>.<?php endif;?></div><?php endif;?></label>
    <?php elseif($option['type']==='same_day'):?><div class="shipping-choice shipping-disabled"><div class="shipping-choice__head"><b>⚡ TOPLUCA Ankara Aynı Gün</b><span>Kullanılamaz</span></div><small><?=h($option['reason'])?></small></div><?php endif;?>
  <?php endforeach;?>
  <div class="checkout-nav"><a class="secondary-btn" href="<?=url('checkout.php?step=address')?>">← Adresi Düzenle</a><button class="primary-btn">Ödemeye Devam Et →</button></div></form>
<?php elseif($step==='payment'):?>
  <h2>Ödeme Yöntemi</h2><p class="lead">Ödeme yönteminizi seçin. Kart bilgileri TOPLUCA veritabanında tutulmayacaktır; kart ödemesi sağlayıcı entegrasyonu aktif edildiğinde güvenli ödeme kuruluşuna yönlendirilir.</p>
  <form method="post"><input type="hidden" name="action" value="select_payment"><?=csrf_field()?>
    <label class="payment-option"><input type="radio" name="payment_method" value="bank_transfer" <?=$state['payment_method']==='bank_transfer'||!$state['payment_method']?'checked':''?>><div><b>🏦 Havale / EFT</b><small>Siparişinizi oluşturun, ödeme bilgileri sipariş sonrasında görüntülensin.</small></div></label>
    <?php if((int)setting('card_payment_enabled',0)===1):?><label class="payment-option"><input type="radio" name="payment_method" value="credit_card" <?=$state['payment_method']==='credit_card'?'checked':''?>><div><b>💳 Kredi / Banka Kartı</b><small>Güvenli ödeme sağlayıcısı üzerinden ödeme.</small></div></label><?php else:?><div class="payment-option" style="opacity:.55"><input type="radio" disabled><div><b>💳 Kredi / Banka Kartı</b><small>Ödeme kuruluşu API anahtarları WebYönet'ten tanımlandığında aktif olacaktır.</small></div></div><?php endif;?>
    <label class="field" style="margin-top:14px">Sipariş Notu<textarea name="note" placeholder="Varsa teslimat veya sipariş notunuz..."><?=h($state['note']??'')?></textarea></label>
    <div class="checkout-nav"><a class="secondary-btn" href="<?=url('checkout.php?step=shipping')?>">← Teslimata Dön</a><button class="primary-btn">Siparişi Kontrol Et →</button></div>
  </form>
<?php else:?>
  <h2>Son Kontrol</h2><p class="lead">Siparişinizi oluşturmadan önce adres, teslimat, ödeme ve ürünleri son kez kontrol edin.</p>
  <div class="review-block"><h4>Teslimat Adresi</h4><p><b><?=h($state['address']['first_name'].' '.$state['address']['last_name'])?></b> · <?=h($state['address']['phone'])?></p><p><?=h($state['address']['address'])?><?=!empty($state['address']['neighborhood'])?' · '.h($state['address']['neighborhood']):''?> · <?=h($state['address']['district'].' / '.$state['address']['city'])?></p><a style="font-size:9px;color:#ff5a00;font-weight:900" href="<?=url('checkout.php?step=address')?>">Düzenle</a></div>
  <div class="review-block"><h4>Teslimat</h4><p><b><?=h($selectedShipping['name']??'')?></b> · <?=$shippingFee<=0?'Ücretsiz':money($shippingFee)?></p><a style="font-size:9px;color:#ff5a00;font-weight:900" href="<?=url('checkout.php?step=shipping')?>">Düzenle</a></div>
  <div class="review-block"><h4>Ödeme</h4><p><b><?=$state['payment_method']==='bank_transfer'?'Havale / EFT':'Kredi / Banka Kartı'?></b></p><a style="font-size:9px;color:#ff5a00;font-weight:900" href="<?=url('checkout.php?step=payment')?>">Düzenle</a></div>
  <form method="post"><input type="hidden" name="action" value="place_order"><?=csrf_field()?><label class="billing-toggle"><input type="checkbox" required> Ön bilgilendirme ve mesafeli satış koşullarını okudum ve kabul ediyorum.</label><div class="checkout-nav"><a class="secondary-btn" href="<?=url('checkout.php?step=payment')?>">← Ödemeye Dön</a><button class="primary-btn">Siparişi Oluştur · <?=money($grand)?></button></div></form>
<?php endif;?>
</div>
<aside class="summary-card"><h3>Sipariş Özeti</h3><div class="checkout-summary-items"><?php foreach($items as $item):?><div class="checkout-summary-item"><img src="<?=url($item['image']?:'assets/img/demo/box.svg')?>" alt=""><div><b><?=h($item['name'])?></b><small><?=(int)$item['quantity']?> adet × <?=money($item['effective_price'])?></small></div><strong><?=money($item['line_total'])?></strong></div><?php endforeach;?></div><div class="summary-row"><span>Ara Toplam</span><strong><?=money($subtotal)?></strong></div><div class="summary-row"><span>Teslimat</span><strong><?=$state['shipping_code']?($shippingFee<=0?'Ücretsiz':money($shippingFee)):'Henüz seçilmedi'?></strong></div><div class="summary-row summary-total"><span>Toplam</span><strong><?=money($grand)?></strong></div><?php if(!$state['address']):?><div class="progress-card">📍 Kargo ve aynı gün teslimat seçenekleri adresinizden sonra gösterilecek.</div><?php elseif(tr_normalize($state['address']['city'])!=='ankara'):?><div class="progress-card" style="background:#eef6ff;color:#285c8d">🚚 <?=h($state['address']['city'])?> için standart kargo seçenekleri gösterilir. Ankara aynı gün seçilemez.</div><?php else:?><div class="progress-card">⚡ Ankara adresiniz için aynı gün teslimat uygunluğu otomatik kontrol edildi.</div><?php endif;?></aside>
</div></div></section>
<?php require __DIR__.'/includes/footer.php';?>
