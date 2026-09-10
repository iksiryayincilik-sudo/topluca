<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';

$error='';
if(($_GET['action']??'')==='logout'){
    unset($_SESSION['user_id']);
    flash('success','Hesabınızdan çıkış yaptınız.');
    redirect('account.php');
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $action=$_POST['action']??'';
    if($action==='login'){
        $email=trim($_POST['email']??'');$password=(string)($_POST['password']??'');
        $s=db()->prepare('SELECT * FROM users WHERE email=? AND is_active=1 LIMIT 1');$s->execute([$email]);$u=$s->fetch();
        if($u && $u['password_hash'] && password_verify($password,$u['password_hash'])){
            session_regenerate_id(true);$_SESSION['user_id']=(int)$u['id'];
            db()->prepare('UPDATE users SET last_login_at=NOW(),updated_at=NOW() WHERE id=?')->execute([$u['id']]);
            redirect('account.php');
        } else $error='E-posta veya şifre hatalı.';
    }
}

$user=null;$orders=[];$addresses=[];
if(!empty($_SESSION['user_id'])){
    $s=db()->prepare('SELECT * FROM users WHERE id=? AND is_active=1');$s->execute([(int)$_SESSION['user_id']]);$user=$s->fetch()?:null;
    if(!$user)unset($_SESSION['user_id']);
    else{
        $s=db()->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 50');$s->execute([$user['id']]);$orders=$s->fetchAll();
        $s=db()->prepare('SELECT * FROM user_addresses WHERE user_id=? ORDER BY is_default_shipping DESC,id DESC');$s->execute([$user['id']]);$addresses=$s->fetchAll();
    }
}
$pageTitle='Hesabım | TOPLUCA';
require __DIR__.'/includes/header.php';
?>
<section class="account-shell"><div class="container">
<?php if(!$user):?>
    <div class="account-login-layout">
        <div class="account-welcome"><span class="eyebrow">TOPLUCA HESABIM</span><h1>Siparişlerinize tek yerden ulaşın.</h1><p>Sipariş takibi, kayıtlı adresler, favoriler ve hızlı alışveriş için hesabınıza giriş yapın.</p><div class="account-benefits"><div>✓ Sipariş geçmişi</div><div>✓ Kayıtlı teslimat adresleri</div><div>✓ Favoriler</div><div>✓ Daha hızlı ödeme</div></div></div>
        <form class="account-login-card" method="post"><?=csrf_field()?><input type="hidden" name="action" value="login"><h2>Giriş Yap</h2><p>Sipariş sonrası oluşturduğunuz hesabınızla giriş yapın.</p><?php if($error):?><div class="checkout-alert error"><?=h($error)?></div><?php endif;?><label>E-posta<input type="email" name="email" autocomplete="email" required></label><label>Şifre<input type="password" name="password" autocomplete="current-password" required></label><button class="checkout-primary">Hesabıma Giriş Yap</button><small>İlk siparişinizi üye olmadan verebilir, sipariş tamamlandıktan sonra yalnızca bir şifre belirleyerek hesabınızı oluşturabilirsiniz.</small></form>
    </div>
<?php else:?>
    <div class="account-head"><div><span class="eyebrow">HESABIM</span><h1>Merhaba, <?=h($user['first_name']?:'TOPLUCA müşterisi')?> 👋</h1><p><?=h($user['email'])?></p></div><a class="ghost-button" href="<?=url('account.php?action=logout')?>">Çıkış Yap</a></div>
    <div class="account-dashboard">
        <aside class="account-nav"><a class="active" href="#orders">Siparişlerim <b><?=count($orders)?></b></a><a href="#addresses">Adreslerim <b><?=count($addresses)?></b></a><a href="#favorites">Favorilerim</a><a href="<?=url('cart.php')?>">Sepetim</a></aside>
        <div class="account-content">
            <section id="orders" class="account-panel"><div class="panel-title"><h2>Siparişlerim</h2><span>Son siparişleriniz</span></div><?php if(!$orders):?><div class="empty-card">Henüz hesabınıza bağlı sipariş yok.</div><?php else:?><div class="account-orders"><?php foreach($orders as $o):?><div class="account-order"><div><small>Sipariş</small><b><?=h($o['order_no'])?></b><span><?=date('d.m.Y H:i',strtotime($o['created_at']))?></span></div><div><small>Durum</small><b><?=h($o['status'])?></b></div><div><small>Teslimat</small><b><?=h($o['shipping_company_name']??'')?></b></div><div><small>Toplam</small><strong><?=money($o['grand_total'])?></strong></div></div><?php endforeach;?></div><?php endif;?></section>
            <section id="addresses" class="account-panel"><div class="panel-title"><h2>Adreslerim</h2><span>Kayıtlı teslimat adresleri</span></div><?php if(!$addresses):?><div class="empty-card">Henüz kayıtlı adresiniz yok. Yeni siparişinizde adresinizi hesabınıza kaydedebileceksiniz.</div><?php else:?><div class="address-cards"><?php foreach($addresses as $a):?><div class="address-card"><b><?=h($a['title'])?></b><span><?=h($a['first_name'].' '.$a['last_name'])?></span><p><?=h($a['address'])?><br><?=h($a['district'].' / '.$a['city'])?></p></div><?php endforeach;?></div><?php endif;?></section>
        </div>
    </div>
<?php endif;?>
</div></section>
<?php require __DIR__.'/includes/footer.php';
