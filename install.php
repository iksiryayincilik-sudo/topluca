<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors','1');

session_name('TOPLUCAINSTALL');
session_start();

function ih(mixed $v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function topluca_slug(string $text): string {
    $text=strtr($text,['ş'=>'s','Ş'=>'s','ı'=>'i','İ'=>'i','ğ'=>'g','Ğ'=>'g','ü'=>'u','Ü'=>'u','ö'=>'o','Ö'=>'o','ç'=>'c','Ç'=>'c']);
    $text=mb_strtolower($text,'UTF-8');$text=preg_replace('~[^a-z0-9]+~u','-',$text);return trim((string)$text,'-');
}
function make_pdo(array $db): PDO {
    return new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",$db['user'],$db['pass'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false
    ]);
}

$root=__DIR__;$configPath=$root.'/config.php';$lockPath=$root.'/.topluca-installed';
$existingConfig=is_file($configPath)?require $configPath:null;
$checks=[
    'PHP 8.1+'=>version_compare(PHP_VERSION,'8.1.0','>='),
    'PDO'=>extension_loaded('pdo'),
    'PDO MySQL'=>extension_loaded('pdo_mysql'),
    'mbstring'=>extension_loaded('mbstring'),
    'uploads yazılabilir'=>is_writable($root)||is_writable(dirname($root))
];
$allChecks=!in_array(false,$checks,true);
$error='';$success=false;$mode=$_POST['mode']??($existingConfig?'existing':'new');

