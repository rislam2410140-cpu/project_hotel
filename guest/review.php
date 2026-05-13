<?php
require_once __DIR__ . '/../includes/require_guest.php';

$user_id = $_SESSION['user_id'];
$booking_id = $_GET['booking_id'] ?? null;
$booking = null;
$error = '';

if (!$booking_id || !is_numeric($booking_id)) {
    redirect_to('guest/my_bookings.php');
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, r.room_type, r.room_number
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.booking_id = ? AND b.user_id = ? AND b.status IN ('checked_out', 'completed')
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        $_SESSION['flash_msg'] = 'Booking not found or cannot be reviewed yet.';
        $_SESSION['flash_type'] = 'error';
        redirect_to('guest/my_bookings.php');
    }
    
    // Check if already reviewed
    $stmt = $pdo->prepare("SELECT review_id FROM reviews WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    if ($stmt->fetch()) {
        $_SESSION['flash_msg'] = 'You have already reviewed this booking.';
        $_SESSION['flash_type'] = 'warning';
        redirect_to('guest/my_bookings.php');
    }
} catch (Exception $e) {
    $error = 'Error loading booking.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'] ?? null;
    $comment = trim($_POST['comment'] ?? '');
    
    if (!$rating || $rating < 1 || $rating > 5) {
        $error = 'Please select a rating (1-5 stars).';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO reviews (booking_id, rating, comment)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$booking_id, $rating, $comment ?: null]);
            
            $_SESSION['flash_msg'] = 'Thank you! Your review has been posted.';
            $_SESSION['flash_type'] = 'success';
            redirect_to('guest/my_bookings.php');
        } catch (Exception $e) {
            $error = 'Error posting review. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Review - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <a href="<?php echo app_url('guest/my_bookings.php'); ?>">← Back to Bookings</a>

            <div style="max-width: 600px; margin: 2rem auto;">
                <div class="card">
                    <h2 style="text-align: center; margin-bottom: 1rem;">Share Your Experience</h2>

                    <?php if ($booking): ?>
                        <div style="background: var(--light); padding: 1.5rem; border-radius: 0.375rem; margin-bottom: 1.5rem; text-align: center;">
                            <p style="margin: 0; color: var(--text-light);">
                                <strong><?php echo htmlspecialchars($booking['room_type']); ?></strong> (<?php echo htmlspecialchars($booking['room_number']); ?>)<br>
                                <?php echo format_date($booking['check_in_date']); ?> - <?php echo format_date($booking['check_out_date']); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="flash-message flash-error" style="margin-bottom: 1rem;">
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Rating (1-5 Stars) *</label>
                            <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label style="display: flex; align-items: center; margin: 0;">
                                        <input type="radio" name="rating" value="<?php echo $i; ?>" required>
                                        <span style="margin-left: 0.5rem; font-size: 1.5rem;">
                                            <?php echo str_repeat('⭐', $i); ?>
                                        </span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Your Comment</label>
                            <textarea name="comment" placeholder="Tell us about your stay..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Post Review</button>
                    </form>

                    <p style="text-align: center; color: var(--text-light); margin-top: 1rem; font-size: 0.9rem;">
                        Your feedback helps us improve our service.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
