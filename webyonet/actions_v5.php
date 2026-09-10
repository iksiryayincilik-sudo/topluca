<?php
declare(strict_types=1);

if($_SERVER['REQUEST_METHOD']!=='POST')return;
$action=$_POST['action']??'';
$v5Actions=['save_site_identity','save_theme','save_theme_schedule','delete_theme_schedule','save_banner_v5','delete_banner_v5','save_feature_box','delete_feature_box'];
if(!in_array($action,$v5Actions,true))return;
csrf_check();

try{
    if($action==='save_site_identity'){
        $logo=trim((string)setting('site_logo',''));
        $favicon=trim((string)setting('site_favicon',''));
        $og=trim((string)setting('site_og_image',''));
        if(!empty($_FILES['site_logo']['name']))$logo=upload_image($_FILES['site_logo'],'site',10)?:$logo;
        if(!empty($_FILES['site_favicon']['name']))$favicon=upload_image($_FILES['site_favicon'],'site',4)?:$favicon;
        if(!empty($_FILES['site_og_image']['name']))$og=upload_image($_FILES['site_og_image'],'site',10)?:$og;
        $pairs=[
            'site_title'=>trim($_POST['site_title']??''),
            'site_description'=>trim($_POST['site_description']??''),
            'site_keywords'=>trim($_POST['site_keywords']??''),
            'site_logo'=>$logo,
            'site_favicon'=>$favicon,
            'site_og_image'=>$og,
            'company_name'=>trim($_POST['company_name']??''),
            'company_email'=>trim($_POST['company_email']??''),
            'company_phone'=>trim($_POST['company_phone']??''),
            'company_address'=>trim($_POST['company_address']??''),
            'footer_description'=>trim($_POST['footer_description']??''),
            'banner_autoplay'=>!empty($_POST['banner_autoplay'])?'1':'0',
            'banner_interval'=>(string)max(2500,min(12000,(int)($_POST['banner_interval']??5500))),
            'bank_name'=>trim($_POST['bank_name']??''),
            'bank_account_name'=>trim($_POST['bank_account_name']??''),
            'bank_iban'=>trim($_POST['bank_iban']??''),
            'bank_branch'=>trim($_POST['bank_branch']??''),
            'bank_payment_note'=>trim($_POST['bank_payment_note']??''),
        ];
        foreach($pairs as $k=>$v)set_setting($k,$v);
        admin_log('site_identity_update','settings',null,'Site kimliği, SEO ve ödeme bilgileri güncellendi.');
        flash('success','Site kimliği, SEO, logo ve ödeme bilgileri kaydedildi.');
        redirect_to('webyonet/?page=settings');
    }

    if($action==='save_theme'){
        $theme=$_POST['active_theme']??'standard';
        if(!array_key_exists($theme,builtin_themes()))throw new RuntimeException('Geçersiz tema.');
        set_setting('active_theme',$theme);
        set_setting('theme_auto_schedule',!empty($_POST['theme_auto_schedule'])?'1':'0');
        admin_log('theme_update','theme',null,$theme);
        flash('success','Aktif tema güncellendi.');
        redirect_to('webyonet/?page=themes');
    }

    if($action==='save_theme_schedule'){
        $name=trim($_POST['name']??'');$theme=$_POST['theme_key']??'standard';$start=$_POST['start_at']??'';$end=$_POST['end_at']??'';
        if($name===''||$start===''||$end==='')throw new RuntimeException('Tema adı, başlangıç ve bitiş tarihi zorunludur.');
        if(!array_key_exists($theme,builtin_themes()))throw new RuntimeException('Geçersiz tema.');
        if(strtotime($end)<=strtotime($start))throw new RuntimeException('Bitiş tarihi başlangıç tarihinden sonra olmalıdır.');
        db()->prepare('INSERT INTO theme_schedules(name,theme_key,start_at,end_at,priority,is_active,created_at,updated_at) VALUES(?,?,?,?,?,1,NOW(),NOW())')->execute([$name,$theme,$start,$end,(int)($_POST['priority']??10)]);
        admin_log('theme_schedule_create','theme',null,$name);
        flash('success','Tema takvimi eklendi.');
        redirect_to('webyonet/?page=themes');
    }

    if($action==='delete_theme_schedule'){
        $id=(int)($_POST['id']??0);db()->prepare('DELETE FROM theme_schedules WHERE id=?')->execute([$id]);
        admin_log('theme_schedule_delete','theme',$id,'');flash('success','Tema takvimi kaldırıldı.');redirect_to('webyonet/?page=themes');
    }

    if($action==='save_banner_v5'){
        $id=(int)($_POST['id']??0);$title=trim($_POST['title']??'');$position=$_POST['position_code']??'home_hero';
        if($title==='')throw new RuntimeException('Banner adı/başlığı zorunludur.');
        if(!array_key_exists($position,banner_slots()))throw new RuntimeException('Geçersiz banner alanı.');
        $desktop=trim($_POST['current_desktop']??'');$mobile=trim($_POST['current_mobile']??'');
        if(!empty($_FILES['desktop_image']['name']))$desktop=upload_image($_FILES['desktop_image'],'banners',12)?:$desktop;
        if(!empty($_FILES['mobile_image']['name']))$mobile=upload_image($_FILES['mobile_image'],'banners',12)?:$mobile;
        $dd=$desktop?image_dimensions($desktop):null;$md=$mobile?image_dimensions($mobile):null;
        $vals=[
            $title,trim($_POST['subtitle']??''),trim($_POST['eyebrow']??''),$desktop?:null,$mobile?:null,
            $dd[0]??null,$dd[1]??null,$md[0]??null,$md[1]??null,
            trim($_POST['link_url']??''),trim($_POST['button_text']??''),trim($_POST['text_color']??'#ffffff'),trim($_POST['overlay_color']??'#000000'),
            max(0,min(0.85,(float)($_POST['overlay_opacity']??0.20))),in_array($_POST['content_align']??'left',['left','center','right'],true)?$_POST['content_align']:'left',
            !empty($_POST['open_new_tab'])?1:0,$position,(int)($_POST['sort_order']??0),($_POST['start_at']??'')?:null,($_POST['end_at']??'')?:null,!empty($_POST['is_active'])?1:0
        ];
        if($id){$vals[]=$id;db()->prepare('UPDATE banners SET title=?,subtitle=?,eyebrow=?,desktop_image=?,mobile_image=?,desktop_width=?,desktop_height=?,mobile_width=?,mobile_height=?,link_url=?,button_text=?,text_color=?,overlay_color=?,overlay_opacity=?,content_align=?,open_new_tab=?,position_code=?,sort_order=?,start_at=?,end_at=?,is_active=?,updated_at=NOW() WHERE id=?')->execute($vals);}else{db()->prepare('INSERT INTO banners(title,subtitle,eyebrow,desktop_image,mobile_image,desktop_width,desktop_height,mobile_width,mobile_height,link_url,button_text,text_color,overlay_color,overlay_opacity,content_align,open_new_tab,position_code,sort_order,start_at,end_at,is_active,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())')->execute($vals);$id=(int)db()->lastInsertId();}
        admin_log('banner_save','banner',$id,$title);flash('success','Banner kaydedildi. Yüklenen görsel ölçüleri otomatik kaydedildi.');redirect_to('webyonet/?page=banners&edit='.$id);
    }

    if($action==='delete_banner_v5'){
        $id=(int)($_POST['id']??0);$s=db()->prepare('SELECT desktop_image,mobile_image,title FROM banners WHERE id=?');$s->execute([$id]);$r=$s->fetch();if($r){db()->prepare('DELETE FROM banners WHERE id=?')->execute([$id]);admin_log('banner_delete','banner',$id,$r['title']);}flash('success','Banner kaldırıldı.');redirect_to('webyonet/?page=banners');
    }

    if($action==='save_feature_box'){
        $id=(int)($_POST['id']??0);$vals=[trim($_POST['icon']??''),trim($_POST['title']??''),trim($_POST['subtitle']??''),trim($_POST['link_url']??''),(int)($_POST['sort_order']??0),!empty($_POST['is_active'])?1:0];
        if($vals[1]==='')throw new RuntimeException('Özellik kutusu başlığı zorunludur.');
        if($id){$vals[]=$id;db()->prepare('UPDATE homepage_feature_boxes SET icon=?,title=?,subtitle=?,link_url=?,sort_order=?,is_active=?,updated_at=NOW() WHERE id=?')->execute($vals);}else{db()->prepare('INSERT INTO homepage_feature_boxes(icon,title,subtitle,link_url,sort_order,is_active,created_at,updated_at) VALUES(?,?,?,?,?,?,NOW(),NOW())')->execute($vals);$id=(int)db()->lastInsertId();}
        admin_log('feature_box_save','homepage',$id,$vals[1]);flash('success','Ana sayfa özellik kutusu kaydedildi.');redirect_to('webyonet/?page=banners&tab=features');
    }

    if($action==='delete_feature_box'){
        $id=(int)($_POST['id']??0);db()->prepare('DELETE FROM homepage_feature_boxes WHERE id=?')->execute([$id]);flash('success','Özellik kutusu kaldırıldı.');redirect_to('webyonet/?page=banners&tab=features');
    }
}catch(Throwable $e){flash('error',$e->getMessage());$back=$_SERVER['HTTP_REFERER']??app_url('webyonet/');header('Location: '.$back);exit;}
