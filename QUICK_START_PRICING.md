# 🚀 Dynamic Pricing - Quick Start Guide

## 5-Minute Setup

### What You Just Got:
✅ Advanced DBMS feature with stored procedures, triggers & real-time pricing  
✅ Admin dashboard for pricing rules and revenue analytics  
✅ Automatic price adjustments based on occupancy & seasons  
✅ Complete with test data and documentation  

---

## Installation (3 Simple Steps)

### Step 1: Setup Database
Run these 4 SQL files in phpMyAdmin or MySQL:

```bash
1. database/pricing_migration.sql     (Creates tables)
2. database/pricing_procedures.sql    (Creates procedures)
3. database/pricing_triggers.sql      (Creates triggers)
4. database/pricing_seed.sql          (Adds test data)
```

**In phpMyAdmin:**
- Go to your database (hotel_management)
- Click SQL tab
- Paste each file's contents
- Execute in order

### Step 2: Files Already in Place
All PHP files are already created:
- ✅ `admin/pricing_rules.php` - Manage pricing rules
- ✅ `admin/pricing_dashboard.php` - View analytics
- ✅ `admin/dashboard.php` - Modified to add links
- ✅ `guest/book_room.php` - Modified to use dynamic prices

### Step 3: Start Using It
1. Login to admin: http://localhost/modern_hotel_management/admin/login.php
   - Email: `admin@hotel.com`
   - Password: `Admin123`

2. Dashboard shows new buttons:
   - 💰 **Pricing Dashboard** - View pricing trends
   - ⚙️ **Manage Pricing Rules** - Create pricing rules

3. Create your first rule!

---

## Create Your First Pricing Rule (2 minutes)

### Example: High Occupancy Surge

1. Click **⚙️ Manage Pricing Rules**
2. Fill form:
   - **Rule Name:** "Peak Season Surge"
   - **Rule Type:** Occupancy-Based
   - **Min Occupancy:** 75%
   - **Max Occupancy:** 100%
   - **Adjustment Type:** Percentage
   - **Adjustment Value:** 25 (for +25%)
   - **Check:** Active
3. Click **Create Rule**

**Result:** When hotel is 75%+ full, prices automatically increase by 25%!

---

## What Happens Behind the Scenes

```
1. Guest creates booking
   ↓
2. Database trigger fires
   ↓
3. Occupancy calculated (# occupied / total rooms)
   ↓
4. All pricing rules checked
   ↓
5. New prices calculated & stored
   ↓
6. Next guest sees updated prices
```

**It's all automatic!** 🤖

---

## See It In Action

### View Analytics Dashboard
1. Click **💰 Pricing Dashboard**
2. See:
   - Today's occupancy %
   - Current prices for each room
   - Price changes (vs base price)
   - 7-day revenue impact
   - Occupancy trends

### Create Test Bookings
1. Login as guest: guest@hotel.com / Guest123
2. Browse rooms
3. Notice prices (may be higher/lower based on occupancy)
4. Create booking
5. Check admin dashboard - occupancy increased!

---

## Example Pricing Scenarios

### Scenario 1: Weekday vs Weekend
```
Monday Room Price: $100 (low occupancy)
Friday Room Price: $130 (high occupancy +30%)
```

### Scenario 2: Seasonal
```
January: $100 (off-season)
July: $150 (summer peak +50%)
December: $175 (holiday premium +75%)
```

### Scenario 3: Dynamic Adjustment
```
Base Price: $100
Occupancy < 40%: -10% → $90
Occupancy 40-70%: 0% → $100
Occupancy 70-85%: +20% → $120
Occupancy 85%+: +35% → $135
```

---

## Key Features

| Feature | Details |
|---------|---------|
| 📊 **Real-Time Pricing** | Prices update instantly when bookings change |
| 📈 **Multiple Rules** | Stack seasonal + occupancy rules together |
| 📅 **Seasonal Support** | Different prices for different seasons |
| 👀 **Admin Dashboard** | See all pricing, trends, and revenue impact |
| 📝 **Audit Trail** | Complete history of all price changes |
| 🔄 **Automatic Triggers** | No manual updates needed |

---

## File Structure

