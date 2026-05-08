-- Dynamic Pricing & Revenue Optimization - Database Schema Migration
-- This adds support for dynamic pricing based on occupancy and seasonal factors

-- 1. Base Room Prices Table
-- Stores the base price for each room type (before dynamic adjustments)
CREATE TABLE IF NOT EXISTS base_room_prices (
    base_price_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL UNIQUE,
    base_price DECIMAL(10, 2) NOT NULL,
    effective_from DATE NOT NULL DEFAULT CURDATE(),
    effective_to DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    INDEX idx_room_id (room_id),
    INDEX idx_effective_dates (effective_from, effective_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Pricing Rules Table
-- Defines rules for dynamic pricing (seasonal, occupancy-based, event-based)
CREATE TABLE IF NOT EXISTS pricing_rules (
    rule_id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(100) NOT NULL,
    rule_type ENUM('seasonal', 'occupancy', 'event') NOT NULL,
    -- For seasonal rules
    season_name VARCHAR(50),
    season_start_date DATE,
    season_end_date DATE,
    -- For occupancy-based rules
    occupancy_min_percent INT,
    occupancy_max_percent INT,
    -- Price adjustment (can be percentage or fixed amount)
    adjustment_type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    adjustment_value DECIMAL(10, 2) NOT NULL,
    -- Rule applicability
    is_active BOOLEAN DEFAULT TRUE,
    applies_to_room_types VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_rule_type (rule_type),
    INDEX idx_dates (season_start_date, season_end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Occupancy History Table
-- Tracks occupancy rates over time for analysis and dynamic pricing
CREATE TABLE IF NOT EXISTS occupancy_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    history_date DATE NOT NULL,
    occupancy_percent INT NOT NULL,
    total_rooms INT NOT NULL,
    occupied_rooms INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (history_date),
    INDEX idx_date (history_date),
    INDEX idx_occupancy_percent (occupancy_percent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Dynamic Pricing History Table
-- Tracks all price changes for audit and analysis
CREATE TABLE IF NOT EXISTS pricing_history (
    pricing_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    adjusted_price DECIMAL(10, 2) NOT NULL,
    occupancy_percent INT NOT NULL,
    applied_rules VARCHAR(500),
    effective_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    INDEX idx_room_id (room_id),
    INDEX idx_effective_date (effective_date),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add current_dynamic_price column to rooms table for caching
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS current_dynamic_price DECIMAL(10, 2);
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS last_price_update TIMESTAMP NULL;

-- Initialize base prices from existing room prices
INSERT INTO base_room_prices (room_id, base_price, effective_from)
SELECT room_id, price, CURDATE() FROM rooms
WHERE room_id NOT IN (SELECT room_id FROM base_room_prices);

-- Initialize current_dynamic_price if NULL
UPDATE rooms SET current_dynamic_price = price, last_price_update = NOW() 
WHERE current_dynamic_price IS NULL;
