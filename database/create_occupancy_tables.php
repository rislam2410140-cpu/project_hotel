<?php
/**
 * Migration Script for Feature #1: Room Occupancy Dashboard
 * Creates necessary tables for occupancy analytics
 */

require_once __DIR__ . '/../database/db_connect.php';

try {
    $pdo = Database::getConnection();
    
    echo "🏨 Creating Occupancy Analytics Tables...\n\n";
    
    // 1. Room Occupancy Summary Table
    $sql1 = "
        CREATE TABLE IF NOT EXISTS room_occupancy_summary (
            summary_id INT AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL,
            total_rooms INT NOT NULL,
            occupied_rooms INT NOT NULL,
            available_rooms INT NOT NULL,
            cleaning_rooms INT DEFAULT 0,
            occupancy_rate DECIMAL(5,2) NOT NULL COMMENT 'Percentage 0-100',
            revenue_today DECIMAL(10,2) DEFAULT 0,
            revenue_month DECIMAL(10,2) DEFAULT 0,
            avg_booking_value DECIMAL(10,2) DEFAULT 0,
            total_bookings INT DEFAULT 0,
            new_bookings INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_date (date),
            INDEX idx_date (date),
            INDEX idx_occupancy (occupancy_rate)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql1);
    echo "✅ Created room_occupancy_summary table\n";
    
    // 2. Daily Statistics Table
    $sql2 = "
        CREATE TABLE IF NOT EXISTS daily_statistics (
            stat_id INT AUTO_INCREMENT PRIMARY KEY,
            stat_date DATE NOT NULL UNIQUE,
            total_bookings INT DEFAULT 0,
            new_bookings INT DEFAULT 0,
            checked_in INT DEFAULT 0,
            checked_out INT DEFAULT 0,
            room_revenue DECIMAL(10,2) DEFAULT 0,
            service_revenue DECIMAL(10,2) DEFAULT 0,
            total_revenue DECIMAL(10,2) DEFAULT 0,
            total_guests INT DEFAULT 0,
            avg_guest_rating DECIMAL(3,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_date (stat_date),
            INDEX idx_revenue (total_revenue)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql2);
    echo "✅ Created daily_statistics table\n";
    
    // 3. Room Performance Table
    $sql3 = "
        CREATE TABLE IF NOT EXISTS room_performance (
            performance_id INT AUTO_INCREMENT PRIMARY KEY,
            room_id INT NOT NULL,
            date DATE NOT NULL,
            status ENUM('available', 'occupied', 'cleaning', 'maintenance') DEFAULT 'available',
            bookings_count INT DEFAULT 0,
            total_revenue DECIMAL(10,2) DEFAULT 0,
            occupancy_hours INT DEFAULT 0,
            guest_rating_avg DECIMAL(3,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
            UNIQUE KEY unique_room_date (room_id, date),
            INDEX idx_room (room_id),
            INDEX idx_date (date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql3);
    echo "✅ Created room_performance table\n";
    
    // 4. Populate initial data
    $sql_populate = "
        INSERT INTO daily_statistics (stat_date, total_bookings, checked_in, checked_out)
        SELECT 
            DATE(created_at) as stat_date,
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status IN ('checked_in') THEN 1 ELSE 0 END) as checked_in,
            SUM(CASE WHEN status IN ('checked_out', 'completed') THEN 1 ELSE 0 END) as checked_out
        FROM bookings
        GROUP BY DATE(created_at)
        ON DUPLICATE KEY UPDATE
            total_bookings = VALUES(total_bookings),
            checked_in = VALUES(checked_in),
            checked_out = VALUES(checked_out)
    ";
    
    $pdo->exec($sql_populate);
    echo "✅ Populated initial statistics\n";
    
    echo "\n✨ Room Occupancy Analytics tables created successfully!\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "⚠️  Tables already exist. Skipping creation.\n";
    } else {
        die("❌ Error: " . $e->getMessage() . "\n");
    }
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
?>
