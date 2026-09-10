<?php
require_once dirname(__DIR__).'/app/bootstrap.php';
$headerCats=main_categories();$u=current_user();
$defaultTitle=seo_value('site_title','TOPLUCA | Kırtasiye, Kitap, Teknoloji ve Ofis');
$defaultDescription=seo_value('site_description','Kırtasiye, kitap, teknoloji ve ofis ihtiyaçları TOPLUCA’da.');
$defaultKeywords=seo_value('site_keywords','kırtasiye, kitap, ofis ürünleri, toner, kartuş, teknoloji');
$finalTitle=$pageTitle??$defaultTitle;$finalDescription=$pageDescription??$defaultDescription;$finalKeywords=$pageKeywords??$defaultKeywords;
$logoSrc=site_logo_url();$favicon=site_favicon_url();
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#ff6000">
<title><?=h($finalTitle)?></title>
<meta name="description" content="<?=h($finalDescription)?>">
<meta name="keywords" content="<?=h($finalKeywords)?>">
<meta name="robots" content="index,follow,max-image-preview:large">
<meta property="og:type" content="website">
<meta property="og:site_name" content="TOPLUCA">
<meta property="og:title" content="<?=h($finalTitle)?>">
<meta property="og:description" content="<?=h($finalDescription)?>">
<meta property="og:url" content="<?=h(app_url(ltrim(parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)??'','/')))?>">
<?php if($favicon):?><link rel="icon" href="<?=h($favicon)?>"><?php endif;?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?=app_url('assets/css/app.css')?>?v=5.0.0">
</head>
<body class="<?=h(theme_body_class())?>">
<div class="announcement">
  <div class="container announcement-inner">
    <div class="announcement-main"><span class="announcement-bolt">⚡</span><b>Ankara’da aynı gün teslimat</b><span><?=h(setting('same_day_cutoff','15:00'))?>'a kadar uygun siparişlerde</span></div>
    <div class="utility-links"><a href="<?=app_url('order-track.php')?>">Sipariş Takibi</a><a href="<?=app_url('contact.php')?>">Yardım</a></div>
  </div>
</div>
<header class="site-header">
  <div class="container header-row">
    <button class="mobile-menu" type="button" aria-label="Menüyü aç" data-menu-button>☰</button>
    <a class="brand-logo" href="<?=app_url()?>" aria-label="TOPLUCA ana sayfa"><img src="<?=h($logoSrc)?>" alt="TOPLUCA" decoding="async"></a>
    <form class="global-search" action="<?=app_url('search.php')?>" method="get" role="search">
      <span class="search-icon">⌕</span><input type="search" name="q" value="<?=h($_GET['q']??'')?>" placeholder="Ürün, marka, kategori, barkod veya ISBN ara" autocomplete="off"><button>Ara</button>
    </form>
    <div class="header-actions">
      <a href="<?=app_url('account.php')?>"><span class="action-icon">♙</span><span class="action-text"><?=$u?'Hesabım':'Giriş Yap'?></span></a>
      <a href="<?=app_url('favorites.php')?>"><span class="action-icon">♡</span><span class="action-text">Favoriler</span></a>
      <a class="cart-action" href="<?=app_url('cart.php')?>"><span class="action-icon">🛒</span><span class="action-text">Sepetim</span><b><?=cart_count()?></b></a>
    </div>
  </div>
  <div class="container mobile-search-wrap"><form class="global-search mobile-search-form" action="<?=app_url('search.php')?>" method="get"><span class="search-icon">⌕</span><input type="search" name="q" placeholder="Ürün, marka veya barkod ara"><button>Ara</button></form></div>
  <nav class="category-nav" data-main-menu>
    <div class="container category-nav-inner">
      <?php foreach($headerCats as $cat):$kids=category_children((int)$cat['id']);?>
      <div class="nav-item">
        <a href="<?=app_url('category.php?slug='.urlencode($cat['slug']))?>"><?=h($cat['name'])?></a>
        <?php if($kids):?><div class="mega"><div class="mega-title"><div><small>KATEGORİ</small><strong><?=h($cat['name'])?></strong></div><a href="<?=app_url('category.php?slug='.urlencode($cat['slug']))?>">Tümünü gör →</a></div><div class="mega-grid"><?php foreach($kids as $kid):?><div class="mega-col"><a class="mega-head" href="<?=app_url('category.php?slug='.urlencode($kid['slug']))?>"><?=h($kid['name'])?></a><?php foreach(array_slice(category_children((int)$kid['id']),0,8) as $g):?><a href="<?=app_url('category.php?slug='.urlencode($g['slug']))?>"><?=h($g['name'])?></a><?php endforeach;?></div><?php endforeach;?></div></div><?php endif;?>
      </div>
      <?php endforeach;?>
    </div>
  </nav>
</header>
<div class="container flash-zone"><?=flash_html()?></div>
<main>