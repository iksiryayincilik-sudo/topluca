<?php
declare(strict_types=1);

function wy_status_tr(string $s): string{
    $m=['new'=>'Yeni','approved'=>'Onaylandı','preparing'=>'Hazırlanıyor','shipped'=>'Kargoda','delivered'=>'Teslim Edildi','cancelled'=>'İptal','pending'=>'Bekliyor','paid'=>'Ödendi','failed'=>'Başarısız','active'=>'Aktif','inactive'=>'Pasif'];
    return $m[$s]??$s;
}
function wy_status_class(string $s): string{
    if(in_array($s,['approved','delivered','paid','active'],true))return 'active';
    if(in_array($s,['preparing','shipped'],true))return 'progressing';
    if(in_array($s,['cancelled','failed','inactive'],true))return 'cancelled';
    return 'new';
}
function wy_page_title(string $page): string{
    $m=['dashboard'=>'Dashboard','orders'=>'Sipariş Yönetimi','order'=>'Sipariş Detayı','customers'=>'Müşteri Yönetimi','products'=>'Ürün Yönetimi','product_edit'=>'Ürün Düzenle','categories'=>'Kategori Ağacı','brands'=>'Marka Yönetimi','stock'=>'Stok Hareketleri','purchases'=>'Satın Alma & Tedarikçi','campaigns'=>'Kampanyalar','banners'=>'Banner & Ana Sayfa','themes'=>'Tasarım & Tema','shipping'=>'Kargo & Teslimat','analytics'=>'Analiz Merkezi','search_tools'=>'Arama Sözlüğü','content'=>'İçerik Sayfaları','settings'=>'Site & SEO Ayarları','admins'=>'Yöneticiler & Yetkiler','logs'=>'İşlem Kayıtları'];
    return $m[$page]??'WebYönet';
}
function wy_metric($value,string $label,string $sub='',string $class=''): string{
    return '<div class="metric '.h($class).'"><span class="label">'.h($label).'</span><strong>'.h((string)$value).'</strong><small>'.h($sub).'</small></div>';
}
function wy_nav(string $page): array{
    $newOrders=(int)db()->query("SELECT COUNT(*) FROM orders WHERE status='new'")->fetchColumn();
    $lowStock=(int)db()->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND stock<=critical_stock')->fetchColumn();
    $abandoned=(int)db()->query("SELECT COUNT(*) FROM carts WHERE converted_order_id IS NULL AND subtotal>0 AND updated_at<DATE_SUB(NOW(),INTERVAL 30 MINUTE)")->fetchColumn();
    $noResult=(int)db()->query("SELECT COUNT(*) FROM search_logs WHERE result_count=0 AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
    return [
        ['GENEL',[['dashboard','▦','Dashboard',null]]],
        ['SATIŞ & MÜŞTERİ',[['orders','▤','Siparişler',$newOrders],['customers','♙','Müşteriler',null]]],
        ['KATALOG',[['products','▣','Ürünler',null],['categories','⌘','Kategoriler',null],['brands','◇','Markalar',null]]],
        ['STOK & TEDARİK',[['stock','▥','Stok Hareketleri',$lowStock],['purchases','＋','Satın Alma',null]]],
        ['PAZARLAMA & İÇERİK',[['campaigns','%','Kampanyalar',null],['banners','▧','Banner & Ana Sayfa',null],['themes','✦','Tasarım & Tema',null],['content','▤','İçerik Sayfaları',null]]],
        ['LOJİSTİK',[['shipping','🚚','Kargo & Ankara',null]]],
        ['RAPORLAMA',[['analytics','⌁','Analiz Merkezi',$abandoned],['search_tools','⌕','Arama Sözlüğü',$noResult]]],
        ['SİSTEM',[['settings','⚙','Site & SEO Ayarları',null],['admins','♜','Yöneticiler & Yetkiler',null],['logs','≡','İşlem Kayıtları',null]]],
    ];
}
function wy_banner_preview(?string $path): string{
    if(!$path)return '<div class="media-empty">Görsel yok</div>';
    return '<img class="banner-thumb" src="'.h(app_url($path)).'" alt="">';
}
function wy_empty(string $title,string $text): string{
    return '<div class="wy-empty"><div class="wy-empty-icon">○</div><b>'.h($title).'</b><span>'.h($text).'</span></div>';
}
