<?php
require dirname(__DIR__).'/app/bootstrap.php';
$_SESSION=[];session_destroy();header('Location: '.url('webyonet/login.php'));exit;
