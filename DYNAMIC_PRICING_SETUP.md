# Dynamic Pricing Feature - Installation & Setup Guide

## Overview
This document guides you through setting up the Dynamic Pricing & Revenue Optimization feature for the hotel management system.

## Database Schema Migration

### Step 1: Apply Schema Changes
Execute this SQL in phpMyAdmin or MySQL CLI:

```sql
SOURCE database/pricing_migration.sql;
```

Or copy and paste the contents of `database/pricing_migration.sql` into phpMyAdmin's SQL tab.

**What this does:**
- Creates `base_room_prices` table - Stores base prices for each room
- Creates `pricing_rules` table - Defines dynamic pricing rules
- Creates `occupancy_history` table - Tracks occupancy rates
- Creates `pricing_history` table - Audit trail of price changes
- Adds columns to `rooms` table for caching current dynamic price

### Step 2: Create Stored Procedures
Execute this SQL:

```sql
SOURCE database/pricing_procedures.sql;
```

**Procedures created:**
- `CalculateDynamicPrice()` - Calculates price based on occupancy & rules
- `UpdateOccupancyHistory()` - Records occupancy rates
- `ApplyDynamicPricingToAllRooms()` - Applies pricing to all rooms

### Step 3: Create Triggers
Execute this SQL:

```sql
SOURCE database/pricing_triggers.sql;
```

**Triggers created:**
- Auto-update prices when bookings are created/updated/deleted
- Auto-update prices when pricing rules change

## File Structure

New files added to the project:

```
database/
├── pricing_migration.sql      # Schema migration
├── pricing_procedures.sql     # Stored procedures
├── pricing_triggers.sql       # Automatic triggers
└── run_pricing_migration.php  # Migration runner (PHP)

admin/
├── pricing_dashboard.php      # View pricing analytics & trends
└── pricing_rules.php          # Manage pricing rules
```

Modified files:
```
admin/dashboard.php            # Added links to pricing features
guest/book_room.php            # Uses dynamic prices for calculations
```

## Features Implemented

### 1. Dynamic Price Calculation
- **Occupancy-Based Pricing**: Prices increase when occupancy > threshold
  - Example: When occupancy reaches 70%, add 15% to base price
  - Example: When occupancy reaches 90%, add 30% to base price

- **Seasonal Pricing**: Different prices for different seasons
  - Example: Summer (June-Aug): +20%
  - Example: Holiday (Dec): +40%

- **Flexible Adjustments**: Set as percentage or fixed amount
  - Percentage: Add 25% to base price
  - Fixed: Add $50 to base price

### 2. Real-Time Price Updates
- Prices automatically recalculate when:
  - New booking is created → occupancy changes → prices adjust
  - Booking is cancelled → occupancy decreases → prices decrease
  - Pricing rules are added/modified → all prices recalculate
  - Daily occupancy history is recorded

### 3. Admin Features
- **Pricing Dashboard** (`admin/pricing_dashboard.php`):
  - View current occupancy and pricing
  - Track revenue impact of dynamic pricing
  - See 7-day occupancy trends
  - Monitor active pricing rules

- **Pricing Rules Management** (`admin/pricing_rules.php`):
  - Create seasonal rules (date-based pricing)
  - Create occupancy-based rules (% increase at high occupancy)
  - Create event-based rules
  - Enable/disable rules
  - Apply rules to specific room types
  - Delete rules

### 4. Guest Features
- **Dynamic Booking Prices**: When guests book, they see the current dynamic price
- **Transparent Pricing**: System shows the actual price they'll pay based on current demand

## Usage Guide

### For Admin Users

#### Creating a Pricing Rule

1. Go to: **Admin Dashboard** → **⚙️ Manage Pricing Rules**

2. **Seasonal Rule Example:**
   - Rule Name: "Summer Peak Season"
   - Rule Type: Seasonal
   - Start Date: June 1
   - End Date: August 31
   - Adjustment: +25% (percentage)
   - Room Types: All (or specific types)
   - Save

3. **Occupancy-Based Rule Example:**
   - Rule Name: "High Occupancy Surge"
   - Rule Type: Occupancy-Based
   - Min Occupancy: 75%
   - Max Occupancy: 100%
   - Adjustment: +30% (percentage)
   - Save

#### Viewing Pricing Analytics

1. Go to: **Admin Dashboard** → **💰 Pricing Dashboard**

2. View:
   - Today's occupancy percentage
   - Current dynamic prices for each room
   - Price adjustments compared to base price
   - 7-day revenue impact analysis
   - Occupancy trends

### For Guest Users

