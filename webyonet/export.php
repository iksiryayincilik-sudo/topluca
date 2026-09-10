<?php
require dirname(__DIR__).'/app/bootstrap.php';require_admin();
$type=(string)($_GET['type']??'products');
header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="topluca-'.$type.'-'.date('Ymd-His').'.csv"');echo "\xEF\xBB\xBF";$out=fopen('php://output','w');
if($type==='orders'){
  fputcsv($out,['siparis_no','tarih','ad','soyad','email','telefon','il','ilce','teslimat','kargo','ara_toplam','kargo_tutari','genel_toplam','odeme','durum'],';');
  $rows=db()->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll();foreach($rows as $r)fputcsv($out,[$r['order_no'],$r['created_at'],$r['shipping_first_name'],$r['shipping_last_name'],$r['customer_email'],$r['customer_phone'],$r['shipping_city'],$r['shipping_district'],$r['delivery_method'],$r['shipping_company_name'],$r['subtotal'],$r['shipping_total'],$r['grand_total'],$r['payment_method'],$r['status']],';');
}else{
  fputcsv($out,['sku','name','category_slug','brand','barcode','isbn','purchase_price','sale_price','compare_price','vat_rate','stock','critical_stock','shipping_policy','same_day_delivery','is_active'],';');
  $rows=db()->query('SELECT p.*,c.slug category_slug,b.name brand_name FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN brands b ON b.id=p.brand_id ORDER BY p.id')->fetchAll();foreach($rows as $r)fputcsv($out,[$r['sku'],$r['name'],$r['category_slug'],$r['brand_name'],$r['barcode'],$r['isbn'],$r['purchase_price'],$r['sale_price'],$r['compare_price'],$r['vat_rate'],$r['stock'],$r['critical_stock'],$r['shipping_policy'],$r['same_day_delivery'],$r['is_active']],';');
}
fclose($out);exit;
