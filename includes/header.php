<?php
require_once dirname(__DIR__).'/app/bootstrap.php';
$headerCategories=categories();
$userHeader=null;
if(!empty($_SESSION['user_id'])){
    try{$s=db()->prepare('SELECT first_name,email FROM users WHERE id=? AND is_active=1 LIMIT 1');$s->execute([(int)$_SESSION['user_id']]);$userHeader=$s->fetch()?:null;}catch(Throwable){}
}
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#ff5a00">
<title><?=h($pageTitle??'TOPLUCA')?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="<?=url('assets/css/style.css')?>?v=2">
<link rel="stylesheet" href="<?=url('assets/css/v2.css')?>?v=2">
</head>
<body>
<div class="topbar"><div class="container topbar-inner"><div>🚚 Ankara'da <b><?=h((string)setting('same_day_cutoff','15:00'))?></b>'a kadar verilen uygun siparişlerde <strong>aynı gün teslimat</strong></div><div class="toplinks"><a href="<?=url('orders.php')?>">Sipariş Takibi</a><a href="<?=url('contact.php')?>">Yardım</a><a href="#">Mağazalarımız</a></div></div></div>
<header class="site-header">
<div class="container head-main">
<button class="menu-btn" type="button" data-menu aria-label="Menü">☰</button>
<a class="logo" href="<?=url()?>"><img src="<?=url('assets/img/logo.svg')?>?v=2" alt="TOPLUCA"></a>
<form class="search" action="<?=url('search.php')?>" method="get"><input type="search" name="q" value="<?=h($_GET['q']??'')?>" placeholder="Aradığınız ürün, marka veya kategori..."><button aria-label="Ara">Ara</button></form>
<div class="actions"><a href="<?=url('account.php')?>"><span><?=$userHeader?'Merhaba, '.h($userHeader['first_name']?:'Hesabım'):'Giriş Yap'?></span></a><a href="<?=url('favorites.php')?>"><span>Favorilerim</span></a><a href="<?=url('cart.php')?>"><span>Sepetim</span><b><?=cart_count()?></b></a></div>
</div>
<div class="container mobile-search"><form action="<?=url('search.php')?>" method="get"><input type="search" name="q" placeholder="Ürün, marka veya kategori ara..."><button aria-label="Ara">🔍</button></form></div>
<nav class="main-nav" data-nav><div class="container nav-inner"><?php foreach($headerCategories as $cat):?><a href="<?=url('category.php?slug='.urlencode($cat['slug']))?>"><?=h($cat['name'])?></a><?php endforeach;?></div></nav>
</header>
<main><div class="container flash-wrap"><?php foreach(flashes() as $f):?><div class="flash <?=h($f['type'])?>"><?=h($f['message'])?></div><?php endforeach;?></div>
