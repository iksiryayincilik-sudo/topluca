<?php
require __DIR__.'/app/bootstrap.php';
$slug=trim($_GET['slug']??'');
$s=db()->prepare('SELECT * FROM categories WHERE slug=? AND is_active=1');
$s->execute([$slug]);
$cat=$s->fetch();
if(!$cat){http_response_code(404);exit('Kategori bulunamadı.');}
page_view('category',(int)$cat['id']);
$ids=category_descendants((int)$cat['id']);
$ph=implode(',',array_fill(0,count($ids),'?'));
$params=$ids;
$where=["p.category_id IN ($ph)",'p.is_active=1'];
$brand=(int)($_GET['brand']??0);
$min=$_GET['min']??'';
$max=$_GET['max']??'';
$stock=!empty($_GET['stock']);
$free=!empty($_GET['free']);
$sort=$_GET['sort']??'popular';
if($brand){$where[]='p.brand_id=?';$params[]=$brand;}
if($min!==''){$where[]='p.sale_price>=?';$params[]=(float)$min;}
if($max!==''){$where[]='p.sale_price<=?';$params[]=(float)$max;}
if($stock)$where[]='p.stock>0';
if($free)$where[]="p.shipping_policy='free'";
$order='p.sales_count DESC,p.is_featured DESC,p.id DESC';
if($sort==='price_asc')$order='p.sale_price ASC';
elseif($sort==='price_desc')$order='p.sale_price DESC';
elseif($sort==='new')$order='p.id DESC';
$sql="SELECT p.*,b.name brand_name,c.name category_name FROM products p LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id WHERE ".implode(' AND ',$where)." ORDER BY $order LIMIT 200";
$s=db()->prepare($sql);$s->execute($params);$products=$s->fetchAll();
$brandSql="SELECT DISTINCT b.* FROM brands b JOIN products p ON p.brand_id=b.id WHERE p.category_id IN ($ph) AND b.is_active=1 ORDER BY b.name";
$bs=db()->prepare($brandSql);$bs->execute($ids);$brands=$bs->fetchAll();
$children=category_children((int)$cat['id']);
$pageTitle=$cat['name'].' | TOPLUCA';
require __DIR__.'/includes/header.php';
?>
<section class="page-hero">
  <div class="container">
    <div class="breadcrumbs"><a href="<?=app_url()?>">Ana Sayfa</a><span>›</span><span><?=h($cat['name'])?></span></div>
    <h1><?=h($cat['name'])?></h1>
    <p><?=count($products)?> ürün listeleniyor</p>
  </div>
</section>
<section class="section">
  <div class="container listing-layout">
    <aside class="filter-panel" data-filter-panel>
      <button class="filter-close" type="button" data-filter-close>Filtreleri Kapat ×</button>
      <?php if($children):?>
      <div class="filter-section"><h4>Alt Kategoriler</h4><?php foreach($children as $ch):?><a href="<?=app_url('category.php?slug='.urlencode($ch['slug']))?>"><?=h($ch['name'])?></a><?php endforeach;?></div>
      <?php endif;?>
      <form method="get">
        <input type="hidden" name="slug" value="<?=h($slug)?>">
        <div class="filter-section">
          <h4>Marka</h4>
          <select name="brand">
            <option value="0">Tüm Markalar</option>
            <?php foreach($brands as $b):?><option value="<?=(int)$b['id']?>" <?=$brand===(int)$b['id']?'selected':''?>><?=h($b['name'])?></option><?php endforeach;?>
          </select>
        </div>
        <div class="filter-section">
          <h4>Fiyat Aralığı</h4>
          <div class="price-filter">
            <input type="number" step="0.01" name="min" placeholder="Min" value="<?=h((string)$min)?>">
            <input type="number" step="0.01" name="max" placeholder="Maks" value="<?=h((string)$max)?>">
          </div>
        </div>
        <div class="filter-section">
          <label><input type="checkbox" name="stock" value="1" <?=$stock?'checked':''?>> Sadece stoktakiler</label>
          <label><input type="checkbox" name="free" value="1" <?=$free?'checked':''?>> Ücretsiz kargo</label>
        </div>
        <div class="filter-section"><button class="filter-apply">Filtreleri Uygula</button></div>
      </form>
    </aside>
    <div>
      <div class="toolbar">
        <div class="toolbar-left"><button class="filter-open" type="button" data-filter-open>☰ Filtrele</button><strong><?=count($products)?> ürün</strong></div>
        <form method="get">
          <input type="hidden" name="slug" value="<?=h($slug)?>">
          <select name="sort" onchange="this.form.submit()">
            <option value="popular" <?=$sort==='popular'?'selected':''?>>Önerilen sıralama</option>
            <option value="new" <?=$sort==='new'?'selected':''?>>En yeniler</option>
            <option value="price_asc" <?=$sort==='price_asc'?'selected':''?>>Fiyat artan</option>
            <option value="price_desc" <?=$sort==='price_desc'?'selected':''?>>Fiyat azalan</option>
          </select>
        </form>
      </div>
      <?php if(!$products):?><div class="empty">Filtrelere uygun ürün bulunamadı.</div><?php else:?><div class="products-grid"><?php foreach($products as $p){require __DIR__.'/includes/product-card.php';}?></div><?php endif;?>
    </div>
  </div>
</section>
<?php require __DIR__.'/includes/footer.php';?>