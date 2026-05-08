<?php
require_once __DIR__ . '/../includes/require_guest.php';

$room_id = $_GET['room_id'] ?? null;
$room = null;
$error = '';

if (!$room_id || !is_numeric($room_id)) {
    redirect_to('public/rooms.php');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ? AND status = 'available'");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();
    
    if (!$room) {
        $_SESSION['flash_msg'] = 'Room not available.';
        $_SESSION['flash_type'] = 'error';
        redirect_to('public/rooms.php');
    }
} catch (Exception $e) {
    $error = 'Error loading room information.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $check_in = trim($_POST['check_in_date'] ?? '');
        $check_out = trim($_POST['check_out_date'] ?? '');
        
        if (empty($check_in) || empty($check_out)) {
            $error = 'Please select both check-in and check-out dates.';
        } else {
            $check_in_obj = DateTime::createFromFormat('Y-m-d', $check_in);
            $check_out_obj = DateTime::createFromFormat('Y-m-d', $check_out);
            $today = new DateTime('now');
            
            if (!$check_in_obj || !$check_out_obj) {
                $error = 'Invalid date format.';
            } elseif ($check_in_obj <= $today) {
                $error = 'Check-in date must be in the future.';
            } elseif ($check_out_obj <= $check_in_obj) {
                $error = 'Check-out date must be after check-in date.';
            } else {
                try {
                    // Check for overlapping bookings
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count FROM bookings 
                        WHERE room_id = ? 
                        AND status IN ('pending', 'confirmed', 'checked_in')
                        AND ((check_in_date < ? AND check_out_date > ?) 
                             OR (check_in_date < ? AND check_out_date > ?))
                    ");
                    $stmt->execute([$room_id, $check_out, $check_in, $check_out, $check_in]);
                    $overlap = $stmt->fetch()['count'];
                    
                    if ($overlap > 0) {
                        $error = 'This room is already booked for these dates. Please select different dates.';
                    } else {
                        // Calculate total price (use dynamic price if available)
                        $nights = $check_in_obj->diff($check_out_obj)->days;
                        $room_price = $room['current_dynamic_price'] ?? $room['price'];
                        $total_price = round($nights * (float)$room_price, 2);
                        
                        // Create booking
                        $stmt = $pdo->prepare("
                            INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price, status)
                            VALUES (?, ?, ?, ?, ?, 'pending')
                        ");
                        $stmt->execute([$_SESSION['user_id'], $room_id, $check_in, $check_out, $total_price]);
                        
                        $booking_id = $pdo->lastInsertId();
                        
                        // Create payment record
                        $stmt = $pdo->prepare("
                            INSERT INTO payments (booking_id, amount, method, payment_status)
                            VALUES (?, ?, 'cash', 'pending')
                        ");
                        $stmt->execute([$booking_id, $total_price]);
                        
                        set_flash('success', 'Booking created successfully! Please review and confirm.');
                        redirect_to('guest/my_bookings.php');
                    }
                } catch (Exception $e) {
                    $error = 'Booking failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Room - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <a href="<?php echo app_url('public/room_details.php'); ?>?room_id=<?php echo $room_id; ?>">← Back to Room</a>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
                <!-- Room Info -->
                <div>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($room['room_type']); ?></h3>
                        <p style="color: var(--text-light); margin: 1rem 0;">Room <?php echo htmlspecialchars($room['room_number']); ?></p>
                        <table style="margin-top: 1rem;">
                            <tr>
                                <td><strong>Room Type:</strong></td>
                                <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Capacity:</strong></td>
                                <td><?php echo $room['capacity']; ?> guests</td>
                            </tr>
                            <tr>
                                <td><strong>Price:</strong></td>
                                <td><strong>$<?php echo number_format($room['price'], 2); ?>/night</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Booking Form -->
                <div>
                    <div class="card">
                        <h3>Book This Room</h3>

                        <?php if ($error): ?>
                            <div class="flash-message flash-error" style="margin-bottom: 1rem;">
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <div class="form-group">
                                <label>Check-in Date *</label>
                                <input type="date" name="check_in_date" id="check_in" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            </div>
                            <div class="form-group">
                                <label>Check-out Date *</label>
                                <input type="date" name="check_out_date" id="check_out" required min="<?php echo date('Y-m-d', strtotime('+2 days')); ?>">
                            </div>

                            <div style="background: var(--light); padding: 1rem; border-radius: 0.375rem; margin-bottom: 1.5rem;">
                                <p style="color: var(--text-light); margin-bottom: 0.5rem;">
                                    <strong>Number of Nights:</strong> <span id="nights">-</span>
                                </p>
                                <p style="color: var(--text-light);">
                                    <strong>Total Price:</strong> <span id="total_price" style="color: var(--primary); font-weight: bold; font-size: 1.25rem;">-</span>
                                </p>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Proceed with Booking</button>
                        </form>

                        <p style="text-align: center; color: var(--text-light); margin-top: 1rem; font-size: 0.9rem;">
                            💡 Select dates above to see the total price
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        const checkInInput = document.getElementById('check_in');
        const checkOutInput = document.getElementById('check_out');
        const nightsSpan = document.getElementById('nights');
        const totalPriceSpan = document.getElementById('total_price');
        const roomPrice = <?php echo $room['price']; ?>;

        function updateCalculations() {
            const checkIn = new Date(checkInInput.value);
            const checkOut = new Date(checkOutInput.value);

            if (checkIn && checkOut && checkOut > checkIn) {
                const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
                const totalPrice = nights * roomPrice;
                
                nightsSpan.textContent = nights;
                totalPriceSpan.textContent = '$' + totalPrice.toFixed(2);
                
                // Update min date for check-out
                const minCheckOut = new Date(checkIn);
                minCheckOut.setDate(minCheckOut.getDate() + 1);
                checkOutInput.min = minCheckOut.toISOString().split('T')[0];
            }
        }

        checkInInput.addEventListener('change', updateCalculations);
        checkOutInput.addEventListener('change', updateCalculations);
    </script>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
