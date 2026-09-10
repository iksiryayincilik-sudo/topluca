<?php
require __DIR__.'/app/bootstrap.php';
$last=$_SESSION['last_order']??null;if(!$last)redirect('account.php');
$s=db()->prepare('SELECT * FROM orders WHERE id=? AND order_no=? LIMIT 1');$s->execute([(int)$last['id'],(string)$last['no']]);$order=$s->fetch();if(!$order)redirect('account.php');
$error='';$accountCreated=false;
if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='create_account'){
  csrf_check();$password=(string)($_POST['password']??'');$confirm=(string)($_POST['password_confirm']??'');
  if($order['user_id']){$error='Bu sipariş zaten bir üyelikle bağlantılı.';}
  elseif(empty($last['claim_token'])||empty($order['guest_claim_token_hash'])||!password_verify((string)$last['claim_token'],$order['guest_claim_token_hash'])){$error='Sipariş güvenlik doğrulaması başarısız.';}
  elseif(strlen($password)<10)$error='Şifreniz en az 10 karakter olmalıdır.';
  elseif($password!==$confirm)$error='Şifreler eşleşmiyor.';
  else{
    $s=db()->prepare('SELECT id FROM users WHERE email=? LIMIT 1');$s->execute([$order['customer_email']]);
    if($s->fetchColumn())$error='Bu e-posta ile zaten bir üyelik var. Hesabınıza giriş yapabilirsiniz.';
    else{
      try{db()->beginTransaction();$s=db()->prepare('INSERT INTO users(email,password_hash,first_name,last_name,phone,is_active,created_at,updated_at) VALUES(?,?,?,?,?,1,NOW(),NOW())');$s->execute([$order['customer_email'],password_hash($password,PASSWORD_DEFAULT),$order['shipping_first_name'],$order['shipping_last_name'],$order['customer_phone']]);$uid=(int)db()->lastInsertId();
        db()->prepare('UPDATE orders SET user_id=?,guest_claim_token_hash=NULL,updated_at=NOW() WHERE id=?')->execute([$uid,$order['id']]);
        db()->prepare("INSERT INTO user_addresses(user_id,title,first_name,last_name,phone,company,tax_office,tax_no,city,district,neighborhood,address,postal_code,is_default_shipping,is_default_billing,created_at,updated_at) VALUES(?,'Teslimat Adresim',?,?,?,?,?,?,?,?,?,?,?,?,1,1,NOW(),NOW())")->execute([$uid,$order['shipping_first_name'],$order['shipping_last_name'],$order['customer_phone'],$order['billing_company'],$order['billing_tax_office'],$order['billing_tax_no'],$order['shipping_city'],$order['shipping_district'],$order['shipping_neighborhood'],$order['shipping_address'],$order['shipping_postal_code']]);
        db()->commit();$s=db()->prepare('SELECT * FROM users WHERE id=?');$s->execute([$uid]);login_user($s->fetch());$accountCreated=true;$order['user_id']=$uid;unset($_SESSION['last_order']['claim_token']);
      }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();$error=$e->getMessage();}
    }
  }
}
$s=db()->prepare('SELECT * FROM order_items WHERE order_id=? ORDER BY id');$s->execute([$order['id']]);$items=$s->fetchAll();$pageTitle='Siparişiniz Alındı | TOPLUCA';require __DIR__.'/includes/header.php';
?>
<section class="checkout-page"><div class="checkout-shell" style="max-width:980px"><div class="checkout-card" style="text-align:center;padding:30px"><div style="width:68px;height:68px;border-radius:50%;background:#e9f9ef;color:#168147;display:grid;place-items:center;font-size:32px;font-weight:900;margin:0 auto 15px">✓</div><h1 style="margin:0;font-size:27px">Siparişiniz başarıyla oluşturuldu!</h1><p class="lead" style="margin-top:8px">Siparişinizi WebYönet üzerinden takip etmeye başladık. Sipariş numaranızı saklayın.</p><div style="display:inline-flex;align-items:center;gap:12px;background:#f5f7f8;border-radius:10px;padding:12px 18px;margin:10px 0 20px"><span style="font-size:10px;color:#777">Sipariş No</span><strong style="font-size:18px"><?=h($order['order_no'])?></strong><button class="secondary-btn" type="button" data-copy="<?=h($order['order_no'])?>">Kopyala</button></div>
<div style="text-align:left;display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:10px"><div class="review-block"><h4>Teslimat</h4><p><b><?=h($order['shipping_company_name'])?></b></p><p><?=h($order['shipping_district'].' / '.$order['shipping_city'])?></p></div><div class="review-block"><h4>Ödeme</h4><p><b><?=$order['payment_method']==='bank_transfer'?'Havale / EFT':'Kart'?></b></p><p>Toplam: <strong><?=money($order['grand_total'])?></strong></p></div></div>
<?php if($order['payment_method']==='bank_transfer'):?><div class="progress-card" style="text-align:left;background:#eef6ff;color:#285c8d">🏦 Havale/EFT siparişi oluşturuldu. Banka bilgileri WebYönet → Site Ayarları üzerinden tanımlandığında burada müşteriye gösterilebilir.</div><?php endif;?>
</div>
<?php if(!$order['user_id']&&!$accountCreated):?><div class="checkout-card" style="margin-top:16px"><h2>Siparişini hesabına dönüştür</h2><p class="lead">Adresini, e-postanı ve telefonunu yeniden yazmana gerek yok. Sadece bir şifre belirle; üyeliğin açılsın ve bu sipariş otomatik olarak hesabına bağlansın.</p><?php if($error):?><div class="flash flash--error"><?=h($error)?></div><?php endif;?><form method="post"><input type="hidden" name="action" value="create_account"><?=csrf_field()?><div class="form-grid"><label class="field">Şifre<input type="password" name="password" minlength="10" autocomplete="new-password" required></label><label class="field">Şifre Tekrar<input type="password" name="password_confirm" minlength="10" autocomplete="new-password" required></label></div><button class="primary-btn" style="margin-top:14px">Üyeliğimi Oluştur ve Siparişimi Bağla</button></form></div><?php elseif($accountCreated):?><div class="flash flash--success" style="margin-top:16px">Üyeliğiniz oluşturuldu ve siparişiniz hesabınıza bağlandı. Artık Hesabım bölümünden takip edebilirsiniz.</div><?php endif;?>
<div class="checkout-card" style="margin-top:16px"><h2>Sipariş İçeriği</h2><?php foreach($items as $item):?><div class="summary-row"><span><?=h($item['product_name'])?> × <?=(int)$item['quantity']?></span><strong><?=money($item['line_total'])?></strong></div><?php endforeach;?><div class="summary-row summary-total"><span>Genel Toplam</span><strong><?=money($order['grand_total'])?></strong></div><div class="checkout-nav"><a class="secondary-btn" href="<?=url()?>">Alışverişe Dön</a><a class="primary-btn" style="width:auto;min-width:180px" href="<?=url('account.php')?>">Hesabıma Git →</a></div></div>
</div></section>
<?php require __DIR__.'/includes/footer.php';?>
