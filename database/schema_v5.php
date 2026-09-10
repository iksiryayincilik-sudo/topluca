<?php
declare(strict_types=1);

function v5_column_exists(PDO $pdo,string $table,string $column): bool{
    $s=$pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?');
    $s->execute([$table,$column]);
    return (int)$s->fetchColumn()>0;
}
function v5_add_column(PDO $pdo,string $table,string $column,string $definition): void{
    if(!v5_column_exists($pdo,$table,$column))$pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
}
function drop_v5_tables(PDO $pdo): void{
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach(['content_pages','homepage_feature_boxes','theme_schedules','admin_roles'] as $table)$pdo->exec('DROP TABLE IF EXISTS `'.$table.'`');
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}
function apply_v5_schema(PDO $pdo): void{
    $pdo->exec("CREATE TABLE IF NOT EXISTS theme_schedules (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(190) NOT NULL,
        theme_key VARCHAR(80) NOT NULL,
        start_at DATETIME NOT NULL,
        end_at DATETIME NOT NULL,
        priority INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX(is_active,start_at,end_at,priority)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_roles (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        role_key VARCHAR(80) NOT NULL UNIQUE,
        name VARCHAR(120) NOT NULL,
        permissions_json LONGTEXT NULL,
        is_system TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS homepage_feature_boxes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        icon VARCHAR(40) NULL,
        title VARCHAR(190) NOT NULL,
        subtitle VARCHAR(255) NULL,
        link_url VARCHAR(500) NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS content_pages (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(190) NOT NULL,
        slug VARCHAR(190) NOT NULL UNIQUE,
        body MEDIUMTEXT NULL,
        meta_title VARCHAR(255) NULL,
        meta_description VARCHAR(500) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    v5_add_column($pdo,'banners','eyebrow','VARCHAR(120) NULL AFTER subtitle');
    v5_add_column($pdo,'banners','desktop_width','INT UNSIGNED NULL AFTER mobile_image');
    v5_add_column($pdo,'banners','desktop_height','INT UNSIGNED NULL AFTER desktop_width');
    v5_add_column($pdo,'banners','mobile_width','INT UNSIGNED NULL AFTER desktop_height');
    v5_add_column($pdo,'banners','mobile_height','INT UNSIGNED NULL AFTER mobile_width');
    v5_add_column($pdo,'banners','text_color','VARCHAR(30) NULL AFTER button_text');
    v5_add_column($pdo,'banners','overlay_color','VARCHAR(30) NULL AFTER text_color');
    v5_add_column($pdo,'banners','overlay_opacity','DECIMAL(4,2) NOT NULL DEFAULT 0.25 AFTER overlay_color');
    v5_add_column($pdo,'banners','content_align','VARCHAR(20) NOT NULL DEFAULT \'left\' AFTER overlay_opacity');
    v5_add_column($pdo,'banners','open_new_tab','TINYINT(1) NOT NULL DEFAULT 0 AFTER content_align');

    v5_add_column($pdo,'admins','role_id','INT UNSIGNED NULL AFTER role');
    v5_add_column($pdo,'categories','meta_keywords','VARCHAR(700) NULL AFTER meta_description');
    v5_add_column($pdo,'products','meta_title','VARCHAR(255) NULL AFTER is_bestseller');
    v5_add_column($pdo,'products','meta_description','VARCHAR(500) NULL AFTER meta_title');
    v5_add_column($pdo,'products','meta_keywords','VARCHAR(700) NULL AFTER meta_description');

    $settings=[
        'site_title'=>'TOPLUCA | Kırtasiye, Kitap, Teknoloji ve Ofis Ürünleri',
        'site_description'=>'Kırtasiye, kitap, bilgisayar sarf, toner, kağıt, okul ve hobi ürünlerini TOPLUCA’da avantajlı fiyatlarla keşfedin.',
        'site_keywords'=>'kırtasiye, kitap, ofis ürünleri, toner, kartuş, bilgisayar, okul ürünleri, toplu alım',
        'site_logo'=>'','site_favicon'=>'','site_og_image'=>'',
        'active_theme'=>'standard','theme_auto_schedule'=>'1',
        'banner_autoplay'=>'1','banner_interval'=>'5500',
        'company_name'=>'Yıldız Ofis Kırtasiye','company_email'=>'info@topluca.net','company_phone'=>'','company_address'=>'Ankara',
        'footer_description'=>'Kırtasiye, kitap, teknoloji ve ofis ihtiyaçlarını tek sepette buluşturan alışveriş platformu.',
        'bank_name'=>'Demo Bankası','bank_account_name'=>'Yıldız Ofis Kırtasiye','bank_iban'=>'TR00 0000 0000 0000 0000 0000 00','bank_branch'=>'Ankara Merkez',
        'bank_payment_note'=>'Havale/EFT açıklamasına sipariş numaranızı yazınız.',
        'free_shipping_limit'=>'750','same_day_enabled'=>'1','same_day_cutoff'=>'15:00','same_day_free_limit'=>'2000','same_day_under_limit_fee'=>'149.90','same_day_allow_paid_under_limit'=>'1','same_day_daily_capacity'=>'50','same_day_weekdays'=>'1,2,3,4,5,6'
    ];
    $st=$pdo->prepare('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=setting_value');
    foreach($settings as $k=>$v)$st->execute([$k,$v]);

    $roles=[
        ['superadmin','Süper Yönetici',['*']],
        ['operations','Operasyon',['orders','customers','shipping','stock']],
        ['catalog','Katalog',['products','categories','brands','banners']],
        ['marketing','Pazarlama',['campaigns','banners','themes','analytics']]
    ];
    $r=$pdo->prepare('INSERT INTO admin_roles(role_key,name,permissions_json,is_system,created_at,updated_at) VALUES(?,?,?,1,NOW(),NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),permissions_json=VALUES(permissions_json),updated_at=NOW()');
    foreach($roles as $row)$r->execute([$row[0],$row[1],json_encode($row[2],JSON_UNESCAPED_UNICODE)]);

    $superRole=(int)$pdo->query("SELECT id FROM admin_roles WHERE role_key='superadmin' LIMIT 1")->fetchColumn();
    if($superRole)$pdo->exec('UPDATE admins SET role_id='.$superRole." WHERE role='superadmin' AND role_id IS NULL");

    $features=[
        ['⚡','Ankara’da Aynı Gün','Adres ve sepete göre otomatik uygunluk kontrolü',null,1],
        ['▤','Toplu Alım Avantajı','Adet arttıkça otomatik kademeli fiyat',null,2],
        ['⌕','Akıllı Arama & Filtre','Barkod, ISBN, marka ve kategoriye göre gelişmiş arama',null,3]
    ];
    if((int)$pdo->query('SELECT COUNT(*) FROM homepage_feature_boxes')->fetchColumn()===0){
        $f=$pdo->prepare('INSERT INTO homepage_feature_boxes(icon,title,subtitle,link_url,sort_order,is_active,created_at,updated_at) VALUES(?,?,?,?,?,1,NOW(),NOW())');
        foreach($features as $row)$f->execute($row);
    }

    if((int)$pdo->query('SELECT COUNT(*) FROM theme_schedules')->fetchColumn()===0){
        $s=$pdo->prepare('INSERT INTO theme_schedules(name,theme_key,start_at,end_at,priority,is_active,created_at,updated_at) VALUES(?,?,?,?,?,1,NOW(),NOW())');
        $year=(int)date('Y');
        $s->execute(['29 Ekim Cumhuriyet Bayramı','republic',$year.'-10-27 00:00:00',$year.'-10-30 23:59:59',100]);
        $s->execute(['Kış Teması','winter',$year.'-12-01 00:00:00',($year+1).'-02-28 23:59:59',10]);
        $s->execute(['Yaz Teması','summer',($year+1).'-06-01 00:00:00',($year+1).'-08-31 23:59:59',10]);
    }
}