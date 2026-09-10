<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';

$orderNo=$_SESSION['last_order_no']??'';
$orderId=(int)($_SESSION['last_order_id']??0);
$orderEmail=(string)($_SESSION['last_order_email']??'');
$error='';$created=false;$already=false;

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $password=(string)($_POST['password']??'');
    $password2=(string)($_POST['password2']??'');
    if(!$orderId||!$orderEmail)$error='Sipariş oturumu bulunamadı.';
    elseif(strlen($password)<8)$error='Şifreniz en az 8 karakter olmalıdır.';
    elseif($password!==$password2)$error='Şifreler eşleşmiyor.';
    else{
        $s=db()->prepare('SELECT id FROM users WHERE email=? LIMIT 1');$s->execute([$orderEmail]);$uid=$s->fetchColumn();
        if($uid){$already=true;}
        else{
            $s=db()->prepare('SELECT customer_name,customer_phone FROM orders WHERE id=? AND customer_email=? LIMIT 1');$s->execute([$orderId,$orderEmail]);$o=$s->fetch();
            if(!$o)$error='Sipariş doğrulanamadı.';
            else{
                $parts=preg_split('/\s+/',trim($o['customer_name']),2);$first=$parts[0]??'';$last=$parts[1]??'';
                db()->beginTransaction();
                try{
                    $s=db()->prepare('INSERT INTO users(email,password_hash,first_name,last_name,phone,is_active,created_at,updated_at) VALUES(?,?,?,?,?,1,NOW(),NOW())');
                    $s->execute([$orderEmail,password_hash($password,PASSWORD_DEFAULT),$first,$last,$o['customer_phone']]);
                    $uid=(int)db()->lastInsertId();
                    db()->prepare('UPDATE orders SET user_id=? WHERE id=? AND customer_email=?')->execute([$uid,$orderId,$orderEmail]);
                    db()->prepare('UPDATE carts SET user_id=? WHERE converted_order_id=?')->execute([$uid,$orderId]);
                    db()->commit();
                    $_SESSION['user_id']=$uid;$created=true;
                }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();$error=$e->getMessage();}
            }
        }
    }
}
$pageTitle='Siparişiniz Alındı | TOPLUCA';
require __DIR__.'/includes/header.php';
?>
<section class="success-shell"><div class="container success-layout">
    <div class="success-main">
        <div class="success-check">✓</div><span class="eyebrow">SİPARİŞ ALINDI</span><h1>Teşekkürler! Siparişiniz oluşturuldu.</h1>
        <?php if($orderNo):?><div class="order-number"><span>Sipariş Numaranız</span><strong><?=h($orderNo)?></strong></div><?php endif;?>
        <p>Siparişiniz WebYönet'e aktarıldı. Durum değişikliklerini sipariş takip ekranından izleyebilirsiniz.</p>
        <div class="success-actions"><a class="checkout-primary inline" href="<?=url('orders.php')?>">Siparişi Takip Et</a><a class="ghost-button" href="<?=url()?>">Alışverişe Devam Et</a></div>
    </div>
    <aside class="account-after-order">
        <?php if($created):?>
            <span class="eyebrow">HESABINIZ HAZIR</span><h2>Üyeliğiniz oluşturuldu 🎉</h2><p>Bu sipariş hesabınıza bağlandı. Bundan sonraki siparişlerinizi tek yerden takip edebilirsiniz.</p><a class="checkout-primary inline" href="<?=url('account.php')?>">Hesabıma Git</a>
        <?php elseif($already):?>
            <span class="eyebrow">ZATEN ÜYESİNİZ</span><h2>Bu e-posta ile bir hesap var.</h2><p>Güvenlik nedeniyle mevcut hesabın şifresini burada değiştirmiyoruz. Hesabınıza giriş yaparak siparişlerinizi takip edebilirsiniz.</p><a class="checkout-primary inline" href="<?=url('account.php')?>">Giriş Yap</a>
        <?php else:?>
            <span class="eyebrow">10 SANİYEDE ÜYELİK</span><h2>Sadece şifre oluşturun.</h2><p><b><?=h($orderEmail)?></b> adresiniz hazır. Adres ve sipariş bilgileriniz yeniden girilmez.</p>
            <?php if($error):?><div class="checkout-alert error"><?=h($error)?></div><?php endif;?>
            <form method="post" class="checkout-form-v2"><?=csrf_field()?><label>Şifre<input type="password" name="password" autocomplete="new-password" required minlength="8"></label><label>Şifre Tekrar<input type="password" name="password2" autocomplete="new-password" required minlength="8"></label><button class="checkout-primary">Üyeliğimi Oluştur</button></form>
        <?php endif;?>
    </aside>
</div></section>
<?php require __DIR__.'/includes/footer.php';
