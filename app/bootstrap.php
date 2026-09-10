<?php
declare(strict_types=1);

$configFile=dirname(__DIR__).'/config.php';
if(!file_exists($configFile)){
    if(basename($_SERVER['SCRIPT_NAME']??'')!=='install.php'){
        header('Location: install.php');exit;
    }
    return;
}
$config=require $configFile;
date_default_timezone_set($config['app']['timezone']??'Europe/Istanbul');
$secure=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';
if(session_status()!==PHP_SESSION_ACTIVE){
    session_name('TOPLUCASESSID');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

function db(): PDO{
    static $pdo=null;global $config;
    if($pdo instanceof PDO)return $pdo;
    $d=$config['db'];
    $pdo=new PDO('mysql:host='.$d['host'].';port='.$d['port'].';dbname='.$d['name'].';charset=utf8mb4',$d['user'],$d['pass'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false
    ]);
    return $pdo;
}
function h($v): string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function app_url(string $path=''): string{global $config;$base=rtrim($config['app']['url']??'','/');return $base.($path!==''?'/'.ltrim($path,'/'):'');}
function redirect_to(string $path): void{header('Location: '.app_url($path));exit;}
function money($v): string{return number_format((float)$v,2,',','.').' TL';}
function normalize_tr(string $v): string{$map=['Ç'=>'c','ç'=>'c','Ğ'=>'g','ğ'=>'g','İ'=>'i','I'=>'i','ı'=>'i','Ö'=>'o','ö'=>'o','Ş'=>'s','ş'=>'s','Ü'=>'u','ü'=>'u'];$v=strtr($v,$map);$v=mb_strtolower($v,'UTF-8');return trim((string)preg_replace('/\s+/u',' ',$v));}
function slugify(string $v): string{$v=normalize_tr($v);$v=(string)preg_replace('/[^a-z0-9]+/','-',$v);return trim($v,'-');}
function csrf_token(): string{if(empty($_SESSION['_csrf']))$_SESSION['_csrf']=bin2hex(random_bytes(32));return (string)$_SESSION['_csrf'];}
function csrf_field(): string{return '<input type="hidden" name="_csrf" value="'.h(csrf_token()).'">';}
function csrf_check(): void{$p=$_POST['_csrf']??'';if(!is_string($p)||!hash_equals(csrf_token(),$p)){http_response_code(419);exit('Güvenlik doğrulaması başarısız.');}}
function flash(string $type,string $message): void{$_SESSION['_flash'][]=['type'=>$type,'message'=>$message];}
function flash_html(): string{$rows=$_SESSION['_flash']??[];unset($_SESSION['_flash']);$o='';foreach($rows as $r)$o.='<div class="flash flash-'.h($r['type']).'">'.h($r['message']).'</div>';return $o;}
function setting(string $key,$default=null){static $cache=[];if(array_key_exists($key,$cache))return $cache[$key];$s=db()->prepare('SELECT setting_value FROM settings WHERE setting_key=?');$s->execute([$key]);$v=$s->fetchColumn();if($v===false)return $default;$cache[$key]=$v;return $v;}
function set_setting(string $key,string $value): void{$s=db()->prepare('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');$s->execute([$key,$value]);}
function user_id(): ?int{return !empty($_SESSION['user_id'])?(int)$_SESSION['user_id']:null;}
function current_user(): ?array{if(!user_id())return null;$s=db()->prepare('SELECT * FROM users WHERE id=? AND is_active=1');$s->execute([user_id()]);return $s->fetch()?:null;}
function admin_id(): ?int{return !empty($_SESSION['admin_id'])?(int)$_SESSION['admin_id']:null;}
function current_admin(): ?array{if(!admin_id())return null;$s=db()->prepare('SELECT * FROM admins WHERE id=? AND is_active=1');$s->execute([admin_id()]);return $s->fetch()?:null;}
function require_admin(): void{if(!admin_id())redirect_to('webyonet/login.php');}
function require_user(): void{if(!user_id()){$_SESSION['after_login']=$_SERVER['REQUEST_URI']??app_url('account.php');redirect_to('account.php');}}
function admin_log(string $action,string $entityType='',?int $entityId=null,string $details=''): void{if(!admin_id())return;$s=db()->prepare('INSERT INTO admin_logs(admin_id,action_name,entity_type,entity_id,details,ip_address,created_at) VALUES(?,?,?,?,?,?,NOW())');$s->execute([admin_id(),$action,$entityType?:null,$entityId,$details,$_SERVER['REMOTE_ADDR']??null]);}
function page_view(string $type,?int $entityId=null): void{try{$s=db()->prepare('INSERT INTO page_views(page_type,entity_id,session_key,user_id,ip_address,created_at) VALUES(?,?,?,?,?,NOW())');$s->execute([$type,$entityId,session_id(),user_id(),$_SERVER['REMOTE_ADDR']??null]);}catch(Throwable $e){}}
function upload_image(array $file,string $folder,int $maxMb=8): ?string{
    if(empty($file['name'])||($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return null;
    if(($file['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK)throw new RuntimeException('Görsel yüklenemedi. PHP yükleme kodu: '.(int)$file['error']);
    if(($file['size']??0)>$maxMb*1024*1024)throw new RuntimeException('Görsel en fazla '.$maxMb.' MB olabilir.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    if(!isset($allowed[$mime]))throw new RuntimeException('Yalnızca JPG, PNG, WEBP veya GIF yüklenebilir.');
    $dir=dirname(__DIR__).'/uploads/'.$folder;if(!is_dir($dir)&&!mkdir($dir,0755,true))throw new RuntimeException('Yükleme klasörü oluşturulamadı.');
    $name=date('Ymd').'-'.bin2hex(random_bytes(10)).'.'.$allowed[$mime];
    if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name))throw new RuntimeException('Görsel kaydedilemedi. uploads klasörü yazma iznini kontrol edin.');
    return 'uploads/'.$folder.'/'.$name;
}
function image_dimensions(string $relativePath): ?array{$file=dirname(__DIR__).'/'.ltrim($relativePath,'/');if(!is_file($file))return null;$info=@getimagesize($file);return $info?[(int)$info[0],(int)$info[1]]:null;}
function table_exists(string $table): bool{$s=db()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');$s->execute([$table]);return (int)$s->fetchColumn()>0;}

require_once __DIR__.'/store.php';
require_once __DIR__.'/shipping.php';
require_once __DIR__.'/theme.php';
