<?php
require dirname(__DIR__).'/app/bootstrap.php';
if(admin_logged_in())redirect('webyonet/index.php');
$error='';
$ip=$_SERVER['REMOTE_ADDR']??'';$ipHash=$ip?hash('sha256',$ip.($config['app_key']??'')):'';
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();$username=trim((string)($_POST['username']??''));$password=(string)($_POST['password']??'');
  $s=db()->prepare("SELECT COUNT(*) FROM admin_login_attempts WHERE username=? AND ip_hash=? AND was_successful=0 AND created_at>=DATE_SUB(NOW(),INTERVAL 15 MINUTE)");$s->execute([$username,$ipHash]);$attempts=(int)$s->fetchColumn();
  if($attempts>=5){$error='Çok fazla hatalı giriş denemesi. 15 dakika sonra yeniden deneyin.';}
  else{
    $s=db()->prepare('SELECT * FROM admins WHERE username=? AND is_active=1 LIMIT 1');$s->execute([$username]);$admin=$s->fetch();$ok=$admin&&password_verify($password,$admin['password_hash']);
    db()->prepare('INSERT INTO admin_login_attempts(username,ip_hash,was_successful,created_at) VALUES(?,?,?,NOW())')->execute([$username,$ipHash,$ok?1:0]);
    if($ok){session_regenerate_id(true);$_SESSION['admin_id']=(int)$admin['id'];db()->prepare('UPDATE admins SET last_login_at=NOW() WHERE id=?')->execute([$admin['id']]);admin_log('login','admin',(int)$admin['id'],'WebYönet girişi');redirect('webyonet/index.php');}
    $error='Kullanıcı adı veya şifre hatalı.';
  }
}
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TOPLUCA WebYönet</title><link rel="stylesheet" href="<?=asset('css/admin.css')?>?v=3.0.0"></head><body>
<div class="admin-login"><section class="admin-login__visual"><div class="admin-login__logo"><?php $logoClass='';require dirname(__DIR__).'/includes/logo.php';?></div><div><h1>TOPLUCA'nın <span>kontrol merkezi.</span></h1><p>Sipariş, stok, fiyat, müşteri, kargo, Ankara aynı gün teslimat, kampanya ve satış analizlerini tek merkezden yönetin.</p></div><small style="color:#6f767d">TOPLUCA WebYönet v3.0</small></section><section class="admin-login__formwrap"><form class="admin-login__card" method="post"><?=csrf_field()?><h2>WebYönet'e Giriş</h2><p>Yönetim merkezine erişmek için hesabınızla giriş yapın.</p><?php if($error):?><div class="admin-flash error"><?=h($error)?></div><?php endif;?><label class="field">Kullanıcı Adı<input name="username" autocomplete="username" autofocus required></label><label class="field">Şifre<input type="password" name="password" autocomplete="current-password" required></label><button class="admin-btn" style="width:100%;min-height:43px;margin-top:8px">Giriş Yap →</button><div style="margin-top:17px;padding-top:14px;border-top:1px solid #edf0f2;font-size:8.5px;color:#8a9198">İlk kurulum hesabı: <b>emrah</b>. Güvenlik için ilk girişten sonra başlangıç şifresini değiştirin.</div></form></section></div></body></html>
