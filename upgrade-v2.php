<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
require __DIR__.'/database/schema_v2.php';

$token=(string)($_GET['token']??'');
$expected=hash('sha256',($config['db']['name']??'').'|'.($config['base_url']??'').'|topluca-v2');
if(!hash_equals($expected,$token)){
    http_response_code(403);
    $safe=hash('sha256',($config['db']['name']??'').'|'.($config['base_url']??'').'|topluca-v2');
    exit('<!doctype html><meta charset="utf-8"><style>body{font-family:Arial;background:#f5f5f5;padding:40px}.b{max-width:760px;margin:auto;background:#fff;padding:30px;border-radius:16px}</style><div class="b"><h1>TOPLUCA V2 Yükseltme</h1><p>Güvenli yükseltme bağlantısını kullanın:</p><code>'.h(url('upgrade-v2.php?token='.$safe)).'</code></div>');
}

try{
    topluca_apply_v2_schema(db());
    echo '<!doctype html><meta charset="utf-8"><style>body{font-family:Arial;background:#f5f5f5;padding:40px}.b{max-width:760px;margin:auto;background:#fff;padding:30px;border-radius:16px}h1{color:#ff5a00}</style><div class="b"><h1>TOPLUCA V2 veritabanı hazır.</h1><p>Üyelik, adresler, kademeli fiyat, ürün özellikleri, banner, tedarikçi, satın alma, stok hareketi, ödeme, sipariş geçmişi, arama eş anlamlıları ve Ankara ilçe tabloları eklendi.</p><p><b>Şimdi bu dosyayı sunucudan silin.</b></p></div>';
}catch(Throwable $e){
    http_response_code(500);
    echo '<pre>'.h($e->getMessage()).'</pre>';
}
