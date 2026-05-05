<?php
// Determine which page is active
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? null;
$user_name = $_SESSION['name'] ?? null;
?>
<header>
    <div class="header-wrapper">
        <a href="<?php echo app_url('index.php'); ?>" class="logo">🏨 <?php echo SITE_NAME; ?></a>
        <nav>
            <?php if ($role === 'guest'): ?>
                <a href="<?php echo app_url('public/rooms.php'); ?>" class="<?php echo in_array($current_page, ['rooms.php', 'room_details.php']) ? 'active' : ''; ?>">Browse Rooms</a>
                <a href="<?php echo app_url('guest/dashboard.php'); ?>" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">My Dashboard</a>
                <a href="<?php echo app_url('guest/my_bookings.php'); ?>" class="<?php echo $current_page === 'my_bookings.php' ? 'active' : ''; ?>">My Bookings</a>
            <?php elseif ($role === 'admin'): ?>
                <a href="<?php echo app_url('admin/dashboard.php'); ?>" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
                <a href="<?php echo app_url('admin/rooms.php'); ?>" class="<?php echo $current_page === 'rooms.php' ? 'active' : ''; ?>">Rooms</a>
                <a href="<?php echo app_url('admin/bookings.php'); ?>" class="<?php echo $current_page === 'bookings.php' ? 'active' : ''; ?>">Bookings</a>
                <a href="<?php echo app_url('admin/users.php'); ?>" class="<?php echo $current_page === 'users.php' ? 'active' : ''; ?>">Users</a>
                <a href="<?php echo app_url('admin/reports.php'); ?>" class="<?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">Reports</a>
            <?php else: ?>
                <a href="<?php echo app_url('index.php'); ?>" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">Home</a>
                <a href="<?php echo app_url('public/rooms.php'); ?>" class="<?php echo in_array($current_page, ['rooms.php', 'room_details.php']) ? 'active' : ''; ?>">Rooms</a>
                <a href="<?php echo app_url('public/about.php'); ?>" class="<?php echo $current_page === 'about.php' ? 'active' : ''; ?>">About</a>
                <a href="<?php echo app_url('public/contact.php'); ?>" class="<?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">Contact</a>
            <?php endif; ?>
        </nav>
        <div class="user-menu">
            <?php if ($role): ?>
                <span>👤 <?php echo htmlspecialchars($user_name); ?></span>
                <?php if ($role === 'guest'): ?>
                    <a href="<?php echo app_url('guest/logout.php'); ?>" class="btn-logout">Logout</a>
                <?php elseif ($role === 'admin'): ?>
                    <a href="<?php echo app_url('admin/logout.php'); ?>" class="btn-logout">Logout</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?php echo app_url('guest/login.php'); ?>" class="btn btn-primary btn-sm">Guest Login</a>
                <a href="<?php echo app_url('admin/login.php'); ?>" class="btn btn-secondary btn-sm">Admin</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Flash Messages -->
<?php
if (isset($_SESSION['flash_msg'])) {
    $flash_type = $_SESSION['flash_type'] ?? 'info';
    $flash_msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
    unset($_SESSION['flash_type']);
?>
    <div class="flash-message flash-<?php echo $flash_type; ?>">
        <span><?php echo htmlspecialchars($flash_msg); ?></span>
        <button class="flash-close" onclick="this.parentElement.style.display='none';">✕</button>
    </div>
<?php } ?>

<div class="page-wrapper">
