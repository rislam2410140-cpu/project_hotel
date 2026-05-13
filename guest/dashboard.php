<?php
require_once __DIR__ . '/../includes/require_guest.php';

$user_id = $_SESSION['user_id'];
$stats = [];

try {
    // Active booking
    $stmt = $pdo->prepare("
        SELECT b.*, r.room_number, r.room_type 
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.user_id = ? AND b.status IN ('confirmed', 'checked_in')
        ORDER BY b.check_in_date DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $active_booking = $stmt->fetch();

    // Upcoming bookings count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $pending_count = $stmt->fetch()['count'];

    // Total bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_bookings = $stmt->fetch()['count'];

    // Total spent
    $stmt = $pdo->prepare("SELECT SUM(total_price) as total FROM bookings WHERE user_id = ? AND status IN ('checked_out', 'completed')");
    $stmt->execute([$user_id]);
    $total_spent = $stmt->fetch()['total'] ?? 0;
} catch (Exception $e) {
    // Handle error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h2 class="section-title">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>! 👋</h2>
            <p class="section-subtitle">Manage your bookings and reservations</p>

            <!-- Stats -->
            <div class="grid grid-4">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_bookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pending_count; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($total_spent, 2); ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">⭐</div>
                    <div class="stat-label">Member</div>
                </div>
            </div>

            <!-- Active Booking -->
            <?php if ($active_booking): ?>
                <div class="card">
                    <h3>🏨 Current Booking</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1.5rem;">
                        <div>
                            <p><strong>Room:</strong> <?php echo htmlspecialchars($active_booking['room_type']); ?> (<?php echo htmlspecialchars($active_booking['room_number']); ?>)</p>
                            <p style="margin-top: 0.5rem;"><strong>Check-in:</strong> <?php echo format_date($active_booking['check_in_date']); ?></p>
                            <p style="margin-top: 0.5rem;"><strong>Check-out:</strong> <?php echo format_date($active_booking['check_out_date']); ?></p>
                        </div>
                        <div>
                            <p><strong>Status:</strong> <span class="badge badge-<?php echo $active_booking['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $active_booking['status'])); ?></span></p>
                            <p style="margin-top: 0.5rem;"><strong>Total Price:</strong> <span style="color: var(--primary); font-weight: bold;">$<?php echo number_format($active_booking['total_price'], 2); ?></span></p>
                        </div>
                    </div>
                    <div class="card-footer">
                <a href="<?php echo app_url('guest/my_bookings.php'); ?>" class="btn btn-primary">View All Bookings</a>
                <a href="<?php echo app_url('guest/room_service.php'); ?>" class="btn btn-secondary">Order Room Service</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="grid grid-3" style="margin-top: 2rem;">
                <div class="card" style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">🛏️</div>
                    <h4>Browse Rooms</h4>
                    <p style="color: var(--text-light); margin: 1rem 0;">Find and book your next stay</p>
                        <a href="<?php echo app_url('public/rooms.php'); ?>" class="btn btn-primary btn-sm">Browse</a>
                </div>
                <div class="card" style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">📅</div>
                    <h4>My Bookings</h4>
                    <p style="color: var(--text-light); margin: 1rem 0;">Manage your reservations</p>
                        <a href="<?php echo app_url('guest/my_bookings.php'); ?>" class="btn btn-primary btn-sm">View</a>
                </div>
                <div class="card" style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">🍽️</div>
                    <h4>Room Service</h4>
                    <p style="color: var(--text-light); margin: 1rem 0;">Order food and amenities</p>
                        <a href="<?php echo app_url('guest/room_service.php'); ?>" class="btn btn-primary btn-sm">Order</a>
                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
