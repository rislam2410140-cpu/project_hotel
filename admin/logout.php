<?php
require_once __DIR__ . '/../config.php';

session_destroy();
session_start();

$_SESSION['flash_msg'] = 'You have been logged out.';
$_SESSION['flash_type'] = 'success';

redirect_to('index.php');
?>
