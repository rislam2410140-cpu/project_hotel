<?php
require_once __DIR__ . '/../includes/require_admin.php';

$stats = [];
$error = '';

try {
    // Bookings by status
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count
        FROM bookings
        GROUP BY status
    ");
    $bookings_by_status = $stmt->fetchAll();
    
    // Revenue by month (last 6 months)
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(amount) as revenue
        FROM payments
        WHERE payment_status = 'paid' AND paid_at IS NOT NULL
        GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
    ");
    $revenue_by_month = $stmt->fetchAll();
    
    // Top rooms by bookings
    $stmt = $pdo->query("
        SELECT r.room_id, r.room_number, r.room_type, COUNT(b.booking_id) as booking_count
        FROM rooms r
        LEFT JOIN bookings b ON r.room_id = b.room_id
        GROUP BY r.room_id
        ORDER BY booking_count DESC
        LIMIT 5
    ");
    $top_rooms = $stmt->fetchAll();
    
    // Overall stats
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
    $stats['total_bookings'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms");
    $stats['total_rooms'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT SUM(total_price) as total FROM bookings WHERE status IN ('checked_out', 'completed')");
    $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'guest'");
    $stats['total_guests'] = $stmt->fetch()['total'];
} catch (Exception $e) {
    $error = 'Error loading reports.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h2 class="section-title">📊 Reports & Analytics</h2>
            <p class="section-subtitle">Business performance and insights</p>

            <?php if ($error): ?>
                <div class="flash-message flash-error">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Key Metrics -->
            <div class="grid grid-4">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($stats['total_revenue'], 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_guests']; ?></div>
                    <div class="stat-label">Guest Accounts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_rooms']; ?></div>
                    <div class="stat-label">Total Rooms</div>
                </div>
            </div>

            <!-- Bookings by Status -->
            <div class="grid grid-2" style="margin-top: 2rem;">
                <div class="card">
                    <h3>Bookings by Status</h3>
                    <table style="margin-top: 1rem;">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings_by_status as $bs): ?>
                                <?php $percentage = $stats['total_bookings'] > 0 ? round(($bs['count'] / $stats['total_bookings']) * 100) : 0; ?>
                                <tr>
                                    <td><span class="badge badge-<?php echo $bs['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $bs['status'])); ?></span></td>
                                    <td><strong><?php echo $bs['count']; ?></strong></td>
                                    <td><div style="background: var(--light); padding: 0.25rem 0.5rem; border-radius: 0.25rem;"><?php echo $percentage; ?>%</div></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card">
                    <h3>Revenue by Month</h3>
                    <table style="margin-top: 1rem;">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revenue_by_month as $rm): ?>
                                <tr>
                                    <td><?php echo date('M Y', strtotime($rm['month'] . '-01')); ?></td>
                                    <td><strong>$<?php echo number_format($rm['revenue'], 2); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Rooms -->
            <div class="card" style="margin-top: 2rem;">
                <h3>Top Rooms by Bookings</h3>
                <table style="margin-top: 1rem;">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Type</th>
                            <th>Bookings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_rooms as $room): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                <td><span class="badge badge-confirmed"><?php echo $room['booking_count']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
