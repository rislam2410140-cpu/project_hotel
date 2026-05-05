<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db_connect.php';

// Guard: Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_msg'] = 'Please login as an admin first.';
    redirect_to('admin/login.php');
}
?>
