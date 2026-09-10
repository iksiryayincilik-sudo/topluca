<?php
declare(strict_types=1);

function topluca_table_exists(PDO $pdo, string $table): bool {
    $s=$pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
    $s->execute([$table]);
    return (int)$s->fetchColumn()>0;
}
function topluca_column_exists(PDO $pdo, string $table, string $column): bool {
    $s=$pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?");
    $s->execute([$table,$column]);
    return (int)$s->fetchColumn()>0;
}
function topluca_add_column(PDO $pdo,string $table,string $column,string $definition): void {
    if(!topluca_column_exists($pdo,$table,$column)) $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
}
function topluca_apply_v2_schema(PDO $pdo): void {
    $sql=[
"CREATE TABLE IF NOT EXISTS app_migrations(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,migration_key VARCHAR(120) NOT NULL UNIQUE,applied_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS users(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,email VARCHAR(190) NOT NULL UNIQUE,password_hash VARCHAR(255) NULL,first_name VARCHAR(120) NULL,last_name VARCHAR(120) NULL,phone VARCHAR(60) NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,email_verified_at DATETIME NULL,last_login_at DATETIME NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,INDEX(phone),INDEX(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS user_addresses(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,title VARCHAR(80) NOT NULL DEFAULT 'Adresim',first_name VARCHAR(120) NOT NULL,last_name VARCHAR(120) NOT NULL,phone VARCHAR(60) NOT NULL,company VARCHAR(190) NULL,tax_office VARCHAR(120) NULL,tax_no VARCHAR(50) NULL,city VARCHAR(120) NOT NULL,district VARCHAR(120) NOT NULL,neighborhood VARCHAR(160) NULL,address TEXT NOT NULL,postal_code VARCHAR(20) NULL,is_default_shipping TINYINT(1) NOT NULL DEFAULT 0,is_default_billing TINYINT(1) NOT NULL DEFAULT 0,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,INDEX(user_id),INDEX(city,district)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS favorites(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,product_id INT UNSIGNED NOT NULL,created_at DATETIME NOT NULL,UNIQUE KEY uq_favorite(user_id,product_id),INDEX(product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS product_price_tiers(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,product_id INT UNSIGNED NOT NULL,min_qty INT UNSIGNED NOT NULL,max_qty INT UNSIGNED NULL,unit_price DECIMAL(12,2) NOT NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,INDEX(product_id,min_qty)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS product_attributes(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,product_id INT UNSIGNED NOT NULL,attribute_name VARCHAR(160) NOT NULL,attribute_value TEXT NOT NULL,sort_order INT NOT NULL DEFAULT 0,INDEX(product_id,sort_order)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS banners(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,title VARCHAR(190) NOT NULL,desktop_image VARCHAR(255) NULL,mobile_image VARCHAR(255) NULL,link_url VARCHAR(500) NULL,button_text VARCHAR(120) NULL,position_code VARCHAR(80) NOT NULL DEFAULT 'home_hero',sort_order INT NOT NULL DEFAULT 0,start_at DATETIME NULL,end_at DATETIME NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,INDEX(position_code,is_active,sort_order)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS suppliers(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(190) NOT NULL,tax_no VARCHAR(50) NULL,phone VARCHAR(60) NULL,email VARCHAR(190) NULL,address TEXT NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS purchases(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,supplier_id INT UNSIGNED NULL,document_no VARCHAR(120) NULL,purchase_date DATE NOT NULL,total DECIMAL(14,2) NOT NULL DEFAULT 0,notes TEXT NULL,created_at DATETIME NOT NULL,INDEX(supplier_id,purchase_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS purchase_items(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,purchase_id BIGINT UNSIGNED NOT NULL,product_id INT UNSIGNED NOT NULL,quantity INT UNSIGNED NOT NULL,unit_cost DECIMAL(12,2) NOT NULL,line_total DECIMAL(14,2) NOT NULL,INDEX(purchase_id),INDEX(product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS stock_movements(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,product_id INT UNSIGNED NOT NULL,movement_type VARCHAR(50) NOT NULL,quantity INT NOT NULL,unit_cost DECIMAL(12,2) NULL,reference_type VARCHAR(60) NULL,reference_id BIGINT UNSIGNED NULL,notes VARCHAR(500) NULL,created_at DATETIME NOT NULL,INDEX(product_id,created_at),INDEX(reference_type,reference_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS order_status_history(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,order_id BIGINT UNSIGNED NOT NULL,status VARCHAR(60) NOT NULL,note VARCHAR(500) NULL,admin_id INT UNSIGNED NULL,created_at DATETIME NOT NULL,INDEX(order_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS payments(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,order_id BIGINT UNSIGNED NOT NULL,provider VARCHAR(80) NOT NULL DEFAULT 'manual',provider_transaction_id VARCHAR(190) NULL,amount DECIMAL(14,2) NOT NULL,status VARCHAR(60) NOT NULL DEFAULT 'pending',raw_reference VARCHAR(500) NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,INDEX(order_id),INDEX(provider_transaction_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS search_synonyms(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,term VARCHAR(190) NOT NULL,synonyms TEXT NOT NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_term(term)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE IF NOT EXISTS same_day_districts(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,district_name VARCHAR(120) NOT NULL UNIQUE,is_active TINYINT(1) NOT NULL DEFAULT 1,extra_fee DECIMAL(12,2) NOT NULL DEFAULT 0,daily_capacity INT UNSIGNED NULL,sort_order INT NOT NULL DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    foreach($sql as $q) $pdo->exec($q);

    if(topluca_table_exists($pdo,'carts')){
        topluca_add_column($pdo,'carts','user_id','BIGINT UNSIGNED NULL AFTER session_key');
        topluca_add_column($pdo,'carts','customer_email','VARCHAR(190) NULL AFTER user_id');
    }
    if(topluca_table_exists($pdo,'orders')){
        topluca_add_column($pdo,'orders','user_id','BIGINT UNSIGNED NULL AFTER order_no');
        topluca_add_column($pdo,'orders','billing_same_as_shipping','TINYINT(1) NOT NULL DEFAULT 1 AFTER address');
        topluca_add_column($pdo,'orders','billing_company','VARCHAR(190) NULL AFTER billing_same_as_shipping');
        topluca_add_column($pdo,'orders','billing_tax_office','VARCHAR(120) NULL AFTER billing_company');
        topluca_add_column($pdo,'orders','billing_tax_no','VARCHAR(50) NULL AFTER billing_tax_office');
        topluca_add_column($pdo,'orders','shipping_method_code','VARCHAR(80) NULL AFTER delivery_method');
        topluca_add_column($pdo,'orders','tracking_no','VARCHAR(120) NULL AFTER shipping_company_name');
        topluca_add_column($pdo,'orders','payment_method','VARCHAR(80) NOT NULL DEFAULT 'bank_transfer' AFTER tracking_no');
        topluca_add_column($pdo,'orders','customer_note','TEXT NULL AFTER payment_method');
    }
    if(topluca_table_exists($pdo,'order_items')){
        topluca_add_column($pdo,'order_items','vat_rate','DECIMAL(6,2) NOT NULL DEFAULT 20 AFTER unit_cost');
        topluca_add_column($pdo,'order_items','discount_total','DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER vat_rate');
    }
    if(topluca_table_exists($pdo,'products')){
        topluca_add_column($pdo,'products','short_description','VARCHAR(700) NULL AFTER description');
        topluca_add_column($pdo,'products','model_number','VARCHAR(120) NULL AFTER barcode');
        topluca_add_column($pdo,'products','desi','DECIMAL(8,2) NULL AFTER model_number');
        topluca_add_column($pdo,'products','weight_kg','DECIMAL(8,3) NULL AFTER desi');
    }
    if(topluca_table_exists($pdo,'admins')){
        topluca_add_column($pdo,'admins','full_name','VARCHAR(160) NULL AFTER username');
        topluca_add_column($pdo,'admins','last_login_at','DATETIME NULL AFTER is_active');
    }

    $districts=['Çankaya','Yenimahalle','Keçiören','Mamak','Etimesgut','Sincan','Altındağ','Gölbaşı','Pursaklar'];
    $st=$pdo->prepare("INSERT INTO same_day_districts(district_name,is_active,sort_order) VALUES(?,1,?) ON DUPLICATE KEY UPDATE district_name=VALUES(district_name)");
    foreach($districts as $i=>$d) $st->execute([$d,$i+1]);

    $synonyms=[
        'mouse'=>'mouse,mause,fare',
        'hoparlör'=>'hoparlör,speaker,ses bombası',
        'hard disk'=>'hard disk,harddisk,hdd',
        'usb bellek'=>'usb bellek,flash bellek,flash disk'
    ];
    $st=$pdo->prepare("INSERT INTO search_synonyms(term,synonyms,is_active,created_at,updated_at) VALUES(?,?,1,NOW(),NOW()) ON DUPLICATE KEY UPDATE synonyms=VALUES(synonyms),updated_at=NOW()");
    foreach($synonyms as $term=>$list) $st->execute([$term,$list]);

    $s=$pdo->prepare("INSERT INTO app_migrations(migration_key,applied_at) VALUES('v2_foundation',NOW()) ON DUPLICATE KEY UPDATE applied_at=applied_at");
    $s->execute();
}
