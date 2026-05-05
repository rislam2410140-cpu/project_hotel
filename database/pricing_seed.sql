-- Dynamic Pricing Feature - Test Data & Seed Migration
-- This file populates the dynamic pricing tables with test data for demonstration

-- 1. Ensure all rooms have base prices set
INSERT INTO base_room_prices (room_id, base_price, effective_from)
SELECT room_id, price, CURDATE() 
FROM rooms
WHERE room_id NOT IN (SELECT room_id FROM base_room_prices WHERE effective_to IS NULL);

-- 2. Create sample pricing rules for testing

-- Seasonal Rules
INSERT INTO pricing_rules (rule_name, rule_type, season_name, adjustment_type, adjustment_value, is_active)
VALUES 
('Summer Peak Season', 'seasonal', 'Summer', 'percentage', 25.00, TRUE),
('Winter Holiday Premium', 'seasonal', 'Holiday', 'percentage', 40.00, TRUE),
('Spring Shoulder Season', 'seasonal', 'Spring', 'percentage', 10.00, TRUE),
('Fall Shoulder Season', 'seasonal', 'Fall', 'percentage', 15.00, TRUE);

-- Occupancy-Based Rules
INSERT INTO pricing_rules (rule_name, rule_type, occupancy_min_percent, occupancy_max_percent, adjustment_type, adjustment_value, is_active)
VALUES 
('Low Occupancy Discount', 'occupancy', 0, 40, 'percentage', -10.00, TRUE),
('Medium Occupancy Standard', 'occupancy', 40, 70, 'percentage', 0.00, TRUE),
('High Occupancy Surge', 'occupancy', 70, 85, 'percentage', 20.00, TRUE),
('Very High Occupancy Peak', 'occupancy', 85, 100, 'percentage', 35.00, TRUE);

-- Event-Based Rules (for future events)
INSERT INTO pricing_rules (rule_name, rule_type, adjustment_type, adjustment_value, is_active)
VALUES 
('Conference Season', 'event', 'percentage', 30.00, FALSE),
('Wedding Season', 'event', 'percentage', 25.00, FALSE),
('Major Festival Week', 'event', 'fixed', 50.00, FALSE);

-- 3. Initialize occupancy history for past week with varied occupancy data
INSERT INTO occupancy_history (history_date, occupancy_percent, total_rooms, occupied_rooms)
VALUES 
(DATE_SUB(CURDATE(), INTERVAL 6 DAY), 45, (SELECT COUNT(*) FROM rooms), FLOOR((SELECT COUNT(*) FROM rooms) * 0.45)),
(DATE_SUB(CURDATE(), INTERVAL 5 DAY), 60, (SELECT COUNT(*) FROM rooms), FLOOR((SELECT COUNT(*) FROM rooms) * 0.60)),
(DATE_SUB(CURDATE(), INTERVAL 4 DAY), 72, (SELECT COUNT(*) FROM rooms), FLOOR((SELECT COUNT(*) FROM rooms) * 0.72)),
(DATE_SUB(CURDATE(), INTERVAL 3 DAY), 88, (SELECT COUNT(*) FROM rooms), FLOOR((SELECT COUNT(*) FROM rooms) * 0.88)),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 75, (SELECT COUNT(*) FROM rooms), FLOOR((SELECT COUNT(*) FROM rooms) * 0.75)),
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 82, (SELECT COUNT(*) FROM rooms), FLOOR((SELECT COUNT(*) FROM rooms) * 0.82)),
(CURDATE(), 65, (SELECT COUNT(*) FROM rooms), FLOOR((SELECT COUNT(*) FROM rooms) * 0.65));

-- 4. Populate pricing history with sample data showing price adjustments
-- This is typically done by procedures, but we can seed it for demo purposes
INSERT INTO pricing_history (room_id, base_price, adjusted_price, occupancy_percent, applied_rules, effective_date)
SELECT 
    r.room_id,
    r.price as base_price,
    ROUND(r.price * (1 + IF(MOD(r.room_id, 2) = 0, 0.20, 0.35)), 2) as adjusted_price,
    IF(MOD(r.room_id, 2) = 0, 75, 88) as occupancy_percent,
    IF(MOD(r.room_id, 2) = 0, 'High Occupancy Surge', 'Very High Occupancy Peak') as applied_rules,
    DATE_SUB(CURDATE(), INTERVAL 1 DAY) as effective_date
FROM rooms
WHERE room_id NOT IN (SELECT DISTINCT room_id FROM pricing_history);

-- 5. Update room current_dynamic_price to reflect today's pricing
UPDATE rooms 
SET 
    current_dynamic_price = ROUND(price * 
        CASE 
            WHEN status = 'occupied' THEN 1.35  -- High occupancy adjustment
            WHEN DAYOFWEEK(NOW()) IN (1, 7) THEN 1.20  -- Weekend surge
            ELSE 1.00  -- Regular rate
        END, 2),
    last_price_update = NOW()
WHERE current_dynamic_price IS NULL OR current_dynamic_price = price;

-- 6. Summary of seeded data
-- Use these queries to verify the data was loaded correctly:

-- Check pricing rules
SELECT 'Pricing Rules' as data_type, COUNT(*) as count FROM pricing_rules;

-- Check occupancy history
SELECT 'Occupancy History' as data_type, COUNT(*) as count FROM occupancy_history;

-- Check pricing history
SELECT 'Pricing History' as data_type, COUNT(*) as count FROM pricing_history;

-- Check room pricing status
SELECT 'Rooms with Dynamic Pricing' as data_type, COUNT(*) as count FROM rooms WHERE current_dynamic_price IS NOT NULL;

-- Show today's average occupancy
SELECT 'Today Occupancy' as metric, occupancy_percent as value FROM occupancy_history WHERE history_date = CURDATE();
