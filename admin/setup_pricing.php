<?php
/**
 * Pricing Feature Setup Script
 * Run this to initialize pricing tables, procedures, and triggers
 * Access: http://localhost/modern_hotel_management/admin/setup_pricing.php
 */

require_once __DIR__ . '/../includes/require_admin.php';

$messages = [];
$errors = [];
$success = false;

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = Database::getConnection();

        // 1. Load pricing schema migration
        $pricing_migration_file = __DIR__ . '/../database/pricing_migration.sql';
        if (file_exists($pricing_migration_file)) {
            $pricing_migration = file_get_contents($pricing_migration_file);
            foreach (explode(';', $pricing_migration) as $statement) {
                $trimmed = trim($statement);
                if ($trimmed && !preg_match('/^--/', $trimmed)) {
                    try {
                        $pdo->exec($statement);
                    } catch (Exception $e) {
                        // Ignore if already exists
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            error_log("Migration warning: " . $e->getMessage());
                        }
                    }
                }
            }
            $messages[] = "✓ Pricing tables created";
        } else {
            $errors[] = "Pricing migration file not found";
        }

        // 2. Create stored procedures
        $procedures_file = __DIR__ . '/../database/pricing_procedures.sql';
        if (file_exists($procedures_file)) {
            $procedures_content = file_get_contents($procedures_file);
            
            // Split procedures by DELIMITER
            $procs = preg_split('/DELIMITER\s*\/\//i', $procedures_content);
            
            foreach ($procs as $proc) {
                $proc_trimmed = trim($proc);
                if (!empty($proc_trimmed) && !preg_match('/^--/', $proc_trimmed)) {
                    // Clean up the procedure content
                    $proc_trimmed = str_replace('DELIMITER ;', '', $proc_trimmed);
                    $proc_trimmed = str_replace('// DELIMITER ;', '', $proc_trimmed);
                    
                    if (trim($proc_trimmed)) {
                        try {
                            $pdo->exec($proc_trimmed);
                        } catch (Exception $e) {
                            if (strpos($e->getMessage(), 'already exists') === false) {
                                error_log("Procedure error: " . $e->getMessage());
                            }
                        }
                    }
                }
            }
            $messages[] = "✓ Stored procedures created (CalculateDynamicPrice, UpdateOccupancyHistory, ApplyDynamicPricingToAllRooms)";
        } else {
            $errors[] = "Procedures file not found";
        }

        // 3. Create triggers
        $triggers_file = __DIR__ . '/../database/pricing_triggers.sql';
        if (file_exists($triggers_file)) {
            $triggers_content = file_get_contents($triggers_file);
            
            // Split triggers by DELIMITER
            $trigs = preg_split('/DELIMITER\s*\/\//i', $triggers_content);
            
            foreach ($trigs as $trig) {
                $trig_trimmed = trim($trig);
                if (!empty($trig_trimmed) && !preg_match('/^--/', $trig_trimmed)) {
                    // Clean up the trigger content
                    $trig_trimmed = str_replace('DELIMITER ;', '', $trig_trimmed);
                    $trig_trimmed = str_replace('// DELIMITER ;', '', $trig_trimmed);
                    
                    if (trim($trig_trimmed)) {
                        try {
                            $pdo->exec($trig_trimmed);
                        } catch (Exception $e) {
                            if (strpos($e->getMessage(), 'already exists') === false) {
                                error_log("Trigger error: " . $e->getMessage());
                            }
                        }
                    }
                }
            }
            $messages[] = "✓ Triggers created (automatic price updates)";
        } else {
            $errors[] = "Triggers file not found";
        }

        // 4. Load pricing seed data
        $pricing_seed_file = __DIR__ . '/../database/pricing_seed.sql';
        if (file_exists($pricing_seed_file)) {
            $pricing_seed = file_get_contents($pricing_seed_file);
            foreach (explode(';', $pricing_seed) as $statement) {
                $trimmed = trim($statement);
                if ($trimmed && !preg_match('/^--/', $trimmed)) {
                    try {
                        $pdo->exec($statement);
                    } catch (Exception $e) {
                        // Ignore duplicate errors
                        if (strpos($e->getMessage(), 'Duplicate') === false) {
                            error_log("Seed warning: " . $e->getMessage());
                        }
                    }
                }
            }
            $messages[] = "✓ Sample pricing rules and data loaded";
        }

        $success = count($errors) === 0;

    } catch (Exception $e) {
        $errors[] = "Database Error: " . $e->getMessage();
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Pricing Feature - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <style>
        .setup-container {
            max-width: 700px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .setup-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: var(--shadow-md);
            padding: 2rem;
            border: 1px solid var(--border-light);
        }

        .setup-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .setup-subtitle {
            color: var(--text-light);
            text-align: center;
            margin-bottom: 2rem;
        }

        .info-box {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(37, 99, 235, 0.05) 100%);
            border: 1px solid rgba(37, 99, 235, 0.2);
            border-left: 4px solid var(--primary);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-box h4 {
            color: var(--dark);
            margin-bottom: 0.75rem;
            font-size: 1rem;
        }

        .info-box ul {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
            color: var(--text);
        }

        .info-box li {
            margin-bottom: 0.5rem;
        }

        .message-list {
            margin: 1.5rem 0;
        }

        .message {
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 0.75rem;
            border-left: 4px solid;
        }

        .message-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
            border-color: var(--success);
            color: #047857;
        }

        .message-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
            border-color: var(--danger);
            color: #7f1d1d;
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
            width: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
            margin-top: 1rem;
        }

        .next-steps {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .next-steps h4 {
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .next-steps ol {
            margin: 0;
            padding-left: 1.5rem;
        }

        .next-steps li {
            color: var(--text);
            margin-bottom: 0.75rem;
        }

        .next-steps a {
            color: var(--primary);
            font-weight: 600;
        }

        .next-steps a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <section class="section">
        <div class="setup-container">
            <div class="setup-card">
                <h2 class="setup-title">💰 Dynamic Pricing Setup</h2>
                <p class="setup-subtitle">Initialize pricing tables, procedures, and triggers</p>

                <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
                    <div class="info-box">
                        <h4>🔧 What This Setup Does:</h4>
                        <ul>
                            <li>Creates pricing tables (pricing_rules, occupancy_history, pricing_history)</li>
                            <li>Creates 3 stored procedures for price calculations</li>
                            <li>Creates 5 triggers for automatic price updates</li>
                            <li>Loads sample pricing rules and test data</li>
                        </ul>
                    </div>

                    <form method="POST">
                        <button type="submit" class="btn btn-primary">⚙️ Setup Pricing Feature</button>
                    </form>
                    
                    <p style="color: var(--text-light); font-size: 0.9rem; text-align: center; margin-top: 1.5rem;">
                        ⚠️ This will only create new tables/procedures. Existing ones won't be affected.
                    </p>

                <?php else: ?>
                    <?php if (count($errors) > 0): ?>
                        <div class="message-list">
                            <h4 style="color: var(--danger); margin-bottom: 1rem;">❌ Errors:</h4>
                            <?php foreach ($errors as $error): ?>
                                <div class="message message-error">
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (count($messages) > 0): ?>
                        <div class="message-list">
                            <h4 style="color: var(--success); margin-bottom: 1rem;">✅ Setup Completed:</h4>
                            <?php foreach ($messages as $msg): ?>
                                <div class="message message-success">
                                    <?php echo htmlspecialchars($msg); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="next-steps">
                            <h4>🎉 Ready to Use!</h4>
                            <ol>
                                <li><strong>Go to Admin Dashboard:</strong> <a href="<?php echo app_url('admin/dashboard.php'); ?>">Dashboard</a></li>
                                <li><strong>Manage Pricing Rules:</strong> <a href="<?php echo app_url('admin/pricing_rules.php'); ?>">Pricing Rules</a></li>
                                <li><strong>View Analytics:</strong> <a href="<?php echo app_url('admin/pricing_dashboard.php'); ?>">Pricing Dashboard</a></li>
                            </ol>
                        </div>

                        <a href="<?php echo app_url('admin/pricing_dashboard.php'); ?>" class="btn btn-primary">
                            📊 Go to Pricing Dashboard
                        </a>
                        <a href="<?php echo app_url('admin/pricing_rules.php'); ?>" class="btn btn-secondary">
                            ⚙️ Manage Pricing Rules
                        </a>
                    <?php else: ?>
                        <a href="<?php echo app_url('admin/setup_pricing.php'); ?>" class="btn btn-secondary">
                            🔄 Try Again
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
