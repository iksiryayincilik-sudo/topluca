<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/checkout.php';

$items=cart_items();
if(!$items) redirect('cart.php');
$subtotal=cart_subtotal();
$step=$_GET['step']??'address';
$allowedSteps=['address','shipping','payment','review'];
if(!in_array($step,$allowedSteps,true))$step='address';
$error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $action=$_POST['action']??'';

    if($action==='save_address'){
        $first=trim($_POST['first_name']??'');
        $last=trim($_POST['last_name']??'');
        $email=trim($_POST['email']??'');
        $phone=trim($_POST['phone']??'');
        $city=trim($_POST['city']??'');
        $district=trim($_POST['district']??'');
        $neighborhood=trim($_POST['neighborhood']??'');
        $address=trim($_POST['address']??'');
        $postal=trim($_POST['postal_code']??'');
        $invoiceType=$_POST['invoice_type']??'individual';
        $company=trim($_POST['company']??'');
        $taxOffice=trim($_POST['tax_office']??'');
        $taxNo=trim($_POST['tax_no']??'');
        if(!$first||!$last||!filter_var($email,FILTER_VALIDATE_EMAIL)||!$phone||!$city||!$district||!$address){
            $error='Lütfen zorunlu teslimat bilgilerini eksiksiz doldurun.';
        } elseif($invoiceType==='company' && (!$company||!$taxOffice||!$taxNo)) {
            $error='Kurumsal fatura için firma, vergi dairesi ve vergi numarası zorunludur.';
        } else {
            checkout_set('address',compact('first','last','email','phone','city','district','neighborhood','address','postal','invoiceType','company','taxOffice','taxNo'));
            unset($_SESSION['checkout']['shipping']);
            redirect('checkout.php?step=shipping');
        }
    }

    if($action==='save_shipping'){
        $address=checkout_data()['address']??null;
        if(!$address) redirect('checkout.php?step=address');
        $method=$_POST['shipping_method']??'';
        $same=same_day_status($address,$items,$subtotal);
        $standard=standard_shipping_options($subtotal);
        $selected=null;
        if($method==='same_day'){
            if(!$same['eligible']) $error='Aynı gün teslimat bu adres/sepet için kullanılamıyor.';
            else $selected=['method'=>'same_day','code'=>'same_day','name'=>'TOPLUCA Ankara Aynı Gün','price'=>(float)$same['fee']];
        } elseif(str_starts_with($method,'carrier:')) {
            $id=(int)substr($method,8);
            foreach($standard as $c){ if((int)$c['id']===$id){$selected=['method'=>'cargo','code'=>'carrier_'.$id,'name'=>$c['name'],'price'=>(float)$c['calculated_price'],'carrier_id'=>$id];break;} }
            if(!$selected)$error='Geçerli bir kargo seçeneği seçin.';
        } else $error='Teslimat seçeneği seçin.';
        if(!$error){ checkout_set('shipping',$selected); redirect('checkout.php?step=payment'); }
    }

    if($action==='save_payment'){
        if(empty(checkout_data()['shipping'])) redirect('checkout.php?step=shipping');
        $payment=$_POST['payment_method']??'bank_transfer';
        if(!in_array($payment,['bank_transfer','cash_on_delivery'],true))$payment='bank_transfer';
        checkout_set('payment',['method'=>$payment]);
        checkout_set('customer_note',trim($_POST['customer_note']??''));
        redirect('checkout.php?step=review');
    }

    if($action==='place_order'){
        $data=checkout_data();
        if(empty($data['address']))redirect('checkout.php?step=address');
        if(empty($data['shipping']))redirect('checkout.php?step=shipping');
        if(empty($data['payment']))redirect('checkout.php?step=payment');
        $a=$data['address'];$sh=$data['shipping'];$pay=$data['payment'];
        // Revalidate same-day at the last possible moment.
        if($sh['method']==='same_day'){
            $same=same_day_status($a,$items,$subtotal);
            if(!$same['eligible']){
                unset($_SESSION['checkout']['shipping']);
                flash('error','Aynı gün teslimat uygunluğu değişti. Lütfen teslimat yöntemini yeniden seçin.');
                redirect('checkout.php?step=shipping');
            }
            $sh['price']=(float)$same['fee'];
        }
        try{
            db()->beginTransaction();
            // Lock products and verify stock.
            foreach($items as $i){
                $s=db()->prepare('SELECT stock,sale_price,purchase_price,vat_rate FROM products WHERE id=? FOR UPDATE');
                $s->execute([(int)$i['id']]);$live=$s->fetch();
                if(!$live || (int)$live['stock']<(int)$i['quantity']) throw new RuntimeException($i['name'].' için yeterli stok bulunmuyor.');
            }
            $orderNo='TPL'.date('ymd').strtoupper(substr(bin2hex(random_bytes(4)),0,6));
            $shipping=(float)$sh['price'];$total=$subtotal+$shipping;
            $customerName=trim($a['first'].' '.$a['last']);
            $s=db()->prepare("INSERT INTO orders(order_no,user_id,customer_name,customer_email,customer_phone,city,district,address,billing_same_as_shipping,billing_company,billing_tax_office,billing_tax_no,subtotal,shipping_total,grand_total,delivery_method,shipping_method_code,shipping_company_name,payment_method,customer_note,status,created_at,updated_at) VALUES(?,NULL,?,?,?,?,?,?,1,?,?,?,?,?,?,?,?,?,?,?,'new',NOW(),NOW())");
            $s->execute([$orderNo,$customerName,$a['email'],$a['phone'],$a['city'],$a['district'],$a['address'],$a['company']?:null,$a['taxOffice']?:null,$a['taxNo']?:null,$subtotal,$shipping,$total,$sh['method'],$sh['code'],$sh['name'],$pay['method'],$data['customer_note']??'']);
            $oid=(int)db()->lastInsertId();
            foreach($items as $i){
                $s=db()->prepare('SELECT stock,sale_price,purchase_price,vat_rate FROM products WHERE id=? FOR UPDATE');$s->execute([(int)$i['id']]);$live=$s->fetch();
                $qty=(int)$i['quantity'];$unit=(float)$live['sale_price'];$line=$unit*$qty;
                $s=db()->prepare('INSERT INTO order_items(order_id,product_id,product_name,sku,quantity,unit_price,unit_cost,vat_rate,discount_total,line_total) VALUES(?,?,?,?,?,?,?,?,0,?)');
                $s->execute([$oid,$i['id'],$i['name'],$i['sku'],$qty,$unit,$live['purchase_price'],$live['vat_rate'],$line]);
                db()->prepare('UPDATE products SET stock=stock-?,sales_count=sales_count+? WHERE id=?')->execute([$qty,$qty,$i['id']]);
                db()->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,unit_cost,reference_type,reference_id,notes,created_at) VALUES(?,'sale',?,?, 'order',?, ?,NOW())")->execute([$i['id'],-$qty,$live['purchase_price'],$oid,'Sipariş '.$orderNo]);
            }
            db()->prepare("INSERT INTO order_status_history(order_id,status,note,created_at) VALUES(?,'new','Sipariş oluşturuldu',NOW())")->execute([$oid]);
            db()->prepare('UPDATE carts SET customer_email=?,converted_order_id=?,subtotal=?,updated_at=NOW() WHERE id=?')->execute([$a['email'],$oid,$subtotal,cart_id()]);
            db()->commit();
            $_SESSION['last_order_id']=$oid;$_SESSION['last_order_no']=$orderNo;$_SESSION['last_order_email']=$a['email'];
            checkout_clear();
            redirect('order-success.php');
        }catch(Throwable $e){ if(db()->inTransaction())db()->rollBack();$error=$e->getMessage(); }
    }
}

