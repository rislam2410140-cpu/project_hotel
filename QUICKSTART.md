# Quick Start Guide

## 1. Initial Setup

The Hotel Management System is ready to use! Follow these steps:

### Option A: Using PHP Built-in Server (Development)
```bash
cd c:\xampp\htdocs\modern_hotel_management
php -S localhost:8000
```
Then open: **http://localhost:8000/**

### Option B: Using XAMPP/WAMP (Production)
1. Start Apache and MySQL from XAMPP Control Panel
2. Place project in `C:\xampp\htdocs\modern_hotel_management\`
3. Open: **http://localhost/modern_hotel_management/**

## 2. Database Setup

**First time only:**

1. Go to: **http://localhost:8000/setup.php**
   (or **http://localhost/modern_hotel_management/setup.php** with XAMPP)

2. Enter database credentials:
   - Host: `localhost`
   - Database: `hotel_management`
   - User: `root`
   - Password: (leave empty for XAMPP default)

3. Check "Import seed data" ✓

4. Click "Setup Database"

5. You'll see confirmation - you're done! ✓

## 3. Login & Explore

### Guest Account (Try first!)
- **Login**: http://localhost:8000/guest/login.php
- **Email**: `guest@hotel.com`
- **Password**: `Guest123`
- **Features**: Browse rooms, make bookings, leave reviews

### Admin Account
- **Login**: http://localhost:8000/admin/login.php
- **Email**: `admin@hotel.com`
- **Password**: `Admin123`
- **Features**: Manage rooms, bookings, users, view reports

## 4. Test Workflow

**As a Guest:**
1. Login
2. Go to "Browse Rooms"
3. Click "View Details" on a room
4. Click "Book This Room"
5. Select dates (future dates only)
6. Click "Proceed with Booking"
7. Go to "My Bookings" to see pending booking

**As Admin:**
1. Login
2. View Dashboard (KPIs)
3. Go to "Bookings"
4. Click "Confirm" on the pending booking
5. View Reports to see analytics

## File Locations

- **Homepage**: `/index.php`
- **Guest Portal**: `/guest/`
- **Admin Panel**: `/admin/`
- **Database Setup**: `/setup.php`
- **Configuration**: `/config.php`
- **Stylesheet**: `/assets/style.css`
- **Database Files**: `/database/`

## Important Notes

✓ **All passwords hashed** with bcrypt
✓ **SQL injection protected** - uses prepared statements
✓ **Responsive design** - works on mobile, tablet, desktop
✓ **No frameworks** - pure PHP, HTML, CSS, JS
✓ **ERD unchanged** - original 6 tables intact

## Common Issues

**Database won't connect?**
- Check if MySQL is running (XAMPP Control Panel)
- Verify credentials in `config.php`
- Make sure `hotel_management` database exists

**Can't access pages?**
- Clear browser cookies
- Check URL is correct:
  - PHP built-in server: `http://localhost:8000/`
  - XAMPP: `http://localhost/modern_hotel_management/`
- Verify PHP server is running

**"Not found" error?**
- Check file path is correct
- Make sure all files were extracted to project folder
- Verify web server docroot is set correctly

## Database

**Schema**: `/database/schema.sql` (6 tables)
- users (guests & admins)
- rooms (inventory)
- bookings (reservations)
- payments (invoicing)
- service_orders (room service)
- reviews (feedback)

**Seed Data**: `/database/seed.sql`
- 1 admin user
- 1 guest user  
- 8 rooms (varied types)
- Sample bookings & reviews

## Support

See **README.md** for:
- Complete feature list
- Technical stack details
- SQL query examples
- Security implementation
- File structure
- Troubleshooting

---

**You're all set!** 🎉

Start at:
- PHP built-in server: `http://localhost:8000/`
- XAMPP: `http://localhost/modern_hotel_management/`
