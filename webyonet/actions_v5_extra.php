<?php
declare(strict_types=1);
if($_SERVER['REQUEST_METHOD']!=='POST')return;
$action=$_POST['action']??'';
$actions=['save_content_page_v5','delete_content_page_v5','save_search_synonym_v5','delete_search_synonym_v5','save_admin_v5','toggle_admin_v5'];
if(!in_array($action,$actions,true))return;
csrf_check();
try{
    if($action==='save_content_page_v5'){
        $id=(int)($_POST['id']??0);$title=trim($_POST['title']??'');if($title==='')throw new RuntimeException('Sayfa başlığı zorunludur.');$slug=trim($_POST['slug']??'')?:slugify($title);$body=trim($_POST['body']??'');$metaTitle=trim($_POST['meta_title']??'')?:null;$metaDescription=trim($_POST['meta_description']??'')?:null;$active=!empty($_POST['is_active'])?1:0;
        if($id){db()->prepare('UPDATE content_pages SET title=?,slug=?,body=?,meta_title=?,meta_description=?,is_active=?,updated_at=NOW() WHERE id=?')->execute([$title,$slug,$body,$metaTitle,$metaDescription,$active,$id]);}
        else{db()->prepare('INSERT INTO content_pages(title,slug,body,meta_title,meta_description,is_active,created_at,updated_at) VALUES(?,?,?,?,?,?,NOW(),NOW())')->execute([$title,$slug,$body,$metaTitle,$metaDescription,$active]);$id=(int)db()->lastInsertId();}
        admin_log('content_page_save','content_page',$id,$title);flash('success','İçerik sayfası kaydedildi.');redirect_to('webyonet/?page=content&edit='.$id);
    }
    if($action==='delete_content_page_v5'){$id=(int)($_POST['id']??0);db()->prepare('DELETE FROM content_pages WHERE id=?')->execute([$id]);admin_log('content_page_delete','content_page',$id,'');flash('success','İçerik sayfası silindi.');redirect_to('webyonet/?page=content');}
    if($action==='save_search_synonym_v5'){
        $term=trim($_POST['term']??'');$syn=trim($_POST['synonyms']??'');if($term===''||$syn==='')throw new RuntimeException('Arama kelimesi ve eş anlamlılar zorunludur.');db()->prepare('INSERT INTO search_synonyms(term,synonyms,is_active,created_at,updated_at) VALUES(?,?,1,NOW(),NOW()) ON DUPLICATE KEY UPDATE synonyms=VALUES(synonyms),is_active=1,updated_at=NOW()')->execute([$term,$syn]);admin_log('search_synonym_save','search',null,$term);flash('success','Arama sözlüğü güncellendi.');redirect_to('webyonet/?page=search_tools');
    }
    if($action==='delete_search_synonym_v5'){$id=(int)($_POST['id']??0);db()->prepare('DELETE FROM search_synonyms WHERE id=?')->execute([$id]);admin_log('search_synonym_delete','search',$id,'');flash('success','Arama eşlemesi silindi.');redirect_to('webyonet/?page=search_tools');}
    if($action==='save_admin_v5'){
        $id=(int)($_POST['id']??0);$username=trim($_POST['username']??'');$full=trim($_POST['full_name']??'');$role=trim($_POST['role']??'operations');if($username==='')throw new RuntimeException('Yönetici kullanıcı adı zorunludur.');
        if($id){db()->prepare('UPDATE admins SET username=?,full_name=?,role=?,is_active=?,updated_at=NOW() WHERE id=?')->execute([$username,$full,$role,!empty($_POST['is_active'])?1:0,$id]);}
        else{$password=(string)($_POST['password']??'');if(strlen($password)<12)throw new RuntimeException('Yeni yönetici şifresi en az 12 karakter olmalıdır.');db()->prepare('INSERT INTO admins(username,full_name,password_hash,role,must_change_password,is_active,created_at,updated_at) VALUES(?,?,?,?,1,1,NOW(),NOW())')->execute([$username,$full,password_hash($password,PASSWORD_DEFAULT),$role]);$id=(int)db()->lastInsertId();}
        admin_log('admin_save','admin',$id,$username);flash('success','Yönetici hesabı kaydedildi.');redirect_to('webyonet/?page=admins');
    }
    if($action==='toggle_admin_v5'){$id=(int)($_POST['id']??0);if($id===admin_id())throw new RuntimeException('Kendi hesabınızı pasif yapamazsınız.');db()->prepare('UPDATE admins SET is_active=IF(is_active=1,0,1),updated_at=NOW() WHERE id=?')->execute([$id]);admin_log('admin_toggle','admin',$id,'');flash('success','Yönetici durumu değiştirildi.');redirect_to('webyonet/?page=admins');}
}catch(Throwable $e){flash('error',$e->getMessage());redirect_to('webyonet/?page='.urlencode($_GET['page']??'dashboard'));}
