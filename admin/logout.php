<?php
require_once __DIR__ . '/../config.php';

session_destroy();

set_flash('success', 'You have been logged out.');
redirect_to('index.php');
?>
