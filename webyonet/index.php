<?php
require dirname(__DIR__).'/app/bootstrap.php';
require_admin();
require_once __DIR__.'/_helpers.php';
require_once __DIR__.'/actions_v5.php';
require_once __DIR__.'/actions.php';

$admin=current_admin();
$page=$_GET['page']??'dashboard';
$allowed=['dashboard','orders','order','customers','products','product_edit','categories','brands','stock','purchases','campaigns','banners','themes','shipping','analytics','settings','logs'];
if(!in_array($page,$allowed,true))$page='dashboard';
$title=wy_page_title($page);
$nav=wy_nav($page);
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($title)?> | TOPLUCA WebYönet</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?=app_url('webyonet/admin.css')?>?v=5.0.0">
</head>
<body>
<div class="admin-layout">
<aside class="admin-sidebar" id="adminSidebar">
  <a class="admin-brand" href="?page=dashboard"><span>TOPLU<b>CA</b></span><small>WebYönet • Yönetim Merkezi</small></a>
  <nav class="admin-nav">
  <?php foreach($nav as $group):?>
    <div class="admin-nav-group"><?=h($group[0])?></div>
    <?php foreach($group[1] as $n):$active=$page===$n[0]||($page==='order'&&$n[0]==='orders')||($page==='product_edit'&&$n[0]==='products');?>
      <a class="<?=$active?'active':''?>" href="?page=<?=h($n[0])?>"><span class="nav-icon"><?=$n[1]?></span><span><?=h($n[2])?></span><?php if($n[3]):?><span class="count"><?=(int)$n[3]?></span><?php endif;?></a>
    <?php endforeach;?>
  <?php endforeach;?>
  </nav>
  <div class="admin-sidebar-foot"><span>TOPLUCA V5</span><small><?=h(setting('company_name','Yıldız Ofis Kırtasiye'))?></small></div>
</aside>
<div class="sidebar-overlay" id="adminOverlay"></div>
<div class="admin-main">
<header class="admin-topbar">
  <div class="admin-top-left"><button class="admin-menu-toggle" id="adminMenuBtn" type="button">☰</button><div><span class="admin-breadcrumb">WebYönet / <?=h($title)?></span><h1><?=h($title)?></h1></div></div>
  <div class="admin-topbar-right"><a class="top-link" target="_blank" href="<?=app_url()?>">Siteyi Gör ↗</a><div class="admin-user"><span class="admin-avatar"><?=h(mb_substr($admin['full_name']?:$admin['username'],0,1))?></span><div><b><?=h($admin['full_name']?:$admin['username'])?></b><small><?=h($admin['role'])?></small></div></div><a class="top-link" href="<?=app_url('webyonet/logout.php')?>">Çıkış</a></div>
</header>
<main class="admin-content">
<?=flash_html()?>
<?php if((int)$admin['must_change_password']===1):?><div class="admin-alert warning"><b>Başlangıç şifresi kullanılıyor.</b> Güvenlik için Site & SEO Ayarları bölümünden yönetici şifresini değiştirin.</div><?php endif;?>
<?php $pageFile=__DIR__.'/pages/'.$page.'.php';if(is_file($pageFile))require $pageFile;else echo wy_empty('Sayfa bulunamadı','Bu WebYönet modülü henüz mevcut değil.'); ?>
</main>
</div>
</div>
<script src="<?=app_url('webyonet/admin.js')?>?v=5.0.0"></script>
</body>
</html>