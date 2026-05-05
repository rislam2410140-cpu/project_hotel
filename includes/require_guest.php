<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db_connect.php';

// Guard: Only allow guests
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guest') {
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_msg'] = 'Please login as a guest first.';
    redirect_to('guest/login.php');
}
?>
