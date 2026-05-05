-- Dynamic Pricing Triggers
-- These triggers automatically maintain pricing and occupancy tracking

-- 1. Trigger: After booking INSERT - Update dynamic prices
DELIMITER //

DROP TRIGGER IF EXISTS after_booking_insert//

CREATE TRIGGER after_booking_insert
AFTER INSERT ON bookings
FOR EACH ROW
BEGIN
    -- Update occupancy history
    CALL UpdateOccupancyHistory();
    
    -- Apply dynamic pricing to all rooms
    CALL ApplyDynamicPricingToAllRooms();
END//

DELIMITER ;

-- 2. Trigger: After booking UPDATE - Update dynamic prices if status changes
DELIMITER //

DROP TRIGGER IF EXISTS after_booking_update//

CREATE TRIGGER after_booking_update
AFTER UPDATE ON bookings
FOR EACH ROW
BEGIN
    -- If status changed to/from active states, recalculate prices
    IF (OLD.status IN ('pending', 'confirmed', 'checked_in') AND 
        NEW.status NOT IN ('pending', 'confirmed', 'checked_in')) OR
       (OLD.status NOT IN ('pending', 'confirmed', 'checked_in') AND 
        NEW.status IN ('pending', 'confirmed', 'checked_in')) THEN
        
        -- Update occupancy history
        CALL UpdateOccupancyHistory();
        
        -- Apply dynamic pricing to all rooms
        CALL ApplyDynamicPricingToAllRooms();
    END IF;
END//

DELIMITER ;

-- 3. Trigger: After booking DELETE - Update dynamic prices
DELIMITER //

DROP TRIGGER IF EXISTS after_booking_delete//

CREATE TRIGGER after_booking_delete
AFTER DELETE ON bookings
FOR EACH ROW
BEGIN
    -- Update occupancy history
    CALL UpdateOccupancyHistory();
    
    -- Apply dynamic pricing to all rooms
    CALL ApplyDynamicPricingToAllRooms();
END//

DELIMITER ;

-- 4. Trigger: After pricing rule INSERT - Update all room prices
DELIMITER //

DROP TRIGGER IF EXISTS after_pricing_rule_insert//

CREATE TRIGGER after_pricing_rule_insert
AFTER INSERT ON pricing_rules
FOR EACH ROW
BEGIN
    -- Recalculate prices for all rooms when a new rule is added
    IF NEW.is_active THEN
        CALL ApplyDynamicPricingToAllRooms();
    END IF;
END//

DELIMITER ;

-- 5. Trigger: After pricing rule UPDATE - Update all room prices
DELIMITER //

DROP TRIGGER IF EXISTS after_pricing_rule_update//

CREATE TRIGGER after_pricing_rule_update
AFTER UPDATE ON pricing_rules
FOR EACH ROW
BEGIN
    -- Recalculate prices when rule is modified or enabled/disabled
    CALL ApplyDynamicPricingToAllRooms();
END//

DELIMITER ;
