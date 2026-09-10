<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors','1');

$lock=__DIR__.'/.installed';
if(file_exists($lock)) exit('TOPLUCA zaten kurulmuş. Güvenlik için install.php dosyasını silin.');

function hh(string $v): string { return htmlspecialchars($v,ENT_QUOTES,'UTF-8'); }
function slug(string $t): string {
    $t=strtr($t,['ş'=>'s','Ş'=>'s','ı'=>'i','İ'=>'i','ğ'=>'g','Ğ'=>'g','ü'=>'u','Ü'=>'u','ö'=>'o','Ö'=>'o','ç'=>'c','Ç'=>'c']);
    $t=strtolower($t); $t=preg_replace('~[^a-z0-9]+~','-',$t); return trim((string)$t,'-');
}

$errors=[];$ok=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
    $host=trim($_POST['host']??'localhost');$port=trim($_POST['port']??'3306');$name=trim($_POST['name']??'');$user=trim($_POST['user']??'');$pass=$_POST['pass']??'';$base=rtrim(trim($_POST['base_url']??''),'/');
    if(!$name||!$user||!$base)$errors[]='Veritabanı adı, kullanıcı ve site adresi zorunludur.';
    if(!$errors){
        try{
            $pdo=new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
            $schema=[
"CREATE TABLE admins(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,username VARCHAR(80) UNIQUE NOT NULL,password_hash VARCHAR(255) NOT NULL,role VARCHAR(50) NOT NULL DEFAULT 'superadmin',must_change_password TINYINT(1) NOT NULL DEFAULT 1,is_active TINYINT(1) NOT NULL DEFAULT 1,created_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE admin_logs(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,admin_id INT UNSIGNED NULL,action_name VARCHAR(120) NOT NULL,details TEXT NULL,ip_address VARCHAR(64) NULL,created_at DATETIME NOT NULL,INDEX(admin_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE login_attempts(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,username VARCHAR(80),ip_address VARCHAR(64),created_at DATETIME NOT NULL,INDEX(username,ip_address,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE categories(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,parent_id INT UNSIGNED NULL,name VARCHAR(190) NOT NULL,slug VARCHAR(190) UNIQUE NOT NULL,sort_order INT NOT NULL DEFAULT 0,is_active TINYINT(1) NOT NULL DEFAULT 1,INDEX(parent_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE brands(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(190) NOT NULL,slug VARCHAR(190) UNIQUE NOT NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,created_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE products(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,category_id INT UNSIGNED NOT NULL,brand_id INT UNSIGNED NULL,name VARCHAR(255) NOT NULL,slug VARCHAR(255) UNIQUE NOT NULL,sku VARCHAR(120) UNIQUE NOT NULL,barcode VARCHAR(120) NULL,purchase_price DECIMAL(12,2) NOT NULL DEFAULT 0,sale_price DECIMAL(12,2) NOT NULL DEFAULT 0,compare_price DECIMAL(12,2) NULL,vat_rate TINYINT UNSIGNED NOT NULL DEFAULT 20,stock INT NOT NULL DEFAULT 0,critical_stock INT NOT NULL DEFAULT 5,shipping_policy ENUM('standard','free','special') NOT NULL DEFAULT 'standard',same_day_delivery TINYINT(1) NOT NULL DEFAULT 0,image VARCHAR(255) NULL,description TEXT NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,is_featured TINYINT(1) NOT NULL DEFAULT 0,view_count BIGINT UNSIGNED NOT NULL DEFAULT 0,sales_count BIGINT UNSIGNED NOT NULL DEFAULT 0,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,INDEX(category_id,is_active),INDEX(brand_id,is_active),INDEX(barcode)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE settings(setting_key VARCHAR(190) PRIMARY KEY,setting_value TEXT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE shipping_companies(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(190) NOT NULL,price DECIMAL(12,2) NOT NULL DEFAULT 0,is_active TINYINT(1) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE carts(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,session_key VARCHAR(190) NOT NULL,subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,converted_order_id BIGINT UNSIGNED NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,INDEX(session_key),INDEX(converted_order_id,updated_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE cart_items(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,cart_id BIGINT UNSIGNED NOT NULL,product_id INT UNSIGNED NOT NULL,quantity INT UNSIGNED NOT NULL DEFAULT 1,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,UNIQUE KEY(cart_id,product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE orders(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,order_no VARCHAR(50) UNIQUE NOT NULL,customer_name VARCHAR(190) NOT NULL,customer_email VARCHAR(190) NOT NULL,customer_phone VARCHAR(80) NOT NULL,city VARCHAR(120) NOT NULL,district VARCHAR(120) NULL,address TEXT NOT NULL,subtotal DECIMAL(12,2) NOT NULL,shipping_total DECIMAL(12,2) NOT NULL DEFAULT 0,grand_total DECIMAL(12,2) NOT NULL,delivery_method ENUM('cargo','same_day') NOT NULL DEFAULT 'cargo',shipping_company_name VARCHAR(190) NULL,status ENUM('new','approved','preparing','shipped','delivered','cancelled') NOT NULL DEFAULT 'new',created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,INDEX(status,created_at),INDEX(customer_email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE order_items(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,order_id BIGINT UNSIGNED NOT NULL,product_id INT UNSIGNED NULL,product_name VARCHAR(255) NOT NULL,sku VARCHAR(120) NOT NULL,quantity INT UNSIGNED NOT NULL,unit_price DECIMAL(12,2) NOT NULL,unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0,line_total DECIMAL(12,2) NOT NULL,INDEX(order_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE search_logs(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,search_term VARCHAR(255) NOT NULL,result_count INT NOT NULL DEFAULT 0,session_key VARCHAR(190) NULL,created_at DATETIME NOT NULL,INDEX(search_term),INDEX(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
"CREATE TABLE page_views(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,page_type VARCHAR(80) NOT NULL,entity_id BIGINT UNSIGNED NULL,session_key VARCHAR(190) NULL,created_at DATETIME NOT NULL,INDEX(page_type,created_at),INDEX(entity_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            ];
            foreach($schema as $sql){ try{$pdo->exec($sql);}catch(Throwable $e){ if(!str_contains($e->getMessage(),'already exists')) throw $e; } }

            $cfg="<?php\nreturn ".var_export(['db'=>['host'=>$host,'port'=>$port,'name'=>$name,'user'=>$user,'pass'=>$pass],'base_url'=>$base,'timezone'=>'Europe/Istanbul'],true).";\n";
            file_put_contents(__DIR__.'/config.php',$cfg);

            $st=$pdo->prepare("INSERT INTO admins(username,password_hash,role,must_change_password,is_active,created_at) VALUES('emrah',?,'superadmin',1,1,NOW()) ON DUPLICATE KEY UPDATE username=username");
            $st->execute([password_hash('emr321456',PASSWORD_DEFAULT)]);

            foreach(['free_shipping_limit'=>'500','same_day_enabled'=>'1','same_day_cutoff'=>'15:00','same_day_free_limit'=>'2000','same_day_under_limit_fee'=>'149.90','same_day_daily_capacity'=>'50'] as $k=>$v){$s=$pdo->prepare('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=setting_value');$s->execute([$k,$v]);}
            if((int)$pdo->query('SELECT COUNT(*) FROM shipping_companies')->fetchColumn()===0)$pdo->exec("INSERT INTO shipping_companies(name,price,is_active) VALUES('Standart Kargo',89.90,1),('Ekonomik Kargo',74.90,1)");

            $ensureCat=function(string $name,?int $parent,int $order=0) use($pdo): int {
                $base=slug($name); $slug=$base;
                if($parent){$q=$pdo->prepare('SELECT slug FROM categories WHERE id=?');$q->execute([$parent]);$ps=$q->fetchColumn(); if($ps)$slug=$ps.'-'.$base;}
                $q=$pdo->prepare('SELECT id FROM categories WHERE slug=?');$q->execute([$slug]); if($id=$q->fetchColumn())return (int)$id;
                $q=$pdo->prepare('INSERT INTO categories(parent_id,name,slug,sort_order,is_active) VALUES(?,?,?,?,1)');$q->execute([$parent,$name,$slug,$order]);return (int)$pdo->lastInsertId();
            };
            $root=[];
            foreach(['Kırtasiye','Kitap','Bilgisayar Sarf Malzemeleri','Yazıcı, Toner & Kartuş','Kağıt & Ofis Ürünleri','Okul Ürünleri','Hobi & Sanat'] as $i=>$n)$root[$n]=$ensureCat($n,null,$i+1);
            $pens=$ensureCat('Kalemler',$root['Kırtasiye'],1);$bookKpss=$ensureCat('KPSS',$root['Kitap'],1);$lis=$ensureCat('GY-GK Lisans',$bookKpss,1);$sb=$ensureCat('Soru Bankası',$lis,1);$mouse=$ensureCat('Mouse',$root['Bilgisayar Sarf Malzemeleri'],1);$toner=$ensureCat('Toner',$root['Yazıcı, Toner & Kartuş'],1);$a4=$ensureCat('Fotokopi Kağıdı',$root['Kağıt & Ofis Ürünleri'],1);$bag=$ensureCat('Okul Çantaları',$root['Okul Ürünleri'],1);$paint=$ensureCat('Resim Boyaları',$root['Hobi & Sanat'],1);

            $ensureBrand=function(string $name)use($pdo):int{$sl=slug($name);$q=$pdo->prepare('SELECT id FROM brands WHERE slug=?');$q->execute([$sl]);if($id=$q->fetchColumn())return(int)$id;$q=$pdo->prepare('INSERT INTO brands(name,slug,is_active,created_at) VALUES(?,?,1,NOW())');$q->execute([$name,$sl]);return(int)$pdo->lastInsertId();};
            $b=['Faber-Castell'=>$ensureBrand('Faber-Castell'),'İksir Yayıncılık'=>$ensureBrand('İksir Yayıncılık'),'Logitech'=>$ensureBrand('Logitech'),'HP'=>$ensureBrand('HP'),'Navigator'=>$ensureBrand('Navigator'),'TOPLUCA'=>$ensureBrand('TOPLUCA'),'Artline'=>$ensureBrand('Artline')];
            $seed=[
                [$pens,$b['Faber-Castell'],'Faber-Castell Tükenmez Kalem Mavi 10’lu','TPL-KRT-001','869000000001',69.90,109.90,129.90,85,'standard',1,74],
                [$sb,$b['İksir Yayıncılık'],'KPSS GY-GK Lisans Bağlam Temelli Soru Bankası','TPL-KTP-001','9786250000001',210,349.90,399.90,120,'standard',1,96],
                [$mouse,$b['Logitech'],'Logitech M220 Kablosuz Mouse Siyah','TPL-BLG-001','5099206066199',315,499,549,48,'free',1,132],
                [$toner,$b['HP'],'HP 107A Uyumlu Siyah Toner','TPL-TNR-001','193905000001',360,599,699,35,'free',1,88],
                [$a4,$b['Navigator'],'Navigator A4 Fotokopi Kağıdı 80 gr 500 Yaprak','TPL-KGT-001','560202400001',145,189.90,209.90,240,'standard',1,214],
                [$bag,$b['TOPLUCA'],'TOPLUCA Günlük Okul Çantası Siyah','TPL-OKL-001','869000000006',420,699,799,28,'free',1,25],
                [$paint,$b['Artline'],'Akrilik Boya Başlangıç Seti 12 Renk','TPL-HOB-001','869000000007',165,279.90,319.90,41,'standard',1,33]
            ];
            foreach($seed as $r){[$cid,$bid,$pn,$sku,$barcode,$buy,$sell,$cmp,$stock,$ship,$same,$sales]=$r;$q=$pdo->prepare('SELECT id FROM products WHERE sku=?');$q->execute([$sku]);if($q->fetchColumn())continue;$q=$pdo->prepare('INSERT INTO products(category_id,brand_id,name,slug,sku,barcode,purchase_price,sale_price,compare_price,vat_rate,stock,critical_stock,shipping_policy,same_day_delivery,description,is_active,is_featured,sales_count,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,20,?,5,?,?,?,1,1,?,NOW(),NOW())');$q->execute([$cid,$bid,$pn,slug($pn),$sku,$barcode,$buy,$sell,$cmp,$stock,$ship,$same,$pn.' - TOPLUCA demo ürünü.',$sales]);}
            file_put_contents($lock,date('c'));
            $ok=true;
        }catch(Throwable $e){$errors[]=$e->getMessage();}
    }
}
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TOPLUCA Kurulum</title><style>body{font-family:Arial;background:#f4f5f7;margin:0;padding:40px}.box{max-width:720px;margin:auto;background:#fff;padding:32px;border-radius:18px;box-shadow:0 20px 60px #00000012}.logo{font-size:34px;font-weight:900}.logo span{color:#ff5a00}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}label{font-size:12px;font-weight:700}input{width:100%;padding:11px;margin-top:6px;border:1px solid #ddd;border-radius:8px;box-sizing:border-box}.full{grid-column:1/-1}button{width:100%;padding:14px;border:0;border-radius:9px;background:#ff5a00;color:#fff;font-weight:900}.err{background:#fff0f0;color:#a22;padding:10px;border-radius:8px;margin:10px 0}.ok{background:#edf9f1;padding:20px;border-radius:10px}.note{background:#fff4ed;padding:12px;border-radius:8px;margin:18px 0;font-size:12px}@media(max-width:600px){body{padding:15px}.grid{grid-template-columns:1fr}.full{grid-column:auto}}</style></head><body><div class="box"><div class="logo">TOPLU<span>CA</span></div><p>FTP-ready PHP/MySQL kurulum</p><?php if($ok):?><div class="ok"><h2>Kurulum tamamlandı 🎉</h2><p>WebYönet: <b>/webyonet/</b></p><p>Kullanıcı: <b>emrah</b><br>Başlangıç şifresi: <b>emr321456</b></p><p><a href="index.php">Siteyi aç</a> · <a href="webyonet/">WebYönet</a></p><p><b>Şimdi install.php dosyasını FTP'den silin.</b></p></div><?php else:?><?php foreach($errors as $e):?><div class="err"><?=hh($e)?></div><?php endforeach;?><div class="note">Önce hosting panelinizden boş MySQL veritabanı ve kullanıcı oluşturun.</div><form method="post"><div class="grid"><label>Sunucu<input name="host" value="<?=hh($_POST['host']??'localhost')?>"></label><label>Port<input name="port" value="<?=hh($_POST['port']??'3306')?>"></label><label>Veritabanı<input name="name" required value="<?=hh($_POST['name']??'')?>"></label><label>Kullanıcı<input name="user" required value="<?=hh($_POST['user']??'')?>"></label><label class="full">Şifre<input type="password" name="pass"></label><label class="full">Site adresi<input name="base_url" required value="<?=hh($_POST['base_url']??'https://topluca.net')?>"></label><div class="full"><button>TOPLUCA'YI KUR</button></div></div></form><?php endif;?></div></body></html>
