<?php
/**
 * Admin Pricing Dashboard
 * Displays current dynamic pricing, revenue impact, and trends
 */

require_once __DIR__ . '/../includes/require_admin.php';

$pdo = Database::getConnection();

try {
    // Get current room pricing overview
    $stmt = $pdo->query("
        SELECT 
            r.room_id,
            r.room_number,
            r.room_type,
            r.price as base_price,
            r.current_dynamic_price,
            ROUND((COALESCE(r.current_dynamic_price, r.price) - r.price) / r.price * 100, 1) as price_change_percent,
            CASE 
                WHEN b.booking_id IS NOT NULL THEN 'Occupied'
                ELSE 'Available'
            END as status
        FROM rooms r
        LEFT JOIN bookings b ON r.room_id = b.room_id 
            AND b.status IN ('confirmed', 'checked_in')
            AND b.check_in_date <= CURDATE()
            AND b.check_out_date > CURDATE()
        ORDER BY r.room_number
    ");
    $room_pricing = $stmt->fetchAll();
} catch (Exception $e) {
    $room_pricing = [];
}

try {
    // Get today's occupancy
    $stmt = $pdo->query("
        SELECT occupancy_percent, total_rooms, occupied_rooms
        FROM occupancy_history
        WHERE history_date = CURDATE()
        LIMIT 1
    ");
    $occupancy = $stmt->fetch();
} catch (Exception $e) {
    $occupancy = null;
}

try {
    // Get revenue impact (comparing base vs dynamic pricing)
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            AVG(adjusted_price - base_price) as avg_price_increase,
            SUM(adjusted_price - base_price) as total_additional_revenue,
            COUNT(*) as updates
        FROM pricing_history
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $revenue_impact = $stmt->fetchAll();
} catch (Exception $e) {
    $revenue_impact = [];
}

try {
    // Get active pricing rules
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_rules, 
               SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_rules,
               SUM(CASE WHEN is_active = FALSE THEN 1 ELSE 0 END) as inactive_rules
        FROM pricing_rules
    ");
    $rules_stats = $stmt->fetch();
} catch (Exception $e) {
    $rules_stats = ['total_rules' => 0, 'active_rules' => 0, 'inactive_rules' => 0];
}

try {
    // Get pricing performance metrics
    $stmt = $pdo->query("
        SELECT 
            AVG(occupancy_percent) as avg_occupancy,
            MAX(occupancy_percent) as peak_occupancy,
            MIN(occupancy_percent) as min_occupancy,
            DATE(history_date) as date
        FROM occupancy_history
        WHERE history_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(history_date)
    ");
    $occupancy_trend = $stmt->fetchAll();
} catch (Exception $e) {
    $occupancy_trend = [];
}

include_once __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <style>
        .section {
            padding: 2rem 0;
            min-height: calc(100vh - 200px);
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            color: var(--text-light);
            margin-bottom: 2rem;
            font-size: 1.05rem;
        }

        .grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            border-left: 4px solid var(--primary);
            box-shadow: var(--shadow-md);
            transition: all var(--transition-base);
            border: 1px solid var(--border-light);
        }

        .stat-card:nth-child(2) {
            border-left-color: var(--success);
        }

        .stat-card:nth-child(3) {
            border-left-color: var(--warning);
        }

        .stat-card:nth-child(4) {
            border-left-color: var(--accent);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .stat-detail {
            font-size: 0.85rem;
            color: var(--text-lighter);
            margin-top: 0.5rem;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: var(--shadow-md);
            padding: 2rem;
            transition: all var(--transition-base);
            border: 1px solid var(--border-light);
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-full {
            grid-column: 1 / -1;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 0.5rem;
            border: 1px solid var(--border-light);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        thead {
            background: var(--light-hover);
            border-bottom: 2px solid var(--border);
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            letter-spacing: 0.3px;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-light);
        }

        tbody tr:hover {
            background: var(--light);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 0.375rem;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .badge-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.05) 100%);
            color: #047857;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .badge-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(245, 158, 11, 0.05) 100%);
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .badge-info {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.15) 0%, rgba(37, 99, 235, 0.05) 100%);
            color: #1e3a8a;
            border: 1px solid rgba(37, 99, 235, 0.2);
        }

        .badge-positive {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.05) 100%);
            color: #047857;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .stats-table tr {
            border-bottom: 1px solid var(--border-light);
        }

        .stats-table tr:hover {
            background: var(--light);
        }

        .stats-table th,
        .stats-table td {
            padding: 0.875rem 1rem;
            text-align: left;
        }

        .stats-table th {
            font-weight: 600;
            background: var(--light-hover);
            color: var(--dark);
            border-bottom: 2px solid var(--border);
        }

        .chart-container {
            background: var(--light-hover);
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border-light);
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
        }

        .empty-state-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.4;
        }

        .btn-link {
            display: inline-block;
            margin-right: 1rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition-base);
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        }

        .action-bar {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 0.75rem;
            box-shadow: var(--shadow-md);
            margin-top: 2rem;
            text-align: center;
            border: 1px solid var(--border-light);
        }

        .action-bar h3 {
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .price-positive {
            color: var(--success);
            font-weight: 600;
        }

        .price-neutral {
            color: var(--text);
        }

        .price-negative {
            color: var(--danger);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h2 class="section-title">📊 Dynamic Pricing Dashboard</h2>
            <p class="section-subtitle">Real-time pricing analytics and revenue optimization</p>

            <!-- KPI Cards -->
            <div class="grid-4">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $occupancy ? $occupancy['occupancy_percent'] . '%' : 'N/A'; ?></div>
                    <div class="stat-label">Today's Occupancy</div>
                    <div class="stat-detail"><?php echo $occupancy ? $occupancy['occupied_rooms'] . '/' . $occupancy['total_rooms'] . ' rooms' : 'No data'; ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-number"><?php echo $rules_stats['active_rules'] ?? 0; ?></div>
                    <div class="stat-label">Active Rules</div>
                    <div class="stat-detail"><?php echo ($rules_stats['total_rules'] ?? 0) - ($rules_stats['active_rules'] ?? 0); ?> inactive</div>
                </div>

                <div class="stat-card">
                    <div class="stat-number">
                        $<?php 
                        $avg_increase = 0;
                        if (!empty($revenue_impact)) {
                            $avg_increase = $revenue_impact[0]['avg_price_increase'] ?? 0;
                        }
                        echo number_format($avg_increase, 2);
                        ?>
                    </div>
                    <div class="stat-label">Avg Price Change</div>
                    <div class="stat-detail">Per room per day</div>
                </div>

                <div class="stat-card">
                    <div class="stat-number">
                        $<?php 
                        $total_additional = 0;
                        if (!empty($revenue_impact)) {
                            $total_additional = array_sum(array_column($revenue_impact, 'total_additional_revenue'));
                        }
                        echo number_format($total_additional, 0);
                        ?>
                    </div>
                    <div class="stat-label">7-Day Revenue Boost</div>
                    <div class="stat-detail">From dynamic pricing</div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid-2">
                <!-- Current Room Pricing -->
                <div class="card card-full">
                    <h3>💲 Current Room Pricing</h3>
                    
                    <?php if (empty($room_pricing)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📭</div>
                            <p>No rooms available</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Room #</th>
                                        <th>Type</th>
                                        <th>Base Price</th>
                                        <th>Dynamic Price</th>
                                        <th>Adjustment</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($room_pricing as $room): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                            <td>$<?php echo number_format($room['base_price'], 2); ?></td>
                                            <td>
                                                <strong>$<?php echo number_format($room['current_dynamic_price'] ?? $room['base_price'], 2); ?></strong>
                                            </td>
                                            <td>
                                                <?php 
                                                $change = $room['price_change_percent'] ?? 0;
                                                $class = $change > 0 ? 'price-positive' : ($change < 0 ? 'price-negative' : 'price-neutral');
                                                ?>
                                                <span class="<?php echo $class; ?>">
                                                    <?php echo ($change > 0 ? '+' : '') . $change . '%'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $room['status'] === 'Occupied' ? 'warning' : 'success'; ?>">
                                                    <?php echo $room['status'] === 'Occupied' ? '🔴 Occupied' : '✓ Available'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Revenue Impact -->
                <div class="card">
                    <h3>📈 Revenue Impact (7 Days)</h3>
                    
                    <?php if (!empty($revenue_impact)): ?>
                        <div class="chart-container">
                            <table class="stats-table">
                                <tr>
                                    <th>Date</th>
                                    <th>Avg Increase</th>
                                    <th>Total Revenue</th>
                                </tr>
                                <?php foreach ($revenue_impact as $impact): ?>
                                    <tr>
                                        <td><?php echo date('M d', strtotime($impact['date'])); ?></td>
                                        <td>
                                            <span class="badge badge-positive">
                                                +$<?php echo number_format($impact['avg_price_increase'], 2); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="price-positive">+$<?php echo number_format($impact['total_additional_revenue'], 2); ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📊</div>
                            <p>No pricing data yet. Create rules and bookings to see impact.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Occupancy Trend -->
                <div class="card">
                    <h3>📊 Occupancy Trend (7 Days)</h3>
                    
                    <?php if (!empty($occupancy_trend)): ?>
                        <div class="chart-container">
                            <table class="stats-table">
                                <tr>
                                    <th>Date</th>
                                    <th>Min</th>
                                    <th>Avg</th>
                                    <th>Peak</th>
                                </tr>
                                <?php foreach ($occupancy_trend as $trend): ?>
                                    <tr>
                                        <td><?php echo date('M d', strtotime($trend['date'])); ?></td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $trend['min_occupancy'] ?? 0; ?>%</span>
                                        </td>
                                        <td>
                                            <strong><?php echo round($trend['avg_occupancy'] ?? 0); ?>%</strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-warning"><?php echo $trend['peak_occupancy'] ?? 0; ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📈</div>
                            <p>No occupancy data available yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="action-bar">
                <h3>⚡ Quick Actions</h3>
                <a href="<?php echo app_url('admin/pricing_rules.php'); ?>" class="btn-link">⚙️ Manage Pricing Rules</a>
                <a href="<?php echo app_url('admin/bookings.php'); ?>" class="btn-link btn-secondary">📅 View All Bookings</a>
            </div>
        </div>
    </section>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
