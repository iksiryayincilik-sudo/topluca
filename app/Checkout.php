<?php
declare(strict_types=1);

function checkout_state(): array {
    return $_SESSION['checkout'] ?? ['address'=>null,'shipping_code'=>null,'payment_method'=>null,'note'=>''];
}
function checkout_save(array $state): void { $_SESSION['checkout']=$state; }
function checkout_reset(): void { unset($_SESSION['checkout']); }

function checkout_prefill_address(): array {
    $user=current_user();
    if($user){
        $s=db()->prepare('SELECT * FROM user_addresses WHERE user_id=? ORDER BY is_default_shipping DESC,id DESC LIMIT 1');$s->execute([(int)$user['id']]);$a=$s->fetch();
        if($a)return [
            'first_name'=>$a['first_name'],'last_name'=>$a['last_name'],'email'=>$user['email'],'phone'=>$a['phone']?:$user['phone'],
            'city'=>$a['city'],'district'=>$a['district'],'neighborhood'=>$a['neighborhood'],'address'=>$a['address'],'postal_code'=>$a['postal_code'],
            'billing_same_as_shipping'=>1,'billing_company'=>$a['company'],'billing_tax_office'=>$a['tax_office'],'billing_tax_no'=>$a['tax_no']
        ];
        return ['first_name'=>$user['first_name'],'last_name'=>$user['last_name'],'email'=>$user['email'],'phone'=>$user['phone'],'city'=>'','district'=>'','neighborhood'=>'','address'=>'','postal_code'=>'','billing_same_as_shipping'=>1,'billing_company'=>'','billing_tax_office'=>'','billing_tax_no'=>''];
    }
    return ['first_name'=>'','last_name'=>'','email'=>'','phone'=>'','city'=>'','district'=>'','neighborhood'=>'','address'=>'','postal_code'=>'','billing_same_as_shipping'=>1,'billing_company'=>'','billing_tax_office'=>'','billing_tax_no'=>''];
}

function checkout_validate_address(array $input): array {
    $address=[
        'first_name'=>trim((string)($input['first_name']??'')),
        'last_name'=>trim((string)($input['last_name']??'')),
        'email'=>mb_strtolower(trim((string)($input['email']??'')),'UTF-8'),
        'phone'=>trim((string)($input['phone']??'')),
        'city'=>trim((string)($input['city']??'')),
        'district'=>trim((string)($input['district']??'')),
        'neighborhood'=>trim((string)($input['neighborhood']??'')),
        'address'=>trim((string)($input['address']??'')),
        'postal_code'=>trim((string)($input['postal_code']??'')),
        'billing_same_as_shipping'=>!empty($input['billing_same_as_shipping'])?1:0,
        'billing_company'=>trim((string)($input['billing_company']??'')),
        'billing_tax_office'=>trim((string)($input['billing_tax_office']??'')),
        'billing_tax_no'=>trim((string)($input['billing_tax_no']??'')),
    ];
    $errors=[];
    if(mb_strlen($address['first_name'])<2)$errors[]='Ad alanını kontrol edin.';
    if(mb_strlen($address['last_name'])<2)$errors[]='Soyad alanını kontrol edin.';
    if(!filter_var($address['email'],FILTER_VALIDATE_EMAIL))$errors[]='Geçerli bir e-posta adresi girin.';
    if(mb_strlen(preg_replace('/\D+/','',$address['phone']))<10)$errors[]='Telefon numarasını kontrol edin.';
    if($address['city']==='')$errors[]='İl seçimi/girişi zorunludur.';
    if($address['district']==='')$errors[]='İlçe zorunludur.';
    if(mb_strlen($address['address'])<8)$errors[]='Açık adresi biraz daha ayrıntılı girin.';
    if(!$address['billing_same_as_shipping'] && $address['billing_company']!=='' && ($address['billing_tax_office']===''||$address['billing_tax_no']===''))$errors[]='Kurumsal fatura için vergi dairesi ve vergi numarası gereklidir.';
    return [$address,$errors];
}

