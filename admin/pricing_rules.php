<?php
/**
 * Admin Pricing Rules Management
 * Allows admins to create, edit, and manage dynamic pricing rules
 */

require_once __DIR__ . '/../includes/require_admin.php';

$pdo = Database::getConnection();
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_rule') {
        try {
            $rule_name = $_POST['rule_name'] ?? '';
            $rule_type = $_POST['rule_type'] ?? '';
            $adjustment_type = $_POST['adjustment_type'] ?? 'percentage';
            $adjustment_value = (float)($_POST['adjustment_value'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $applies_to_room_types = $_POST['applies_to_room_types'] ?? '';
            
            $season_name = null;
            $season_start_date = null;
            $season_end_date = null;
            $occupancy_min = null;
            $occupancy_max = null;
            
            if ($rule_type === 'seasonal') {
                $season_name = $_POST['season_name'] ?? '';
                $season_start_date = $_POST['season_start_date'] ?? null;
                $season_end_date = $_POST['season_end_date'] ?? null;
            } elseif ($rule_type === 'occupancy') {
                $occupancy_min = (int)($_POST['occupancy_min'] ?? 0);
                $occupancy_max = (int)($_POST['occupancy_max'] ?? 100);
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO pricing_rules 
                (rule_name, rule_type, adjustment_type, adjustment_value, is_active, 
                 applies_to_room_types, season_name, season_start_date, season_end_date,
                 occupancy_min_percent, occupancy_max_percent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $rule_name, $rule_type, $adjustment_type, $adjustment_value, $is_active,
                $applies_to_room_types, $season_name, $season_start_date, $season_end_date,
                $occupancy_min, $occupancy_max
            ]);
            
            $_SESSION['flash_msg'] = 'Pricing rule created successfully!';
            $_SESSION['flash_type'] = 'success';
            redirect_to('admin/pricing_rules.php');
        } catch (Exception $e) {
            $error = "Error creating rule: " . $e->getMessage();
        }
    } 
    elseif ($action === 'delete_rule') {
        try {
            $rule_id = (int)($_POST['rule_id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM pricing_rules WHERE rule_id = ?");
            $stmt->execute([$rule_id]);
            $_SESSION['flash_msg'] = 'Pricing rule deleted!';
            $_SESSION['flash_type'] = 'success';
            redirect_to('admin/pricing_rules.php');
        } catch (Exception $e) {
            $error = "Error deleting rule: " . $e->getMessage();
        }
    }
}

// Get all pricing rules
try {
    $stmt = $pdo->query("
        SELECT * FROM pricing_rules
        ORDER BY created_at DESC
    ");
    $rules = $stmt->fetchAll();
} catch (Exception $e) {
    $rules = [];
    $error = "Error loading rules: " . $e->getMessage();
}

// Get room types for the form
try {
    $stmt = $pdo->query("SELECT DISTINCT room_type FROM rooms ORDER BY room_type");
    $room_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $room_types = [];
}

include_once __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing Rules - <?php echo SITE_NAME; ?></title>
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

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: var(--shadow-md);
            padding: 2rem;
            transition: box-shadow var(--transition-base);
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

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all var(--transition-fast);
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group select {
            cursor: pointer;
        }

        .form-group small {
            display: block;
            margin-top: 0.4rem;
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group.checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group.checkbox input {
            width: auto;
            margin: 0;
        }

        .form-group.checkbox label {
            margin-bottom: 0;
            font-weight: 500;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.625rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            animation: slideInDown 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
            border-color: var(--success);
            color: #047857;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
            border-color: var(--danger);
            color: #7f1d1d;
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

        .badge-secondary {
            background: linear-gradient(135deg, rgba(107, 114, 128, 0.15) 0%, rgba(107, 114, 128, 0.05) 100%);
            color: #374151;
            border: 1px solid rgba(107, 114, 128, 0.2);
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all var(--transition-base);
            font-weight: 600;
            text-align: center;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.3px;
            width: 100%;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            width: auto;
        }

        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state-text {
            color: var(--text-light);
            font-size: 1.05rem;
        }

        .hidden-fields {
            display: none;
        }

        .type-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 0.5rem;
            vertical-align: middle;
        }

        .seasonal .type-indicator {
            background: var(--accent);
        }

        .occupancy .type-indicator {
            background: var(--warning);
        }

        .event .type-indicator {
            background: var(--primary);
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h2 class="section-title">💰 Pricing Rules Management</h2>
            <p class="section-subtitle">Create and manage dynamic pricing rules for your rooms</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="grid-2">
                <!-- Create New Rule -->
                <div class="card">
                    <h3>➕ Create New Rule</h3>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="add_rule">
                        
                        <div class="form-group">
                            <label for="rule_name">Rule Name *</label>
                            <input type="text" id="rule_name" name="rule_name" required 
                                   placeholder="e.g., Summer Peak, High Occupancy Surge">
                        </div>

                        <div class="form-group">
                            <label for="rule_type">Rule Type *</label>
                            <select id="rule_type" name="rule_type" required onchange="updateFormFields()">
                                <option value="">Select rule type...</option>
                                <option value="seasonal">📅 Seasonal (Date Range)</option>
                                <option value="occupancy">📊 Occupancy-Based</option>
                                <option value="event">🎉 Event-Based</option>
                            </select>
                        </div>

                        <!-- Seasonal Fields -->
                        <div id="seasonal-fields" class="hidden-fields">
                            <div class="form-group">
                                <label for="season_name">Season Name</label>
                                <input type="text" id="season_name" name="season_name" 
                                       placeholder="e.g., Summer, Winter, Holiday">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="season_start_date">Start Date</label>
                                    <input type="date" id="season_start_date" name="season_start_date">
                                </div>
                                <div class="form-group">
                                    <label for="season_end_date">End Date</label>
                                    <input type="date" id="season_end_date" name="season_end_date">
                                </div>
                            </div>
                        </div>

                        <!-- Occupancy Fields -->
                        <div id="occupancy-fields" class="hidden-fields">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="occupancy_min">Minimum Occupancy %</label>
                                    <input type="number" id="occupancy_min" name="occupancy_min" 
                                           min="0" max="100" value="70" placeholder="0">
                                </div>
                                <div class="form-group">
                                    <label for="occupancy_max">Maximum Occupancy %</label>
                                    <input type="number" id="occupancy_max" name="occupancy_max" 
                                           min="0" max="100" value="100" placeholder="100">
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="adjustment_type">Adjustment Type *</label>
                                <select id="adjustment_type" name="adjustment_type" required>
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount ($)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="adjustment_value">Adjustment Value *</label>
                                <input type="number" id="adjustment_value" name="adjustment_value" 
                                       required step="0.01" placeholder="e.g., 25 or 50">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="applies_to_room_types">Applies to Room Types</label>
                            <select id="applies_to_room_types" name="applies_to_room_types" multiple>
                                <option value="">All Room Types</option>
                                <?php foreach ($room_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>">
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small>Hold Ctrl/Cmd to select multiple rooms</small>
                        </div>

                        <div class="form-group checkbox">
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <label for="is_active">Activate this rule immediately</label>
                        </div>

                        <button type="submit" class="btn btn-primary">➕ Create Rule</button>
                    </form>
                </div>

                <!-- Rules List -->
                <div class="card">
                    <h3>📋 Active Rules (<?php echo count($rules); ?>)</h3>
                    
                    <?php if (empty($rules)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📭</div>
                            <p class="empty-state-text">No pricing rules yet.<br>Create one to get started!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Rule Name</th>
                                        <th>Type</th>
                                        <th>Adjustment</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rules as $rule): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($rule['rule_name']); ?></strong>
                                                <?php if (!empty($rule['applies_to_room_types'])): ?>
                                                    <div style="font-size: 0.85rem; color: var(--text-light); margin-top: 0.25rem;">
                                                        <?php echo htmlspecialchars($rule['applies_to_room_types']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $rule['rule_type'] === 'seasonal' ? 'info' : 
                                                         ($rule['rule_type'] === 'occupancy' ? 'warning' : 'secondary');
                                                ?> <?php echo $rule['rule_type']; ?>">
                                                    <span class="type-indicator"></span><?php echo ucfirst($rule['rule_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong>
                                                    <?php 
                                                    if ($rule['adjustment_type'] === 'percentage') {
                                                        echo ($rule['adjustment_value'] > 0 ? '+' : '') . $rule['adjustment_value'] . '%';
                                                    } else {
                                                        echo ($rule['adjustment_value'] > 0 ? '+$' : '-$') . abs($rule['adjustment_value']);
                                                    }
                                                    ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $rule['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $rule['is_active'] ? '✓ Active' : '○ Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_rule">
                                                    <input type="hidden" name="rule_id" value="<?php echo $rule['rule_id']; ?>">
                                                    <button type="submit" class="btn btn-danger" 
                                                            onclick="return confirm('Delete this rule?')">🗑️</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script>
        function updateFormFields() {
            const ruleType = document.getElementById('rule_type').value;
            const seasonalFields = document.getElementById('seasonal-fields');
            const occupancyFields = document.getElementById('occupancy-fields');
            
            seasonalFields.classList.toggle('hidden-fields', ruleType !== 'seasonal');
            occupancyFields.classList.toggle('hidden-fields', ruleType !== 'occupancy');
        }
    </script>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
