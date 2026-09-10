<?php
$todayRevenue=(float)db()->query("SELECT COALESCE(SUM(grand_total),0) FROM orders WHERE DATE(created_at)=CURDATE() AND status<>'cancelled'")->fetchColumn();
$todayOrders=(int)db()->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$monthRevenue=(float)db()->query("SELECT COALESCE(SUM(grand_total),0) FROM orders WHERE YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE()) AND status<>'cancelled'")->fetchColumn();
$gross=(float)db()->query("SELECT COALESCE(SUM((oi.unit_price-oi.unit_cost)*oi.quantity),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE DATE(o.created_at)=CURDATE() AND o.status<>'cancelled'")->fetchColumn();
$newOrders=(int)db()->query("SELECT COUNT(*) FROM orders WHERE status='new'")->fetchColumn();
$customers=(int)db()->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
$lowStock=(int)db()->query("SELECT COUNT(*) FROM products WHERE is_active=1 AND stock<=critical_stock")->fetchColumn();
$abandoned=(int)db()->query("SELECT COUNT(*) FROM carts WHERE converted_order_id IS NULL AND subtotal>0 AND updated_at<DATE_SUB(NOW(),INTERVAL 30 MINUTE)")->fetchColumn();
$recent=db()->query("SELECT * FROM orders ORDER BY id DESC LIMIT 10")->fetchAll();
$searches=db()->query("SELECT search_term,COUNT(*) total FROM search_logs WHERE created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY search_term ORDER BY total DESC LIMIT 8")->fetchAll();
$noresult=db()->query("SELECT search_term,COUNT(*) total FROM search_logs WHERE result_count=0 AND created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY search_term ORDER BY total DESC LIMIT 8")->fetchAll();
$days=db()->query("SELECT DATE(created_at) d,SUM(grand_total) t FROM orders WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) AND status<>'cancelled' GROUP BY DATE(created_at) ORDER BY d")->fetchAll();
$max=1;foreach($days as $d)$max=max($max,(float)$d['t']);
?>
<div class="admin-page-head"><div><span class="eyebrow">OPERASYON MERKEZİ</span><h2>Günün Özeti</h2><p>Satış, sipariş, müşteri, stok ve arama performansını tek ekrandan yönetin.</p></div><div class="admin-actions"><a class="admin-btn primary" href="?page=product_edit">+ Yeni Ürün</a><a class="admin-btn light" href="?page=orders">Siparişleri Aç</a></div></div>
<div class="metric-grid metric-grid-8">
<?=wy_metric(money($todayRevenue),'Bugünkü Ciro',$todayOrders.' sipariş','highlight')?>
<?=wy_metric(money($gross),'Bugünkü Brüt Kâr','Sipariş maliyet snapshotı')?>
<?=wy_metric(money($monthRevenue),'Aylık Ciro',date('m/Y'))?>
<?=wy_metric((string)$newOrders,'Yeni Sipariş','İşlem bekliyor')?>
<?=wy_metric((string)$customers,'Aktif Üye','Müşteri hesabı')?>
<?=wy_metric((string)$lowStock,'Kritik Stok','Kontrol edilmeli',$lowStock?'danger':'')?>
<?=wy_metric((string)$abandoned,'Terk Sepet','30 dk+ hareketsiz')?>
<?=wy_metric(active_theme_key(),'Aktif Tema','WebYönet → Tasarım')?>
</div>
<div class="admin-grid-2">
<section class="admin-card"><div class="admin-card-head"><div><h3>Son Siparişler</h3><p>Operasyon ekibinin takip etmesi gereken son hareketler</p></div><a class="admin-btn light" href="?page=orders">Tüm siparişler</a></div><div class="table-wrap"><table class="admin-table"><thead><tr><th>Sipariş</th><th>Müşteri</th><th>Tutar</th><th>Teslimat</th><th>Ödeme</th><th>Durum</th></tr></thead><tbody><?php if(!$recent):?><tr><td colspan="6"><?=wy_empty('Henüz sipariş yok','İlk sipariş geldiğinde burada görünecek.')?></td></tr><?php endif;?><?php foreach($recent as $o):?><tr><td><a class="name" href="?page=order&id=<?=(int)$o['id']?>"><?=h($o['order_no'])?></a><small><?=h(date('d.m.Y H:i',strtotime($o['created_at'])))?></small></td><td><b><?=h($o['customer_name'])?></b><small><?=h($o['city'].' / '.$o['district'])?></small></td><td><b><?=money($o['grand_total'])?></b></td><td><?=h($o['shipping_company_name']?:$o['delivery_method'])?></td><td><span class="status <?=wy_status_class($o['payment_status'])?>"><?=h(wy_status_tr($o['payment_status']))?></span></td><td><span class="status <?=wy_status_class($o['status'])?>"><?=h(wy_status_tr($o['status']))?></span></td></tr><?php endforeach;?></tbody></table></div></section>
<section class="admin-card"><div class="admin-card-head"><div><h3>Son 7 Gün Satış</h3><p>Günlük ciro trendi</p></div></div><div class="admin-card-body"><div class="chart-bars"><?php if(!$days):?><div class="chart-empty">Henüz satış verisi yok</div><?php endif;?><?php foreach($days as $d):?><div class="chart-col"><div class="chart-value"><?=money($d['t'])?></div><div class="chart-bar" style="height:<?=max(6,(float)$d['t']/$max*100)?>%"></div><b><?=date('d.m',strtotime($d['d']))?></b></div><?php endforeach;?></div></div></section>
</div>
<div class="admin-grid-equal">
<section class="admin-card"><div class="admin-card-head"><div><h3>En Çok Arananlar</h3><p>Son 30 gün</p></div><a href="?page=analytics" class="admin-btn light">Analiz</a></div><div class="admin-card-body quick-list"><?php if(!$searches)echo wy_empty('Arama verisi yok','Müşteriler arama yaptıkça burada listelenecek.');foreach($searches as $s):?><div class="quick-row"><span><?=h($s['search_term'])?></span><b><?=(int)$s['total']?></b></div><?php endforeach;?></div></section>
<section class="admin-card"><div class="admin-card-head"><div><h3>Sonuç Bulunamayan Aramalar</h3><p>Ürün eklemek için fırsat listesi</p></div></div><div class="admin-card-body quick-list"><?php if(!$noresult)echo wy_empty('Sonuçsuz arama yok','Harika; henüz sonuçsuz arama kaydı oluşmadı.');foreach($noresult as $s):?><div class="quick-row"><span><?=h($s['search_term'])?></span><b><?=(int)$s['total']?></b></div><?php endforeach;?></div></section>
</div>