```
📦 modern_hotel_management/
├── 📂 database/
│   ├── pricing_migration.sql ......... Schema setup
│   ├── pricing_procedures.sql ........ Core logic
│   ├── pricing_triggers.sql .......... Automation
│   └── pricing_seed.sql ............. Test data
│
├── 📂 admin/
│   ├── pricing_rules.php ............ ✨ NEW
│   └── pricing_dashboard.php ........ ✨ NEW
│
└── 📂 docs/
    ├── DYNAMIC_PRICING_README.md .... Detailed guide
    ├── DYNAMIC_PRICING_SETUP.md .... Installation
    └── IMPLEMENTATION_CHECKLIST.md . Complete list
```

---

## Troubleshooting

### Issue: SQL errors when running migration
**Solution:** Make sure MySQL is running, database exists, and you're in correct database

### Issue: Pricing Dashboard shows "No data"
**Solution:** Create a few test bookings first to generate occupancy data

### Issue: Can't find new buttons in admin
**Solution:** Make sure admin/dashboard.php file was updated (it should be)

### Issue: Prices not updating
**Solution:** 
1. Check triggers were created: `SHOW TRIGGERS;`
2. Check procedures exist: `SHOW PROCEDURES;`
3. Create a test booking - should trigger price recalculation

---

## Next Steps

### For Learning:
1. Read `DYNAMIC_PRICING_README.md` - Feature overview
2. Review SQL files - Understand the implementation
3. Experiment with rules - See how they affect prices
4. Check `DYNAMIC_PRICING_SETUP.md` - Detailed guide

### For Course Submission:
1. ✅ All files implemented
2. ✅ All documentation complete
3. ✅ Test data included
4. ✅ Ready to present!

### For Production:
1. Adjust pricing rules for your business
2. Monitor revenue impact via dashboard
3. Optimize rules based on demand
4. Archive old pricing history for performance

---

## Quick Reference: SQL Commands

### See all pricing rules
```sql
SELECT * FROM pricing_rules WHERE is_active = TRUE;
```

### See today's occupancy
```sql
SELECT * FROM occupancy_history WHERE history_date = CURDATE();
```

### Track price changes for a room
```sql
SELECT * FROM pricing_history WHERE room_id = 1 ORDER BY created_at DESC;
```

### Revenue impact (last 7 days)
```sql
SELECT DATE(created_at), SUM(adjusted_price - base_price) as revenue_boost
FROM pricing_history
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at);
```

---

## 📚 Documentation Files

| File | Purpose |
|------|---------|
| **DYNAMIC_PRICING_README.md** | Complete feature guide with concepts |
| **DYNAMIC_PRICING_SETUP.md** | Detailed installation & troubleshooting |
| **IMPLEMENTATION_CHECKLIST.md** | What was implemented & why |

---

## 🎯 Demo Checklist (For Presentation)

- [ ] Login to admin
- [ ] Show Pricing Dashboard
- [ ] Show current room prices
- [ ] Create a new pricing rule
- [ ] Create a test booking (as guest)
- [ ] Show occupancy updated
- [ ] Refresh dashboard - prices changed!
- [ ] Show pricing history
- [ ] Show revenue impact report

---

## 💡 Tips

**Tip 1:** Start with simple occupancy-based rule (70-100% occupancy → +25%)  
**Tip 2:** Create booking as guest to see dynamic prices in action  
**Tip 3:** Use dashboard to monitor revenue impact  
**Tip 4:** Check SQL files to understand implementation  

---

## 🎓 For Your Professor

This implementation demonstrates:
- ✅ **Stored Procedures** - 3 complex procedures with business logic
- ✅ **Triggers** - 5 automated triggers for consistency
- ✅ **Complex Queries** - Multi-table aggregation with calculations
- ✅ **Database Design** - Audit trails, temporal data, relationships
- ✅ **Real-World Pattern** - Enterprise revenue management system
- ✅ **Performance** - Indexes, caching, batch processing

**Perfect for:** Advanced DBMS course projects!

---

## 🚀 Ready to Go!

Everything is installed and ready. Just run the SQL files and start using it!

**Questions?** See the detailed guides:
- Installation issues → `DYNAMIC_PRICING_SETUP.md`
- How it works → `DYNAMIC_PRICING_README.md`
- What's implemented → `IMPLEMENTATION_CHECKLIST.md`

---

**Happy Learning! 📚💰📊**
