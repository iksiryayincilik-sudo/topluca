<?php
require __DIR__.'/app/bootstrap.php';

$items=cart_items();
if(!$items)redirect_to('cart.php');
$subtotal=cart_subtotal();
$step=$_GET['step']??'address';
$allowed=['address','delivery','payment','review'];
if(!in_array($step,$allowed,true))$step='address';

function checkout_redirect(string $step): void{redirect_to('checkout.php?step='.$step);}
function checkout_save_user_address(array $address): void{
    if(!user_id()||empty($address['save_address']))return;
    $s=db()->prepare('SELECT COUNT(*) FROM user_addresses WHERE user_id=? AND city=? AND district=? AND address=?');
    $s->execute([user_id(),$address['city'],$address['district'],$address['address']]);
    if((int)$s->fetchColumn()>0)return;
    $hasDefault=db()->prepare('SELECT COUNT(*) FROM user_addresses WHERE user_id=?');$hasDefault->execute([user_id()]);$default=(int)$hasDefault->fetchColumn()===0?1:0;
    db()->prepare('INSERT INTO user_addresses(user_id,title,first_name,last_name,phone,company,tax_office,tax_no,city,district,neighborhood,address,postal_code,is_default_shipping,is_default_billing,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())')->execute([
        user_id(),$address['title']?:'Adresim',$address['first_name'],$address['last_name'],$address['phone'],$address['billing_type']==='corporate'?$address['company']:null,$address['billing_type']==='corporate'?$address['tax_office']:null,$address['billing_type']==='corporate'?$address['tax_no']:null,$address['city'],$address['district'],$address['neighborhood']?:null,$address['address'],$address['postal_code']?:null,$default,$default
    ]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $action=$_POST['action']??'';
    try{
        if($action==='save_address'){
            $address=[
                'title'=>trim($_POST['title']??'Adresim'),
                'first_name'=>trim($_POST['first_name']??''),'last_name'=>trim($_POST['last_name']??''),
                'email'=>trim($_POST['email']??''),'phone'=>trim($_POST['phone']??''),
                'city'=>trim($_POST['city']??''),'district'=>trim($_POST['district']??''),
                'neighborhood'=>trim($_POST['neighborhood']??''),'postal_code'=>trim($_POST['postal_code']??''),
                'address'=>trim($_POST['address']??''),'billing_type'=>$_POST['billing_type']??'individual',
                'company'=>trim($_POST['company']??''),'tax_office'=>trim($_POST['tax_office']??''),'tax_no'=>trim($_POST['tax_no']??''),
                'save_address'=>!empty($_POST['save_address'])?1:0
            ];
            foreach(['first_name','last_name','email','phone','city','district','address'] as $required)if($address[$required]==='')throw new RuntimeException('Lütfen zorunlu adres ve iletişim alanlarını doldurun.');
            if(!filter_var($address['email'],FILTER_VALIDATE_EMAIL))throw new RuntimeException('Geçerli bir e-posta adresi girin.');
            if($address['billing_type']==='corporate'&&($address['company']===''||$address['tax_office']===''||$address['tax_no']===''))throw new RuntimeException('Kurumsal fatura için firma, vergi dairesi ve vergi numarası zorunludur.');
            $_SESSION['checkout_address']=$address;
            unset($_SESSION['checkout_shipping'],$_SESSION['checkout_payment']);
            checkout_redirect('delivery');
        }
        if($action==='save_delivery'){
            $address=$_SESSION['checkout_address']??null;
            if(!$address)checkout_redirect('address');
            $selection=shipping_selection_from_code((string)($_POST['shipping_code']??''),$subtotal,$address,$items);
            if(!$selection)throw new RuntimeException('Seçtiğiniz teslimat yöntemi artık uygun değil. Lütfen tekrar seçim yapın.');
            $_SESSION['checkout_shipping']=$selection;
            checkout_redirect('payment');
        }
        if($action==='save_payment'){
            if(($_POST['payment_method']??'')!=='bank_transfer')throw new RuntimeException('Şu anda yalnızca Havale / EFT ile ödeme aktiftir.');
            $_SESSION['checkout_payment']=['method'=>'bank_transfer'];
            checkout_redirect('review');
        }
        if($action==='create_order'){
            $address=$_SESSION['checkout_address']??null;$shipping=$_SESSION['checkout_shipping']??null;$payment=$_SESSION['checkout_payment']??null;
            if(!$address||!$shipping||!$payment)throw new RuntimeException('Sipariş adımları tamamlanmamış.');
            $freshShipping=shipping_selection_from_code((string)$shipping['code'],$subtotal,$address,$items);
            if(!$freshShipping)throw new RuntimeException('Teslimat uygunluğu değişti. Lütfen teslimat adımını tekrar kontrol edin.');
            $shipping=$freshShipping;$grand=round($subtotal+(float)$shipping['fee'],2);$pdo=db();$pdo->beginTransaction();
            try{
                foreach($items as $item){
                    $lock=$pdo->prepare('SELECT stock,purchase_price,is_active FROM products WHERE id=? FOR UPDATE');$lock->execute([(int)$item['id']]);$row=$lock->fetch();
                    if(!$row||(int)$row['is_active']!==1||(int)$row['stock']<(int)$item['quantity'])throw new RuntimeException($item['name'].' için yeterli stok kalmadı.');
                }
                do{$orderNo='TPL-'.date('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(5)),0,8));$q=$pdo->prepare('SELECT COUNT(*) FROM orders WHERE order_no=?');$q->execute([$orderNo]);}while((int)$q->fetchColumn()>0);
                $claimToken=null;$claimHash=null;$claimExp=null;
                if(!user_id()){$claimToken=bin2hex(random_bytes(32));$claimHash=hash('sha256',$claimToken);$claimExp=date('Y-m-d H:i:s',time()+86400*7);}
                $customerName=trim($address['first_name'].' '.$address['last_name']);
                $billSame=$address['billing_type']==='individual'?1:0;$company=$address['billing_type']==='corporate'?$address['company']:null;$taxOffice=$address['billing_type']==='corporate'?$address['tax_office']:null;$taxNo=$address['billing_type']==='corporate'?$address['tax_no']:null;$companyId=$shipping['company_id']??null;
                $st=$pdo->prepare('INSERT INTO orders(order_no,user_id,customer_name,customer_email,customer_phone,city,district,neighborhood,address,billing_same_as_shipping,billing_company,billing_tax_office,billing_tax_no,subtotal,discount_total,shipping_total,grand_total,delivery_method,shipping_company_id,shipping_company_name,payment_method,payment_status,status,account_claim_token_hash,account_claim_expires_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
                $st->execute([$orderNo,user_id(),$customerName,$address['email'],$address['phone'],$address['city'],$address['district'],$address['neighborhood']?:null,$address['address'],$billSame,$company,$taxOffice,$taxNo,$subtotal,(float)$shipping['fee'],$grand,$shipping['code']==='same_day'?'same_day':'cargo',$companyId,$shipping['name'],'bank_transfer','pending','new',$claimHash,$claimExp]);
                $orderId=(int)$pdo->lastInsertId();
                foreach($items as $item){
                    $price=unit_price($item,(int)$item['quantity']);$line=round($price*(int)$item['quantity'],2);
                    $pdo->prepare('INSERT INTO order_items(order_id,product_id,product_name,sku,quantity,unit_price,unit_cost,vat_rate,discount_total,line_total) VALUES(?,?,?,?,?,?,?,?,0,?)')->execute([$orderId,$item['id'],$item['name'],$item['sku'],$item['quantity'],$price,$item['purchase_price'],$item['vat_rate'],$line]);
                    $pdo->prepare('UPDATE products SET stock=stock-?,sales_count=sales_count+? WHERE id=?')->execute([$item['quantity'],$item['quantity'],$item['id']]);
                    $pdo->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,unit_cost,reference_type,reference_id,notes,created_at) VALUES(?,'sale',?,?,'order',?,'Online sipariş',NOW())")->execute([$item['id'],-1*(int)$item['quantity'],$item['purchase_price'],$orderId]);
                }
                $pdo->prepare("INSERT INTO order_status_history(order_id,status,note,created_at) VALUES(?,'new','Sipariş oluşturuldu',NOW())")->execute([$orderId]);
                $pdo->prepare("INSERT INTO payments(order_id,provider,amount,status,notes,created_at,updated_at) VALUES(?,'bank_transfer',?,'pending','Havale/EFT bekleniyor',NOW(),NOW())")->execute([$orderId,$grand]);
                $pdo->prepare('UPDATE carts SET converted_order_id=?,customer_email=?,subtotal=?,updated_at=NOW() WHERE id=?')->execute([$orderId,$address['email'],$subtotal,cart_id()]);
                $pdo->commit();
                checkout_save_user_address($address);
                $_SESSION['last_order']=['id'=>$orderId,'no'=>$orderNo,'claim_token'=>$claimToken];
                unset($_SESSION['checkout_address'],$_SESSION['checkout_shipping'],$_SESSION['checkout_payment']);
                redirect_to('order-success.php');
            }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
        }
    }catch(Throwable $e){flash('error',$e->getMessage());checkout_redirect($step);}
}

$address=$_SESSION['checkout_address']??null;$shipping=$_SESSION['checkout_shipping']??null;$payment=$_SESSION['checkout_payment']??null;
if($step!=='address'&&!$address)checkout_redirect('address');if(in_array($step,['payment','review'],true)&&!$shipping)checkout_redirect('delivery');if($step==='review'&&!$payment)checkout_redirect('payment');
$same=$address?same_day_quote($address,$items,$subtotal):null;$cargo=standard_shipping_options($subtotal);
$pageTitle='Siparişi Tamamla | TOPLUCA';$pageDescription='Teslimat adresi, kargo ve Havale/EFT ödeme adımlarını güvenli biçimde tamamlayın.';
require __DIR__.'/includes/header.php';
?>
<section class="checkout-page"><div class="container checkout-shell">
<div class="checkout-steps"><?php foreach(['address'=>['1','Adres'],'delivery'=>['2','Teslimat'],'payment'=>['3','Ödeme'],'review'=>['4','Son Kontrol']] as $key=>$meta):?><div class="step <?=$step===$key?'active':''?>"><b><?=$meta[0]?></b><span><?=$meta[1]?></span></div><?php endforeach;?></div>
<div class="checkout-grid"><div class="checkout-card">
<?php if($step==='address'):?>
<h2>Teslimat bilgileri</h2><p>Kargo ve Ankara aynı gün seçeneklerini gösterebilmemiz için önce teslimat adresinizi girin.</p>
<form method="post"><?=csrf_field()?><input type="hidden" name="action" value="save_address"><div class="form-grid"><?php $u=current_user();?><div class="field"><label>Adres Başlığı</label><input name="title" value="<?=h($address['title']??'Adresim')?>"></div><div class="field"></div><div class="field"><label>Ad *</label><input name="first_name" required value="<?=h($address['first_name']??($u['first_name']??''))?>"></div><div class="field"><label>Soyad *</label><input name="last_name" required value="<?=h($address['last_name']??($u['last_name']??''))?>"></div><div class="field"><label>E-posta *</label><input type="email" name="email" required value="<?=h($address['email']??($u['email']??''))?>"></div><div class="field"><label>Telefon *</label><input name="phone" required value="<?=h($address['phone']??($u['phone']??''))?>"></div><div class="field"><label>İl *</label><input name="city" required placeholder="Örn. Ankara" value="<?=h($address['city']??'')?>"></div><div class="field"><label>İlçe *</label><input name="district" required placeholder="Örn. Çankaya" value="<?=h($address['district']??'')?>"></div><div class="field"><label>Mahalle</label><input name="neighborhood" value="<?=h($address['neighborhood']??'')?>"></div><div class="field"><label>Posta Kodu</label><input name="postal_code" value="<?=h($address['postal_code']??'')?>"></div><div class="field full"><label>Açık Adres *</label><textarea name="address" required><?=h($address['address']??'')?></textarea></div><div class="field"><label>Fatura Tipi</label><select name="billing_type"><option value="individual" <?=($address['billing_type']??'individual')==='individual'?'selected':''?>>Bireysel</option><option value="corporate" <?=($address['billing_type']??'')==='corporate'?'selected':''?>>Kurumsal</option></select></div><div class="field"><label>Firma</label><input name="company" value="<?=h($address['company']??'')?>"></div><div class="field"><label>Vergi Dairesi</label><input name="tax_office" value="<?=h($address['tax_office']??'')?>"></div><div class="field"><label>Vergi No / TCKN</label><input name="tax_no" value="<?=h($address['tax_no']??'')?>"></div><?php if(user_id()):?><div class="field full"><label class="checkout-check"><input type="checkbox" name="save_address" value="1" checked> Bu adresi hesabıma kaydet</label></div><?php endif;?></div><div class="checkout-actions"><a class="secondary-btn" href="<?=app_url('cart.php')?>">← Sepete Dön</a><button class="primary-btn">Teslimat Seçeneklerini Göster →</button></div></form>
<?php elseif($step==='delivery'):?>
<h2>Teslimat yöntemi</h2><p><b><?=h($address['city'].' / '.$address['district'])?></b> adresiniz için kullanılabilir seçenekler.</p>
<form method="post"><?=csrf_field()?><input type="hidden" name="action" value="save_delivery"><?php foreach($cargo as $o):?><label class="delivery-option"><div class="delivery-option-head"><span><input type="radio" name="shipping_code" value="cargo_<?=(int)$o['id']?>" required> <strong><?=h($o['name'])?></strong></span><b><?=$o['calculated_fee']<=0?'Ücretsiz':money($o['calculated_fee'])?></b></div><small><?=h($o['estimated_days']?:'1-3 iş günü')?> · Takip numarası sipariş onayından sonra eklenir.</small></label><?php endforeach;?><label class="delivery-option <?=($same&&$same['available'])?'':'disabled'?>"><div class="delivery-option-head"><span><input type="radio" name="shipping_code" value="same_day" <?=($same&&$same['available'])?'':'disabled'?>> <strong>⚡ TOPLUCA Ankara Aynı Gün</strong></span><b><?=($same&&$same['available'])?($same['fee']<=0?'Ücretsiz':money($same['fee'])):'Kullanılamaz'?></b></div><?php if($same&&$same['available']):?><small><?=h($address['district'])?> · <?=h($same['cutoff'])?> öncesi uygun siparişlerde.</small><?php if($same['remaining']>0):?><div class="same-day-note"><?=money($same['remaining'])?> daha eklerseniz aynı gün teslimat ücretsiz olur. Şimdi seçerseniz <?=money($same['fee'])?>.</div><?php endif;?><?php else:?><small><?=h($same['reason']??'Adres bilgisi uygun değil.')?></small><?php endif;?></label><div class="checkout-actions"><a class="secondary-btn" href="<?=app_url('checkout.php?step=address')?>">← Adresi Düzenle</a><button class="primary-btn">Ödemeye Geç →</button></div></form>
<?php elseif($step==='payment'):?>
<h2>Ödeme yöntemi</h2><p>Şu anda Havale / EFT aktif. Kartla ödeme daha sonra ödeme kuruluşu entegrasyonuyla açılabilir.</p>
<form method="post"><?=csrf_field()?><input type="hidden" name="action" value="save_payment"><label class="payment-option selected"><input type="radio" name="payment_method" value="bank_transfer" checked> <strong>Havale / EFT</strong><small><?=h(setting('bank_payment_note','Havale/EFT açıklamasına sipariş numaranızı yazınız.'))?></small><div class="bank-box"><b><?=h(setting('bank_name','Demo Bankası'))?></b><br><?=h(setting('bank_account_name','Yıldız Ofis Kırtasiye'))?><?php if(trim((string)setting('bank_branch',''))!==''):?><br>Şube: <?=h(setting('bank_branch',''))?><?php endif;?><br><strong>IBAN: <?=h(setting('bank_iban','TR00 0000 0000 0000 0000 0000 00'))?></strong></div></label><label class="payment-option disabled"><input type="radio" disabled> <strong>Kredi / Banka Kartı</strong><small>Şu anda aktif değil.</small></label><div class="checkout-actions"><a class="secondary-btn" href="<?=app_url('checkout.php?step=delivery')?>">← Teslimata Dön</a><button class="primary-btn">Son Kontrole Geç →</button></div></form>
<?php else:?>
<h2>Siparişi kontrol edin</h2><p>Sipariş oluşturulduğunda stok yeniden doğrulanır ve size benzersiz bir TOPLUCA sipariş kodu verilir.</p><div class="review-block"><h3>Teslimat Adresi</h3><div class="review-item"><span><?=h($address['first_name'].' '.$address['last_name'])?><br><?=h($address['address'])?><br><?=h($address['district'].' / '.$address['city'])?></span><a href="<?=app_url('checkout.php?step=address')?>">Düzenle</a></div></div><div class="review-block"><h3>Teslimat</h3><div class="review-item"><span><?=h($shipping['name'])?></span><b><?=$shipping['fee']<=0?'Ücretsiz':money($shipping['fee'])?></b></div></div><div class="review-block"><h3>Ödeme</h3><div class="review-item"><span>Havale / EFT</span><a href="<?=app_url('checkout.php?step=payment')?>">Düzenle</a></div></div><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="create_order"><div class="checkout-actions"><a class="secondary-btn" href="<?=app_url('checkout.php?step=payment')?>">← Ödemeye Dön</a><button class="primary-btn">Siparişi Oluştur →</button></div></form>
<?php endif;?>
</div>
<aside class="checkout-summary"><h3>Sipariş Özeti</h3><?php foreach($items as $it):?><div class="checkout-product"><span><?=h($it['name'])?> × <?=(int)$it['quantity']?></span><b><?=money($it['line_total'])?></b></div><?php endforeach;?><div class="summary-row"><span>Ürünler</span><b><?=money($subtotal)?></b></div><?php if($shipping):?><div class="summary-row"><span>Teslimat</span><b><?=$shipping['fee']<=0?'Ücretsiz':money($shipping['fee'])?></b></div><?php endif;?><div class="summary-row total"><span>Toplam</span><span><?=money($subtotal+($shipping['fee']??0))?></span></div><div class="secure-note">🔒 Fiyat ve stok sipariş oluşturma anında tekrar doğrulanır.</div></aside>
</div></div></section>
<?php require __DIR__.'/includes/footer.php';?>