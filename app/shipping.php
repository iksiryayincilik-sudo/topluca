<?php
declare(strict_types=1);

function standard_shipping_options(float $subtotal): array{
    $s=db()->query("SELECT * FROM shipping_companies WHERE is_active=1 ORDER BY sort_order,base_fee");$rows=$s->fetchAll();$global=(float)setting('free_shipping_limit',750);foreach($rows as &$r){$limit=$r['free_shipping_limit']!==null?(float)$r['free_shipping_limit']:$global;$r['calculated_fee']=$subtotal>=$limit?0:(float)$r['base_fee'];$r['free_limit']=$limit;}return $rows;
}
function same_day_quote(array $address,array $items,float $subtotal): array{
    $result=['available'=>false,'reason'=>'','fee'=>0.0,'free_limit'=>(float)setting('same_day_free_limit',2000),'remaining'=>0.0,'district'=>null,'cutoff'=>(string)setting('same_day_cutoff','15:00')];
    if((int)setting('same_day_enabled',1)!==1){$result['reason']='Aynı gün teslimat şu anda kapalı.';return $result;}
    if(normalize_tr((string)($address['city']??''))!=='ankara'){$result['reason']='Aynı gün teslimat yalnızca Ankara adreslerinde kullanılabilir.';return $result;}
    $district=trim((string)($address['district']??''));$s=db()->prepare('SELECT * FROM same_day_districts WHERE is_active=1 AND LOWER(district_name)=LOWER(?) LIMIT 1');$s->execute([$district]);$d=$s->fetch();if(!$d){$result['reason']='Bu Ankara ilçesi şu anda aynı gün teslimat bölgesinde değil.';return $result;}$result['district']=$d;
    $week=(int)date('N');$allowed=array_map('intval',array_filter(explode(',',(string)setting('same_day_weekdays','1,2,3,4,5,6'))));if(!in_array($week,$allowed,true)){$result['reason']='Bugün aynı gün teslimat yapılmıyor.';return $result;}
    $h=db()->prepare('SELECT COUNT(*) FROM same_day_holidays WHERE holiday_date=CURDATE() AND is_active=1');$h->execute();if((int)$h->fetchColumn()>0){$result['reason']='Bugün aynı gün teslimat için kapalı gün.';return $result;}
    if(date('H:i')>$result['cutoff']){$result['reason']='Bugünkü aynı gün teslimat sipariş saati sona erdi.';return $result;}
    foreach($items as $item){if(!(int)$item['same_day_delivery']){$result['reason']='Sepette aynı gün teslimata uygun olmayan ürün bulunuyor.';return $result;}if((int)$item['stock']<(int)$item['quantity']){$result['reason']='Sepette stok durumu değişen ürün bulunuyor.';return $result;}}
    $capacity=$d['daily_capacity']!==null?(int)$d['daily_capacity']:(int)setting('same_day_daily_capacity',60);$s=db()->prepare("SELECT COUNT(*) FROM orders WHERE delivery_method='same_day' AND DATE(created_at)=CURDATE() AND status<>'cancelled'");$s->execute();if((int)$s->fetchColumn()>=$capacity){$result['reason']='Bugünkü aynı gün teslimat kapasitesi doldu.';return $result;}
    $limit=$result['free_limit'];$extra=(float)$d['extra_fee'];if($subtotal>=$limit){$result['fee']=$extra;}else{$remaining=round($limit-$subtotal,2);$result['remaining']=$remaining;if((int)setting('same_day_allow_paid_under_limit',1)!==1){$result['reason']=money($remaining).' daha ekleyerek aynı gün teslimat limitine ulaşabilirsiniz.';return $result;}$result['fee']=round((float)setting('same_day_under_limit_fee',149.90)+$extra,2);}
    $result['available']=true;return $result;
}
function shipping_selection_from_code(string $code,float $subtotal,array $address,array $items): ?array{
    if($code==='same_day'){$q=same_day_quote($address,$items,$subtotal);if(!$q['available'])return null;return ['code'=>'same_day','name'=>'TOPLUCA Ankara Aynı Gün','fee'=>$q['fee'],'detail'=>$q];}
    foreach(standard_shipping_options($subtotal) as $o){if('cargo_'.$o['id']===$code)return ['code'=>$code,'name'=>$o['name'],'fee'=>(float)$o['calculated_fee'],'company_id'=>(int)$o['id'],'detail'=>$o];}
    return null;
}
