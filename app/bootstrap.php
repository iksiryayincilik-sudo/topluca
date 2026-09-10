<?php
declare(strict_types=1);

$configFile = dirname(__DIR__) . '/config.php';
if (!file_exists($configFile)) {
    http_response_code(503);
    exit('TOPLUCA henüz kurulmamış. Lütfen /install.php adresini açın.');
}

$config = require $configFile;
date_default_timezone_set($config['timezone'] ?? 'Europe/Istanbul');

$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('TOPLUCASESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function db(): PDO {
    static $pdo = null;
    global $config;
    if ($pdo instanceof PDO) return $pdo;
    $d = $config['db'];
    $pdo = new PDO(
        "mysql:host={$d['host']};port={$d['port']};dbname={$d['name']};charset=utf8mb4",
        $d['user'],
        $d['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    return $pdo;
}

function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money(float|int|string $v): string { return number_format((float)$v, 2, ',', '.') . ' TL'; }
function url(string $path=''): string {
    global $config;
    return rtrim($config['base_url'], '/') . '/' . ltrim($path, '/');
}
function redirect(string $path): never { header('Location: ' . url($path)); exit; }
function slugify(string $text): string {
    $text = strtr($text,['ş'=>'s','Ş'=>'s','ı'=>'i','İ'=>'i','ğ'=>'g','Ğ'=>'g','ü'=>'u','Ü'=>'u','ö'=>'o','Ö'=>'o','ç'=>'c','Ç'=>'c']);
    $text = strtolower($text);
    $text = preg_replace('~[^a-z0-9]+~','-',$text);
    return trim((string)$text,'-');
}
function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.h(csrf_token()).'">'; }
function csrf_check(): void {
    if (!hash_equals(csrf_token(), (string)($_POST['_csrf'] ?? ''))) {
        http_response_code(419); exit('Güvenlik doğrulaması başarısız.');
    }
}
function flash(string $type,string $message): void { $_SESSION['flash'][]=['type'=>$type,'message'=>$message]; }
function flashes(): array { $f=$_SESSION['flash']??[]; unset($_SESSION['flash']); return $f; }
function setting(string $key,mixed $default=null): mixed {
    static $cache=[];
    if (array_key_exists($key,$cache)) return $cache[$key];
    $s=db()->prepare('SELECT setting_value FROM settings WHERE setting_key=?'); $s->execute([$key]);
    $v=$s->fetchColumn();
    return $cache[$key]=($v===false?$default:$v);
}
function categories(): array { return db()->query('SELECT * FROM categories WHERE parent_id IS NULL AND is_active=1 ORDER BY sort_order,name')->fetchAll(); }
function cart_id(): int {
    $sid=session_id();
    $s=db()->prepare('SELECT id FROM carts WHERE session_key=? AND converted_order_id IS NULL ORDER BY id DESC LIMIT 1'); $s->execute([$sid]);
    $id=$s->fetchColumn(); if ($id) return (int)$id;
    $s=db()->prepare('INSERT INTO carts(session_key,subtotal,created_at,updated_at) VALUES(?,0,NOW(),NOW())'); $s->execute([$sid]);
    return (int)db()->lastInsertId();
}
function cart_items(): array {
    $s=db()->prepare('SELECT ci.id cart_item_id,ci.quantity,p.*,b.name brand_name,c.name category_name FROM cart_items ci JOIN products p ON p.id=ci.product_id LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id WHERE ci.cart_id=? ORDER BY ci.id DESC');
    $s->execute([cart_id()]); $rows=$s->fetchAll();
    foreach($rows as &$r){ $r['line_total']=(float)$r['sale_price']*(int)$r['quantity']; }
    return $rows;
}
function cart_count(): int { $n=0; foreach(cart_items() as $i)$n+=(int)$i['quantity']; return $n; }
function cart_subtotal(): float { $n=0; foreach(cart_items() as $i)$n+=(float)$i['line_total']; return round($n,2); }
function admin_logged_in(): bool { return !empty($_SESSION['admin_id']); }
function require_admin(): void { if(!admin_logged_in()){ header('Location: '.url('webyonet/login.php')); exit; } }
function admin_log(string $action,string $details=''): void {
    if(!admin_logged_in()) return;
    $s=db()->prepare('INSERT INTO admin_logs(admin_id,action_name,details,ip_address,created_at) VALUES(?,?,?,?,NOW())');
    $s->execute([(int)$_SESSION['admin_id'],$action,$details,$_SERVER['REMOTE_ADDR']??'']);
}