$data=checkout_data();
if($step!=='address' && empty($data['address']))redirect('checkout.php?step=address');
if(in_array($step,['payment','review'],true) && empty($data['shipping']))redirect('checkout.php?step=shipping');
if($step==='review' && empty($data['payment']))redirect('checkout.php?step=payment');
$address=$data['address']??[];
$shipping=$data['shipping']??[];
$payment=$data['payment']??[];
$same=$address?same_day_status($address,$items,$subtotal):null;
$standard=$address?standard_shipping_options($subtotal):[];
$pageTitle='Siparişi Tamamla | TOPLUCA';
require __DIR__.'/includes/header.php';
?>
<section class="checkout-shell">
<div class="container">
    <div class="checkout-top">
        <div><span class="eyebrow">GÜVENLİ ÖDEME</span><h1>Siparişi Tamamla</h1><p>Adres → Teslimat → Ödeme → Onay</p></div>
        <div class="checkout-steps">
            <?php foreach(['address'=>'1 Adres','shipping'=>'2 Teslimat','payment'=>'3 Ödeme','review'=>'4 Onay'] as $k=>$label):?>
                <a class="step-chip <?=$step===$k?'active':''?>" href="<?=checkout_step_url($k)?>"><?=h($label)?></a>
            <?php endforeach;?>
        </div>
    </div>
    <?php if($error):?><div class="checkout-alert error"><?=h($error)?></div><?php endif;?>
    <div class="checkout-grid">
        <section class="checkout-card">
        <?php if($step==='address'):?>
            <div class="checkout-card-head"><div><span class="step-no">1</span><h2>Teslimat ve fatura bilgileri</h2></div><p>Önce adresinizi belirleyelim. Teslimat seçenekleri bu adrese göre hesaplanır.</p></div>
            <form method="post" class="checkout-form-v2"><?=csrf_field()?><input type="hidden" name="action" value="save_address">
                <div class="field-grid two"><label>Ad *<input name="first_name" required value="<?=h($address['first']??'')?>"></label><label>Soyad *<input name="last_name" required value="<?=h($address['last']??'')?>"></label></div>
                <div class="field-grid two"><label>E-posta *<input type="email" name="email" required value="<?=h($address['email']??'')?>"></label><label>Telefon *<input name="phone" required value="<?=h($address['phone']??'')?>"></label></div>
                <div class="field-grid two"><label>İl *<input name="city" required placeholder="Örn. Ankara" value="<?=h($address['city']??'')?>"></label><label>İlçe *<input name="district" required placeholder="Örn. Çankaya" value="<?=h($address['district']??'')?>"></label></div>
                <div class="field-grid two"><label>Mahalle<input name="neighborhood" value="<?=h($address['neighborhood']??'')?>"></label><label>Posta Kodu<input name="postal_code" value="<?=h($address['postal']??'')?>"></label></div>
                <label>Açık Adres *<textarea name="address" rows="4" required><?=h($address['address']??'')?></textarea></label>
                <div class="invoice-switch"><label><input type="radio" name="invoice_type" value="individual" <?=($address['invoiceType']??'individual')==='individual'?'checked':''?>> Bireysel fatura</label><label><input type="radio" name="invoice_type" value="company" <?=($address['invoiceType']??'')==='company'?'checked':''?>> Kurumsal fatura</label></div>
                <div class="field-grid three invoice-company"><label>Firma<input name="company" value="<?=h($address['company']??'')?>"></label><label>Vergi Dairesi<input name="tax_office" value="<?=h($address['taxOffice']??'')?>"></label><label>Vergi No<input name="tax_no" value="<?=h($address['taxNo']??'')?>"></label></div>
                <button class="checkout-primary">Teslimat Seçeneklerine İlerle →</button>
            </form>
        <?php elseif($step==='shipping'):?>
            <div class="checkout-card-head"><div><span class="step-no">2</span><h2>Teslimat yöntemi</h2></div><p><?=h(($address['city']??'').' / '.($address['district']??''))?> adresine uygun seçenekler.</p></div>
            <div class="address-summary"><div><strong><?=h(($address['first']??'').' '.($address['last']??''))?></strong><span><?=h($address['address']??'')?>, <?=h($address['district']??'')?> / <?=h($address['city']??'')?></span></div><a href="<?=checkout_step_url('address')?>">Adresi değiştir</a></div>
            <form method="post"><?=csrf_field()?><input type="hidden" name="action" value="save_shipping"><div class="shipping-choice-list">
                <?php if($same && $same['eligible']):?>
                <label class="shipping-choice premium"><input type="radio" name="shipping_method" value="same_day" required><span class="shipping-icon">⚡</span><span class="shipping-main"><b>TOPLUCA Ankara Aynı Gün</b><small>Bugün teslim • Son sipariş <?=h($same['cutoff'])?></small><?php if($same['remaining']>0):?><em><?=money($same['remaining'])?> daha ekleyin, aynı gün teslimat ücretsiz olsun.</em><?php else:?><em>Ücretsiz aynı gün teslimat hakkınız aktif.</em><?php endif;?></span><strong><?=$same['fee']>0?money($same['fee']):'Ücretsiz'?></strong></label>
                <?php else:?>
                <div class="shipping-unavailable"><b>Ankara Aynı Gün Teslimat</b><span>Bu sipariş için kullanılamıyor.</span><?php if($same)foreach($same['reasons'] as $r):?><small>• <?=h($r)?></small><?php endforeach;?></div>
                <?php endif;?>
                <?php foreach($standard as $c):?>
                <label class="shipping-choice"><input type="radio" name="shipping_method" value="carrier:<?=(int)$c['id']?>" required><span class="shipping-icon">🚚</span><span class="shipping-main"><b><?=h($c['name'])?></b><small>Standart gönderim • Sipariş sonrası takip numarası</small></span><strong><?=$c['calculated_price']>0?money($c['calculated_price']):'Ücretsiz'?></strong></label>
                <?php endforeach;?>
            </div><button class="checkout-primary">Ödemeye İlerle →</button></form>
        <?php elseif($step==='payment'):?>
            <div class="checkout-card-head"><div><span class="step-no">3</span><h2>Ödeme yöntemi</h2></div><p>Ödeme sağlayıcısı bağlanana kadar güvenli manuel seçenekler aktif.</p></div>
            <form method="post"><?=csrf_field()?><input type="hidden" name="action" value="save_payment">
                <div class="payment-choice-list"><label class="payment-choice"><input type="radio" name="payment_method" value="bank_transfer" checked><span>🏦</span><div><b>Havale / EFT</b><small>Siparişinizi oluşturun, ödeme bilgisini sipariş ekranında görün.</small></div></label><label class="payment-choice muted"><input type="radio" name="payment_method" value="cash_on_delivery"><span>📦</span><div><b>Kapıda ödeme</b><small>Yönetimden aktif edilen gönderilerde kullanılabilir.</small></div></label></div>
                <label>Sipariş notu<textarea name="customer_note" rows="3" placeholder="Teslimatla ilgili bir notunuz varsa yazabilirsiniz."><?=h($data['customer_note']??'')?></textarea></label>
                <button class="checkout-primary">Siparişi Kontrol Et →</button>
            </form>
        <?php else:?>
            <div class="checkout-card-head"><div><span class="step-no">4</span><h2>Son kontrol</h2></div><p>Siparişi oluşturmadan önce tüm bilgileri kontrol edin.</p></div>
            <div class="review-box"><div><span>Teslimat adresi</span><b><?=h(($address['first']??'').' '.($address['last']??''))?></b><p><?=h($address['address']??'')?>, <?=h($address['district']??'')?> / <?=h($address['city']??'')?></p><a href="<?=checkout_step_url('address')?>">Düzenle</a></div><div><span>Teslimat</span><b><?=h($shipping['name']??'')?></b><p><?=$shipping['price']??0?money($shipping['price']):'Ücretsiz'?></p><a href="<?=checkout_step_url('shipping')?>">Düzenle</a></div><div><span>Ödeme</span><b><?=($payment['method']??'')==='bank_transfer'?'Havale / EFT':'Kapıda ödeme'?></b><a href="<?=checkout_step_url('payment')?>">Düzenle</a></div></div>
            <form method="post"><?=csrf_field()?><input type="hidden" name="action" value="place_order"><label class="terms"><input type="checkbox" required> Ön bilgilendirme ve mesafeli satış koşullarını okudum ve kabul ediyorum.</label><button class="checkout-primary big">Siparişi Oluştur ve Tamamla</button></form>
        <?php endif;?>
        </section>
        <aside class="checkout-summary-v2"><h3>Sipariş Özeti</h3><div class="summary-items"><?php foreach($items as $i):?><div class="summary-item"><span><b><?=h($i['name'])?></b><small><?=h($i['brand_name'])?> • <?=intval($i['quantity'])?> adet</small></span><strong><?=money($i['line_total'])?></strong></div><?php endforeach;?></div><div class="summary-line"><span>Ürünler</span><strong><?=money($subtotal)?></strong></div><?php if($shipping):?><div class="summary-line"><span>Teslimat</span><strong><?=($shipping['price']??0)>0?money($shipping['price']):'Ücretsiz'?></strong></div><?php endif;?><div class="summary-total"><span>Toplam</span><strong><?=money($subtotal+(float)($shipping['price']??0))?></strong></div><div class="summary-security">🔒 Güvenli oturum<br><small>Adres ve sipariş bilgileriniz şifreli bağlantı üzerinden işlenir.</small></div></aside>
    </div>
</div>
</section>
<?php require __DIR__.'/includes/footer.php';
