<?php
require_once __DIR__ . '/../includes/require_admin.php';

$bookings = [];
$error = '';

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? '';
    $new_status = $_POST['new_status'] ?? '';
    
    if ($booking_id && $new_status) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ?");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch();
            
            if ($booking) {
                $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
                $stmt->execute([$new_status, $booking_id]);
                
                // Update room status if check-in
                if ($new_status === 'checked_in') {
                    $stmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE room_id = ?");
                    $stmt->execute([$booking['room_id']]);
                }
                
                // Update room status if check-out/completed
                if ($new_status === 'checked_out' || $new_status === 'completed') {
                    $stmt = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE room_id = ?");
                    $stmt->execute([$booking['room_id']]);
                }
                
                $_SESSION['flash_msg'] = 'Booking status updated!';
                $_SESSION['flash_type'] = 'success';
                redirect_to('admin/bookings.php');
            }
        } catch (Exception $e) {
            $error = 'Error updating booking.';
        }
    }
}

// Get all bookings
try {
    $stmt = $pdo->query("
        SELECT b.*, u.name as guest_name, u.email as guest_email, r.room_number, r.room_type, p.payment_status
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id
        ORDER BY b.check_in_date DESC
    ");
    $bookings = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Error loading bookings.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Admin - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h2 class="section-title">Manage Bookings</h2>
            <p class="section-subtitle">View and manage all reservations</p>

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
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['guest_name']); ?></strong><br>
                                        <span style="font-size: 0.85rem; color: var(--text-light);"><?php echo htmlspecialchars($booking['guest_email']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['room_type']); ?> (<?php echo htmlspecialchars($booking['room_number']); ?>)</td>
                                    <td><?php echo format_date($booking['check_in_date']); ?></td>
                                    <td><?php echo format_date($booking['check_out_date']); ?></td>
                                    <td><strong>$<?php echo number_format($booking['total_price'], 2); ?></strong></td>
                                    <td><span class="badge badge-<?php echo $booking['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?></span></td>
                                    <td><span class="badge badge-<?php echo $booking['payment_status']; ?>"><?php echo ucfirst($booking['payment_status']); ?></span></td>
                                    <td style="min-width: 250px;">
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <input type="hidden" name="new_status" value="confirmed">
                                                <button type="submit" class="btn btn-success btn-sm">Confirm</button>
                                            </form>
                                        <?php elseif ($booking['status'] === 'confirmed'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <input type="hidden" name="new_status" value="checked_in">
                                                <button type="submit" class="btn btn-primary btn-sm">Check-in</button>
                                            </form>
                                        <?php elseif ($booking['status'] === 'checked_in'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <input type="hidden" name="new_status" value="completed">
                                                <button type="submit" class="btn btn-success btn-sm">Checkout</button>
                                            </form>
                                        <?php elseif (in_array($booking['status'], ['pending', 'confirmed', 'checked_in'])): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Cancel this booking?');">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <input type="hidden" name="new_status" value="cancelled">
                                                <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
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
