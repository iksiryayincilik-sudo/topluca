<?php
require __DIR__.'/app/bootstrap.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
  csrf_check();$pid=(int)($_POST['product_id']??0);
  if(!user_logged_in()){$_SESSION['after_login']=$_SERVER['HTTP_REFERER']??url('favorites.php');flash('info','Favorilere eklemek için giriş yapın veya üye olun.');redirect('account.php');}
  $uid=(int)$_SESSION['user_id'];$s=db()->prepare('SELECT id FROM favorites WHERE user_id=? AND product_id=?');$s->execute([$uid,$pid]);$id=$s->fetchColumn();
  if($id){db()->prepare('DELETE FROM favorites WHERE id=?')->execute([$id]);flash('success','Ürün favorilerden çıkarıldı.');}else{db()->prepare('INSERT INTO favorites(user_id,product_id,created_at) VALUES(?,?,NOW())')->execute([$uid,$pid]);flash('success','Ürün favorilerinize eklendi.');}
  $back=$_SERVER['HTTP_REFERER']??url('favorites.php');header('Location: '.$back);exit;
}
require_user();$uid=(int)$_SESSION['user_id'];$s=db()->prepare("SELECT p.*,b.name brand_name,b.slug brand_slug,c.name category_name,c.slug category_slug FROM favorites f JOIN products p ON p.id=f.product_id LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id WHERE f.user_id=? AND p.is_active=1 ORDER BY f.id DESC");$s->execute([$uid]);$products=$s->fetchAll();$pageTitle='Favorilerim | TOPLUCA';require __DIR__.'/includes/header.php';
?>
<section class="account-page"><div class="shell"><div class="breadcrumbs"><a href="<?=url()?>">Anasayfa</a><span>›</span><a href="<?=url('account.php')?>">Hesabım</a><span>›</span><span>Favorilerim</span></div><div class="section-head"><div class="section-head__copy"><small>HESABIM</small><h2>Favorilerim</h2></div><span style="font-size:10px;color:#777"><?=count($products)?> ürün</span></div><?php if(!$products):?><div class="empty-state"><div class="empty-state__icon">♡</div><h2>Favori listeniz boş</h2><p>Beğendiğiniz ürünleri kalp simgesine dokunarak burada saklayabilirsiniz.</p><a class="primary-btn" href="<?=url()?>">Ürünleri Keşfet</a></div><?php else:?><div class="products-grid"><?php foreach($products as $product)require __DIR__.'/includes/product-card.php';?></div><?php endif;?></div></section>
<?php require __DIR__.'/includes/footer.php';?>