When booking a room, guests see:
1. Base availability and room details
2. **Current dynamic price** (which may be higher/lower than base)
3. Total calculated price for their stay
4. Booking confirmation

## Database Tables Schema

### base_room_prices
```
- base_price_id (PK)
- room_id (FK to rooms)
- base_price: DECIMAL(10,2)
- effective_from: DATE
- effective_to: DATE (NULL = currently active)
```

### pricing_rules
```
- rule_id (PK)
- rule_name: VARCHAR(100)
- rule_type: ENUM(seasonal, occupancy, event)
- season_name, start_date, end_date (for seasonal)
- occupancy_min_percent, occupancy_max_percent (for occupancy)
- adjustment_type: ENUM(percentage, fixed)
- adjustment_value: DECIMAL(10,2)
- is_active: BOOLEAN
- applies_to_room_types: VARCHAR(255) (comma-separated)
```

### occupancy_history
```
- history_id (PK)
- history_date: DATE (UNIQUE)
- occupancy_percent: INT (0-100)
- total_rooms: INT
- occupied_rooms: INT
```

### pricing_history
```
- pricing_id (PK)
- room_id (FK)
- base_price: DECIMAL(10,2)
- adjusted_price: DECIMAL(10,2)
- occupancy_percent: INT
- applied_rules: VARCHAR(500) (comma-separated rule names)
- effective_date: DATE
```

### rooms (modified)
```
Added columns:
- current_dynamic_price: DECIMAL(10,2)
- last_price_update: TIMESTAMP
```

## Example Pricing Scenarios

### Scenario 1: Weekend Surge
- Occupancy today: 85%
- Base room price: $100
- Rule 1: "Weekend" (only Friday-Sunday): +15%
- Rule 2: "High Occupancy" (80%+): +25%
- **Result**: $100 × (1 + 0.15) × (1 + 0.25) = **$143.75**

### Scenario 2: Low Season
- Occupancy today: 20%
- Base room price: $100
- Rule 1: "Low Occupancy" (< 50%): -10%
- **Result**: $100 × (1 - 0.10) = **$90**

### Scenario 3: Holiday Premium
- Date: December 24
- Base room price: $100
- Rule 1: "Holiday Season" (Dec 20-Jan 1): +40%
- **Result**: $100 × (1 + 0.40) = **$140**

## Testing the Feature

### Test Case 1: Create Pricing Rule
1. Go to Manage Pricing Rules
2. Create: "Test Rule" - Occupancy-based, 70%-100%, +20%
3. Verify rule appears in list

### Test Case 2: Check Price Updates
1. View Pricing Dashboard
2. Create a new booking
3. Observe: Occupancy increases → prices should update
4. Check pricing history for records

### Test Case 3: Book at Dynamic Price
1. Create a pricing rule (e.g., +15% at 80% occupancy)
2. Create enough bookings to reach 80% occupancy
3. Try to book a room as guest
4. Verify booking price includes the 15% adjustment

## Troubleshooting

### Issue: "Unknown procedure CalculateDynamicPrice"
**Solution**: Run `database/pricing_procedures.sql` to create procedures

### Issue: "Table 'pricing_rules' doesn't exist"
**Solution**: Run `database/pricing_migration.sql` to create tables

### Issue: Prices not updating
**Solution**: Ensure triggers were created with `database/pricing_triggers.sql`

### Issue: No occupancy data
**Solution**: The system updates occupancy when bookings are created. Create test bookings first.

## Performance Optimization

For high-traffic systems:
- Schedule the `ApplyDynamicPricingToAllRooms()` procedure to run during off-peak hours
- Create indexes on pricing history for reporting queries
- Archive old pricing history after 90 days

```sql
-- Suggested: Run this daily at midnight via cron job
-- CALL ApplyDynamicPricingToAllRooms();
```

## Advanced Customization

### Modify Price Calculation Logic
Edit: `database/pricing_procedures.sql` - Function `CalculateDynamicPrice()`

### Add More Rule Types
Add to `pricing_rules.rule_type` ENUM and update procedures

### Enable Email Alerts
Add to `pricing_triggers.sql` to send alerts when price changes exceed threshold

## Courses/Learning Concepts Demonstrated

This implementation showcases advanced DBMS concepts:

1. **Stored Procedures** - Business logic in database layer
2. **Triggers** - Automatic data consistency
3. **Complex Joins** - Multi-table aggregations
4. **Window Functions** - Ranking, trending (if using MySQL 8.0+)
5. **Temporal Queries** - Date-based logic
6. **Transaction Handling** - ACID properties
7. **Indexes & Performance** - Query optimization
8. **Audit Trails** - Historical data tracking

---

For questions or issues, refer to the code comments in each file.
