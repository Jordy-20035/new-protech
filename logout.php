<?php
require_once 'config.php';
require_once 'auth_functions.php';

logout_user();
header("Location: auth.html");
exit();
?>