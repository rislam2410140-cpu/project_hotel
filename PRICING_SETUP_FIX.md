# 🔧 Pricing Setup & Database Initialization

## Problem
```
Error loading rules: SQLSTATE[42S02]: Base table or view not found: 1146 
Table 'hotel_management.pricing_rules' doesn't exist
```

This error occurs because the pricing database tables haven't been created yet.

---

## Solution: 3 Ways to Setup

### ⭐ Method 1: One-Click Setup (Easiest)

**Go to:** http://localhost/modern_hotel_management/admin/setup_pricing.php

1. Click **⚙️ Setup Pricing Feature** button
2. Wait for confirmation
3. Done! All tables, procedures, and triggers created automatically

**What happens:**
- ✅ Creates `pricing_rules` table
- ✅ Creates `base_room_prices` table
- ✅ Creates `occupancy_history` table
- ✅ Creates `pricing_history` table (audit trail)
- ✅ Creates 3 stored procedures
- ✅ Creates 5 automatic triggers
- ✅ Loads sample pricing data

**No manual SQL needed!**

---

### Method 2: Full System Setup

**Go to:** http://localhost/modern_hotel_management/setup.php

(This is the main setup page for the entire project)

1. Fill in database details (defaults usually work)
2. Check "Import seed data"
3. Click **Setup Database**

This will initialize everything including pricing tables.

**Best for:** First-time project setup

---

### Method 3: Manual SQL (Advanced)

If you prefer to run SQL directly:

```bash
# 1. Create tables
mysql -u root hotel_management < database/pricing_migration.sql

# 2. Create procedures
mysql -u root hotel_management < database/pricing_procedures.sql

# 3. Create triggers
mysql -u root hotel_management < database/pricing_triggers.sql

# 4. Load test data (optional)
mysql -u root hotel_management < database/pricing_seed.sql
```

Or paste each file contents into phpMyAdmin SQL tab.

---

## What Gets Created

### Tables (4 new)
```sql
pricing_rules          -- Stores pricing rules
base_room_prices       -- Base price per room
occupancy_history      -- Daily occupancy tracking
pricing_history        -- Audit trail of all price changes
```

### Stored Procedures (3 new)
```sql
CalculateDynamicPrice()              -- Calculates prices
UpdateOccupancyHistory()             -- Records occupancy
ApplyDynamicPricingToAllRooms()      -- Updates all prices
```

### Triggers (5 new)
```sql
after_booking_insert       -- Auto-update prices on new booking
after_booking_update       -- Auto-update prices on booking change
after_booking_delete       -- Auto-update prices on booking cancel
after_pricing_rule_insert  -- Apply new rules immediately
after_pricing_rule_update  -- Apply rule changes immediately
```

---

## Verify Setup Worked

### Quick Check:
1. Admin login: http://localhost/modern_hotel_management/admin/login.php
2. Go to admin dashboard
3. Click **💰 Pricing Dashboard**
4. Should load without errors ✓

### Detailed Check via phpMyAdmin:
1. Open: http://localhost/phpmyadmin
2. Select `hotel_management` database
3. Check **Tables** tab - should see:
   - ✅ pricing_rules
   - ✅ base_room_prices
   - ✅ occupancy_history
   - ✅ pricing_history

4. Check **Routines** tab → **Procedures** - should see:
   - ✅ CalculateDynamicPrice
   - ✅ UpdateOccupancyHistory
   - ✅ ApplyDynamicPricingToAllRooms

5. Check **Triggers** - should see:
   - ✅ after_booking_delete
   - ✅ after_booking_insert
   - ✅ after_booking_update
   - ✅ after_pricing_rule_insert
   - ✅ after_pricing_rule_update

If all present → Setup successful! ✅

---

## After Setup: Next Steps

### 1. Create a Pricing Rule
- Admin login
- Go to: **⚙️ Manage Pricing Rules**
- Click **Create Rule**
- Example:
  - Name: "High Occupancy Surge"
  - Type: Occupancy-Based
  - Min: 75%, Max: 100%
  - Adjustment: +25%
  - Active: Yes
- Click **Create Rule**

### 2. View Pricing Dashboard
- Go to: **💰 Pricing Dashboard**
- See:
  - Current room prices
  - Occupancy percentage
  - Revenue impact
  - 7-day trends

### 3. Test Feature
- Guest login: guest@hotel.com / Guest123
- Try booking a room
- Notice prices (may be adjusted)
- Complete booking

### 4. Monitor Impact
- Admin dashboard
- Pricing Dashboard shows revenue changes
- See price adjustments in real-time

---

## Troubleshooting

### Still Getting "Table Not Found" Error?

**Try this:**
1. Go to: http://localhost/modern_hotel_management/admin/setup_pricing.php
2. Click the button
3. Wait for "Setup Completed" message
4. Refresh pricing page

### "Already Exists" Error?

**This is OK!** Means:
- Tables/procedures already exist
- Won't create duplicates
- Safe to run multiple times

### MySQL Not Running?

**Fix:**
1. Open XAMPP Control Panel
2. Click "Start" for MySQL
3. Wait for status to show "Running"
4. Try setup again

### Access Denied Error?

**Check config.php:**
```php
define('DB_USER', 'root');     // Should be 'root'
define('DB_PASS', '');         // Should be empty for XAMPP
```

---

## Setup Flow Diagram

```
User visits pricing page
    ↓
Gets "Table not found" error
    ↓
Goes to admin/setup_pricing.php
    ↓
Clicks "Setup Pricing Feature"
    ↓
Script creates:
    - Tables
    - Procedures
    - Triggers
    - Sample data
    ↓
Gets "Setup Completed" message
    ↓
Goes to pricing dashboard
    ↓
Everything works! ✅
```

---

## Files Involved in Setup

**Setup Scripts:**
- `setup.php` - Main project setup (now includes pricing)
- `admin/setup_pricing.php` - Pricing-only setup ⭐

**Migration Files:**
- `database/pricing_migration.sql` - Table definitions
- `database/pricing_procedures.sql` - Stored procedures
- `database/pricing_triggers.sql` - Triggers
- `database/pricing_seed.sql` - Sample data

**Feature Pages:**
- `admin/pricing_rules.php` - Manage rules
- `admin/pricing_dashboard.php` - View analytics

---

## Documentation

For more information:
- **DATABASE_SETUP.md** - Detailed setup guide
- **DYNAMIC_PRICING_README.md** - Feature overview
- **QUICK_START_PRICING.md** - Quick reference
- **DYNAMIC_PRICING_SETUP.md** - Installation guide

---

## Summary

**If you see "Table not found" error:**

✅ **Go to:** http://localhost/modern_hotel_management/admin/setup_pricing.php  
✅ **Click:** Setup Button  
✅ **Wait:** For confirmation  
✅ **Done:** Feature is ready to use!  

**It's that simple!** 🚀

---

**Status:** Setup System Complete ✅  
**Easy Setup Available:** Yes ⭐  
**Manual SQL Still Available:** Yes  
**Safe to Run Multiple Times:** Yes ✅  

You're all set! 🎉
