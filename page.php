<?php
require __DIR__.'/app/bootstrap.php';
$slug=trim($_GET['slug']??'');
$page=content_page_by_slug($slug);
if(!$page){http_response_code(404);$pageTitle='Sayfa bulunamadı | TOPLUCA';require __DIR__.'/includes/header.php';echo '<section class="section"><div class="container"><div class="empty"><h2>Sayfa bulunamadı</h2><p>Aradığınız içerik yayında değil.</p><a class="primary-btn" href="'.h(app_url()).'">Ana Sayfaya Dön</a></div></div></section>';require __DIR__.'/includes/footer.php';exit;}
page_view('content_page',(int)$page['id']);
$pageTitle=trim((string)$page['meta_title'])!==''?$page['meta_title']:$page['title'].' | TOPLUCA';
$pageDescription=trim((string)$page['meta_description'])!==''?$page['meta_description']:mb_substr(strip_tags((string)$page['body']),0,155);
require __DIR__.'/includes/header.php';
?>
<section class="page-hero"><div class="container"><div class="breadcrumbs"><a href="<?=app_url()?>">Ana Sayfa</a><span>›</span><span><?=h($page['title'])?></span></div><h1><?=h($page['title'])?></h1></div></section>
<section class="section"><div class="container"><article class="content-page-card"><?=nl2br(h((string)$page['body']))?></article></div></section>
<?php require __DIR__.'/includes/footer.php';?>