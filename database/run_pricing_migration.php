<?php
/**
 * Dynamic Pricing Migration Script
 * Applies the dynamic pricing database schema changes
 */

require_once __DIR__ . '/../database/db_connect.php';

try {
    $pdo = Database::getConnection();
    
    // Read and execute migration SQL
    $migration_sql = file_get_contents(__DIR__ . '/pricing_migration.sql');
    
    // Split by statements and execute each one
    $statements = array_filter(array_map('trim', explode(';', $migration_sql)), function($s) {
        return strlen($s) > 0 && !preg_match('/^--/', $s);
    });
    
    foreach ($statements as $statement) {
        if (trim($statement)) {
            // Skip comment lines
            $statement = preg_replace('/--.*$/m', '', $statement);
            if (trim($statement)) {
                $pdo->exec($statement);
                echo "✓ Executed statement\n";
            }
        }
    }
    
    echo "\n✅ Dynamic Pricing migration completed successfully!\n";
    
} catch (Exception $e) {
    die("❌ Migration failed: " . $e->getMessage() . "\n");
}
?>
