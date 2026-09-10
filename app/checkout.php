<?php
declare(strict_types=1);

function checkout_data(): array { return $_SESSION['checkout']??[]; }
function checkout_set(string $key,mixed $value): void { $_SESSION['checkout'][$key]=$value; }
function checkout_clear(): void { unset($_SESSION['checkout']); }
function normalize_city(string $city): string {
    $city=trim(mb_strtolower($city,'UTF-8'));
    $map=['İ'=>'i','I'=>'i'];
    return strtr($city,$map);
}
function is_ankara_address(array $address): bool { return normalize_city((string)($address['city']??''))==='ankara'; }
function all_items_same_day_eligible(array $items): bool {
    if(!$items) return false;
    foreach($items as $i) if(!(int)($i['same_day_delivery']??0)) return false;
    return true;
}
function active_same_day_district(string $district): bool {
    if(trim($district)==='') return false;
    $s=db()->prepare('SELECT COUNT(*) FROM same_day_districts WHERE is_active=1 AND LOWER(district_name)=LOWER(?)');
    $s->execute([trim($district)]);
    return (int)$s->fetchColumn()>0;
}
function same_day_status(array $address,array $items,float $subtotal): array {
    $enabled=(int)setting('same_day_enabled',1)===1;
    $cutoff=(string)setting('same_day_cutoff','15:00');
    $freeLimit=(float)setting('same_day_free_limit',2000);
    $paidFee=(float)setting('same_day_under_limit_fee',149.90);
    $capacity=(int)setting('same_day_daily_capacity',50);
    $used=0;
    try{$used=(int)db()->query("SELECT COUNT(*) FROM orders WHERE delivery_method='same_day' AND DATE(created_at)=CURDATE() AND status<>'cancelled'")->fetchColumn();}catch(Throwable){}
    $reasons=[];
    if(!$enabled)$reasons[]='Aynı gün teslimat şu anda kapalı.';
    if(!is_ankara_address($address))$reasons[]='Teslimat adresi Ankara değil.';
    if(is_ankara_address($address) && !active_same_day_district((string)($address['district']??'')))$reasons[]='Seçilen ilçe aynı gün teslimat bölgesinde değil.';
    if(!all_items_same_day_eligible($items))$reasons[]='Sepette aynı gün teslimata uygun olmayan ürün var.';
    if(date('H:i')>$cutoff)$reasons[]='Bugünkü son sipariş saati geçti.';
    if($capacity>0 && $used>=$capacity)$reasons[]='Bugünkü aynı gün teslimat kapasitesi doldu.';
    return [
        'eligible'=>!$reasons,
        'reasons'=>$reasons,
        'cutoff'=>$cutoff,
        'free_limit'=>$freeLimit,
        'remaining'=>max(0,$freeLimit-$subtotal),
        'fee'=>$subtotal>=$freeLimit?0:$paidFee,
        'capacity'=>$capacity,
        'used'=>$used
    ];
}
function standard_shipping_options(float $subtotal): array {
    $freeLimit=(float)setting('free_shipping_limit',500);
    $allFree=true;$items=cart_items(); if(!$items)$allFree=false;
    foreach($items as $i) if(($i['shipping_policy']??'standard')!=='free'){$allFree=false;break;}
    $rows=db()->query('SELECT * FROM shipping_companies WHERE is_active=1 ORDER BY price,name')->fetchAll();
    foreach($rows as &$r){$r['calculated_price']=($allFree||$subtotal>=$freeLimit)?0:(float)$r['price'];}
    return $rows;
}
function checkout_step_url(string $step): string { return url('checkout.php?step='.$step); }
