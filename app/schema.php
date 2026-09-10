<?php
declare(strict_types=1);

function topluca_schema(PDO $pdo, bool $fresh = false): void
{
    $tables = [
        'admin_logs','admin_login_attempts','admins','page_views','search_logs','search_synonyms',
        'payments','order_status_history','order_items','orders','cart_items','carts','favorites','user_addresses','users',
        'purchase_items','purchases','suppliers','stock_movements','product_price_tiers','product_attributes','product_images','product_category','products',
        'campaigns','banners','homepage_sections','same_day_holidays','same_day_districts','shipping_carriers','brands','categories','settings'
    ];

    if ($fresh) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    $schema = [
        "CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(190) PRIMARY KEY,
            setting_value TEXT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            parent_id INT UNSIGNED NULL,
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL UNIQUE,
            icon_key VARCHAR(60) NULL,
            description TEXT NULL,
            image VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            show_in_menu TINYINT(1) NOT NULL DEFAULT 1,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            meta_title VARCHAR(255) NULL,
            meta_description VARCHAR(500) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_category_parent(parent_id,sort_order),
            INDEX idx_category_active(is_active,show_in_menu)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS brands (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL UNIQUE,
            logo VARCHAR(255) NULL,
            description TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_brand_active(is_active,name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS products (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id INT UNSIGNED NOT NULL,
            brand_id INT UNSIGNED NULL,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            sku VARCHAR(120) NOT NULL UNIQUE,
            barcode VARCHAR(120) NULL,
            isbn VARCHAR(40) NULL,
            model_number VARCHAR(120) NULL,
            short_description VARCHAR(700) NULL,
            description MEDIUMTEXT NULL,
            purchase_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            sale_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            compare_price DECIMAL(12,2) NULL,
            vat_rate DECIMAL(6,2) NOT NULL DEFAULT 20,
            stock INT NOT NULL DEFAULT 0,
            reserved_stock INT NOT NULL DEFAULT 0,
            critical_stock INT NOT NULL DEFAULT 5,
            min_order_qty INT UNSIGNED NOT NULL DEFAULT 1,
            weight_kg DECIMAL(8,3) NULL,
            desi DECIMAL(8,2) NULL,
            shipping_policy VARCHAR(24) NOT NULL DEFAULT 'standard',
            same_day_delivery TINYINT(1) NOT NULL DEFAULT 1,
            image VARCHAR(255) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            is_new TINYINT(1) NOT NULL DEFAULT 0,
            is_bestseller TINYINT(1) NOT NULL DEFAULT 0,
            rating_avg DECIMAL(3,2) NOT NULL DEFAULT 5.00,
            review_count INT UNSIGNED NOT NULL DEFAULT 0,
            view_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
            sales_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
            meta_title VARCHAR(255) NULL,
            meta_description VARCHAR(500) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_product_category(category_id,is_active),
            INDEX idx_product_brand(brand_id,is_active),
            INDEX idx_product_barcode(barcode),
            INDEX idx_product_isbn(isbn),
            INDEX idx_product_sales(is_active,sales_count),
            INDEX idx_product_featured(is_active,is_featured)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS product_category (
            product_id INT UNSIGNED NOT NULL,
            category_id INT UNSIGNED NOT NULL,
            PRIMARY KEY(product_id,category_id),
            INDEX idx_pc_category(category_id,product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS product_images (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id INT UNSIGNED NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            alt_text VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_primary TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            INDEX idx_product_image(product_id,sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS product_attributes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id INT UNSIGNED NOT NULL,
            attribute_group VARCHAR(120) NULL,
            attribute_name VARCHAR(160) NOT NULL,
            attribute_value TEXT NOT NULL,
            is_filterable TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            INDEX idx_attribute_product(product_id,sort_order),
            INDEX idx_attribute_filter(attribute_name,is_filterable)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS product_price_tiers (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id INT UNSIGNED NOT NULL,
            min_qty INT UNSIGNED NOT NULL,
            max_qty INT UNSIGNED NULL,
            unit_price DECIMAL(12,2) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            INDEX idx_tier_product(product_id,min_qty,is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS stock_movements (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id INT UNSIGNED NOT NULL,
            movement_type VARCHAR(50) NOT NULL,
            quantity INT NOT NULL,
            unit_cost DECIMAL(12,2) NULL,
            reference_type VARCHAR(60) NULL,
            reference_id BIGINT UNSIGNED NULL,
            notes VARCHAR(500) NULL,
            admin_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_stock_product(product_id,created_at),
            INDEX idx_stock_reference(reference_type,reference_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS suppliers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(190) NOT NULL,
            contact_name VARCHAR(190) NULL,
            phone VARCHAR(60) NULL,
            email VARCHAR(190) NULL,
            tax_office VARCHAR(120) NULL,
            tax_no VARCHAR(50) NULL,
            address TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS purchases (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            supplier_id INT UNSIGNED NULL,
            document_no VARCHAR(120) NULL,
            purchase_date DATE NOT NULL,
            total DECIMAL(14,2) NOT NULL DEFAULT 0,
            notes TEXT NULL,
            admin_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_purchase_supplier(supplier_id,purchase_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS purchase_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            purchase_id BIGINT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NOT NULL,
            quantity INT UNSIGNED NOT NULL,
            unit_cost DECIMAL(12,2) NOT NULL,
            line_total DECIMAL(14,2) NOT NULL,
            INDEX idx_purchase_item_purchase(purchase_id),
            INDEX idx_purchase_item_product(product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS shipping_carriers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(190) NOT NULL,
            code VARCHAR(80) NOT NULL UNIQUE,
            logo VARCHAR(255) NULL,
            base_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
            free_shipping_limit DECIMAL(12,2) NULL,
            tracking_url VARCHAR(500) NULL,
            estimated_days VARCHAR(80) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_carrier_active(is_active,sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS same_day_districts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            district_name VARCHAR(120) NOT NULL UNIQUE,
            extra_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
            daily_capacity INT UNSIGNED NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS same_day_holidays (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            holiday_date DATE NOT NULL UNIQUE,
            note VARCHAR(255) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            first_name VARCHAR(120) NOT NULL,
            last_name VARCHAR(120) NOT NULL,
            phone VARCHAR(60) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            email_verified_at DATETIME NULL,
            last_login_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_user_phone(phone),
            INDEX idx_user_created(created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS user_addresses (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(80) NOT NULL DEFAULT 'Adresim',
            first_name VARCHAR(120) NOT NULL,
            last_name VARCHAR(120) NOT NULL,
            phone VARCHAR(60) NOT NULL,
            company VARCHAR(190) NULL,
            tax_office VARCHAR(120) NULL,
            tax_no VARCHAR(50) NULL,
            city VARCHAR(120) NOT NULL,
            district VARCHAR(120) NOT NULL,
            neighborhood VARCHAR(160) NULL,
            address TEXT NOT NULL,
            postal_code VARCHAR(20) NULL,
            is_default_shipping TINYINT(1) NOT NULL DEFAULT 0,
            is_default_billing TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_address_user(user_id),
            INDEX idx_address_city(city,district)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS favorites (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uq_favorite(user_id,product_id),
            INDEX idx_favorite_product(product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS carts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            session_key VARCHAR(190) NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            customer_email VARCHAR(190) NULL,
            subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
            converted_order_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_cart_session(session_key,converted_order_id),
            INDEX idx_cart_user(user_id,converted_order_id),
            INDEX idx_cart_abandoned(converted_order_id,updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS cart_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            cart_id BIGINT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NOT NULL,
            quantity INT UNSIGNED NOT NULL DEFAULT 1,
            unit_price_snapshot DECIMAL(12,2) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uq_cart_product(cart_id,product_id),
            INDEX idx_cart_item_product(product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_no VARCHAR(50) NOT NULL UNIQUE,
            user_id BIGINT UNSIGNED NULL,
            customer_email VARCHAR(190) NOT NULL,
            customer_phone VARCHAR(60) NOT NULL,
            shipping_first_name VARCHAR(120) NOT NULL,
            shipping_last_name VARCHAR(120) NOT NULL,
            shipping_city VARCHAR(120) NOT NULL,
            shipping_district VARCHAR(120) NOT NULL,
            shipping_neighborhood VARCHAR(160) NULL,
            shipping_address TEXT NOT NULL,
            shipping_postal_code VARCHAR(20) NULL,
            billing_same_as_shipping TINYINT(1) NOT NULL DEFAULT 1,
            billing_company VARCHAR(190) NULL,
            billing_tax_office VARCHAR(120) NULL,
            billing_tax_no VARCHAR(50) NULL,
            subtotal DECIMAL(14,2) NOT NULL,
            discount_total DECIMAL(14,2) NOT NULL DEFAULT 0,
            shipping_total DECIMAL(14,2) NOT NULL DEFAULT 0,
            grand_total DECIMAL(14,2) NOT NULL,
            delivery_method VARCHAR(40) NOT NULL,
            shipping_method_code VARCHAR(80) NOT NULL,
            shipping_company_name VARCHAR(190) NULL,
            tracking_no VARCHAR(120) NULL,
            payment_method VARCHAR(80) NOT NULL,
            payment_status VARCHAR(40) NOT NULL DEFAULT 'pending',
            status VARCHAR(40) NOT NULL DEFAULT 'new',
            customer_note TEXT NULL,
            guest_claim_token_hash VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_order_user(user_id,created_at),
            INDEX idx_order_email(customer_email,created_at),
            INDEX idx_order_status(status,created_at),
            INDEX idx_order_delivery(delivery_method,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS order_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NULL,
            product_name VARCHAR(255) NOT NULL,
            sku VARCHAR(120) NOT NULL,
            quantity INT UNSIGNED NOT NULL,
            unit_price DECIMAL(12,2) NOT NULL,
            unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
            vat_rate DECIMAL(6,2) NOT NULL DEFAULT 20,
            discount_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            line_total DECIMAL(14,2) NOT NULL,
            INDEX idx_order_item_order(order_id),
            INDEX idx_order_item_product(product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS order_status_history (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(60) NOT NULL,
            note VARCHAR(500) NULL,
            admin_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_order_history(order_id,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS payments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            provider VARCHAR(80) NOT NULL DEFAULT 'manual',
            provider_transaction_id VARCHAR(190) NULL,
            amount DECIMAL(14,2) NOT NULL,
            status VARCHAR(60) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_payment_order(order_id),
            INDEX idx_payment_provider(provider_transaction_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS campaigns (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(190) NOT NULL,
            target_type VARCHAR(30) NOT NULL DEFAULT 'all',
            target_id INT UNSIGNED NULL,
            discount_type VARCHAR(30) NOT NULL DEFAULT 'percent',
            discount_value DECIMAL(12,2) NOT NULL DEFAULT 0,
            min_cart_total DECIMAL(12,2) NULL,
            start_at DATETIME NULL,
            end_at DATETIME NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_campaign_active(is_active,start_at,end_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS banners (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(190) NOT NULL,
            eyebrow VARCHAR(120) NULL,
            subtitle VARCHAR(500) NULL,
            desktop_image VARCHAR(255) NULL,
            mobile_image VARCHAR(255) NULL,
            link_url VARCHAR(500) NULL,
            button_text VARCHAR(120) NULL,
            position_code VARCHAR(80) NOT NULL DEFAULT 'home_hero',
            sort_order INT NOT NULL DEFAULT 0,
            start_at DATETIME NULL,
            end_at DATETIME NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_banner_position(position_code,is_active,sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS homepage_sections (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            section_code VARCHAR(80) NOT NULL UNIQUE,
            title VARCHAR(190) NOT NULL,
            subtitle VARCHAR(500) NULL,
            source_type VARCHAR(40) NOT NULL DEFAULT 'featured',
            source_value VARCHAR(190) NULL,
            item_limit INT UNSIGNED NOT NULL DEFAULT 5,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            updated_at DATETIME NOT NULL,
            INDEX idx_home_section(is_active,sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS search_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            search_term VARCHAR(255) NOT NULL,
            result_count INT NOT NULL DEFAULT 0,
            session_key VARCHAR(190) NULL,
            user_id BIGINT UNSIGNED NULL,
            clicked_product_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_search_term(search_term),
            INDEX idx_search_date(created_at),
            INDEX idx_search_zero(result_count,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS search_synonyms (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            term VARCHAR(190) NOT NULL UNIQUE,
            synonyms TEXT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS page_views (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_type VARCHAR(80) NOT NULL,
            entity_id BIGINT UNSIGNED NULL,
            session_key VARCHAR(190) NULL,
            user_id BIGINT UNSIGNED NULL,
            ip_hash CHAR(64) NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_page_type(page_type,created_at),
            INDEX idx_page_entity(entity_id,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(80) NOT NULL UNIQUE,
            full_name VARCHAR(160) NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'superadmin',
            must_change_password TINYINT(1) NOT NULL DEFAULT 1,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_login_at DATETIME NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS admin_login_attempts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(80) NULL,
            ip_hash CHAR(64) NULL,
            was_successful TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            INDEX idx_admin_attempt(username,ip_hash,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS admin_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_id INT UNSIGNED NULL,
            action_name VARCHAR(120) NOT NULL,
            entity_type VARCHAR(80) NULL,
            entity_id BIGINT UNSIGNED NULL,
            details TEXT NULL,
            ip_hash CHAR(64) NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_admin_log(admin_id,created_at),
            INDEX idx_admin_entity(entity_type,entity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($schema as $sql) {
        $pdo->exec($sql);
    }
}

function topluca_seed(PDO $pdo): void
{
    $settings = [
        'site_name' => 'TOPLUCA',
        'site_tagline' => 'İhtiyacın olan ne varsa, Topluca.',
        'free_shipping_limit' => '750',
        'same_day_enabled' => '1',
        'same_day_cutoff' => '15:00',
        'same_day_free_limit' => '2000',
        'same_day_under_limit_fee' => '149.90',
        'same_day_allow_paid_under_limit' => '1',
        'same_day_daily_capacity' => '50',
        'same_day_weekdays' => '1,2,3,4,5,6',
        'support_phone' => '0850 302 0 850',
        'support_email' => 'destek@topluca.net',
        'company_city' => 'Ankara',
        'bank_transfer_enabled' => '1',
        'card_payment_enabled' => '0',
        'card_payment_provider' => '',
        'currency' => 'TRY'
    ];
    $st = $pdo->prepare("INSERT INTO settings(setting_key,setting_value,updated_at) VALUES(?,?,NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=NOW()");
    foreach ($settings as $k=>$v) $st->execute([$k,$v]);

    $categorySeed = [
        [null,'Kırtasiye','kirtasiye','pen',1],
        [null,'Kitap','kitap','book',2],
        [null,'Bilgisayar Sarf Malzemeleri','bilgisayar-sarf-malzemeleri','monitor',3],
        [null,'Yazıcı, Toner & Kartuş','yazici-toner-kartus','printer',4],
        [null,'Kağıt & Ofis Ürünleri','kagit-ofis-urunleri','paper',5],
        [null,'Okul Ürünleri','okul-urunleri','bag',6],
        [null,'Hobi & Sanat','hobi-sanat','palette',7]
    ];
    $st = $pdo->prepare("INSERT INTO categories(parent_id,name,slug,icon_key,sort_order,show_in_menu,is_active,created_at,updated_at) VALUES(?,?,?,?,?,1,1,NOW(),NOW())");
    foreach ($categorySeed as $row) $st->execute($row);

    $ids=[];
    foreach($pdo->query("SELECT id,slug FROM categories WHERE parent_id IS NULL") as $r) $ids[$r['slug']] = (int)$r['id'];

    $children = [
        ['kirtasiye','Kalemler','kalemler',1],['kirtasiye','Defter & Ajanda','defter-ajanda',2],['kirtasiye','Dosyalama & Arşivleme','dosyalama-arsivleme',3],['kirtasiye','Yapıştırıcı & Bant','yapistirici-bant',4],
        ['kitap','KPSS','kpss',1],['kitap','YKS','yks',2],['kitap','8. Sınıf','8-sinif',3],['kitap','7. Sınıf','7-sinif',4],['kitap','6. Sınıf','6-sinif',5],['kitap','5. Sınıf','5-sinif',6],
        ['bilgisayar-sarf-malzemeleri','Klavye & Mouse','klavye-mouse',1],['bilgisayar-sarf-malzemeleri','RAM','ram',2],['bilgisayar-sarf-malzemeleri','SSD & HDD','ssd-hdd',3],['bilgisayar-sarf-malzemeleri','Kablo & Adaptör','kablo-adaptor',4],
        ['yazici-toner-kartus','Yazıcılar','yazicilar',1],['yazici-toner-kartus','Toner','toner',2],['yazici-toner-kartus','Kartuş','kartus',3],
        ['kagit-ofis-urunleri','Fotokopi Kağıdı','fotokopi-kagidi',1],['kagit-ofis-urunleri','Etiket & Zarf','etiket-zarf',2],['kagit-ofis-urunleri','Hesap Makinesi','hesap-makinesi',3],
        ['okul-urunleri','Okul Çantası','okul-cantasi',1],['okul-urunleri','Kalemlik','kalemlik',2],['okul-urunleri','Geometri Seti','geometri-seti',3],
        ['hobi-sanat','Boyalar','boyalar',1],['hobi-sanat','Fırça & Tuval','firca-tuval',2],['hobi-sanat','Hobi Setleri','hobi-setleri',3]
    ];
    $st=$pdo->prepare("INSERT INTO categories(parent_id,name,slug,sort_order,show_in_menu,is_active,created_at,updated_at) VALUES(?,?,?,?,0,1,NOW(),NOW())");
    foreach($children as $r){ $st->execute([$ids[$r[0]],$r[1],$r[2],$r[3]]); }

    $childIds=[];
    foreach($pdo->query("SELECT id,slug FROM categories") as $r) $childIds[$r['slug']] = (int)$r['id'];
    $kpss=$childIds['kpss'];
    $st=$pdo->prepare("INSERT INTO categories(parent_id,name,slug,sort_order,show_in_menu,is_active,created_at,updated_at) VALUES(?,?,?,?,0,1,NOW(),NOW())");
    $st->execute([$kpss,'GY-GK Lisans','kpss-gy-gk-lisans',1]);
    $gy=(int)$pdo->lastInsertId();
    $st->execute([$gy,'Soru Bankası','kpss-gy-gk-lisans-soru-bankasi',1]);
    $kpssSb=(int)$pdo->lastInsertId();
    $st->execute([$gy,'Konu Anlatımı','kpss-gy-gk-lisans-konu-anlatimi',2]);
    $st->execute([$gy,'Deneme','kpss-gy-gk-lisans-deneme',3]);

    $brands=['Faber-Castell','İksir Yayıncılık','Logitech','HP','Navigator','TOPLUCA','Artline'];
    $bst=$pdo->prepare("INSERT INTO brands(name,slug,is_active,created_at,updated_at) VALUES(?,?,1,NOW(),NOW())");
    $brandIds=[];
    foreach($brands as $b){$slug=topluca_slug($b);$bst->execute([$b,$slug]);$brandIds[$b]=(int)$pdo->lastInsertId();}

    $products = [
        [$childIds['kalemler'],$brandIds['Faber-Castell'],'Faber-Castell Grip Tükenmez Kalem 10’lu','TPL-KRT-001','869000000001',72.00,109.90,129.90,20,84,'standard',1,'assets/img/demo/pen.svg','Günlük okul ve ofis kullanımı için akıcı yazım sağlayan 10’lu tükenmez kalem seti.',1,1,148],
        [$kpssSb,$brandIds['İksir Yayıncılık'],'KPSS GY-GK Lisans Bağlam Temelli Soru Bankası','TPL-KTP-001','9786250000001',210.00,349.90,399.90,0,120,'standard',1,'assets/img/demo/book.svg','KPSS Genel Yetenek Genel Kültür hazırlığında bağlam temelli soru pratiği için demo ürün.',1,1,192],
        [$childIds['klavye-mouse'],$brandIds['Logitech'],'Logitech MK235 Kablosuz Klavye Mouse Seti','TPL-BLG-001','5099206063976',410.00,599.00,699.00,20,46,'free',1,'assets/img/demo/mouse.svg','Güvenilir 2.4 GHz kablosuz bağlantı, tam boy klavye ve ergonomik mouse.',1,1,276],
        [$childIds['toner'],$brandIds['HP'],'HP 107A Uyumlu Siyah Toner','TPL-TNR-001','193905000001',360.00,599.00,699.00,20,35,'free',1,'assets/img/demo/toner.svg','HP Laser 107 serisi ve uyumlu modeller için yüksek baskı kalitesi sunan demo toner.',1,0,118],
        [$childIds['fotokopi-kagidi'],$brandIds['Navigator'],'Navigator A4 Fotokopi Kağıdı 80 gr 500 Yaprak','TPL-KGT-001','560202400001',145.00,189.90,209.90,20,240,'standard',1,'assets/img/demo/paper.svg','80 gr, 500 yaprak, yüksek beyazlıkta A4 fotokopi kağıdı.',1,1,344],
        [$childIds['okul-cantasi'],$brandIds['TOPLUCA'],'TOPLUCA Günlük Okul Çantası Lacivert','TPL-OKL-001','869000000006',420.00,699.00,799.00,20,28,'free',1,'assets/img/demo/bag.svg','Günlük okul kullanımı için çok bölmeli, dayanıklı ve ergonomik sırt çantası.',1,0,67],
        [$childIds['boyalar'],$brandIds['Artline'],'Akrilik Boya Başlangıç Seti 12 Renk','TPL-HOB-001','869000000007',165.00,279.90,319.90,20,41,'standard',1,'assets/img/demo/paint.svg','Hobi ve sanat çalışmaları için 12 renk akrilik boya başlangıç seti.',1,0,83]
    ];
    $pst=$pdo->prepare("INSERT INTO products(category_id,brand_id,name,slug,sku,barcode,purchase_price,sale_price,compare_price,vat_rate,stock,critical_stock,shipping_policy,same_day_delivery,image,short_description,description,is_active,is_featured,is_bestseller,rating_avg,review_count,sales_count,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,?,?,4.90,?,?,NOW(),NOW())");
    foreach($products as $p){
        $slug=topluca_slug($p[2]);
        $pst->execute([$p[0],$p[1],$p[2],$slug,$p[3],$p[4],$p[5],$p[6],$p[7],$p[8],$p[9],5,$p[10],$p[11],$p[12],$p[13],$p[13],$p[14],$p[15],rand(35,240),$p[16]]);
        $pid=(int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO product_category(product_id,category_id) VALUES(?,?)")->execute([$pid,$p[0]]);
        $attrs=[['Genel','Marka',$brands[array_search($p[1],$brandIds,true)] ?? 'TOPLUCA'],['Genel','Stok Kodu',$p[3]],['Teslimat','Ankara Aynı Gün',$p[11]?'Uygun':'Uygun Değil']];
        $ast=$pdo->prepare("INSERT INTO product_attributes(product_id,attribute_group,attribute_name,attribute_value,is_filterable,sort_order) VALUES(?,?,?,?,?,?)");
        foreach($attrs as $i=>$a)$ast->execute([$pid,$a[0],$a[1],$a[2],$i===0?1:0,$i+1]);
    }

    $carriers=[['yurtici','Yurtiçi Kargo',89.90,'1-3 iş günü'],['aras','Aras Kargo',84.90,'1-3 iş günü'],['mng','MNG Kargo',79.90,'1-4 iş günü']];
    $st=$pdo->prepare("INSERT INTO shipping_carriers(code,name,base_fee,estimated_days,is_active,sort_order,created_at,updated_at) VALUES(?,?,?,?,1,?,NOW(),NOW())");
    foreach($carriers as $i=>$c)$st->execute([$c[0],$c[1],$c[2],$c[3],$i+1]);

    $districts=['Çankaya','Yenimahalle','Keçiören','Mamak','Etimesgut','Sincan','Altındağ','Gölbaşı','Pursaklar'];
    $st=$pdo->prepare("INSERT INTO same_day_districts(district_name,is_active,sort_order) VALUES(?,1,?)");
    foreach($districts as $i=>$d)$st->execute([$d,$i+1]);

    $synonyms=['mouse'=>'mouse,mause,fare','hoparlör'=>'hoparlör,speaker,ses bombası','hard disk'=>'hard disk,harddisk,hdd','usb bellek'=>'usb bellek,flash bellek,flash disk'];
    $st=$pdo->prepare("INSERT INTO search_synonyms(term,synonyms,is_active,created_at,updated_at) VALUES(?,?,1,NOW(),NOW())");
    foreach($synonyms as $term=>$syn)$st->execute([$term,$syn]);

    $sections=[['bestsellers','Çok Satanlar','Müşterilerin en çok tercih ettiği ürünler','bestseller','',5,1],['books','Kitap Fırsatları','Sınav hazırlığından kültür kitaplarına','category','kitap',5,2],['technology','Teknoloji / Bilgisayar','Ofis ve teknoloji ihtiyaçları','category','bilgisayar-sarf-malzemeleri',5,3]];
    $st=$pdo->prepare("INSERT INTO homepage_sections(section_code,title,subtitle,source_type,source_value,item_limit,sort_order,is_active,updated_at) VALUES(?,?,?,?,?,?,?,1,NOW())");
    foreach($sections as $s)$st->execute($s);

    $admin=$pdo->prepare("INSERT INTO admins(username,full_name,password_hash,role,must_change_password,is_active,created_at) VALUES('emrah','Emrah',?,'superadmin',1,1,NOW())");
    $admin->execute([password_hash('emr321456',PASSWORD_DEFAULT)]);
}
