<?php
require __DIR__.'/app/bootstrap.php';
$q=trim($_GET['q']??'');$params=[];$where='p.is_active=1';
if($q!==''){$like='%'.$q.'%';$where.=' AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ? OR b.name LIKE ? OR c.name LIKE ?)';$params=array_fill(0,5,$like);}
$s=db()->prepare("SELECT p.*,b.name brand_name,c.name category_name FROM products p LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id WHERE $where ORDER BY p.sales_count DESC,p.id DESC LIMIT 100");$s->execute($params);$products=$s->fetchAll();
if($q!==''){$l=db()->prepare('INSERT INTO search_logs(search_term,result_count,session_key,created_at) VALUES(?,?,?,NOW())');$l->execute([$q,count($products),session_id()]);}
$pageTitle=($q?'"'.$q.'" Arama Sonuçları':'Tüm Ürünler').' | TOPLUCA';require __DIR__.'/includes/header.php';
?><section class="page-head"><div class="container"><h1><?=$q?'“'.h($q).'” için sonuçlar':'Tüm Ürünler'?></h1><p><?=count($products)?> ürün bulundu</p></div></section><section class="section"><div class="container"><?php if(!$products):?><div class="summary"><h3>Aradığınız ürün bulunamadı.</h3><p>Farklı bir kelimeyle tekrar deneyin.</p></div><?php else:?><div class="products-grid"><?php foreach($products as $p) require __DIR__.'/includes/product-card.php';?></div><?php endif;?></div></section><?php require __DIR__.'/includes/footer.php';
