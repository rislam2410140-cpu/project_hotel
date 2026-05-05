<?php
require_once __DIR__ . '/../includes/require_guest.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'] ?? null;
    $items_str = trim($_POST['items'] ?? '');
    
    if (empty($room_id) || empty($items_str)) {
        $error = 'Please select a room and items.';
    } else {
        try {
            // Parse items (comma-separated)
            $items = array_map('trim', explode(',', $items_str));
            $items = array_filter($items);
            
            if (empty($items)) {
                $error = 'Please enter at least one item.';
            } else {
                // Simple pricing: $5 per item
                $total_price = count($items) * 5;
                
                $stmt = $pdo->prepare("
                    INSERT INTO service_orders (room_id, booking_id, items, total_price, status)
                    VALUES (?, NULL, ?, ?, 'pending')
                ");
                $stmt->execute([$room_id, json_encode($items), $total_price]);
                
                $_SESSION['flash_msg'] = 'Room service order placed successfully!';
                $_SESSION['flash_type'] = 'success';
                redirect_to('guest/room_service.php');
            }
        } catch (Exception $e) {
            $error = 'Error placing order. Please try again.';
        }
    }
}

// Get active rooms for current user
$user_rooms = [];
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT r.room_id, r.room_number, r.room_type
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.user_id = ? AND b.status IN ('checked_in')
        ORDER BY b.check_in_date DESC
    ");
    $stmt->execute([$user_id]);
    $user_rooms = $stmt->fetchAll();
} catch (Exception $e) {}

// Get service order history
$orders = [];
try {
    $stmt = $pdo->prepare("
        SELECT so.*, r.room_number, r.room_type
        FROM service_orders so
        JOIN rooms r ON so.room_id = r.room_id
        WHERE so.room_id IN (
            SELECT r.room_id FROM rooms r
            JOIN bookings b ON b.room_id = r.room_id
            WHERE b.user_id = ?
        )
        ORDER BY so.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Service - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h2 class="section-title">Room Service</h2>
            <p class="section-subtitle">Order food, drinks, and amenities to your room</p>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Order Form -->
                <div>
                    <div class="card">
                        <h3>Place an Order</h3>

                        <?php if ($error): ?>
                            <div class="flash-message flash-error" style="margin-bottom: 1rem;">
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (count($user_rooms) > 0): ?>
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label>Room *</label>
                                    <select name="room_id" required>
                                        <option value="">Select a room...</option>
                                        <?php foreach ($user_rooms as $room): ?>
                                            <option value="<?php echo $room['room_id']; ?>">
                                                <?php echo htmlspecialchars($room['room_type']); ?> (<?php echo htmlspecialchars($room['room_number']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Items (comma-separated) *</label>
                                    <textarea name="items" placeholder="e.g., Coffee, Sandwich, Towels, Extra Pillow" required></textarea>
                                    <small style="color: var(--text-light);">Each item is $5</small>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block">Place Order</button>
                            </form>
                        <?php else: ?>
                            <div style="padding: 2rem; text-align: center;">
                                <p style="color: var(--text-light); margin-bottom: 1rem;">
                                    You must be checked in to a room to place a room service order.
                                </p>
                                <a href="<?php echo app_url('guest/my_bookings.php'); ?>" class="btn btn-primary">View Bookings</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order History -->
                <div>
                    <div class="card">
                        <h3>Order History</h3>
                        <?php if (count($orders) > 0): ?>
                            <div style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($orders as $order): ?>
                                    <div style="padding: 1rem; border-bottom: 1px solid var(--border);">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                            <strong><?php echo htmlspecialchars($order['room_type']); ?> (<?php echo htmlspecialchars($order['room_number']); ?>)</strong>
                                            <span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></span>
                                        </div>
                                        <p style="color: var(--text-light); font-size: 0.9rem; margin: 0.5rem 0;">
                                            Items: <?php echo implode(', ', json_decode($order['items'], true)); ?>
                                        </p>
                                        <p style="color: var(--primary); font-weight: bold;">$<?php echo number_format($order['total_price'], 2); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--text-light); text-align: center; padding: 2rem 0;">No orders yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
