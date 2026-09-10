<?php
declare(strict_types=1);

function topluca_slug(string $text): string {
    $text=strtr($text,['ş'=>'s','Ş'=>'s','ı'=>'i','İ'=>'i','ğ'=>'g','Ğ'=>'g','ü'=>'u','Ü'=>'u','ö'=>'o','Ö'=>'o','ç'=>'c','Ç'=>'c']);
    $text=mb_strtolower($text,'UTF-8');
    $text=preg_replace('~[^a-z0-9]+~u','-',$text);
    return trim((string)$text,'-');
}

$pdo=new PDO(
    'mysql:host='.(getenv('DB_HOST')?:'127.0.0.1').';port='.(getenv('DB_PORT')?:'3306').';dbname='.(getenv('DB_NAME')?:'topluca_test').';charset=utf8mb4',
    getenv('DB_USER')?:'root',
    getenv('DB_PASS')?:'root',
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]
);
require dirname(__DIR__).'/app/schema.php';
topluca_schema($pdo,true);
topluca_seed($pdo);
$checks=[
    'categories'=>(int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
    'products'=>(int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'admins'=>(int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn(),
    'carriers'=>(int)$pdo->query('SELECT COUNT(*) FROM shipping_carriers')->fetchColumn(),
    'users_table'=>(int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='users'")->fetchColumn(),
];
foreach($checks as $name=>$value){if($value<1)throw new RuntimeException("Smoke check failed: {$name}");echo "OK {$name}: {$value}\n";}
$admin=$pdo->query("SELECT * FROM admins WHERE username='emrah'")->fetch();
if(!$admin||!password_verify('emr321456',$admin['password_hash']))throw new RuntimeException('Admin seed password check failed');
echo "OK admin password hash\n";
