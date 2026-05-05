-- Dynamic Pricing Stored Procedures
-- These procedures implement the core pricing logic

-- 1. Calculate Dynamic Price based on occupancy and rules
-- This procedure returns the dynamically adjusted price for a room
DELIMITER //

DROP PROCEDURE IF EXISTS CalculateDynamicPrice//

CREATE PROCEDURE CalculateDynamicPrice(
    IN p_room_id INT,
    IN p_check_in_date DATE,
    IN p_base_price DECIMAL(10,2),
    OUT p_dynamic_price DECIMAL(10,2),
    OUT p_applied_rules VARCHAR(500)
)
BEGIN
    DECLARE v_occupancy_percent INT DEFAULT 0;
    DECLARE v_total_adjustment DECIMAL(10,2) DEFAULT 0;
    DECLARE v_temp_price DECIMAL(10,2);
    DECLARE v_final_price DECIMAL(10,2);
    DECLARE v_season_name VARCHAR(50);
    DECLARE v_rules_applied VARCHAR(500) DEFAULT '';
    DECLARE rule_cursor CURSOR FOR
        SELECT rule_id, rule_name, adjustment_type, adjustment_value, season_name
        FROM pricing_rules
        WHERE is_active = TRUE
        AND (applies_to_room_types IS NULL OR applies_to_room_types = '' OR FIND_IN_SET(
            (SELECT room_type FROM rooms WHERE room_id = p_room_id),
            REPLACE(applies_to_room_types, ', ', ',')
        ) > 0);
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET rule_cursor = NULL;
    
    -- Get current occupancy percentage
    SELECT COALESCE(occupancy_percent, 0) INTO v_occupancy_percent
    FROM occupancy_history
    WHERE history_date = CURDATE()
    LIMIT 1;
    
    -- If no occupancy data for today, calculate from current bookings
    IF v_occupancy_percent = 0 THEN
        SELECT COALESCE(
            ROUND((COUNT(DISTINCT room_id) * 100.0 / (SELECT COUNT(*) FROM rooms)), 0),
            0
        ) INTO v_occupancy_percent
        FROM bookings
        WHERE status IN ('confirmed', 'checked_in')
        AND check_in_date <= CURDATE()
        AND check_out_date > CURDATE();
    END IF;
    
    -- Start with base price
    SET v_final_price = p_base_price;
    
    -- Apply pricing rules
    OPEN rule_cursor;
    price_loop: LOOP
        FETCH rule_cursor INTO @rule_id, @rule_name, @adj_type, @adj_value, @season_name;
        IF @rule_id IS NULL THEN
            LEAVE price_loop;
        END IF;
        
        -- Check if rule applies
        SET @rule_applies = FALSE;
        
        -- Check seasonal rule
        IF @season_name IS NOT NULL AND 
           MONTH(p_check_in_date) >= MONTH(STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(STR_TO_DATE(@season_name, '%M-%d')), '-01'), '%Y-%m-%d')) AND
           MONTH(p_check_in_date) <= MONTH(STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(STR_TO_DATE(@season_name, '%M-%d')), '-28'), '%Y-%m-%d')) THEN
            SET @rule_applies = TRUE;
        END IF;
        
        -- Check occupancy rule
        IF (SELECT occupancy_min_percent FROM pricing_rules WHERE rule_id = @rule_id) IS NOT NULL AND
           v_occupancy_percent >= (SELECT occupancy_min_percent FROM pricing_rules WHERE rule_id = @rule_id) AND
           v_occupancy_percent < (SELECT occupancy_max_percent FROM pricing_rules WHERE rule_id = @rule_id) THEN
            SET @rule_applies = TRUE;
        END IF;
        
        -- Apply adjustment if rule applies
        IF @rule_applies THEN
            IF @adj_type = 'percentage' THEN
                SET v_final_price = v_final_price * (1 + @adj_value / 100);
            ELSE
                SET v_final_price = v_final_price + @adj_value;
            END IF;
            SET v_rules_applied = CONCAT(v_rules_applied, IF(v_rules_applied != '', ', ', ''), @rule_name);
        END IF;
    END LOOP;
    CLOSE rule_cursor;
    
    -- Return values
    SET p_dynamic_price = ROUND(v_final_price, 2);
    SET p_applied_rules = v_rules_applied;
    
    -- Log to pricing history
    INSERT INTO pricing_history (room_id, base_price, adjusted_price, occupancy_percent, applied_rules, effective_date)
    VALUES (p_room_id, p_base_price, p_dynamic_price, v_occupancy_percent, v_rules_applied, CURDATE());
    
END//

DELIMITER ;

-- 2. Update Occupancy History
-- This procedure calculates and records the current occupancy percentage
DELIMITER //

DROP PROCEDURE IF EXISTS UpdateOccupancyHistory//

CREATE PROCEDURE UpdateOccupancyHistory()
BEGIN
    DECLARE v_total_rooms INT;
    DECLARE v_occupied_rooms INT;
    DECLARE v_occupancy_percent INT;
    
    -- Get total rooms
    SELECT COUNT(*) INTO v_total_rooms FROM rooms;
    
    -- Get occupied/booked rooms for today
    SELECT COALESCE(COUNT(DISTINCT room_id), 0) INTO v_occupied_rooms
    FROM bookings
    WHERE status IN ('confirmed', 'checked_in')
    AND check_in_date <= CURDATE()
    AND check_out_date > CURDATE();
    
    -- Calculate occupancy percentage
    IF v_total_rooms > 0 THEN
        SET v_occupancy_percent = ROUND((v_occupied_rooms * 100.0 / v_total_rooms), 0);
    ELSE
        SET v_occupancy_percent = 0;
    END IF;
    
    -- Insert or update occupancy history
    INSERT INTO occupancy_history (history_date, occupancy_percent, total_rooms, occupied_rooms)
    VALUES (CURDATE(), v_occupancy_percent, v_total_rooms, v_occupied_rooms)
    ON DUPLICATE KEY UPDATE 
        occupancy_percent = v_occupancy_percent,
        total_rooms = v_total_rooms,
        occupied_rooms = v_occupied_rooms;
    
END//

DELIMITER ;

-- 3. Apply Dynamic Pricing to All Rooms
-- This procedure updates prices for all rooms based on current occupancy
DELIMITER //

DROP PROCEDURE IF EXISTS ApplyDynamicPricingToAllRooms//

CREATE PROCEDURE ApplyDynamicPricingToAllRooms()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_room_id INT;
    DECLARE v_base_price DECIMAL(10,2);
    DECLARE v_dynamic_price DECIMAL(10,2);
    DECLARE v_rules_applied VARCHAR(500);
    
    DECLARE room_cursor CURSOR FOR
        SELECT r.room_id, COALESCE(b.base_price, r.price)
        FROM rooms r
        LEFT JOIN base_room_prices b ON r.room_id = b.room_id AND b.effective_to IS NULL;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Update occupancy history first
    CALL UpdateOccupancyHistory();
    
    -- Open cursor and process each room
    OPEN room_cursor;
    room_loop: LOOP
        FETCH room_cursor INTO v_room_id, v_base_price;
        IF done THEN
            LEAVE room_loop;
        END IF;
        
        -- Calculate dynamic price for this room
        CALL CalculateDynamicPrice(v_room_id, CURDATE(), v_base_price, v_dynamic_price, v_rules_applied);
        
        -- Update room's current dynamic price
        UPDATE rooms 
        SET current_dynamic_price = v_dynamic_price, last_price_update = NOW()
        WHERE room_id = v_room_id;
        
    END LOOP;
    CLOSE room_cursor;
    
END//

DELIMITER ;
