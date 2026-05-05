# 🚀 Pricing Feature - Database Setup Guide

## Quick Fix for Table Not Found Error

If you're seeing this error:
```
Error loading rules: SQLSTATE[42S02]: Base table or view not found: 1146 
Table 'hotel_management.pricing_rules' doesn't exist
```

**You need to initialize the pricing database tables first.** Choose one of these methods:

---

## Method 1: Automated Setup (Easiest) ⭐

### Step 1: Access Setup Page
1. Go to: **http://localhost/modern_hotel_management/admin/setup_pricing.php**
2. Click **⚙️ Setup Pricing Feature** button
3. Wait for confirmation message

### What This Does:
✅ Creates all pricing tables  
✅ Creates 3 stored procedures  
✅ Creates 5 automatic triggers  
✅ Loads sample pricing rules  

### Result:
- ✅ Tables created
- ✅ Procedures ready
- ✅ Triggers active
- ✅ Sample data loaded

**No manual SQL needed!** Just click a button.

---

## Method 2: Full System Setup

### If This is Your First Time Running the Project:

1. Go to: **http://localhost/modern_hotel_management/setup.php**
2. Enter database details (usually defaults work):
   - Host: `localhost`
   - Database: `hotel_management`
   - User: `root`
   - Password: (empty)
3. Check "Import seed data"
4. Click **Setup Database**

This will:
- ✅ Create all base tables (rooms, users, bookings, etc.)
- ✅ Create pricing tables (pricing_rules, occupancy_history, pricing_history)
- ✅ Create all stored procedures
- ✅ Create all triggers
- ✅ Load demo data including pricing rules

---

## Method 3: Manual SQL Setup

If you prefer to run SQL manually:

### Step 1: Create Tables
```bash
mysql -u root hotel_management < database/pricing_migration.sql
```

### Step 2: Create Procedures
```bash
mysql -u root hotel_management < database/pricing_procedures.sql
```

### Step 3: Create Triggers
```bash
mysql -u root hotel_management < database/pricing_triggers.sql
```

### Step 4: Load Test Data (Optional)
```bash
mysql -u root hotel_management < database/pricing_seed.sql
```

---

## Verification: Check If Setup Worked

### Option 1: Try the Feature
1. Login to admin: http://localhost/modern_hotel_management/admin/login.php
   - Email: `admin@hotel.com`
   - Password: `Admin123`
2. Click **💰 Pricing Dashboard** - if it loads, you're good!
3. Click **⚙️ Manage Pricing Rules** - create a test rule

### Option 2: Check Database via phpMyAdmin
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Select `hotel_management` database
3. Check Tables - you should see:
   - ✅ `pricing_rules`
   - ✅ `base_room_prices`
   - ✅ `occupancy_history`
   - ✅ `pricing_history`

4. Check Procedures (Routines → Procedures):
   - ✅ `CalculateDynamicPrice`
   - ✅ `UpdateOccupancyHistory`
   - ✅ `ApplyDynamicPricingToAllRooms`

5. Check Triggers:
   - ✅ `after_booking_insert`
   - ✅ `after_booking_update`
   - ✅ `after_booking_delete`
   - ✅ `after_pricing_rule_insert`
   - ✅ `after_pricing_rule_update`

---

## Troubleshooting

### Error: "Stored procedure already exists"
**This is OK!** It means the procedure was already set up. You can safely ignore it.

### Error: "Base table or view not found"
**Solution:** Run Method 1 (Automated Setup) above.

### Error: "Connection refused"
**Check:**
1. MySQL is running (XAMPP control panel)
2. Database credentials in config.php are correct
3. Database `hotel_management` exists

### Error: "Access denied"
**Check:**
1. MySQL user is `root`
2. Password is correct (empty by default for XAMPP)
3. Permissions are correct

---

## After Setup: What's Next?

### 1. Create Your First Pricing Rule
1. Admin login: http://localhost/modern_hotel_management/admin/login.php
2. Go to: **⚙️ Manage Pricing Rules**
3. Create rule example:
   - Name: "High Occupancy Surge"
   - Type: Occupancy-Based
   - Min: 75%, Max: 100%
   - Adjustment: +25%
   - Status: Active