function place_order(array $address,array $shipping,string $paymentMethod,string $note=''): array {
    $items=cart_items();
    if(!$items)throw new RuntimeException('Sepetiniz boş.');
    $subtotal=cart_subtotal();

    $freshShipping=find_shipping_option((string)$shipping['code'],$address,$items,$subtotal);
    if(!$freshShipping)throw new RuntimeException('Teslimat seçeneği artık kullanılamıyor. Lütfen teslimat adımına dönün.');

    $paymentAllowed=['bank_transfer'];
    if((int)setting('card_payment_enabled',0)===1)$paymentAllowed[]='credit_card';
    if(!in_array($paymentMethod,$paymentAllowed,true))throw new RuntimeException('Ödeme yöntemi kullanılamıyor.');

    $pdo=db();$pdo->beginTransaction();
    try{
        $locked=[];
        foreach($items as $item){
            $s=$pdo->prepare('SELECT * FROM products WHERE id=? AND is_active=1 FOR UPDATE');$s->execute([(int)$item['id']]);$p=$s->fetch();
            if(!$p)throw new RuntimeException($item['name'].' artık satışta değil.');
            $available=(int)$p['stock']-(int)$p['reserved_stock'];
            if($available<(int)$item['quantity'])throw new RuntimeException($p['name'].' için yeterli stok kalmadı.');
            $price=effective_price($p,(int)$item['quantity']);
            $locked[]=['row'=>$p,'qty'=>(int)$item['quantity'],'price'=>$price,'line_total'=>round($price*(int)$item['quantity'],2)];
        }
        $reSubtotal=round(array_sum(array_column($locked,'line_total')),2);
        if(abs($reSubtotal-$subtotal)>0.01)throw new RuntimeException('Sepet fiyatlarında değişiklik oldu. Sepete dönüp fiyatları kontrol edin.');

        $shippingFee=(float)$freshShipping['fee'];
        $grand=round($reSubtotal+$shippingFee,2);
        $orderNo='TPL'.date('ymd').strtoupper(substr(bin2hex(random_bytes(5)),0,8));
        $claimToken=user_logged_in()?null:bin2hex(random_bytes(24));
        $claimHash=$claimToken?password_hash($claimToken,PASSWORD_DEFAULT):null;
        $user=current_user();

        $s=$pdo->prepare("INSERT INTO orders(order_no,user_id,customer_email,customer_phone,shipping_first_name,shipping_last_name,shipping_city,shipping_district,shipping_neighborhood,shipping_address,shipping_postal_code,billing_same_as_shipping,billing_company,billing_tax_office,billing_tax_no,subtotal,discount_total,shipping_total,grand_total,delivery_method,shipping_method_code,shipping_company_name,payment_method,payment_status,status,customer_note,guest_claim_token_hash,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,?,?,?,?,?,?,?,'pending','new',?,?,NOW(),NOW())");
        $s->execute([
            $orderNo,$user['id']??null,$address['email'],$address['phone'],$address['first_name'],$address['last_name'],$address['city'],$address['district'],$address['neighborhood'],$address['address'],$address['postal_code'],$address['billing_same_as_shipping'],$address['billing_company'],$address['billing_tax_office'],$address['billing_tax_no'],$reSubtotal,$shippingFee,$grand,$freshShipping['type'],$freshShipping['code'],$freshShipping['name'],$paymentMethod,$note,$claimHash
        ]);
        $orderId=(int)$pdo->lastInsertId();

        $insertItem=$pdo->prepare('INSERT INTO order_items(order_id,product_id,product_name,sku,quantity,unit_price,unit_cost,vat_rate,discount_total,line_total) VALUES(?,?,?,?,?,?,?,?,0,?)');
        foreach($locked as $li){
            $p=$li['row'];
            $insertItem->execute([$orderId,$p['id'],$p['name'],$p['sku'],$li['qty'],$li['price'],$p['purchase_price'],$p['vat_rate'],$li['line_total']]);
            $pdo->prepare('UPDATE products SET stock=stock-?,sales_count=sales_count+?,updated_at=NOW() WHERE id=?')->execute([$li['qty'],$li['qty'],$p['id']]);
            $pdo->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,unit_cost,reference_type,reference_id,notes,created_at) VALUES(?,'sale',?,?, 'order',?,'Sipariş satışı',NOW())")->execute([$p['id'],-$li['qty'],$p['purchase_price'],$orderId]);
        }

        $pdo->prepare("INSERT INTO order_status_history(order_id,status,note,created_at) VALUES(?,'new','Sipariş oluşturuldu',NOW())")->execute([$orderId]);
        $pdo->prepare("INSERT INTO payments(order_id,provider,amount,status,created_at,updated_at) VALUES(?,?,?,'pending',NOW(),NOW())")->execute([$orderId,$paymentMethod==='bank_transfer'?'bank_transfer':(string)setting('card_payment_provider','card'),$grand]);
        $pdo->prepare('UPDATE carts SET customer_email=?,converted_order_id=?,subtotal=?,updated_at=NOW() WHERE id=?')->execute([$address['email'],$orderId,$reSubtotal,cart_id()]);

        if($user){
            $s=$pdo->prepare('SELECT COUNT(*) FROM user_addresses WHERE user_id=?');$s->execute([(int)$user['id']]);
            if((int)$s->fetchColumn()===0){
                $pdo->prepare("INSERT INTO user_addresses(user_id,title,first_name,last_name,phone,company,tax_office,tax_no,city,district,neighborhood,address,postal_code,is_default_shipping,is_default_billing,created_at,updated_at) VALUES(?,'Adresim',?,?,?,?,?,?,?,?,?,?,?,?,1,1,NOW(),NOW())")->execute([(int)$user['id'],$address['first_name'],$address['last_name'],$address['phone'],$address['billing_company'],$address['billing_tax_office'],$address['billing_tax_no'],$address['city'],$address['district'],$address['neighborhood'],$address['address'],$address['postal_code']]);
            }
        }

        $pdo->commit();
        $_SESSION['last_order']=['id'=>$orderId,'no'=>$orderNo,'claim_token'=>$claimToken,'email'=>$address['email']];
        unset($_SESSION['cart_id']);checkout_reset();
        return ['id'=>$orderId,'order_no'=>$orderNo,'grand_total'=>$grand,'claim_token'=>$claimToken];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
