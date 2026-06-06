<?php
require_once __DIR__ . '/../includes/require_guest.php';

$booking_id = $_GET['booking_id'] ?? null;
$error = '';
$booking = null;
$payment = null;

if (!$booking_id) {
    set_flash('error', 'Invalid booking ID');
    redirect_to('guest/my_bookings.php');
}

try {
    // Get booking details
    $stmt = $pdo->prepare("
        SELECT b.*, r.room_number, r.room_type, r.price, p.payment_id, p.payment_status, p.method, p.amount
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        set_flash('error', 'Booking not found');
        redirect_to('guest/my_bookings.php');
    }

    // Get payment if exists
    if ($booking['payment_id']) {
        $payment = [
            'payment_id' => $booking['payment_id'],
            'payment_status' => $booking['payment_status'],
            'method' => $booking['method'],
            'amount' => $booking['amount']
        ];
    }
} catch (Exception $e) {
    error_log("Error fetching booking: " . $e->getMessage());
    set_flash('error', 'Error loading booking details');
    redirect_to('guest/my_bookings.php');
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_payment') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF token verification failed';
    } else {
        $payment_method = $_POST['payment_method'] ?? '';
        $card_name = $_POST['card_name'] ?? '';
        $card_number = $_POST['card_number'] ?? '';

        // Validate payment method
        $valid_methods = ['card', 'bkash', 'nagad', 'cash'];
        if (!in_array($payment_method, $valid_methods)) {
            $error = 'Invalid payment method selected';
        } elseif ($payment_method === 'card') {
            // Validate card details
            if (empty($card_name) || empty($card_number)) {
                $error = 'Please enter complete card details';
            } elseif (strlen($card_number) < 13 || strlen($card_number) > 19) {
                $error = 'Invalid card number format';
            }
        }

        if (!$error) {
            try {
                // Check if payment already exists
                $stmt_check = $pdo->prepare("SELECT payment_id FROM payments WHERE booking_id = ?");
                $stmt_check->execute([$booking_id]);
                $existing_payment = $stmt_check->fetch();

                $amount = $booking['total_price'];

                if ($existing_payment) {
                    // Update existing payment
                    $stmt_update = $pdo->prepare("
                        UPDATE payments 
                        SET method = ?, payment_status = ?, amount = ?, paid_at = NOW()
                        WHERE payment_id = ?
                    ");
                    $stmt_update->execute([$payment_method, 'paid', $amount, $existing_payment['payment_id']]);
                } else {
                    // Create new payment record
                    $stmt_insert = $pdo->prepare("
                        INSERT INTO payments (booking_id, amount, method, payment_status, paid_at)
                        VALUES (?, ?, ?, 'paid', NOW())
                    ");
                    $stmt_insert->execute([$booking_id, $amount, $payment_method]);
                }

                set_flash('success', 'Payment processed successfully! Your booking is confirmed.');
                redirect_to('guest/payment_success.php?booking_id=' . $booking_id);
            } catch (Exception $e) {
                error_log("Payment processing error: " . $e->getMessage());
                $error = 'Error processing payment. Please try again.';
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
    <title>Payment - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
    <style>
        .payment-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 2rem 0;
        }

        @media (max-width: 768px) {
            .payment-container {
                grid-template-columns: 1fr;
            }
        }

        .booking-summary {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            height: fit-content;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .summary-label {
            color: var(--text-light);
            font-weight: 500;
        }

        .summary-value {
            font-weight: 600;
            color: var(--text-dark);
        }

        .total-amount {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1rem;
            border-radius: 6px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin-top: 1rem;
        }

        .payment-form {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--input-bg);
            color: var(--text-dark);
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .method-option {
            position: relative;
        }

        .method-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .method-label {
            display: block;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--input-bg);
        }

        .method-option input[type="radio"]:checked + .method-label {
            border-color: var(--primary-color);
            background: rgba(59, 130, 246, 0.1);
            font-weight: 600;
            color: var(--primary-color);
        }

        .card-details {
            display: none;
            background: rgba(59, 130, 246, 0.05);
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .card-details.active {
            display: block;
        }

        .card-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .info-box strong {
            display: block;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .payment-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 6px;
            color: #22c55e;
            margin-bottom: 1rem;
        }

        .payment-status::before {
            content: "✓";
            font-weight: bold;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h2 class="section-title">Complete Payment</h2>
            <p class="section-subtitle">Secure payment for your booking</p>

            <?php if ($error): ?>
                <div class="flash-message flash-error">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($payment && $payment['payment_status'] === 'paid'): ?>
                <div class="card">
                    <div class="payment-status">
                        Payment Already Completed
                    </div>
                    <p style="color: var(--text-light); margin-bottom: 1rem;">
                        This booking has already been paid for using <strong><?php echo ucfirst($payment['method']); ?></strong>.
                    </p>
                    <a href="<?php echo app_url('guest/my_bookings.php'); ?>" class="btn btn-primary">Back to Bookings</a>
                </div>
            <?php else: ?>
                <div class="payment-container">
                    <!-- Booking Summary -->
                    <div class="booking-summary">
                        <h3 style="margin-bottom: 1.5rem;">Booking Summary</h3>
                        
                        <div class="summary-item">
                            <span class="summary-label">Room Type:</span>
                            <span class="summary-value"><?php echo htmlspecialchars($booking['room_type']); ?></span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Room Number:</span>
                            <span class="summary-value"><?php echo htmlspecialchars($booking['room_number']); ?></span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Check-in:</span>
                            <span class="summary-value"><?php echo format_date($booking['check_in_date']); ?></span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Check-out:</span>
                            <span class="summary-value"><?php echo format_date($booking['check_out_date']); ?></span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Number of Nights:</span>
                            <span class="summary-value">
                                <?php
                                $nights = (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / (60 * 60 * 24);
                                echo $nights;
                                ?>
                            </span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">Price per Night:</span>
                            <span class="summary-value"><?php echo format_price($booking['price']); ?></span>
                        </div>

                        <div class="total-amount">
                            <?php echo format_price($booking['total_price']); ?>
                        </div>

                        <div style="margin-top: 1rem; font-size: 0.9rem; color: var(--text-light); text-align: center;">
                            <p>This is a DEMO payment system for demonstration purposes.</p>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <div class="payment-form">
                        <h3 style="margin-bottom: 1.5rem;">Payment Details</h3>

                        <div class="info-box">
                            <strong>Demo Payment Methods Available</strong>
                            <p>This is a demonstration system. All payment methods are simulated. No real charges will be made.</p>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="action" value="process_payment">

                            <div class="form-group">
                                <label>Select Payment Method</label>
                                <div class="payment-methods">
                                    <div class="method-option">
                                        <input type="radio" id="method_card" name="payment_method" value="card" required onchange="toggleCardDetails()">
                                        <label for="method_card" class="method-label">
                                            💳 Card
                                        </label>
                                    </div>
                                    <div class="method-option">
                                        <input type="radio" id="method_bkash" name="payment_method" value="bkash" required onchange="toggleCardDetails()">
                                        <label for="method_bkash" class="method-label">
                                            📱 bKash
                                        </label>
                                    </div>
                                    <div class="method-option">
                                        <input type="radio" id="method_nagad" name="payment_method" value="nagad" required onchange="toggleCardDetails()">
                                        <label for="method_nagad" class="method-label">
                                            📱 Nagad
                                        </label>
                                    </div>
                                    <div class="method-option">
                                        <input type="radio" id="method_cash" name="payment_method" value="cash" required onchange="toggleCardDetails()">
                                        <label for="method_cash" class="method-label">
                                            💵 Cash
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="card-details" id="cardDetails">
                                <div class="form-group">
                                    <label for="card_name">Cardholder Name</label>
                                    <input type="text" id="card_name" name="card_name" placeholder="John Doe">
                                </div>

                                <div class="form-group">
                                    <label for="card_number">Card Number</label>
                                    <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                                </div>

                                <div class="card-row">
                                    <div class="form-group">
                                        <label for="card_expiry">Expiry Date</label>
                                        <input type="text" id="card_expiry" placeholder="MM/YY" maxlength="5" onkeyup="formatExpiry(this)">
                                    </div>
                                    <div class="form-group">
                                        <label for="card_cvv">CVV</label>
                                        <input type="text" id="card_cvv" placeholder="123" maxlength="4" onkeyup="this.value = this.value.replace(/[^0-9]/g, '')">
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top: 1.5rem;">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    Pay <?php echo format_price($booking['total_price']); ?>
                                </button>
                            </div>
                        </form>

                        <div style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-light); text-align: center;">
                            <p>🔒 All transactions are secure and encrypted</p>
                            <p style="margin-top: 0.5rem;">Test card: 4532 1111 1111 1111</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function toggleCardDetails() {
            const method = document.querySelector('input[name="payment_method"]:checked').value;
            const cardDetails = document.getElementById('cardDetails');
            const cardNameInput = document.getElementById('card_name');
            const cardNumberInput = document.getElementById('card_number');

            if (method === 'card') {
                cardDetails.classList.add('active');
                cardNameInput.required = true;
                cardNumberInput.required = true;
            } else {
                cardDetails.classList.remove('active');
                cardNameInput.required = false;
                cardNumberInput.required = false;
            }
        }

        function formatExpiry(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            input.value = value;
        }

        // Format card number with spaces
        document.getElementById('card_number').addEventListener('input', function() {
            let value = this.value.replace(/\s/g, '').replace(/[^0-9]/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            this.value = formattedValue;
        });
    </script>
</body>
</html>
