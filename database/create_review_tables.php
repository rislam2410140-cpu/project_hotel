<?php
/**
 * Migration Script for Feature #2: Guest Review & Rating System
 * Creates tables for reviews, responses, and sentiment tracking
 */

require_once __DIR__ . '/../database/db_connect.php';

try {
    $pdo = Database::getConnection();
    
    echo "⭐ Creating Guest Review System Tables...\n\n";
    
    // 1. Enhance existing reviews table (if needed)
    $sql1 = "
        ALTER TABLE reviews 
        ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'rejected', 'flagged') DEFAULT 'pending',
        ADD COLUMN IF NOT EXISTS sentiment VARCHAR(20) DEFAULT NULL COMMENT 'positive, neutral, negative',
        ADD COLUMN IF NOT EXISTS helpful_count INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS verified_purchase BOOLEAN DEFAULT TRUE,
        ADD COLUMN IF NOT EXISTS room_id INT,
        ADD COLUMN IF NOT EXISTS admin_response_id INT
    ";
    
    try {
        $pdo->exec($sql1);
        echo "✅ Enhanced reviews table\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "ℹ️  Columns already exist in reviews table\n";
    }
    
    // 2. Review Responses Table (Admin replies to reviews)
    $sql2 = "
        CREATE TABLE IF NOT EXISTS review_responses (
            response_id INT AUTO_INCREMENT PRIMARY KEY,
            review_id INT NOT NULL,
            admin_id INT NOT NULL,
            response_text TEXT NOT NULL,
            response_sentiment VARCHAR(20) DEFAULT 'neutral',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE,
            FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_review (review_id),
            INDEX idx_admin (admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql2);
    echo "✅ Created review_responses table\n";
    
    // 3. Review Helpfulness Votes Table
    $sql3 = "
        CREATE TABLE IF NOT EXISTS review_votes (
            vote_id INT AUTO_INCREMENT PRIMARY KEY,
            review_id INT NOT NULL,
            user_id INT NOT NULL,
            is_helpful BOOLEAN NOT NULL COMMENT '1 for helpful, 0 for not helpful',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_vote (review_id, user_id),
            FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            INDEX idx_review (review_id),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql3);
    echo "✅ Created review_votes table\n";
    
    // 4. Review Moderation Flags Table
    $sql4 = "
        CREATE TABLE IF NOT EXISTS review_flags (
            flag_id INT AUTO_INCREMENT PRIMARY KEY,
            review_id INT NOT NULL,
            flag_reason VARCHAR(100) NOT NULL COMMENT 'spam, offensive, fake, etc',
            flag_description TEXT,
            flagged_by INT,
            status ENUM('pending', 'reviewed', 'approved', 'dismissed') DEFAULT 'pending',
            reviewed_at TIMESTAMP NULL,
            reviewed_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE,
            FOREIGN KEY (flagged_by) REFERENCES users(user_id) ON DELETE SET NULL,
            FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
            INDEX idx_review (review_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql4);
    echo "✅ Created review_flags table\n";
    
    // 5. Review Analytics Table
    $sql5 = "
        CREATE TABLE IF NOT EXISTS review_analytics (
            analytics_id INT AUTO_INCREMENT PRIMARY KEY,
            room_id INT NOT NULL,
            date DATE NOT NULL,
            total_reviews INT DEFAULT 0,
            approved_reviews INT DEFAULT 0,
            avg_rating DECIMAL(3,2) DEFAULT 0,
            positive_reviews INT DEFAULT 0,
            neutral_reviews INT DEFAULT 0,
            negative_reviews INT DEFAULT 0,
            total_helpful_votes INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
            UNIQUE KEY unique_room_date (room_id, date),
            INDEX idx_room (room_id),
            INDEX idx_date (date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql5);
    echo "✅ Created review_analytics table\n";
    
    echo "\n✨ Guest Review System tables created successfully!\n";
    echo "   Next steps:\n";
    echo "   1. Create guest/review_form.php\n";
    echo "   2. Create admin/review_management.php\n";
    echo "   3. Create includes/ReviewManager.php helper class\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "⚠️  Some tables already exist. Skipping.\n";
    } else {
        die("❌ Error: " . $e->getMessage() . "\n");
    }
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
?>
