<?php
declare(strict_types=1);

function v5_normalize_files(array $files): array{
    if(!isset($files['name']))return [];
    if(!is_array($files['name']))return [$files];
    $out=[];
    foreach($files['name'] as $i=>$name)$out[]=['name'=>$name,'type'=>$files['type'][$i]??'','tmp_name'=>$files['tmp_name'][$i]??'','error'=>$files['error'][$i]??UPLOAD_ERR_NO_FILE,'size'=>$files['size'][$i]??0];
    return $out;
}

if($_SERVER['REQUEST_METHOD']!=='POST')return;
$action=$_POST['action']??'';
$v5Actions=['save_product_v5','delete_product_image_v5','set_primary_image_v5','save_category_v5','save_site_identity','save_theme','save_theme_schedule','delete_theme_schedule','save_banner_v5','delete_banner_v5','save_feature_box','delete_feature_box'];
if(!in_array($action,$v5Actions,true))return;
csrf_check();

try{
    if($action==='save_product_v5'){
        $id=(int)($_POST['id']??0);$name=trim($_POST['name']??'');$sku=trim($_POST['sku']??'');
        if($name===''||$sku==='')throw new RuntimeException('Ürün adı ve stok kodu zorunludur.');
        $slug=trim($_POST['slug']??'')?:slugify($name);
        $categoryId=(int)($_POST['category_id']??0);if(!$categoryId)throw new RuntimeException('Kategori seçmelisiniz.');
        $brandId=($_POST['brand_id']??'')!==''?(int)$_POST['brand_id']:null;
        $data=[$categoryId,$brandId,$name,$slug,$sku,trim($_POST['barcode']??'')?:null,trim($_POST['isbn']??'')?:null,trim($_POST['model_number']??'')?:null,trim($_POST['short_description']??'')?:null,trim($_POST['description']??'')?:null,(float)($_POST['purchase_price']??0),(float)($_POST['sale_price']??0),($_POST['compare_price']??'')!==''?(float)$_POST['compare_price']:null,(float)($_POST['vat_rate']??20),(int)($_POST['stock']??0),(int)($_POST['critical_stock']??5),($_POST['weight_kg']??'')!==''?(float)$_POST['weight_kg']:null,($_POST['desi']??'')!==''?(float)$_POST['desi']:null,$_POST['shipping_policy']??'standard',!empty($_POST['same_day_delivery'])?1:0,!empty($_POST['is_active'])?1:0,!empty($_POST['is_featured'])?1:0,!empty($_POST['is_new'])?1:0,!empty($_POST['is_bestseller'])?1:0,trim($_POST['meta_title']??'')?:null,trim($_POST['meta_description']??'')?:null,trim($_POST['meta_keywords']??'')?:null];
        if($id){
            $data[]=$id;
            db()->prepare('UPDATE products SET category_id=?,brand_id=?,name=?,slug=?,sku=?,barcode=?,isbn=?,model_number=?,short_description=?,description=?,purchase_price=?,sale_price=?,compare_price=?,vat_rate=?,stock=?,critical_stock=?,weight_kg=?,desi=?,shipping_policy=?,same_day_delivery=?,is_active=?,is_featured=?,is_new=?,is_bestseller=?,meta_title=?,meta_description=?,meta_keywords=?,updated_at=NOW() WHERE id=?')->execute($data);
            admin_log('product_update','product',$id,$name);
        }else{
            db()->prepare('INSERT INTO products(category_id,brand_id,name,slug,sku,barcode,isbn,model_number,short_description,description,purchase_price,sale_price,compare_price,vat_rate,stock,critical_stock,weight_kg,desi,shipping_policy,same_day_delivery,is_active,is_featured,is_new,is_bestseller,meta_title,meta_description,meta_keywords,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())')->execute($data);
            $id=(int)db()->lastInsertId();admin_log('product_create','product',$id,$name);
        }
        db()->prepare('DELETE FROM product_attributes WHERE product_id=?')->execute([$id]);
        $attrLines=preg_split('/\r\n|\r|\n/',trim($_POST['attributes_text']??''));$as=db()->prepare('INSERT INTO product_attributes(product_id,attribute_name,attribute_value,filter_key,sort_order) VALUES(?,?,?,?,?)');$sort=1;
        foreach($attrLines as $line){if(strpos($line,':')===false)continue;[$k,$v]=array_map('trim',explode(':',$line,2));if($k!==''&&$v!=='')$as->execute([$id,$k,$v,slugify($k),$sort++]);}
        db()->prepare('DELETE FROM product_price_tiers WHERE product_id=?')->execute([$id]);$mins=$_POST['tier_min']??[];$maxs=$_POST['tier_max']??[];$prices=$_POST['tier_price']??[];$ts=db()->prepare('INSERT INTO product_price_tiers(product_id,min_qty,max_qty,unit_price,is_active) VALUES(?,?,?,?,1)');
        foreach($mins as $i=>$min){$min=(int)$min;$price=(float)($prices[$i]??0);if($min>0&&$price>0)$ts->execute([$id,$min,($maxs[$i]??'')!==''?(int)$maxs[$i]:null,$price]);}
        if(!empty($_FILES['images'])){
            foreach(v5_normalize_files($_FILES['images']) as $file){if(($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)continue;$path=upload_image($file,'products',10);if(!$path)continue;$primary=(int)db()->query('SELECT COUNT(*) FROM product_images WHERE product_id='.(int)$id)->fetchColumn()===0?1:0;$maxSort=(int)db()->query('SELECT COALESCE(MAX(sort_order),0) FROM product_images WHERE product_id='.(int)$id)->fetchColumn();db()->prepare('INSERT INTO product_images(product_id,image_path,alt_text,sort_order,is_primary,created_at) VALUES(?,?,?,?,?,NOW())')->execute([$id,$path,$name,$maxSort+1,$primary]);}
        }
        flash('success','Ürün, görseller, SEO, özellikler ve fiyat kademeleri kaydedildi.');redirect_to('webyonet/?page=product_edit&id='.$id);
    }

    if($action==='delete_product_image_v5'){
        $imageId=(int)($_POST['image_id']??0);$productId=(int)($_POST['product_id']??0);$s=db()->prepare('SELECT * FROM product_images WHERE id=? AND product_id=?');$s->execute([$imageId,$productId]);$im=$s->fetch();if($im){db()->prepare('DELETE FROM product_images WHERE id=?')->execute([$imageId]);$file=dirname(__DIR__).'/'.$im['image_path'];if(is_file($file))@unlink($file);if((int)$im['is_primary']===1){$next=db()->prepare('SELECT id FROM product_images WHERE product_id=? ORDER BY sort_order,id LIMIT 1');$next->execute([$productId]);$nid=$next->fetchColumn();if($nid)db()->prepare('UPDATE product_images SET is_primary=1 WHERE id=?')->execute([(int)$nid]);}admin_log('product_image_delete','product',$productId,$im['image_path']);}
        flash('success','Görsel kaldırıldı.');redirect_to('webyonet/?page=product_edit&id='.$productId);
    }

    if($action==='set_primary_image_v5'){
        $imageId=(int)($_POST['image_id']??0);$productId=(int)($_POST['product_id']??0);db()->prepare('UPDATE product_images SET is_primary=0 WHERE product_id=?')->execute([$productId]);db()->prepare('UPDATE product_images SET is_primary=1 WHERE id=? AND product_id=?')->execute([$imageId,$productId]);flash('success','Ana ürün görseli değiştirildi.');redirect_to('webyonet/?page=product_edit&id='.$productId);
    }

    if($action==='save_category_v5'){
        $id=(int)($_POST['id']??0);$name=trim($_POST['name']??'');if($name==='')throw new RuntimeException('Kategori adı zorunludur.');$slug=trim($_POST['slug']??'')?:slugify($name);$parent=($_POST['parent_id']??'')!==''?(int)$_POST['parent_id']:null;$image=trim($_POST['current_image']??'');if(!empty($_FILES['image']['name']))$image=upload_image($_FILES['image'],'categories',8)?:$image;
        $vals=[$parent,$name,$slug,trim($_POST['icon']??''),$image?:null,trim($_POST['description']??''),(int)($_POST['sort_order']??0),!empty($_POST['show_in_menu'])?1:0,!empty($_POST['is_active'])?1:0,trim($_POST['meta_title']??'')?:null,trim($_POST['meta_description']??'')?:null,trim($_POST['meta_keywords']??'')?:null];
        if($id){$vals[]=$id;db()->prepare('UPDATE categories SET parent_id=?,name=?,slug=?,icon=?,image=?,description=?,sort_order=?,show_in_menu=?,is_active=?,meta_title=?,meta_description=?,meta_keywords=? WHERE id=?')->execute($vals);}else{db()->prepare('INSERT INTO categories(parent_id,name,slug,icon,image,description,sort_order,show_in_menu,is_active,meta_title,meta_description,meta_keywords) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')->execute($vals);$id=(int)db()->lastInsertId();}
        admin_log('category_save','category',$id,$name);flash('success','Kategori ve SEO bilgileri kaydedildi.');redirect_to('webyonet/?page=categories&edit='.$id);
    }

    if($action==='save_site_identity'){
        $logo=trim((string)setting('site_logo',''));$favicon=trim((string)setting('site_favicon',''));$og=trim((string)setting('site_og_image',''));
        if(!empty($_FILES['site_logo']['name']))$logo=upload_image($_FILES['site_logo'],'site',10)?:$logo;
        if(!empty($_FILES['site_favicon']['name']))$favicon=upload_image($_FILES['site_favicon'],'site',4)?:$favicon;
        if(!empty($_FILES['site_og_image']['name']))$og=upload_image($_FILES['site_og_image'],'site',10)?:$og;
        $pairs=['site_title'=>trim($_POST['site_title']??''),'site_description'=>trim($_POST['site_description']??''),'site_keywords'=>trim($_POST['site_keywords']??''),'site_logo'=>$logo,'site_favicon'=>$favicon,'site_og_image'=>$og,'company_name'=>trim($_POST['company_name']??''),'company_email'=>trim($_POST['company_email']??''),'company_phone'=>trim($_POST['company_phone']??''),'company_address'=>trim($_POST['company_address']??''),'footer_description'=>trim($_POST['footer_description']??''),'banner_autoplay'=>!empty($_POST['banner_autoplay'])?'1':'0','banner_interval'=>(string)max(2500,min(12000,(int)($_POST['banner_interval']??5500))),'bank_name'=>trim($_POST['bank_name']??''),'bank_account_name'=>trim($_POST['bank_account_name']??''),'bank_iban'=>trim($_POST['bank_iban']??''),'bank_branch'=>trim($_POST['bank_branch']??''),'bank_payment_note'=>trim($_POST['bank_payment_note']??'')];
        foreach($pairs as $k=>$v)set_setting($k,$v);admin_log('site_identity_update','settings',null,'Site kimliği, SEO ve ödeme bilgileri güncellendi.');flash('success','Site kimliği, SEO, logo ve ödeme bilgileri kaydedildi.');redirect_to('webyonet/?page=settings');
    }

    if($action==='save_theme'){
        $theme=$_POST['active_theme']??'standard';if(!array_key_exists($theme,builtin_themes()))throw new RuntimeException('Geçersiz tema.');set_setting('active_theme',$theme);set_setting('theme_auto_schedule',!empty($_POST['theme_auto_schedule'])?'1':'0');admin_log('theme_update','theme',null,$theme);flash('success','Aktif tema güncellendi.');redirect_to('webyonet/?page=themes');
    }

    if($action==='save_theme_schedule'){
        $name=trim($_POST['name']??'');$theme=$_POST['theme_key']??'standard';$start=$_POST['start_at']??'';$end=$_POST['end_at']??'';if($name===''||$start===''||$end==='')throw new RuntimeException('Tema adı, başlangıç ve bitiş tarihi zorunludur.');if(!array_key_exists($theme,builtin_themes()))throw new RuntimeException('Geçersiz tema.');if(strtotime($end)<=strtotime($start))throw new RuntimeException('Bitiş tarihi başlangıç tarihinden sonra olmalıdır.');db()->prepare('INSERT INTO theme_schedules(name,theme_key,start_at,end_at,priority,is_active,created_at,updated_at) VALUES(?,?,?,?,?,1,NOW(),NOW())')->execute([$name,$theme,$start,$end,(int)($_POST['priority']??10)]);admin_log('theme_schedule_create','theme',null,$name);flash('success','Tema takvimi eklendi.');redirect_to('webyonet/?page=themes');
    }

    if($action==='delete_theme_schedule'){$id=(int)($_POST['id']??0);db()->prepare('DELETE FROM theme_schedules WHERE id=?')->execute([$id]);admin_log('theme_schedule_delete','theme',$id,'');flash('success','Tema takvimi kaldırıldı.');redirect_to('webyonet/?page=themes');}

    if($action==='save_banner_v5'){
        $id=(int)($_POST['id']??0);$title=trim($_POST['title']??'');$position=$_POST['position_code']??'home_hero';if($title==='')throw new RuntimeException('Banner adı/başlığı zorunludur.');if(!array_key_exists($position,banner_slots()))throw new RuntimeException('Geçersiz banner alanı.');$desktop=trim($_POST['current_desktop']??'');$mobile=trim($_POST['current_mobile']??'');if(!empty($_FILES['desktop_image']['name']))$desktop=upload_image($_FILES['desktop_image'],'banners',12)?:$desktop;if(!empty($_FILES['mobile_image']['name']))$mobile=upload_image($_FILES['mobile_image'],'banners',12)?:$mobile;$dd=$desktop?image_dimensions($desktop):null;$md=$mobile?image_dimensions($mobile):null;
        $vals=[$title,trim($_POST['subtitle']??''),trim($_POST['eyebrow']??''),$desktop?:null,$mobile?:null,$dd[0]??null,$dd[1]??null,$md[0]??null,$md[1]??null,trim($_POST['link_url']??''),trim($_POST['button_text']??''),trim($_POST['text_color']??'#ffffff'),trim($_POST['overlay_color']??'#000000'),max(0,min(.85,(float)($_POST['overlay_opacity']??.20))),in_array($_POST['content_align']??'left',['left','center','right'],true)?$_POST['content_align']:'left',!empty($_POST['open_new_tab'])?1:0,$position,(int)($_POST['sort_order']??0),($_POST['start_at']??'')?:null,($_POST['end_at']??'')?:null,!empty($_POST['is_active'])?1:0];
        if($id){$vals[]=$id;db()->prepare('UPDATE banners SET title=?,subtitle=?,eyebrow=?,desktop_image=?,mobile_image=?,desktop_width=?,desktop_height=?,mobile_width=?,mobile_height=?,link_url=?,button_text=?,text_color=?,overlay_color=?,overlay_opacity=?,content_align=?,open_new_tab=?,position_code=?,sort_order=?,start_at=?,end_at=?,is_active=?,updated_at=NOW() WHERE id=?')->execute($vals);}else{db()->prepare('INSERT INTO banners(title,subtitle,eyebrow,desktop_image,mobile_image,desktop_width,desktop_height,mobile_width,mobile_height,link_url,button_text,text_color,overlay_color,overlay_opacity,content_align,open_new_tab,position_code,sort_order,start_at,end_at,is_active,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())')->execute($vals);$id=(int)db()->lastInsertId();}
        admin_log('banner_save','banner',$id,$title);flash('success','Banner kaydedildi. Yüklenen görsel ölçüleri otomatik kaydedildi.');redirect_to('webyonet/?page=banners&edit='.$id);
    }

    if($action==='delete_banner_v5'){$id=(int)($_POST['id']??0);$s=db()->prepare('SELECT title FROM banners WHERE id=?');$s->execute([$id]);$r=$s->fetch();if($r){db()->prepare('DELETE FROM banners WHERE id=?')->execute([$id]);admin_log('banner_delete','banner',$id,$r['title']);}flash('success','Banner kaldırıldı.');redirect_to('webyonet/?page=banners');}

    if($action==='save_feature_box'){
        $id=(int)($_POST['id']??0);$vals=[trim($_POST['icon']??''),trim($_POST['title']??''),trim($_POST['subtitle']??''),trim($_POST['link_url']??''),(int)($_POST['sort_order']??0),!empty($_POST['is_active'])?1:0];if($vals[1]==='')throw new RuntimeException('Özellik kutusu başlığı zorunludur.');if($id){$vals[]=$id;db()->prepare('UPDATE homepage_feature_boxes SET icon=?,title=?,subtitle=?,link_url=?,sort_order=?,is_active=?,updated_at=NOW() WHERE id=?')->execute($vals);}else{db()->prepare('INSERT INTO homepage_feature_boxes(icon,title,subtitle,link_url,sort_order,is_active,created_at,updated_at) VALUES(?,?,?,?,?,?,NOW(),NOW())')->execute($vals);$id=(int)db()->lastInsertId();}admin_log('feature_box_save','homepage',$id,$vals[1]);flash('success','Ana sayfa özellik kutusu kaydedildi.');redirect_to('webyonet/?page=banners&tab=features');
    }

    if($action==='delete_feature_box'){$id=(int)($_POST['id']??0);db()->prepare('DELETE FROM homepage_feature_boxes WHERE id=?')->execute([$id]);flash('success','Özellik kutusu kaldırıldı.');redirect_to('webyonet/?page=banners&tab=features');}
}catch(Throwable $e){flash('error',$e->getMessage());$back=$_SERVER['HTTP_REFERER']??app_url('webyonet/');header('Location: '.$back);exit;}
