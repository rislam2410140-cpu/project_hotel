<?php
require_once __DIR__ . '/../includes/require_admin.php';

// Get statistics
$stats = [];
try {
    // Total rooms
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms");
    $stats['total_rooms'] = $stmt->fetch()['total'];
    
    // Total bookings
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
    $stats['total_bookings'] = $stmt->fetch()['total'];
    
    // Pending bookings
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'");
    $stats['pending_bookings'] = $stmt->fetch()['total'];
    
    // Total revenue
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE payment_status = 'paid'");
    $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;
    
    // Occupied rooms
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'occupied'");
    $stats['occupied_rooms'] = $stmt->fetch()['total'];
    
    // Available rooms
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'available'");
    $stats['available_rooms'] = $stmt->fetch()['total'];
} catch (Exception $e) {
    // Error handling
}

// Recent bookings
$recent_bookings = [];
try {
    $stmt = $pdo->query("
        SELECT b.*, u.name as guest_name, r.room_number, r.room_type
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN rooms r ON b.room_id = r.room_id
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $recent_bookings = $stmt->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h2 class="section-title">Admin Dashboard</h2>
            <p class="section-subtitle">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>! 📊</p>

            <!-- KPI Cards -->
            <div class="grid grid-4">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_rooms']; ?></div>
                    <div class="stat-label">Total Rooms</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['available_rooms']; ?></div>
                    <div class="stat-label">Available</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['occupied_rooms']; ?></div>
                    <div class="stat-label">Occupied</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($stats['total_revenue'], 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>

            <!-- Management Summary -->
            <div class="grid grid-3" style="margin-top: 2rem;">
                <div class="card" style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">📅</div>
                    <h4>Bookings</h4>
                    <p style="font-size: 1.75rem; color: var(--primary); font-weight: bold;"><?php echo $stats['total_bookings']; ?></p>
                    <p style="color: var(--text-light); margin: 0.5rem 0; font-size: 0.9rem;">
                        <strong style="color: var(--warning);"><?php echo $stats['pending_bookings']; ?></strong> pending
                    </p>
                </div>
                <div class="card" style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">💳</div>
                    <h4>Revenue</h4>
                    <p style="font-size: 1.75rem; color: var(--success); font-weight: bold;">$<?php echo number_format($stats['total_revenue'], 2); ?></p>
                    <p style="color: var(--text-light); margin: 0; font-size: 0.9rem;">Paid invoices</p>
                </div>
                <div class="card" style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">📊</div>
                    <h4>Occupancy</h4>
                    <p style="font-size: 1.75rem; color: var(--primary); font-weight: bold;">
                        <?php echo $stats['total_rooms'] > 0 ? round(($stats['occupied_rooms'] / $stats['total_rooms']) * 100) : 0; ?>%
                    </p>
                    <p style="color: var(--text-light); margin: 0; font-size: 0.9rem;">Current rate</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-4" style="margin-top: 2rem;">
                <a href="<?php echo app_url('admin/rooms.php'); ?>" class="btn btn-primary btn-block">Manage Rooms</a>
                <a href="<?php echo app_url('admin/bookings.php'); ?>" class="btn btn-primary btn-block">Manage Bookings</a>
                <a href="<?php echo app_url('admin/users.php'); ?>" class="btn btn-primary btn-block">Manage Users</a>
                <a href="<?php echo app_url('admin/reports.php'); ?>" class="btn btn-primary btn-block">View Reports</a>
            </div>

            <!-- Advanced Features -->
            <div class="grid grid-2" style="margin-top: 1rem;">
                <a href="<?php echo app_url('admin/pricing_dashboard.php'); ?>" class="btn btn-success btn-block" style="background: #10b981;">💰 Pricing Dashboard</a>
                <a href="<?php echo app_url('admin/pricing_rules.php'); ?>" class="btn btn-warning btn-block" style="background: #f59e0b;">⚙️ Manage Pricing Rules</a>
            </div>

            <!-- Setup Link (if needed) -->
            <div style="margin-top: 1rem; text-align: center;">
                <a href="<?php echo app_url('admin/setup_pricing.php'); ?>" style="color: var(--text-light); font-size: 0.9rem; text-decoration: none;">Need to setup pricing? Click here →</a>
            </div>

            <!-- Recent Bookings -->
            <div class="card" style="margin-top: 2rem;">
                <h3>📋 Recent Bookings</h3>
                <?php if (count($recent_bookings) > 0): ?>
                    <div style="overflow-x: auto; margin-top: 1rem;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Guest</th>
                                    <th>Room</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['room_type']); ?> (<?php echo htmlspecialchars($booking['room_number']); ?>)</td>
                                        <td><?php echo format_date($booking['check_in_date']); ?></td>
                                        <td><?php echo format_date($booking['check_out_date']); ?></td>
                                        <td><span class="badge badge-<?php echo $booking['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?></span></td>
                                        <td><strong>$<?php echo number_format($booking['total_price'], 2); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-light); text-align: center; padding: 2rem;">No bookings yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
