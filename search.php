<?php
require __DIR__.'/app/bootstrap.php';
$q=trim((string)($_GET['q']??''));$terms=$q!==''?[$q]:[];
if($q!==''){
  foreach(db()->query('SELECT term,synonyms FROM search_synonyms WHERE is_active=1')->fetchAll() as $syn){$pool=array_map('trim',explode(',',$syn['synonyms']));$pool[]=$syn['term'];$norms=array_map('tr_normalize',$pool);if(in_array(tr_normalize($q),$norms,true)){$terms=array_values(array_unique(array_merge($terms,$pool)));}}
}
$params=[];$conditions=[];foreach($terms as $term){$like='%'.$term.'%';$conditions[]='(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ? OR p.isbn LIKE ? OR b.name LIKE ? OR c.name LIKE ? OR p.short_description LIKE ?)';for($i=0;$i<7;$i++)$params[]=$like;}
$where=$conditions?implode(' OR ',$conditions):'1=1';$sort=(string)($_GET['sort']??'recommended');$order=match($sort){'price_asc'=>'p.sale_price ASC','price_desc'=>'p.sale_price DESC','new'=>'p.id DESC',default=>'p.is_bestseller DESC,p.sales_count DESC,p.id DESC'};
$sql="SELECT p.*,b.name brand_name,b.slug brand_slug,c.name category_name,c.slug category_slug FROM products p LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1 AND ($where) ORDER BY $order LIMIT 120";$s=db()->prepare($sql);$s->execute($params);$products=$s->fetchAll();
if($q!==''){$s=db()->prepare('INSERT INTO search_logs(search_term,result_count,session_key,user_id,created_at) VALUES(?,?,?,?,NOW())');$s->execute([$q,count($products),session_id(),user_logged_in()?(int)$_SESSION['user_id']:null]);}
$pageTitle=($q!==''?'“'.$q.'” Arama Sonuçları':'Tüm Ürünler').' | TOPLUCA';view_event('search');require __DIR__.'/includes/header.php';
?>
<section class="page-hero"><div class="shell"><div class="breadcrumbs"><a href="<?=url()?>">Anasayfa</a><span>›</span><span>Arama</span></div><h1><?=$q!==''?'“'.h($q).'” için sonuçlar':'Tüm Ürünler'?></h1><p><b><?=count($products)?></b> ürün bulundu<?=count($terms)>1?' · Benzer arama ifadeleri de kontrol edildi.':''?></p></div></section>
<section class="section"><div class="shell"><div class="catalog-toolbar"><div class="catalog-toolbar__count"><b><?=count($products)?></b> sonuç</div><div class="catalog-toolbar__right"><form method="get"><input type="hidden" name="q" value="<?=h($q)?>"><select name="sort" data-auto-submit><option value="recommended">Önerilen</option><option value="price_asc" <?=$sort==='price_asc'?'selected':''?>>Fiyat Artan</option><option value="price_desc" <?=$sort==='price_desc'?'selected':''?>>Fiyat Azalan</option><option value="new" <?=$sort==='new'?'selected':''?>>Yeni Gelenler</option></select></form></div></div>
<?php if(!$products):?><div class="empty-state"><div class="empty-state__icon">🔎</div><h2>Aradığınız ürün bulunamadı.</h2><p>Farklı bir kelime deneyin. Sonuçsuz aramanız WebYönet'e kaydedildi; böylece ürün ve eş anlamlı geliştirmelerinde kullanılabilir.</p><a class="primary-btn" href="<?=url()?>">Ana Sayfaya Dön</a></div><?php else:?><div class="products-grid"><?php foreach($products as $product)require __DIR__.'/includes/product-card.php';?></div><?php endif;?></div></section>
<?php require __DIR__.'/includes/footer.php';?>
