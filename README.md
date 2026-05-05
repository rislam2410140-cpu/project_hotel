# Luxe Hotel Management System

A modern, full-featured hotel management system built with PHP 8+, MySQL, and vanilla HTML/CSS/JavaScript. Clean UI, responsive design, and secure authentication.

## Features

### Public Pages
- **Homepage** - Hero section with hotel info, room types, facilities, booking process
- **Room Browsing** - Filter rooms by type and price
- **Room Details** - View room amenities and book
- **About & Contact** - Hotel information and contact form

### Guest Portal (Guest Login: `guest@hotel.com` / `Guest123`)
- **Dashboard** - View active bookings, stats, quick actions
- **Browse & Book Rooms** - Select dates, calculate pricing, create bookings
- **My Bookings** - Manage reservations, cancel if pending/confirmed
- **Room Service** - Order food and amenities during stay
- **Reviews** - Leave feedback after checkout

### Admin Panel (Admin Login: `admin@hotel.com` / `Admin123`)
- **Dashboard** - KPIs, revenue, occupancy rate, recent bookings
- **Room Management** - Create, edit, delete rooms
- **Booking Management** - Confirm, check-in, checkout, cancel bookings
- **User Management** - View guest accounts and activity
- **Reports** - Analytics, revenue by month, top rooms

## Technical Stack

- **Backend**: PHP 8+ with PDO (prepared statements for security)
- **Database**: MySQL with InnoDB
- **Frontend**: HTML5, CSS3 (no frameworks), Vanilla JavaScript
- **Architecture**: MVC-style with includes and guards

## Database Design

The system maintains the ERD structure with 6 core tables:

```
Users ──< Bookings >── Rooms
  │          │           │
  └─< Payments ────────┘
                        ├─ Service_Orders
                        
Bookings ──< Reviews
```

### Tables
- **users** - Guest and admin accounts with password hashing
- **rooms** - Room inventory with type, price, capacity, status
- **bookings** - Reservations with overlap prevention
- **payments** - Invoice tracking (pending/paid/failed)
- **service_orders** - Room service orders with JSON items
- **reviews** - Guest ratings and feedback (1-5 stars)

## Setup Instructions

### Prerequisites
- PHP 8.0+
- MySQL 5.7+
- XAMPP, WAMP, or similar
- Web server

### Installation

1. **Download/Extract** the project to your web root:
   ```bash
   # XAMPP: C:\xampp\htdocs\modern_hotel_management\
   # Or your web server's public folder
   ```

2. **Create MySQL Database**:
   ```bash
   # In phpMyAdmin or MySQL CLI:
   CREATE DATABASE hotel_management;
   ```

3. **Import Schema**:
   ```bash
   # In phpMyAdmin: Import database/schema.sql
   # Or via CLI:
   mysql -u root hotel_management < database/schema.sql
   ```

4. **Import Seed Data** (optional - adds demo data):
   ```bash
   mysql -u root hotel_management < database/seed.sql
   ```

5. **Configure Database** (if needed):
   Edit `config.php`:
   ```php
   define('DB_HOST', 'localhost');      // Your host
   define('DB_NAME', 'hotel_management'); // Your database
   define('DB_USER', 'root');           // Your MySQL user
   define('DB_PASS', '');               // Your MySQL password
   ```

6. **Start Web Server**:
   ```bash
   # XAMPP: Start Apache and MySQL from control panel
   # Or use PHP built-in server:
   php -S localhost:8000
   ```

7. **Access the Application**:
   - Homepage: http://localhost/modern_hotel_management/
   - Guest Login: http://localhost/modern_hotel_management/guest/login.php
   - Admin Login: http://localhost/modern_hotel_management/admin/login.php

## Demo Credentials

### Guest Account
- **Email**: `guest@hotel.com`
- **Password**: `Guest123`

### Admin Account
- **Email**: `admin@hotel.com`
- **Password**: `Admin123`

## Key Features & Logic

### Booking System
- ✅ Date validation (check-in < check-out, future dates only)
- ✅ Overlap prevention (queries active bookings before inserting)
- ✅ Automatic price calculation (nights × room price)
- ✅ Status workflow: pending → confirmed → checked_in → completed
- ✅ Guest can cancel only if pending/confirmed

### Room Management
- ✅ Status tracking: available, occupied, cleaning
- ✅ Auto-update on check-in (→ occupied) and checkout (→ available)
- ✅ Room type filtering on public pages

### Payment System
- ✅ Auto-created as 'pending' on booking
- ✅ Payment methods: cash, bkash, nagad, card
- ✅ Admin can mark as paid/failed
- ✅ Revenue reports (paid payments only)

### Reviews System
- ✅ Only available after checkout/completed
- ✅ 1-5 star rating
- ✅ Optional comment field
- ✅ One review per booking (UNIQUE constraint)

