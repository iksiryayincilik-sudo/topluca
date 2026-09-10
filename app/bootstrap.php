<?php
declare(strict_types=1);

$configPath = dirname(__DIR__) . '/config.php';
if (!is_file($configPath)) {
    http_response_code(503);
    exit('TOPLUCA henüz kurulmamış. Lütfen install.php dosyasını açın.');
}

$config = require $configPath;
date_default_timezone_set($config['timezone'] ?? 'Europe/Istanbul');

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
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

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

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

function h(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function money(float|int|string $v): string { return number_format((float)$v, 2, ',', '.') . ' TL'; }
function topluca_slug(string $text): string {
    $text = strtr($text,['ş'=>'s','Ş'=>'s','ı'=>'i','İ'=>'i','ğ'=>'g','Ğ'=>'g','ü'=>'u','Ü'=>'u','ö'=>'o','Ö'=>'o','ç'=>'c','Ç'=>'c']);
    $text = mb_strtolower($text,'UTF-8');
    $text = preg_replace('~[^a-z0-9]+~u','-',$text);
    return trim((string)$text,'-');
}
function tr_normalize(string $text): string {
    return mb_strtolower(trim(strtr($text,['I'=>'ı','İ'=>'i'])),'UTF-8');
}
function url(string $path=''): string {
    global $config;
    return rtrim($config['base_url'],'/') . '/' . ltrim($path,'/');
}
function redirect(string $path): never { header('Location: '.url($path)); exit; }
function asset(string $path): string { return url('assets/'.ltrim($path,'/')); }

function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.h(csrf_token()).'">'; }
function csrf_check(): void {
    $token = (string)($_POST['_csrf'] ?? '');
    if (!$token || !hash_equals(csrf_token(),$token)) {
        http_response_code(419);
        exit('Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.');
    }
}
function flash(string $type,string $message): void { $_SESSION['flash'][]=['type'=>$type,'message'=>$message]; }
function flashes(): array { $x=$_SESSION['flash']??[]; unset($_SESSION['flash']); return $x; }
function setting(string $key,mixed $default=null): mixed {
    static $cache=[];
    if (array_key_exists($key,$cache)) return $cache[$key];
    $s=db()->prepare('SELECT setting_value FROM settings WHERE setting_key=?');
    $s->execute([$key]);
    $v=$s->fetchColumn();
    return $cache[$key]=($v===false?$default:$v);
}

function main_categories(): array {
    return db()->query("SELECT * FROM categories WHERE parent_id IS NULL AND is_active=1 AND show_in_menu=1 ORDER BY sort_order,name")->fetchAll();
}
function child_categories(int $parentId): array {
    $s=db()->prepare("SELECT * FROM categories WHERE parent_id=? AND is_active=1 ORDER BY sort_order,name");
    $s->execute([$parentId]); return $s->fetchAll();
}
function descendant_ids(int $categoryId): array {
    $ids=[$categoryId]; $queue=[$categoryId];
    while($queue){
        $id=array_shift($queue);
        $s=db()->prepare('SELECT id FROM categories WHERE parent_id=? AND is_active=1');$s->execute([$id]);
        foreach($s->fetchAll() as $r){$n=(int)$r['id']; if(!in_array($n,$ids,true)){$ids[]=$n;$queue[]=$n;}}
    }
    return $ids;
}
function category_by_slug(string $slug): ?array {
    $s=db()->prepare('SELECT * FROM categories WHERE slug=? AND is_active=1 LIMIT 1');$s->execute([$slug]); return $s->fetch()?:null;
}
function category_path(array $category): array {
    $path=[$category]; $parent=$category['parent_id']??null;
    while($parent){$s=db()->prepare('SELECT * FROM categories WHERE id=? LIMIT 1');$s->execute([(int)$parent]);$r=$s->fetch();if(!$r)break;array_unshift($path,$r);$parent=$r['parent_id'];}
    return $path;
}

