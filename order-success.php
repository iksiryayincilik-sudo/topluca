<?php
require __DIR__.'/app/bootstrap.php';
$pageTitle='Siparişiniz Alındı | TOPLUCA';$orderNo=$_SESSION['last_order_no']??'';unset($_SESSION['last_order_no']);require __DIR__.'/includes/header.php';
?><section class="section"><div class="container"><div class="summary" style="max-width:700px;margin:auto;text-align:center;padding:35px"><h1>✓ Siparişiniz Alındı!</h1><?php if($orderNo):?><p>Sipariş numaranız:</p><h2><?=h($orderNo)?></h2><?php endif;?><p>Siparişiniz TOPLUCA Yönetim Merkezi'ne iletildi.</p><a class="checkout" href="<?=url()?>">Ana Sayfaya Dön</a></div></div></section><?php require __DIR__.'/includes/footer.php';
