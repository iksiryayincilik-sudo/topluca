<?php
declare(strict_types=1);

function cart_all_free_shipping(array $items): bool {
    if (!$items) return false;
    foreach ($items as $item) if (($item['shipping_policy'] ?? 'standard') !== 'free') return false;
    return true;
}

function cart_all_same_day(array $items): bool {
    if (!$items) return false;
    foreach ($items as $item) if ((int)($item['same_day_delivery'] ?? 0) !== 1) return false;
    return true;
}

function same_day_status(array $address,array $items,float $subtotal): array {
    $result=[
        'eligible'=>false,
        'code'=>'same_day',
        'name'=>'TOPLUCA Ankara Aynı Gün',
        'fee'=>0.0,
        'reason'=>'',
        'meta'=>[]
    ];

    if ((int)setting('same_day_enabled',1)!==1) {
        $result['reason']='Aynı gün teslimat şu anda kapalı.'; return $result;
    }

    if (tr_normalize((string)($address['city']??''))!=='ankara') {
        $result['reason']='Aynı gün teslimat yalnızca Ankara adreslerinde kullanılabilir.'; return $result;
    }

    $district=trim((string)($address['district']??''));
    if ($district==='') {$result['reason']='İlçe bilgisi gerekli.';return $result;}

    $s=db()->prepare('SELECT * FROM same_day_districts WHERE is_active=1 AND LOWER(district_name)=LOWER(?) LIMIT 1');
    $s->execute([$district]);$districtRow=$s->fetch();
    if(!$districtRow){$result['reason']=$district.' ilçesi aynı gün teslimat bölgesinde değil.';return $result;}

    $weekday=(int)date('N');
    $allowed=array_map('intval',array_filter(explode(',',(string)setting('same_day_weekdays','1,2,3,4,5,6'))));
    if(!in_array($weekday,$allowed,true)){$result['reason']='Bugün aynı gün teslimat yapılmıyor.';return $result;}

    $s=db()->prepare('SELECT COUNT(*) FROM same_day_holidays WHERE holiday_date=CURDATE() AND is_active=1');$s->execute();
    if((int)$s->fetchColumn()>0){$result['reason']='Bugün aynı gün teslimat hizmeti kapalı.';return $result;}

    $cutoff=(string)setting('same_day_cutoff','15:00');
    if(date('H:i')>$cutoff){$result['reason']='Bugünkü aynı gün teslimat sipariş saati '.$cutoff.' itibarıyla sona erdi.';return $result;}

    if(!cart_all_same_day($items)){$result['reason']='Sepetinizde aynı gün teslimata uygun olmayan ürün var.';return $result;}

    $globalCapacity=(int)setting('same_day_daily_capacity',50);
    $districtCapacity=$districtRow['daily_capacity']!==null?(int)$districtRow['daily_capacity']:null;
    $s=db()->prepare("SELECT COUNT(*) FROM orders WHERE delivery_method='same_day' AND status<>'cancelled' AND DATE(created_at)=CURDATE()");$s->execute();$used=(int)$s->fetchColumn();
    if($used>=$globalCapacity){$result['reason']='Bugünkü aynı gün teslimat kapasitesi doldu.';return $result;}
    if($districtCapacity!==null){
        $s=db()->prepare("SELECT COUNT(*) FROM orders WHERE delivery_method='same_day' AND status<>'cancelled' AND DATE(created_at)=CURDATE() AND LOWER(shipping_district)=LOWER(?)");$s->execute([$district]);
        if((int)$s->fetchColumn()>=$districtCapacity){$result['reason']=$district.' için bugünkü teslimat kapasitesi doldu.';return $result;}
    }

    $freeLimit=(float)setting('same_day_free_limit',2000);
    $extra=(float)$districtRow['extra_fee'];
    if($subtotal>=$freeLimit){$fee=$extra;$remaining=0.0;}
    else{
        $remaining=max(0,$freeLimit-$subtotal);
        if((int)setting('same_day_allow_paid_under_limit',1)!==1){$result['reason']=money($remaining).' daha ekleyerek aynı gün teslimat limitine ulaşabilirsiniz.';return $result;}
        $fee=(float)setting('same_day_under_limit_fee',149.90)+$extra;
    }

    $result['eligible']=true;
    $result['fee']=round($fee,2);
    $result['meta']=[
        'district'=>$district,
        'cutoff'=>$cutoff,
        'free_limit'=>$freeLimit,
        'remaining_to_free'=>$remaining,
        'capacity_remaining'=>max(0,$globalCapacity-$used)
    ];
    return $result;
}

function shipping_options_for(array $address,array $items,float $subtotal): array {
    $options=[];
    $globalFree=(float)setting('free_shipping_limit',750);
    $allFree=cart_all_free_shipping($items);

    $carriers=db()->query('SELECT * FROM shipping_carriers WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();
    foreach($carriers as $carrier){
        $limit=$carrier['free_shipping_limit']!==null?(float)$carrier['free_shipping_limit']:$globalFree;
        $fee=($allFree||$subtotal>=$limit)?0.0:(float)$carrier['base_fee'];
        $options[]=[
            'eligible'=>true,
            'type'=>'cargo',
            'code'=>'cargo:'.$carrier['code'],
            'carrier_id'=>(int)$carrier['id'],
            'name'=>$carrier['name'],
            'fee'=>round($fee,2),
            'estimated_days'=>$carrier['estimated_days']?:'1-4 iş günü',
            'reason'=>'',
            'meta'=>['free_limit'=>$limit,'remaining_to_free'=>max(0,$limit-$subtotal)]
        ];
    }

    $same=same_day_status($address,$items,$subtotal);
    $same['type']='same_day';
    $same['estimated_days']=$same['eligible']?'Bugün teslim':'Uygun değil';
    $options[]=$same;
    return $options;
}

function find_shipping_option(string $code,array $address,array $items,float $subtotal): ?array {
    foreach(shipping_options_for($address,$items,$subtotal) as $option) if(($option['code']??'')===$code&&($option['eligible']??false)) return $option;
    return null;
}
