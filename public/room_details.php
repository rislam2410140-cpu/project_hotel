<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db_connect.php';

$room = null;
$error = '';

if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
    redirect_to('public/rooms.php');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ?");
    $stmt->execute([$_GET['room_id']]);
    $room = $stmt->fetch();
    
    if (!$room) {
        redirect_to('public/rooms.php');
    }
} catch (Exception $e) {
    $error = "Error loading room details";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($room['room_type']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <a href="<?php echo app_url('public/rooms.php'); ?>" style="margin-bottom: 1rem;">← Back to Rooms</a>
            
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 3rem;">
                <!-- Room Info -->
                <div>
                    <div style="background: var(--light); padding: 3rem; border-radius: 0.5rem; text-align: center; margin-bottom: 2rem;">
                        <div style="font-size: 4rem;">🛏️</div>
                        <h1><?php echo htmlspecialchars($room['room_type']); ?></h1>
                        <p style="color: var(--text-light); margin: 1rem 0;">Room <?php echo htmlspecialchars($room['room_number']); ?></p>
                        <div class="badge badge-<?php echo $room['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $room['status'])); ?>
                        </div>
                    </div>

                    <div class="card">
                        <h3>Room Details</h3>
                        <table style="margin-top: 1rem;">
                            <tr>
                                <td style="font-weight: 600;">Room Type:</td>
                                <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Room Number:</td>
                                <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Capacity:</td>
                                <td><?php echo $room['capacity']; ?> guest<?php echo $room['capacity'] > 1 ? 's' : ''; ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600;">Price per Night:</td>
                                <td style="font-size: 1.25rem; color: var(--primary); font-weight: bold;">$<?php echo number_format($room['price'], 2); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Booking Card -->
                <div>
                    <div class="card">
                        <h3>Amenities</h3>
                        <ul style="list-style: none; margin-top: 1rem;">
                            <li style="padding: 0.5rem 0;">✓ Free WiFi</li>
                            <li style="padding: 0.5rem 0;">✓ Air Conditioning</li>
                            <li style="padding: 0.5rem 0;">✓ Flat Screen TV</li>
                            <li style="padding: 0.5rem 0;">✓ Private Bathroom</li>
                            <li style="padding: 0.5rem 0;">✓ Mini Bar</li>
                            <li style="padding: 0.5rem 0;">✓ Work Desk</li>
                            <li style="padding: 0.5rem 0;">✓ Daily Housekeeping</li>
                            <li style="padding: 0.5rem 0;">✓ 24/7 Room Service</li>
                        </ul>
                    </div>

                    <?php if ($room['status'] === 'available'): ?>
                        <div class="card">
                            <h3>Ready to Book?</h3>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'guest'): ?>
                                <p style="color: var(--text-light); margin-bottom: 1.5rem;">
                                    Proceed to book this room for your stay.
                                </p>
                                <a href="<?php echo app_url('guest/book_room.php'); ?>?room_id=<?php echo $room['room_id']; ?>" class="btn btn-primary btn-block">
                                    Book This Room
                                </a>
                            <?php else: ?>
                                <p style="color: var(--text-light); margin-bottom: 1.5rem;">
                                    Please login as a guest to book this room.
                                </p>
                                <a href="<?php echo app_url('guest/login.php'); ?>" class="btn btn-primary btn-block">
                                    Login to Book
                                </a>
                                <p style="text-align: center; margin-top: 1rem; color: var(--text-light);">
                                    Don't have an account? <a href="<?php echo app_url('guest/signup.php'); ?>">Sign up here</a>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <p style="color: var(--danger); font-weight: 500;">This room is currently not available. Please check our other rooms.</p>
                            <a href="<?php echo app_url('public/rooms.php'); ?>" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                                Browse Other Rooms
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
