<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
if(empty($_SESSION['user_id'])){flash('error','Favorilerinizi görmek için hesabınıza giriş yapın.');redirect('account.php');}
$uid=(int)$_SESSION['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();$pid=(int)($_POST['product_id']??0);$action=$_POST['action']??'';
    if($action==='remove')db()->prepare('DELETE FROM favorites WHERE user_id=? AND product_id=?')->execute([$uid,$pid]);
    if($action==='add')db()->prepare('INSERT IGNORE INTO favorites(user_id,product_id,created_at) VALUES(?,?,NOW())')->execute([$uid,$pid]);
    redirect('favorites.php');
}
$s=db()->prepare('SELECT p.*,b.name brand_name FROM favorites f JOIN products p ON p.id=f.product_id LEFT JOIN brands b ON b.id=p.brand_id WHERE f.user_id=? AND p.is_active=1 ORDER BY f.id DESC');$s->execute([$uid]);$products=$s->fetchAll();
$pageTitle='Favorilerim | TOPLUCA';require __DIR__.'/includes/header.php';
?><section class="page-head"><div class="container"><h1>Favorilerim</h1><p><?=count($products)?> ürün</p></div></section><section class="section"><div class="container"><?php if(!$products):?><div class="empty-card">Henüz favorilerinize ürün eklemediniz.</div><?php else:?><div class="products-grid"><?php foreach($products as $p):?><article class="product-card"><a class="product-image" href="<?=url('product.php?slug='.urlencode($p['slug']))?>"><?php if($p['image']):?><img src="<?=url($p['image'])?>" alt="<?=h($p['name'])?>"><?php else:?><div class="placeholder"><span><?=h(mb_substr($p['name'],0,1))?></span><small>TOPLUCA</small></div><?php endif;?></a><div class="product-body"><div class="brand"><?=h($p['brand_name']??'')?></div><a class="name" href="<?=url('product.php?slug='.urlencode($p['slug']))?>"><?=h($p['name'])?></a><div class="price"><strong><?=money($p['sale_price'])?></strong></div><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?=(int)$p['id']?>"><button class="add-cart" style="background:#171717">Favorilerden Çıkar</button></form></div></article><?php endforeach;?></div><?php endif;?></div></section><?php require __DIR__.'/includes/footer.php';
