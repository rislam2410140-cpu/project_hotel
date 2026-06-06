<?php
require_once __DIR__ . '/../includes/require_guest.php';

$booking_id = $_GET['booking_id'] ?? null;
$booking = null;
$payment = null;

if (!$booking_id) {
    redirect_to('guest/my_bookings.php');
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, r.room_number, r.room_type, r.price, p.payment_id, p.payment_status, p.method, p.amount, p.paid_at
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        redirect_to('guest/my_bookings.php');
    }

    if ($booking['payment_id']) {
        $payment = [
            'payment_id' => $booking['payment_id'],
            'payment_status' => $booking['payment_status'],
            'method' => $booking['method'],
            'amount' => $booking['amount'],
            'paid_at' => $booking['paid_at']
        ];
    }
} catch (Exception $e) {
    error_log("Error fetching booking: " . $e->getMessage());
    redirect_to('guest/my_bookings.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
    <style>
        .success-container {
            max-width: 600px;
            margin: 2rem auto;
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 2rem;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-title {
            font-size: 2rem;
            font-weight: bold;
            color: #22c55e;
            margin-bottom: 0.5rem;
        }

        .success-subtitle {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 2rem;
        }

        .receipt {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .receipt-header h4 {
            margin: 0;
            color: var(--primary-color);
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .receipt-row:last-child {
            border-bottom: none;
        }

        .receipt-label {
            color: var(--text-light);
            font-weight: 500;
        }

        .receipt-value {
            font-weight: 600;
            color: var(--text-dark);
        }

        .receipt-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid var(--primary-color);
            font-size: 1.3rem;
        }

        .payment-method-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .confirmation-number {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 2rem;
            font-family: monospace;
            font-size: 0.95rem;
        }

        .confirmation-number strong {
            color: var(--primary-color);
            display: block;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 2rem;
        }

        @media (max-width: 576px) {
            .btn-group {
                grid-template-columns: 1fr;
            }
        }

        .info-banner {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            border-radius: 4px;
            margin-top: 2rem;
        }

        .info-banner strong {
            display: block;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <div class="success-container">
                <div class="success-icon">✓</div>
                <div class="success-title">Payment Successful!</div>
                <p class="success-subtitle">Your booking has been confirmed</p>

                <?php if ($booking && $payment): ?>
                    <div class="confirmation-number">
                        <strong>Confirmation Number</strong>
                        BK-<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?>-<?php echo strtoupper(substr(md5($booking['booking_id'] . $booking['user_id']), 0, 6)); ?>
                    </div>

                    <div class="receipt">
                        <div class="receipt-header">
                            <h4><?php echo SITE_NAME; ?></h4>
                            <p style="margin: 0; color: var(--text-light); font-size: 0.9rem;">Payment Receipt</p>
                        </div>

                        <div class="receipt-row">
                            <span class="receipt-label">Room Type</span>
                            <span class="receipt-value"><?php echo htmlspecialchars($booking['room_type']); ?></span>
                        </div>

                        <div class="receipt-row">
                            <span class="receipt-label">Room Number</span>
                            <span class="receipt-value"><?php echo htmlspecialchars($booking['room_number']); ?></span>
                        </div>

                        <div class="receipt-row">
                            <span class="receipt-label">Check-in</span>
                            <span class="receipt-value"><?php echo format_date($booking['check_in_date']); ?></span>
                        </div>

                        <div class="receipt-row">
                            <span class="receipt-label">Check-out</span>
                            <span class="receipt-value"><?php echo format_date($booking['check_out_date']); ?></span>
                        </div>

                        <div class="receipt-row">
                            <span class="receipt-label">Payment Method</span>
                            <span class="receipt-value">
                                <span class="payment-method-badge">
                                    <?php
                                    $method_emoji = [
                                        'card' => '💳',
                                        'bkash' => '📱',
                                        'nagad' => '📱',
                                        'cash' => '💵'
                                    ];
                                    echo ($method_emoji[$payment['method']] ?? '') . ' ' . ucfirst($payment['method']);
                                    ?>
                                </span>
                            </span>
                        </div>

                        <div class="receipt-row">
                            <span class="receipt-label">Paid At</span>
                            <span class="receipt-value"><?php echo $payment['paid_at'] ? date('M d, Y \a\t H:i A', strtotime($payment['paid_at'])) : 'N/A'; ?></span>
                        </div>

                        <div class="receipt-total">
                            <span>Total Paid</span>
                            <span style="color: #22c55e;"><?php echo format_price($payment['amount']); ?></span>
                        </div>
                    </div>

                    <div class="info-banner">
                        <strong>📧 Confirmation Email</strong>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.95rem; color: var(--text-light);">
                            A confirmation email has been sent to your registered email address with all booking details.
                        </p>
                    </div>

                    <div class="btn-group">
                        <a href="<?php echo app_url('guest/my_bookings.php'); ?>" class="btn btn-primary">View All Bookings</a>
                        <a href="<?php echo app_url('public/rooms.php'); ?>" class="btn btn-secondary">Browse More Rooms</a>
                    </div>
                <?php else: ?>
                    <div class="card" style="text-align: center; padding: 2rem;">
                        <p style="color: var(--text-light); margin-bottom: 1rem;">Booking details not found.</p>
                        <a href="<?php echo app_url('guest/my_bookings.php'); ?>" class="btn btn-primary">Back to Bookings</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
