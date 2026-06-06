<?php
/**
 * Password Reset Feature Migration Script
 * Creates the password_reset_tokens table for secure password recovery
 */

require_once __DIR__ . '/../database/db_connect.php';

try {
    $pdo = Database::getConnection();
    
    // Create password_reset_tokens table
    $sql = "
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            token_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL UNIQUE,
            token_hash VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_token (token_hash),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    echo "✅ Password Reset feature migration completed successfully!\n";
    echo "   ✓ Created password_reset_tokens table\n";
    
    // Clean up expired tokens (optional)
    $cleanup = "DELETE FROM password_reset_tokens WHERE expires_at < NOW() AND used_at IS NULL";
    $pdo->exec($cleanup);
    echo "   ✓ Cleaned up expired tokens\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "✅ Password Reset tokens table already exists.\n";
    } else {
        die("❌ Migration failed: " . $e->getMessage() . "\n");
    }
} catch (Exception $e) {
    die("❌ Migration failed: " . $e->getMessage() . "\n");
}
?>
