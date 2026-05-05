<?php
/**
 * Database Setup Helper
 * Run this once to initialize the database
 * Access: http://localhost/modern_hotel_management/setup.php
 */

require_once __DIR__ . '/config.php';

// Show setup form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup</title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
</head>
<body>
    <section class="section">
        <div class="container">
            <div style="max-width: 600px; margin: 0 auto;">
                <div class="card">
                    <h2 style="text-align: center; margin-bottom: 1rem;">🏨 Hotel Management System - Setup</h2>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Database Host</label>
                            <input type="text" name="db_host" value="localhost" required>
                        </div>
                        <div class="form-group">
                            <label>Database Name</label>
                            <input type="text" name="db_name" value="hotel_management" required>
                        </div>
                        <div class="form-group">
                            <label>Database User</label>
                            <input type="text" name="db_user" value="root" required>
                        </div>
                        <div class="form-group">
                            <label>Database Password</label>
                            <input type="password" name="db_pass">
                        </div>
                        
                        <label style="display: flex; align-items: center; margin-bottom: 1.5rem;">
                            <input type="checkbox" name="seed_data" checked>
                            <span style="margin-left: 0.5rem;">Import seed data (demo users, rooms, bookings)</span>
                        </label>
                        
                        <button type="submit" class="btn btn-primary btn-block">Setup Database</button>
                    </form>

                    <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid var(--border);">

                    <p style="color: var(--text-light); font-size: 0.9rem;">
                        ⚠️ <strong>First Time Only:</strong> This will create tables and load sample data.
                    </p>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
<?php
    exit;
}

// Process setup
$db_host = $_POST['db_host'] ?? 'localhost';
$db_name = $_POST['db_name'] ?? 'hotel_management';
$db_user = $_POST['db_user'] ?? 'root';
$db_pass = $_POST['db_pass'] ?? '';
$seed_data = isset($_POST['seed_data']);

$messages = [];
$errors = [];

