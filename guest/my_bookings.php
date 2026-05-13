<?php
require_once __DIR__ . '/../includes/require_guest.php';

$user_id = $_SESSION['user_id'];
$bookings = [];
$error = '';

try {
    // Get all bookings for this user
    $stmt = $pdo->prepare("
        SELECT b.*, r.room_number, r.room_type, r.price, p.payment_status
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id
        WHERE b.user_id = ?
        ORDER BY b.check_in_date DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Error loading bookings.';
}

// Handle cancel booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $booking_id = $_POST['booking_id'] ?? null;
    
    if ($booking_id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ? AND user_id = ?");
            $stmt->execute([$booking_id, $user_id]);
            $booking = $stmt->fetch();
            
            if ($booking && in_array($booking['status'], ['pending', 'confirmed'])) {
                $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
                $stmt->execute([$booking_id]);
                
                $_SESSION['flash_msg'] = 'Booking cancelled successfully.';
                $_SESSION['flash_type'] = 'success';
                redirect_to('guest/my_bookings.php');
            }
        } catch (Exception $e) {
            $error = 'Error cancelling booking.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h2 class="section-title">My Bookings</h2>
            <p class="section-subtitle">View and manage your reservations</p>

            <?php if ($error): ?>
                <div class="flash-message flash-error">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (count($bookings) > 0): ?>
                <div class="card">
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
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
                                            <strong><?php echo htmlspecialchars($booking['room_type']); ?></strong><br>
                                            <span style="color: var(--text-light); font-size: 0.9rem;"><?php echo htmlspecialchars($booking['room_number']); ?></span>
                                        </td>
                                        <td><?php echo format_date($booking['check_in_date']); ?></td>
                                        <td><?php echo format_date($booking['check_out_date']); ?></td>
                                        <td><strong>$<?php echo number_format($booking['price'], 2); ?></strong></td>
                                        <td><span class="badge badge-<?php echo $booking['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?></span></td>
                                        <td><span class="badge badge-<?php echo $booking['payment_status']; ?>"><?php echo ucfirst($booking['payment_status']); ?></span></td>
                                        <td style="min-width: 200px;">
                                            <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                                <form method="POST" onsubmit="return confirm('Cancel this booking?');" style="display: inline;">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($booking['status'] === 'checked_out' || $booking['status'] === 'completed'): ?>
                                                <?php
                                                // Check if already reviewed
                                                $stmt_review = $pdo->prepare("SELECT review_id FROM reviews WHERE booking_id = ?");
                                                $stmt_review->execute([$booking['booking_id']]);
                                                $has_review = $stmt_review->fetch();
                                                ?>
                                                <?php if (!$has_review): ?>
                                                    <a href="<?php echo app_url('guest/review.php'); ?>?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-primary btn-sm">Write Review</a>
                                                <?php else: ?>
                                                    <span class="badge badge-confirmed">✓ Reviewed</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <p style="color: var(--text-light); font-size: 1.1rem; margin-bottom: 1rem;">No bookings yet.</p>
                    <a href="<?php echo app_url('public/rooms.php'); ?>" class="btn btn-primary">Browse Rooms & Book Now</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
