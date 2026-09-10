<?php
require_once dirname(__DIR__).'/app/bootstrap.php';
$headerCategories=main_categories();
$headerUser=current_user();
$pageTitle=$pageTitle??'TOPLUCA';
$pageDescription=$pageDescription??'Kırtasiye, kitap, teknoloji, ofis ve okul ihtiyaçları TOPLUCA’da.';
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#ff5a00">
<title><?=h($pageTitle)?></title>
<meta name="description" content="<?=h($pageDescription)?>">
<link rel="stylesheet" href="<?=asset('css/app.css')?>?v=3.0.0">
</head>
<body>
<div class="top-strip">
  <div class="shell top-strip__inner">
    <div class="top-strip__promise">
      <span class="top-strip__icon">🚚</span>
      Ankara'da <strong><?=h((string)setting('same_day_cutoff','15:00'))?>'a kadar</strong> verilen uygun siparişlerde <b>aynı gün teslimat</b>
    </div>
    <nav class="top-strip__links" aria-label="Hızlı bağlantılar">
      <a href="<?=url('orders.php')?>">Sipariş Takibi</a>
      <span></span>
      <a href="<?=url('contact.php')?>">Yardım</a>
      <span></span>
      <a href="<?=url('contact.php')?>">Mağazalarımız</a>
    </nav>
  </div>
</div>
<header class="site-header" data-site-header>
  <div class="shell header-main">
    <button class="icon-button mobile-only" type="button" aria-label="Menüyü aç" data-menu-open>
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>
    <a class="logo-link" href="<?=url()?>" aria-label="TOPLUCA ana sayfa">
      <?php $logoClass='header-logo'; require __DIR__.'/logo.php'; ?>
    </a>
    <form class="global-search desktop-search" action="<?=url('search.php')?>" method="get" role="search">
      <input type="search" name="q" value="<?=h($_GET['q']??'')?>" placeholder="Aradığınız ürün, marka veya kategori..." autocomplete="off" aria-label="Ürün ara">
      <button type="submit" aria-label="Ara">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
      </button>
    </form>
    <div class="header-actions">
      <a class="header-action" href="<?=url('account.php')?>">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4.5 21c.8-5 3.4-7 7.5-7s6.7 2 7.5 7"/></svg>
        <span><b><?=$headerUser?h($headerUser['first_name']):'Giriş Yap'?></b><small><?=$headerUser?'Hesabım':'Hesabım'?></small></span>
      </a>
      <a class="header-action hide-small" href="<?=url('favorites.php')?>">
        <svg viewBox="0 0 24 24"><path d="M20.8 4.6c-2-2-5.2-2-7.2 0L12 6.2l-1.6-1.6c-2-2-5.2-2-7.2 0s-2 5.2 0 7.2L12 20l8.8-8.2c2-2 2-5.2 0-7.2Z"/></svg>
        <span><b>Favorilerim</b><small>Listem</small></span>
      </a>
      <a class="header-action cart-action" href="<?=url('cart.php')?>">
        <svg viewBox="0 0 24 24"><path d="M3 4h2l2.2 10.5h9.9L20 7H6"/><circle cx="9" cy="19" r="1.2"/><circle cx="17" cy="19" r="1.2"/></svg>
        <span><b>Sepetim</b><small><?=cart_count()?> ürün</small></span>
        <em><?=cart_count()?></em>
      </a>
    </div>
  </div>
  <div class="shell mobile-search-wrap mobile-only">
    <form class="global-search" action="<?=url('search.php')?>" method="get">
      <input type="search" name="q" placeholder="Ürün, marka veya kategori ara...">
      <button type="submit" aria-label="Ara"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg></button>
    </form>
  </div>
  <nav class="category-nav" aria-label="Ürün kategorileri">
    <div class="shell category-nav__inner">
      <?php foreach($headerCategories as $cat): $children=child_categories((int)$cat['id']); ?>
      <div class="category-nav__item">
        <a href="<?=url('category.php?slug='.urlencode($cat['slug']))?>">
          <span class="nav-mini-icon"><?=match($cat['icon_key']??''){ 'book'=>'▤','monitor'=>'▣','printer'=>'▧','paper'=>'▥','bag'=>'▢','palette'=>'◉',default=>'✎' }?></span>
          <?=h($cat['name'])?>
        </a>
        <?php if($children):?>
        <div class="mega-menu">
          <div class="mega-menu__title"><span><?=h($cat['name'])?></span><a href="<?=url('category.php?slug='.urlencode($cat['slug']))?>">Tümünü Gör →</a></div>
          <div class="mega-menu__grid">
            <?php foreach($children as $child):?><a href="<?=url('category.php?slug='.urlencode($child['slug']))?>"><b><?=h($child['name'])?></b><small>Ürünleri incele</small></a><?php endforeach;?>
          </div>
        </div>
        <?php endif;?>
      </div>
      <?php endforeach;?>
    </div>
  </nav>
</header>
<div class="mobile-drawer" data-mobile-drawer aria-hidden="true">
  <div class="mobile-drawer__backdrop" data-menu-close></div>
  <aside class="mobile-drawer__panel">
    <div class="mobile-drawer__head">
      <a href="<?=url()?>"><?php $logoClass='drawer-logo';require __DIR__.'/logo.php';?></a>
      <button class="icon-button" type="button" data-menu-close aria-label="Menüyü kapat">×</button>
    </div>
    <a class="mobile-account" href="<?=url('account.php')?>">👤 <?=$headerUser?'Merhaba, '.h($headerUser['first_name']):'Giriş Yap / Üye Ol'?></a>
    <div class="mobile-categories">
      <?php foreach($headerCategories as $cat):?><a href="<?=url('category.php?slug='.urlencode($cat['slug']))?>"><span><?=h($cat['name'])?></span><b>›</b></a><?php endforeach;?>
    </div>
    <div class="mobile-links"><a href="<?=url('favorites.php')?>">♡ Favorilerim</a><a href="<?=url('orders.php')?>">Sipariş Takibi</a><a href="<?=url('contact.php')?>">Yardım & İletişim</a></div>
  </aside>
</div>
<main>
<?php $flashItems=flashes();if($flashItems):?><div class="shell flash-stack"><?php foreach($flashItems as $f):?><div class="flash flash--<?=h($f['type'])?>"><?=h($f['message'])?></div><?php endforeach;?></div><?php endif;?>