### Security
- ✅ Password hashing with bcrypt (password_hash/password_verify)
- ✅ PDO prepared statements (prevent SQL injection)
- ✅ Session-based authentication
- ✅ Role-based access control (guest vs admin)
- ✅ Guards: require_guest.php, require_admin.php

## File Structure

```
modern_hotel_management/
├── database/
│   ├── schema.sql           # Table definitions
│   ├── seed.sql             # Demo data
│   └── db_connect.php       # PDO connection class
├── includes/
│   ├── header.php           # Reusable header with nav
│   ├── footer.php           # Reusable footer
│   ├── require_guest.php    # Guest auth guard + helpers
│   ├── require_admin.php    # Admin auth guard + helpers
├── assets/
│   ├── style.css            # Unified theme (colors, typography)
│   └── app.js               # Client-side interactions
├── public/
│   ├── index.php            # Homepage
│   ├── rooms.php            # Room browser
│   ├── room_details.php     # Room detail + booking link
│   ├── about.php            # About page
│   └── contact.php          # Contact page
├── guest/
│   ├── login.php            # Guest login form
│   ├── signup.php           # Guest registration
│   ├── dashboard.php        # Guest dashboard
│   ├── book_room.php        # Booking form
│   ├── my_bookings.php      # Bookings list & management
│   ├── room_service.php     # Service orders
│   ├── review.php           # Review submission
│   └── logout.php           # Session cleanup
├── admin/
│   ├── login.php            # Admin login
│   ├── dashboard.php        # KPIs & stats
│   ├── rooms.php            # Room CRUD
│   ├── bookings.php         # Booking management
│   ├── users.php            # User list
│   ├── reports.php          # Analytics
│   └── logout.php           # Session cleanup
├── config.php               # Environment settings
└── README.md                # This file
```

## Important SQL Queries Used

### Check for Overlapping Bookings
```sql
SELECT COUNT(*) FROM bookings
WHERE room_id = ?
AND status IN ('pending', 'confirmed', 'checked_in')
AND ((check_in_date < ? AND check_out_date > ?)
     OR (check_in_date < ? AND check_out_date > ?))
```

### Revenue Report
```sql
SELECT DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(amount) as revenue
FROM payments
WHERE payment_status = 'paid'
GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
```

### Top Rooms by Bookings
```sql
SELECT r.room_id, r.room_number, COUNT(b.booking_id) as booking_count
FROM rooms r
LEFT JOIN bookings b ON r.room_id = b.room_id
GROUP BY r.room_id
ORDER BY booking_count DESC
```

### Guest Statistics
```sql
SELECT u.*, COUNT(b.booking_id) as total_bookings, SUM(b.total_price) as total_spent
FROM users u
LEFT JOIN bookings b ON u.user_id = b.user_id
WHERE u.role = 'guest'
GROUP BY u.user_id
```

## UI/UX Design

### Color Theme
- Primary: #2563eb (Blue)
- Success: #10b981 (Green)
- Warning: #f59e0b (Yellow)
- Danger: #ef4444 (Red)
- Dark: #1f2937 (Gray)

### Components
- **Cards**: Clean white containers with subtle shadows
- **Buttons**: Consistent styling with hover states
- **Status Badges**: Color-coded for quick identification
- **Tables**: Striped rows, hover effects, responsive
- **Forms**: Clear labels, error messages, validation
- **Modals**: For editing/actions without page reload

### Responsive Design
- Mobile-first approach
- Flexible grid system
- Touch-friendly buttons and inputs
- Breakpoints: 480px, 768px

## Security Notes

1. **All passwords hashed** with bcrypt (PASSWORD_BCRYPT algorithm)
2. **All database queries** use prepared statements to prevent SQL injection
3. **Session security** enabled (HTTP-only cookies)
4. **Input validation** on all forms
5. **Role-based guards** prevent unauthorized access
6. **CSRF protection** can be added if needed (not included in MVP)

## Known Limitations

- Email notifications not implemented
- PDF exports not implemented
- Advanced date range filters in reports
- SMS alerts not implemented
- Real payment gateway integration not included

## Future Enhancements

- Email confirmations and reminders
- Multi-language support
- Advanced reporting and analytics
- Mobile app
- Real payment gateway integration
- SMS notifications
- Booking modifications/extensions
- Multiple admin roles (manager, housekeeper, etc.)

## Troubleshooting

### Database Connection Error
- Check config.php settings
- Verify MySQL is running
- Ensure database exists and schema imported
- Check database user permissions

### Session/Login Issues
- Clear browser cookies
- Verify sessions folder is writable
- Check PHP session settings in php.ini

### Missing Pages
- Verify .php files exist in correct directories
- Check file permissions (read access)
- Verify htdocs path is correct

## License

This project is provided as-is for educational and commercial use.

## Support

For issues or questions, check the code comments or database schema for implementation details.

---

**Built with ❤️ using PHP, MySQL, and vanilla web technologies**
# project_hotel