function active_campaigns(): array {
    static $rows=null;
    if($rows!==null)return $rows;
    $rows=db()->query("SELECT * FROM campaigns WHERE is_active=1 AND (start_at IS NULL OR start_at<=NOW()) AND (end_at IS NULL OR end_at>=NOW()) ORDER BY id DESC")->fetchAll();
    return $rows;
}
function campaign_price(array $product): float {
    $base=(float)$product['sale_price']; $best=$base;
    foreach(active_campaigns() as $c){
        $match=$c['target_type']==='all' || ($c['target_type']==='product'&&(int)$c['target_id']===(int)$product['id']) || ($c['target_type']==='brand'&&(int)$c['target_id']===(int)($product['brand_id']??0)) || ($c['target_type']==='category'&&(int)$c['target_id']===(int)$product['category_id']);
        if(!$match)continue;
        $candidate=$c['discount_type']==='percent' ? $base*(1-((float)$c['discount_value']/100)) : $base-(float)$c['discount_value'];
        $best=min($best,max(0,$candidate));
    }
    return round($best,2);
}
function tier_price(int $productId,int $qty,float $fallback): float {
    $s=db()->prepare("SELECT unit_price FROM product_price_tiers WHERE product_id=? AND is_active=1 AND min_qty<=? AND (max_qty IS NULL OR max_qty>=?) ORDER BY min_qty DESC LIMIT 1");
    $s->execute([$productId,$qty,$qty]);$v=$s->fetchColumn();return $v===false?$fallback:(float)$v;
}
function effective_price(array $product,int $qty=1): float {
    return round(tier_price((int)$product['id'],$qty,campaign_price($product)),2);
}
function product_images(int $productId,?string $fallback=null): array {
    $s=db()->prepare('SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC,sort_order,id');$s->execute([$productId]);$rows=$s->fetchAll();
    if(!$rows&&$fallback)$rows=[['image_path'=>$fallback,'alt_text'=>null,'is_primary'=>1]];
    return $rows;
}
function product_attributes(int $productId): array {
    $s=db()->prepare('SELECT * FROM product_attributes WHERE product_id=? ORDER BY sort_order,id');$s->execute([$productId]);return $s->fetchAll();
}

function user_logged_in(): bool { return !empty($_SESSION['user_id']); }
function current_user(): ?array {
    if(!user_logged_in())return null;
    static $u=null; if($u)return $u;
    $s=db()->prepare('SELECT * FROM users WHERE id=? AND is_active=1 LIMIT 1');$s->execute([(int)$_SESSION['user_id']]);return $u=$s->fetch()?:null;
}
function login_user(array $user): void {
    session_regenerate_id(true); $_SESSION['user_id']=(int)$user['id'];
    db()->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([(int)$user['id']]);
    if(isset($_SESSION['cart_id'])) db()->prepare('UPDATE carts SET user_id=? WHERE id=?')->execute([(int)$user['id'],(int)$_SESSION['cart_id']]);
}
function logout_user(): void { unset($_SESSION['user_id']); session_regenerate_id(true); }
function require_user(): void { if(!user_logged_in()){$_SESSION['after_login']=$_SERVER['REQUEST_URI']??url('account.php');redirect('account.php');} }