4. Click **Create Rule**

### 2. Test the Feature
1. Login as guest: guest@hotel.com / Guest123
2. Try to book a room
3. Notice: Prices may be adjusted based on occupancy
4. Complete booking

### 3. Monitor Impact
1. Admin login
2. Go to: **💰 Pricing Dashboard**
3. Watch:
   - Current occupancy %
   - Room prices (base vs dynamic)
   - Revenue impact analysis
   - Occupancy trends

---

## Database Schema Overview

### New Tables Created:

**pricing_rules**
- Stores all pricing rules (seasonal, occupancy-based, events)
- When active, automatically applied to room prices

**base_room_prices**
- Stores base price for each room
- Used as starting point for dynamic pricing

**occupancy_history**
- Records daily occupancy percentage
- Used to determine which pricing rules apply

**pricing_history**
- Audit trail of all price changes
- Shows what rules were applied and why
- Useful for analytics and debugging

### How They Work Together:

```
Guest creates booking
    ↓
Trigger: after_booking_insert
    ↓
Occupancy updates (occupancy_history)
    ↓
For each room: CalculateDynamicPrice()
    ├─ Gets occupancy %
    ├─ Checks all active pricing_rules
    ├─ Calculates adjusted price
    └─ Stores in pricing_history (audit trail)
    ↓
Room's current_dynamic_price updates
    ↓
Next booking sees new prices!
```

---

## Files Involved

### Setup/Migration Files:
- `database/pricing_migration.sql` - Table definitions
- `database/pricing_procedures.sql` - Stored procedures
- `database/pricing_triggers.sql` - Auto-price triggers
- `database/pricing_seed.sql` - Sample data

### Setup Scripts:
- `setup.php` - Main setup (includes pricing)
- `admin/setup_pricing.php` - Pricing-only setup ⭐

### Feature Pages:
- `admin/pricing_rules.php` - Create/manage rules
- `admin/pricing_dashboard.php` - View analytics

---

## Recommended Setup Path

### First Time Users:
1. **Best Option:** Use `admin/setup_pricing.php`
   - Quickest
   - No manual SQL
   - Clear feedback

### Fresh Project Install:
1. **Best Option:** Use `setup.php`
   - Sets up everything at once
   - Includes demo data
   - One-stop setup

### Adding to Existing Project:
1. Use `admin/setup_pricing.php`
2. Or use Method 3 (manual SQL)
3. Everything is IF NOT EXISTS safe

---

## Verification Checklist

After setup, verify:

- [ ] Can access Pricing Dashboard without errors
- [ ] Can access Pricing Rules without errors
- [ ] Can create a new pricing rule
- [ ] Can see pricing tables in phpMyAdmin
- [ ] Can see pricing procedures in phpMyAdmin
- [ ] Can see pricing triggers in phpMyAdmin

If all checked, you're ready to go! 🚀

---

## Getting Help

**If you get errors:**

1. Check error message carefully - it usually tells you what's wrong
2. Most common: Tables don't exist → Use `admin/setup_pricing.php`
3. Check MySQL is running
4. Verify database credentials in `config.php`

**For detailed info:**
- See `DYNAMIC_PRICING_SETUP.md` for full documentation
- See `QUICK_START_PRICING.md` for feature overview
- See `DYNAMIC_PRICING_README.md` for concepts explained

---

## What if Setup Fails?

### Try This:

1. **Clear errors, fresh start:**
   - Close MySQL connections
   - Restart MySQL (XAMPP control panel)
   - Try again

2. **Manual verification:**
   - Use phpMyAdmin directly
   - Manually run the SQL files
   - Check for error messages

3. **Check database:**
   - phpMyAdmin → hotel_management
   - Check "Tables" tab
   - Look for existing pricing tables

4. **Reset and retry:**
   - Can safely run setup multiple times
   - Won't create duplicates (uses IF NOT EXISTS)
   - Just click button again

---

**Status:** Setup Complete ✅  
**Ready to use pricing feature!** 🚀

---

For questions, see documentation files or check code comments.
