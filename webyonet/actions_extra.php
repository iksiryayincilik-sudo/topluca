<?php
declare(strict_types=1);

if($_SERVER['REQUEST_METHOD']!=='POST')return;
$action=$_POST['action']??'';
if(!in_array($action,['save_supplier_v5','save_purchase_v5','change_admin_password_v5'],true))return;
csrf_check();
try{
    if($action==='save_supplier_v5'){
        $name=trim($_POST['name']??'');if($name==='')throw new RuntimeException('Tedarikçi adı zorunludur.');
        db()->prepare('INSERT INTO suppliers(name,tax_no,phone,email,address,is_active,created_at,updated_at) VALUES(?,?,?,?,?,1,NOW(),NOW())')->execute([$name,trim($_POST['tax_no']??'')?:null,trim($_POST['phone']??'')?:null,trim($_POST['email']??'')?:null,trim($_POST['address']??'')?:null]);
        $id=(int)db()->lastInsertId();admin_log('supplier_create','supplier',$id,$name);flash('success','Tedarikçi eklendi.');redirect_to('webyonet/?page=purchases');
    }
    if($action==='save_purchase_v5'){
        $supplierId=($_POST['supplier_id']??'')!==''?(int)$_POST['supplier_id']:null;$productId=(int)($_POST['product_id']??0);$qty=max(1,(int)($_POST['quantity']??1));$cost=(float)($_POST['unit_cost']??0);$date=$_POST['purchase_date']??date('Y-m-d');if(!$productId||$cost<0)throw new RuntimeException('Ürün ve maliyet bilgisi geçersiz.');
        db()->beginTransaction();
        $total=round($qty*$cost,2);db()->prepare('INSERT INTO purchases(supplier_id,document_no,purchase_date,total,notes,created_at) VALUES(?,?,?,?,?,NOW())')->execute([$supplierId,trim($_POST['document_no']??'')?:null,$date,$total,trim($_POST['notes']??'')?:null]);$purchaseId=(int)db()->lastInsertId();
        db()->prepare('INSERT INTO purchase_items(purchase_id,product_id,quantity,unit_cost,line_total) VALUES(?,?,?,?,?)')->execute([$purchaseId,$productId,$qty,$cost,$total]);
        db()->prepare('UPDATE products SET stock=stock+?,purchase_price=?,updated_at=NOW() WHERE id=?')->execute([$qty,$cost,$productId]);
        db()->prepare('INSERT INTO stock_movements(product_id,movement_type,quantity,unit_cost,reference_type,reference_id,notes,admin_id,created_at) VALUES(?,?,?,?,?,?,?,?,NOW())')->execute([$productId,'purchase',$qty,$cost,'purchase',$purchaseId,trim($_POST['notes']??'')?:null,admin_id()]);
        db()->commit();admin_log('purchase_create','purchase',$purchaseId,'Ürün #'.$productId.' / '.$qty.' adet');flash('success','Mal alışı kaydedildi; stok ve alış maliyeti güncellendi.');redirect_to('webyonet/?page=purchases');
    }
    if($action==='change_admin_password_v5'){
        $admin=current_admin();$current=$_POST['current_password']??'';$new=$_POST['new_password']??'';$confirm=$_POST['new_password_confirm']??'';
        if(!$admin||!password_verify($current,$admin['password_hash']))throw new RuntimeException('Mevcut şifre yanlış.');
        if(strlen($new)<12)throw new RuntimeException('Yeni şifre en az 12 karakter olmalıdır.');
        if($new!==$confirm)throw new RuntimeException('Yeni şifreler eşleşmiyor.');
        db()->prepare('UPDATE admins SET password_hash=?,must_change_password=0,updated_at=NOW() WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),(int)$admin['id']]);admin_log('admin_password_change','admin',(int)$admin['id'],'Şifre değiştirildi');flash('success','Yönetici şifresi değiştirildi.');redirect_to('webyonet/?page=settings');
    }
}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error',$e->getMessage());$back=$_SERVER['HTTP_REFERER']??app_url('webyonet/');header('Location: '.$back);exit;}