function cart_id(): int {
    if(!empty($_SESSION['cart_id'])){
        $s=db()->prepare('SELECT id FROM carts WHERE id=? AND converted_order_id IS NULL');$s->execute([(int)$_SESSION['cart_id']]);if($s->fetchColumn())return (int)$_SESSION['cart_id'];
    }
    $sid=session_id();$uid=user_logged_in()?(int)$_SESSION['user_id']:null;
    $s=db()->prepare('SELECT id FROM carts WHERE session_key=? AND converted_order_id IS NULL ORDER BY id DESC LIMIT 1');$s->execute([$sid]);$id=$s->fetchColumn();
    if(!$id&&$uid){$s=db()->prepare('SELECT id FROM carts WHERE user_id=? AND converted_order_id IS NULL ORDER BY id DESC LIMIT 1');$s->execute([$uid]);$id=$s->fetchColumn();}
    if(!$id){$s=db()->prepare('INSERT INTO carts(session_key,user_id,subtotal,created_at,updated_at) VALUES(?,?,0,NOW(),NOW())');$s->execute([$sid,$uid]);$id=db()->lastInsertId();}
    $_SESSION['cart_id']=(int)$id;return (int)$id;
}
function cart_items(): array {
    $s=db()->prepare("SELECT ci.id cart_item_id,ci.quantity,p.*,b.name brand_name,c.name category_name FROM cart_items ci JOIN products p ON p.id=ci.product_id LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id WHERE ci.cart_id=? AND p.is_active=1 ORDER BY ci.id DESC");
    $s->execute([cart_id()]);$rows=$s->fetchAll();
    foreach($rows as &$r){$r['effective_price']=effective_price($r,(int)$r['quantity']);$r['line_total']=round((float)$r['effective_price']*(int)$r['quantity'],2);}return $rows;
}
function cart_count(): int {$n=0;foreach(cart_items() as $i)$n+=(int)$i['quantity'];return $n;}
function cart_subtotal(): float {$n=0;foreach(cart_items() as $i)$n+=(float)$i['line_total'];return round($n,2);}
function sync_cart_total(): void {db()->prepare('UPDATE carts SET subtotal=?,updated_at=NOW() WHERE id=?')->execute([cart_subtotal(),cart_id()]);}
function add_to_cart(int $productId,int $qty=1): bool {
    $s=db()->prepare('SELECT * FROM products WHERE id=? AND is_active=1 LIMIT 1');$s->execute([$productId]);$p=$s->fetch();if(!$p||((int)$p['stock']-(int)$p['reserved_stock'])<=0)return false;
    $qty=max((int)$p['min_order_qty'],min(99,$qty));$available=max(0,(int)$p['stock']-(int)$p['reserved_stock']);$cid=cart_id();
    $s=db()->prepare('SELECT id,quantity FROM cart_items WHERE cart_id=? AND product_id=?');$s->execute([$cid,$productId]);$e=$s->fetch();
    if($e){$new=min($available,(int)$e['quantity']+$qty);db()->prepare('UPDATE cart_items SET quantity=?,unit_price_snapshot=?,updated_at=NOW() WHERE id=?')->execute([$new,effective_price($p,$new),$e['id']]);}
    else{db()->prepare('INSERT INTO cart_items(cart_id,product_id,quantity,unit_price_snapshot,created_at,updated_at) VALUES(?,?,?,?,NOW(),NOW())')->execute([$cid,$productId,min($qty,$available),effective_price($p,$qty)]);}sync_cart_total();return true;
}

function product_card_query(string $where='1=1',array $params=[],string $order='p.is_bestseller DESC,p.sales_count DESC,p.id DESC',int $limit=20): array {
    $sql="SELECT p.*,b.name brand_name,b.slug brand_slug,c.name category_name,c.slug category_slug FROM products p LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1 AND {$where} ORDER BY {$order} LIMIT ".max(1,min(200,$limit));
    $s=db()->prepare($sql);$s->execute($params);return $s->fetchAll();
}
function view_event(string $type,?int $entityId=null): void {
    try{$ip=$_SERVER['REMOTE_ADDR']??'';$hash=$ip?hash('sha256',$ip.($GLOBALS['config']['app_key']??'')):null;$s=db()->prepare('INSERT INTO page_views(page_type,entity_id,session_key,user_id,ip_hash,created_at) VALUES(?,?,?,?,?,NOW())');$s->execute([$type,$entityId,session_id(),user_logged_in()?(int)$_SESSION['user_id']:null,$hash]);}catch(Throwable $e){}
}

function admin_logged_in(): bool { return !empty($_SESSION['admin_id']); }
function require_admin(): void { if(!admin_logged_in())redirect('webyonet/login.php'); }
function current_admin(): ?array {if(!admin_logged_in())return null;$s=db()->prepare('SELECT * FROM admins WHERE id=? AND is_active=1');$s->execute([(int)$_SESSION['admin_id']]);return $s->fetch()?:null;}
function admin_log(string $action,string $entityType='',?int $entityId=null,string $details=''): void {
    if(!admin_logged_in())return;$ip=$_SERVER['REMOTE_ADDR']??'';$hash=$ip?hash('sha256',$ip.($GLOBALS['config']['app_key']??'')):null;$s=db()->prepare('INSERT INTO admin_logs(admin_id,action_name,entity_type,entity_id,details,ip_hash,created_at) VALUES(?,?,?,?,?,?,NOW())');$s->execute([(int)$_SESSION['admin_id'],$action,$entityType,$entityId,$details,$hash]);
}

require_once __DIR__.'/Shipping.php';
require_once __DIR__.'/Checkout.php';
