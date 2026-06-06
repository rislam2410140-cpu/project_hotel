<?php
require_once __DIR__ . '/../includes/require_admin.php';

$payments = [];
$filter_status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$error = '';

try {
    $query = "
        SELECT p.*, b.booking_id, b.check_in_date, b.check_out_date, 
               u.name, u.email, r.room_number, r.room_type
        FROM payments p
        JOIN bookings b ON p.booking_id = b.booking_id
        JOIN users u ON b.user_id = u.user_id
        JOIN rooms r ON b.room_id = r.room_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($filter_status) {
        $query .= " AND p.payment_status = ?";
        $params[] = $filter_status;
    }
    
    if ($search) {
        $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR r.room_number LIKE ?)";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $query .= " ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching payments: " . $e->getMessage());
    $error = 'Error loading payments.';
}

// Get payment statistics
$stats = [
    'total_payments' => 0,
    'total_amount' => 0,
    'paid_count' => 0,
    'pending_count' => 0,
    'failed_count' => 0
];

try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(amount) as total_amount,
            SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid,
            SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) as failed
        FROM payments
    ");
    $result = $stmt->fetch();
    $stats = [
        'total_payments' => $result['total'] ?? 0,
        'total_amount' => $result['total_amount'] ?? 0,
        'paid_count' => $result['paid'] ?? 0,
        'pending_count' => $result['pending'] ?? 0,
        'failed_count' => $result['failed'] ?? 0
    ];
} catch (Exception $e) {
    error_log("Error fetching payment stats: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0.5rem 0;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-bar {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-bar select,
        .filter-bar input {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: var(--input-bg);
            color: var(--text-dark);
        }

        .filter-bar select:focus,
        .filter-bar input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .payment-table {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .payment-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-table th {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }

        .payment-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .payment-table tr:last-child td {
            border-bottom: none;
        }

        .payment-table tr:hover {
            background: rgba(59, 130, 246, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
        }

        .status-paid {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }

        .status-pending {
            background: rgba(234, 179, 8, 0.2);
            color: #eab308;
        }

        .status-failed {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .method-badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            background: rgba(59, 130, 246, 0.2);
            color: var(--primary-color);
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-light);
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <div style="margin-bottom: 2rem;">
                <h2 class="section-title">Payment Management</h2>
                <p class="section-subtitle">View and manage all payment transactions</p>
            </div>

            <?php if ($error): ?>
                <div class="flash-message flash-error">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Payments</div>
                    <div class="stat-value"><?php echo $stats['total_payments']; ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value"><?php echo format_price($stats['total_amount']); ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-label" style="color: #22c55e;">Paid</div>
                    <div class="stat-value" style="color: #22c55e;"><?php echo $stats['paid_count']; ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-label" style="color: #eab308;">Pending</div>
                    <div class="stat-value" style="color: #eab308;"><?php echo $stats['pending_count']; ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-label" style="color: #ef4444;">Failed</div>
                    <div class="stat-value" style="color: #ef4444;"><?php echo $stats['failed_count']; ?></div>
                </div>
            </div>

            <!-- Filter Bar -->
            <form method="GET" class="filter-bar">
                <select name="status" onchange="this.form.submit()">
                    <option value="">All Payment Status</option>
                    <option value="paid" <?php echo $filter_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="failed" <?php echo $filter_status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>

                <input type="text" name="search" placeholder="Search by guest name, email or room number..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 250px;">

                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($filter_status || $search): ?>
                    <a href="<?php echo app_url('admin/payments.php'); ?>" class="btn btn-secondary">Clear Filters</a>
                <?php endif; ?>
            </form>

            <!-- Payments Table -->
            <?php if (count($payments) > 0): ?>
                <div class="payment-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Paid Date</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($payment['name']); ?></strong><br>
                                        <span style="color: var(--text-light); font-size: 0.9rem;"><?php echo htmlspecialchars($payment['email']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($payment['room_type']); ?></strong><br>
                                        <span style="color: var(--text-light); font-size: 0.9rem;"><?php echo htmlspecialchars($payment['room_number']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo format_price($payment['amount']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="method-badge">
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
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                                            <?php echo ucfirst($payment['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $payment['paid_at'] ? format_date($payment['paid_at']) : '-'; ?></td>
                                    <td><?php echo format_date($payment['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="card empty-state">
                    <p>No payments found.</p>
                    <?php if ($filter_status || $search): ?>
                        <a href="<?php echo app_url('admin/payments.php'); ?>" class="btn btn-primary">View All Payments</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
