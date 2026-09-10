<?php
require dirname(__DIR__).'/app/bootstrap.php';
if(admin_logged_in())admin_log('logout','admin',(int)$_SESSION['admin_id'],'WebYönet çıkışı');
unset($_SESSION['admin_id']);session_regenerate_id(true);redirect('webyonet/login.php');
