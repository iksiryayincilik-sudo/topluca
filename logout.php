<?php
require __DIR__.'/app/bootstrap.php';
logout_user();
flash('success','Hesabınızdan çıkış yaptınız.');
redirect('account.php');