try {
    // Create connection
    $dsn = "mysql:host=" . $db_host . ";charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $db_name");
    $messages[] = "✓ Database '$db_name' ready";

    // Use the database
    $pdo->exec("USE $db_name");

    // Read and execute schema
    $schema_file = __DIR__ . '/database/schema.sql';
    if (file_exists($schema_file)) {
        $schema = file_get_contents($schema_file);
        // Execute schema statements
        foreach (explode(';', $schema) as $statement) {
            if (trim($statement)) {
                $pdo->exec($statement);
            }
        }
        $messages[] = "✓ Database schema created successfully";
    } else {
        $errors[] = "Schema file not found: $schema_file";
    }

    // Load pricing schema migration if file exists
    $pricing_migration_file = __DIR__ . '/database/pricing_migration.sql';
    if (file_exists($pricing_migration_file)) {
        $pricing_migration = file_get_contents($pricing_migration_file);
        foreach (explode(';', $pricing_migration) as $statement) {
            $trimmed = trim($statement);
            if ($trimmed && !preg_match('/^--/', $trimmed)) {
                try {
                    $pdo->exec($statement);
                } catch (Exception $e) {
                    // Ignore errors for IF NOT EXISTS statements
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        // Only report actual errors, not duplicate key errors
                    }
                }
            }
        }
        $messages[] = "✓ Dynamic pricing schema created";
    }

    // Create stored procedures for pricing
    $procedures_file = __DIR__ . '/database/pricing_procedures.sql';
    if (file_exists($procedures_file)) {
        $procedures = file_get_contents($procedures_file);
        // Split by DELIMITER // and execute each procedure
        $procs = preg_split('/DELIMITER\s+\/\//i', $procedures);
        foreach ($procs as $proc) {
            $trimmed = trim($proc);
            if ($trimmed && !preg_match('/^--/', $trimmed)) {
                // Remove DELIMITER // at the end
                $trimmed = str_replace('DELIMITER ;', '', $trimmed);
                $trimmed = str_replace('// DELIMITER ;', '', $trimmed);
                if (trim($trimmed)) {
                    try {
                        $pdo->exec($trimmed);
                    } catch (Exception $e) {
                        // Ignore if procedure already exists
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            error_log("Procedure warning: " . $e->getMessage());
                        }
                    }
                }
            }
        }
        $messages[] = "✓ Pricing procedures created";
    }

    // Create triggers for pricing
    $triggers_file = __DIR__ . '/database/pricing_triggers.sql';
    if (file_exists($triggers_file)) {
        $triggers = file_get_contents($triggers_file);
        // Split by DELIMITER // and execute each trigger
        $trigs = preg_split('/DELIMITER\s+\/\//i', $triggers);
        foreach ($trigs as $trig) {
            $trimmed = trim($trig);
            if ($trimmed && !preg_match('/^--/', $trimmed)) {
                // Remove DELIMITER // at the end
                $trimmed = str_replace('DELIMITER ;', '', $trimmed);
                $trimmed = str_replace('// DELIMITER ;', '', $trimmed);
                if (trim($trimmed)) {
                    try {
                        $pdo->exec($trimmed);
                    } catch (Exception $e) {
                        // Ignore if trigger already exists
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            error_log("Trigger warning: " . $e->getMessage());
                        }
                    }
                }
            }
        }
        $messages[] = "✓ Pricing triggers created";
    }

    // Load seed data if requested
    if ($seed_data) {
        $seed_file = __DIR__ . '/database/seed.sql';
        if (file_exists($seed_file)) {
            $seed = file_get_contents($seed_file);
            foreach (explode(';', $seed) as $statement) {
                if (trim($statement)) {
                    $pdo->exec($statement);
                }
            }
            $messages[] = "✓ Sample data loaded (see README for demo credentials)";
        } else {
            $errors[] = "Seed file not found: $seed_file";
        }

        // Load pricing seed data if requested
        $pricing_seed_file = __DIR__ . '/database/pricing_seed.sql';
        if (file_exists($pricing_seed_file)) {
            $pricing_seed = file_get_contents($pricing_seed_file);
            foreach (explode(';', $pricing_seed) as $statement) {
                $trimmed = trim($statement);
                if ($trimmed && !preg_match('/^--/', $trimmed)) {
                    try {
                        $pdo->exec($statement);
                    } catch (Exception $e) {
                        // Ignore duplicate key errors
                        if (strpos($e->getMessage(), 'Duplicate') === false) {
                            error_log("Seed data warning: " . $e->getMessage());
                        }
                    }
                }
            }
            $messages[] = "✓ Pricing sample data loaded";
        }
    }

} catch (PDOException $e) {
    $errors[] = "Database Error: " . $e->getMessage();
} catch (Exception $e) {
    $errors[] = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Complete</title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
</head>
<body>
    <section class="section">
        <div class="container">
            <div style="max-width: 600px; margin: 0 auto;">
                <div class="card">
                    <h2 style="text-align: center; margin-bottom: 1rem;">✓ Setup Complete</h2>

                    <?php if (count($errors) > 0): ?>
                        <div class="flash-message flash-error">
                            <?php foreach ($errors as $error): ?>
                                <p>❌ <?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (count($messages) > 0): ?>
                        <div class="flash-message flash-success">
                            <?php foreach ($messages as $msg): ?>
                                <p><?php echo htmlspecialchars($msg); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div style="background: var(--light); padding: 1.5rem; border-radius: 0.375rem; margin: 1.5rem 0;">
                        <h4>Next Steps:</h4>
                        <ol style="margin: 1rem 0; padding-left: 1.5rem;">
                            <li>👉 Go to <a href="<?php echo app_url('index.php'); ?>">Homepage</a></li>
                            <li>🔓 <a href="<?php echo app_url('guest/login.php'); ?>">Guest Login</a> - guest@hotel.com / Guest123</li>
                            <li>🔐 <a href="<?php echo app_url('admin/login.php'); ?>">Admin Login</a> - admin@hotel.com / Admin123</li>
                        </ol>
                    </div>

                    <a href="<?php echo app_url('index.php'); ?>" class="btn btn-primary btn-block">Go to Homepage</a>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
