<?php
require_once __DIR__ . '/../includes/require_admin.php';

$users = [];
$error = '';

try {
    $stmt = $pdo->query("
        SELECT u.*, COUNT(b.booking_id) as total_bookings, SUM(b.total_price) as total_spent
        FROM users u
        LEFT JOIN bookings b ON u.user_id = b.user_id AND b.status IN ('checked_out', 'completed')
        WHERE u.role = 'guest'
        GROUP BY u.user_id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Error loading users.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h2 class="section-title">Manage Users</h2>
            <p class="section-subtitle">View guest accounts and activity</p>

            <?php if ($error): ?>
                <div class="flash-message flash-error">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <div class="card">
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Bookings</th>
                                <th>Total Spent</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></td>
                                    <td><span class="badge badge-confirmed"><?php echo $user['total_bookings'] ?? 0; ?></span></td>
                                    <td><strong>$<?php echo number_format($user['total_spent'] ?? 0, 2); ?></strong></td>
                                    <td><?php echo format_date($user['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