if($_SERVER['REQUEST_METHOD']==='POST'){
    $token=(string)($_POST['_csrf']??'');
    if(empty($_SESSION['_csrf'])||!hash_equals($_SESSION['_csrf'],$token))$error='Güvenlik oturumu yenilendi. Sayfayı yenileyip tekrar deneyin.';
    elseif(!$allChecks)$error='Sunucuda gerekli PHP bileşenlerinden biri eksik.';
    else{
        try{
            if($mode==='existing'){
                if(!$existingConfig||empty($existingConfig['db']))throw new RuntimeException('Mevcut config.php bağlantısı bulunamadı.');
                $db=$existingConfig['db'];$baseUrl=rtrim((string)($existingConfig['base_url']??''),'/');
            }else{
                $db=['host'=>trim((string)($_POST['db_host']??'localhost')),'port'=>trim((string)($_POST['db_port']??'3306')),'name'=>trim((string)($_POST['db_name']??'')),'user'=>trim((string)($_POST['db_user']??'')),'pass'=>(string)($_POST['db_pass']??'')];
                $baseUrl=rtrim(trim((string)($_POST['base_url']??'')),'/');
                if(!$db['name']||!$db['user']||!$baseUrl)throw new RuntimeException('Veritabanı adı, kullanıcısı ve site adresi zorunludur.');
            }

            $pdo=make_pdo($db);
            $fresh=isset($_POST['fresh'])&&$_POST['fresh']==='1';
            if($fresh&&trim((string)($_POST['fresh_confirm']??''))!=='TOPLUCA')throw new RuntimeException('Sıfırlama için onay alanına TOPLUCA yazmalısınız.');

            require_once $root.'/app/schema.php';
            topluca_schema($pdo,$fresh);

            $productCount=(int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
            if($productCount===0)topluca_seed($pdo);

            if($mode==='new'){
                $appKey=bin2hex(random_bytes(32));
                $config="<?php\nreturn ".var_export([
                    'db'=>$db,'base_url'=>$baseUrl,'timezone'=>'Europe/Istanbul','app_key'=>$appKey,'environment'=>'production'
                ],true).";\n";
                if(file_put_contents($configPath,$config)===false)throw new RuntimeException('config.php oluşturulamadı. Klasör yazma izinlerini kontrol edin.');
            }

            if(!is_dir($root.'/uploads/products'))@mkdir($root.'/uploads/products',0755,true);
            if(!is_dir($root.'/uploads/banners'))@mkdir($root.'/uploads/banners',0755,true);
            if(!is_dir($root.'/uploads/brands'))@mkdir($root.'/uploads/brands',0755,true);
            @file_put_contents($lockPath,'TOPLUCA V3 '.date('c'));
            $success=true;
        }catch(Throwable $e){$error=$e->getMessage();}
    }
}

if(empty($_SESSION['_csrf']))$_SESSION['_csrf']=bin2hex(random_bytes(32));
$detectedBase='https://'.($_SERVER['HTTP_HOST']??'iksiryayincilik.com').rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/topluca/install.php')),'/');
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TOPLUCA V3 Kurulum</title><style>
*{box-sizing:border-box}body{margin:0;background:#f3f5f7;color:#171717;font-family:Inter,Arial,sans-serif}.wrap{width:min(920px,calc(100% - 28px));margin:45px auto}.card{background:#fff;border:1px solid #e4e7eb;border-radius:22px;box-shadow:0 24px 70px #0000000d;overflow:hidden}.head{padding:30px 34px;background:linear-gradient(120deg,#121212,#272727);color:#fff}.brand{font-size:30px;font-weight:950;letter-spacing:-1.5px}.brand b{color:#ff5a00}.head p{color:#bbb;margin-bottom:0}.body{padding:30px 34px}.checks{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;margin:18px 0}.check{padding:11px;border-radius:10px;background:#f7f8f9;font-size:12px}.check.ok{color:#157244}.check.no{background:#fff0f0;color:#a32929}.notice{padding:14px;border-radius:10px;background:#fff5ee;color:#87451e;margin:15px 0;font-size:13px}.error{padding:14px;border-radius:10px;background:#fff0f0;color:#a32929;margin:15px 0}.success{padding:25px;border-radius:15px;background:#ecfaf2;color:#155e35}.success a{color:#e54f00;font-weight:800}.tabs{display:flex;gap:8px;margin:20px 0}.tabs label{flex:1;padding:14px;border:1px solid #ddd;border-radius:11px;cursor:pointer}.grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}label.field{font-size:12px;font-weight:800;color:#4b5563}input{width:100%;display:block;margin-top:6px;padding:12px;border:1px solid #d9dde2;border-radius:9px;outline:0}input:focus{border-color:#ff5a00;box-shadow:0 0 0 3px #ff5a0015}.full{grid-column:1/-1}.danger{margin-top:22px;padding:15px;border:1px solid #ffd8d8;background:#fff8f8;border-radius:12px}.btn{width:100%;margin-top:20px;border:0;border-radius:10px;padding:14px;background:#ff5a00;color:#fff;font-weight:900;cursor:pointer}.muted{font-size:12px;color:#777}@media(max-width:680px){.wrap{margin:15px auto}.head,.body{padding:23px}.grid,.checks{grid-template-columns:1fr}.tabs{display:block}.tabs label{display:block;margin-bottom:8px}}
</style></head><body><div class="wrap"><div class="card"><div class="head"><div class="brand">TOPLU<b>CA</b> <span style="font-size:13px;color:#ff5a00">V3</span></div><p>Temiz kurulum • PHP/MySQL • FTP/Plesk uyumlu</p></div><div class="body">
<?php if($success):?><div class="success"><h2>Kurulum tamamlandı 🎉</h2><p>TOPLUCA V3 veritabanı ve örnek ürünler hazır.</p><p><b>WebYönet:</b> <code>emrah</code> / <code>emr321456</code></p><p><a href="<?=ih(($existingConfig['base_url']??$baseUrl).'/')?>">Siteyi aç →</a> &nbsp; <a href="<?=ih(($existingConfig['base_url']??$baseUrl).'/webyonet/')?>">WebYönet →</a></p><p><b>Kurulumdan sonra install.php dosyasını Plesk Dosyalar bölümünden sil.</b></p></div>
<?php else:?>
<h2>Kuruluma hazır mıyız?</h2><div class="checks"><?php foreach($checks as $name=>$ok):?><div class="check <?=$ok?'ok':'no'?>"><?=$ok?'✓':'×'?> <?=ih($name)?></div><?php endforeach;?></div>
<?php if($error):?><div class="error"><?=ih($error)?></div><?php endif;?>
<form method="post"><input type="hidden" name="_csrf" value="<?=ih($_SESSION['_csrf'])?>"><div class="tabs">
<?php if($existingConfig):?><label><input type="radio" name="mode" value="existing" <?=$mode==='existing'?'checked':''?>> <b>Mevcut veritabanı bağlantısını kullan</b><br><span class="muted">config.php içindeki bağlantıyla devam eder; şifreyi yeniden istemez.</span></label><?php endif;?>
<label><input type="radio" name="mode" value="new" <?=$mode==='new'?'checked':''?>> <b>Yeni bağlantı gir</b><br><span class="muted">Yeni veritabanı veya yeni klasör kurulumu için.</span></label></div>
<div class="grid"><label class="field">MySQL Sunucu<input name="db_host" value="localhost"></label><label class="field">Port<input name="db_port" value="3306"></label><label class="field">Veritabanı Adı<input name="db_name"></label><label class="field">Veritabanı Kullanıcısı<input name="db_user"></label><label class="field full">Veritabanı Şifresi<input type="password" name="db_pass"></label><label class="field full">Site Adresi<input name="base_url" value="<?=ih($detectedBase)?>"></label></div>
<div class="danger"><label><input style="width:auto;display:inline" type="checkbox" name="fresh" value="1"> <b>Mevcut TOPLUCA tablolarını tamamen sıfırla ve temiz kurulum yap</b></label><p class="muted">Bu seçenek yalnızca geliştirme veritabanında kullanılmalı. Mevcut demo siparişleri/ürünleri siler.</p><label class="field">Sıfırlama onayı<input name="fresh_confirm" placeholder="Sıfırlamak istiyorsanız TOPLUCA yazın"></label></div>
<button class="btn">TOPLUCA V3'Ü KUR</button></form><div class="notice">Not: FTP bağlantısı bu kurulumdan bağımsızdır. Bu dosya yalnızca web klasörü ve MySQL üzerinde işlem yapar; Plesk FTP servisini açıp kapatamaz.</div>
<?php endif;?></div></div></div></body></html>