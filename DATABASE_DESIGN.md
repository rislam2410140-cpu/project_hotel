# 🏨 Hotel Management System - Database Design

## Executive Summary

The Modern Hotel Management System uses a **relational database** with **MySQL/InnoDB** to manage:
- **Core Operations**: Rooms, Users, Bookings, Payments
- **Guest Services**: Service Orders, Reviews  
- **Dynamic Pricing**: Pricing Rules, Occupancy Tracking, Price History

Total: **11 tables** with comprehensive relationships and integrity constraints.

---

## 📊 Entity-Relationship Diagram (ER Diagram)

```
                            USERS (Core Users)
                                 │
                    ┌────────────┬┴──────────────┐
                    │            │               │
                    ▼            ▼               ▼
              BOOKINGS      REVIEWS        SERVICE_ORDERS
                    │            │               │
                    ├────────────┼───────────────┤
                    ▼            ▼               ▼
                PAYMENTS      BOOKINGS      SERVICE_ORDERS
                                                 │
                                                 ▼
                                             ROOMS
                                                 │
                    ┌────────────────────────────┼────────────────┐
                    │                            │                │
                    ▼                            ▼                ▼
            BASE_ROOM_PRICES          OCCUPANCY_HISTORY    PRICING_HISTORY
                                                               │
                                                               ▼
                                                         PRICING_RULES

RELATIONSHIPS:
   users          1 ──→ ∞  bookings
   bookings       1 ──→ ∞  payments (1:1 with unique constraint)
   bookings       1 ──→ 1  reviews (1:1 with unique constraint)
   rooms          1 ──→ ∞  bookings
   rooms          1 ──→ ∞  service_orders
   rooms          1 ──→ 1  base_room_prices (1:1 with unique constraint)
   rooms          1 ──→ ∞  pricing_history
   pricing_rules  ∞ ──→ ∞  pricing (applied via rules engine)
```

---

## 📋 Database Tables

### 1. **USERS** (User Management)

**Purpose**: Store all user accounts (guests and administrators)

| Column | Type | Constraints | Purpose |
|--------|------|-------------|---------|
| `user_id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique user identifier |
| `name` | VARCHAR(100) | NOT NULL | User's full name |
| `email` | VARCHAR(100) | UNIQUE, NOT NULL | Login email (unique) |
| `phone` | VARCHAR(20) | NULLABLE | Contact number |
| `password_hash` | VARCHAR(255) | NOT NULL | Bcrypt hashed password |
| `role` | ENUM('guest', 'admin') | NOT NULL, DEFAULT 'guest' | User type |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Account creation time |

**Indexes**:
- PRIMARY KEY: `user_id`
- UNIQUE: `email`

**Sample Data**:
```
user_id=1  | admin@hotel.com     | role=admin
user_id=2  | guest@hotel.com     | role=guest
```

---

### 2. **ROOMS** (Room Inventory)

**Purpose**: Store room details and real-time status

| Column | Type | Constraints | Purpose |
|--------|------|-------------|---------|
| `room_id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique room identifier |
| `room_number` | VARCHAR(20) | UNIQUE, NOT NULL | Physical room number (e.g., "101", "A-201") |
| `room_type` | VARCHAR(50) | NOT NULL | Type (Single, Double, Suite, etc.) |
| `price` | DECIMAL(10,2) | NOT NULL | Base/standard price per night |
| `status` | ENUM('available', 'occupied', 'cleaning') | DEFAULT 'available' | Current room state |
| `capacity` | INT | DEFAULT 2 | Number of guests |
| `current_dynamic_price` | DECIMAL(10,2) | NULLABLE | Current adjusted price (cached) |
| `last_price_update` | TIMESTAMP | NULLABLE | When dynamic price was last updated |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Room added date |

**Indexes**:
- PRIMARY KEY: `room_id`
- UNIQUE: `room_number`

**Sample Data**:
```
room_id=1  | 101   | Double   | 5000.00  | available
room_id=2  | 102   | Suite    | 8000.00  | occupied
room_id=3  | 201   | Single   | 3000.00  | cleaning
```

---

### 3. **BOOKINGS** (Reservation System)

**Purpose**: Track all room reservations and their lifecycle

| Column | Type | Constraints | Purpose |
|--------|------|-------------|---------|
| `booking_id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique booking identifier |
| `user_id` | INT | FOREIGN KEY → USERS.user_id | Which guest booked |
| `room_id` | INT | FOREIGN KEY → ROOMS.room_id | Which room booked |
| `check_in_date` | DATE | NOT NULL | Reservation start date |
| `check_out_date` | DATE | NOT NULL | Reservation end date |
| `total_price` | DECIMAL(10,2) | NOT NULL | Total booking cost |
| `status` | ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'completed') | DEFAULT 'pending' | Booking state |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Booking creation time |

**Indexes**:
- PRIMARY KEY: `booking_id`
- FOREIGN KEY: `user_id` (ON DELETE CASCADE)
- FOREIGN KEY: `room_id` (ON DELETE CASCADE)
- COMPOSITE: `idx_user_id (user_id)`
- COMPOSITE: `idx_room_id (room_id)`
- COMPOSITE: `idx_dates (check_in_date, check_out_date)`
- COMPOSITE: `idx_status (status)`

**Relationships**:
- ✓ 1 User can have ∞ Bookings
- ✓ 1 Room can have ∞ Bookings  
- ✓ Each Booking → 1 Payment
- ✓ Each Booking → 1 Review (optional)
- ✓ Each Booking → ∞ Service Orders (optional)

**Sample Data**:
```
booking_id=1 | user_id=2 | room_id=1 | 2026-05-10 to 2026-05-12 | status=confirmed | price=10000.00
booking_id=2 | user_id=2 | room_id=2 | 2026-05-15 to 2026-05-18 | status=pending   | price=24000.00
```

---

### 4. **PAYMENTS** (Payment Processing)

**Purpose**: Track payment status for each booking (1:1 relationship with BOOKINGS)

| Column | Type | Constraints | Purpose |
|--------|------|-------------|---------|
| `payment_id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique payment identifier |
| `booking_id` | INT | FOREIGN KEY, UNIQUE | Which booking this payment is for |
| `amount` | DECIMAL(10,2) | NOT NULL | Payment amount |
| `method` | ENUM('cash', 'bkash', 'nagad', 'card') | DEFAULT 'cash' | Payment method |
| `payment_status` | ENUM('pending', 'paid', 'failed') | DEFAULT 'pending' | Payment state |
| `paid_at` | TIMESTAMP | NULLABLE | When payment completed |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Payment initiated time |

**Indexes**:
- PRIMARY KEY: `payment_id`
- FOREIGN KEY: `booking_id` (ON DELETE CASCADE, UNIQUE)
- COMPOSITE: `idx_booking_id (booking_id)`
- COMPOSITE: `idx_status (payment_status)`

**Constraints**:
- ✓ **UNIQUE**: One payment per booking
- ✓ **CASCADE DELETE**: If booking deleted, payment deleted

**Sample Data**:
```
payment_id=1 | booking_id=1 | amount=10000.00 | method=card       | status=paid    | paid_at=2026-05-09 14:30:00
payment_id=2 | booking_id=2 | amount=24000.00 | method=bkash      | status=pending | paid_at=NULL
```

---

### 5. **SERVICE_ORDERS** (Room Service)

**Purpose**: Track guest service requests (room service, housekeeping, etc.)

| Column | Type | Constraints | Purpose |
|--------|------|-------------|---------|
| `order_id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique order identifier |
| `room_id` | INT | FOREIGN KEY → ROOMS.room_id | Which room ordered |
| `booking_id` | INT | FOREIGN KEY → BOOKINGS.booking_id | Which booking (optional) |
| `items` | JSON | NOT NULL | Order details (nested JSON) |
| `total_price` | DECIMAL(10,2) | NOT NULL | Order total |
| `status` | ENUM('pending', 'preparing', 'delivered') | DEFAULT 'pending' | Service state |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Order time |

**Indexes**:
- PRIMARY KEY: `order_id`
- FOREIGN KEY: `room_id` (ON DELETE CASCADE)
- FOREIGN KEY: `booking_id` (ON DELETE SET NULL)
- COMPOSITE: `idx_room_id (room_id)`
- COMPOSITE: `idx_booking_id (booking_id)`
- COMPOSITE: `idx_status (status)`

**Relationships**:
- ✓ 1 Room can have ∞ Service Orders
- ✓ 1 Booking can have ∞ Service Orders (optional)

**Sample Data (items field as JSON)**:
```json
{
  "order_id": 1,
  "room_id": 1,
  "items": [
    {"name": "Room Cleaning", "qty": 1, "price": 500},
    {"name": "Coffee & Tea", "qty": 2, "price": 300}
  ],
  "total_price": 800,
  "status": "delivered"
}
```

---

### 6. **REVIEWS** (Guest Feedback)

**Purpose**: Store guest ratings and comments (1:1 with BOOKINGS)

| Column | Type | Constraints | Purpose |
|--------|------|-------------|---------|
| `review_id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique review identifier |
| `booking_id` | INT | FOREIGN KEY, UNIQUE | Which booking this review is for |
| `rating` | INT | CHECK (1-5), NOT NULL | Star rating (1-5) |
| `comment` | TEXT | NULLABLE | Written feedback |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Review date |

**Indexes**:
- PRIMARY KEY: `review_id`
- FOREIGN KEY: `booking_id` (ON DELETE CASCADE, UNIQUE)
- COMPOSITE: `idx_booking_id (booking_id)`
- COMPOSITE: `idx_rating (rating)`

**Constraints**:
- ✓ **UNIQUE**: One review per booking (optional)
- ✓ **CHECK**: Rating must be 1-5
- ✓ **CASCADE DELETE**: If booking deleted, review deleted

**Sample Data**:
```
review_id=1 | booking_id=1 | rating=5 | comment="Excellent stay! Clean rooms and friendly staff."
review_id=2 | booking_id=2 | rating=4 | comment="Good location, could improve breakfast"
```

---

## 💰 Dynamic Pricing Tables

### 7. **BASE_ROOM_PRICES** (Price Foundation)

**Purpose**: Store historical base prices for each room (1:1 with ROOMS)

| Column | Type | Constraints | Purpose |
|--------|------|-------------|---------|
| `base_price_id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique price record identifier |
| `room_id` | INT | FOREIGN KEY, UNIQUE | Which room |
| `base_price` | DECIMAL(10,2) | NOT NULL | Base price per night |
| `effective_from` | DATE | NOT NULL, DEFAULT TODAY | Start date of this price |
| `effective_to` | DATE | NULLABLE | End date of this price (NULL = ongoing) |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last update time |

**Indexes**:
- PRIMARY KEY: `base_price_id`
- FOREIGN KEY: `room_id` (ON DELETE CASCADE, UNIQUE)
- COMPOSITE: `idx_room_id (room_id)`
- COMPOSITE: `idx_effective_dates (effective_from, effective_to)`

**Purpose**: Provides historical price tracking and audit trail

**Sample Data**:
```
base_price_id=1 | room_id=1 | base_price=5000.00  | from=2026-01-01 | to=NULL
base_price_id=2 | room_id=2 | base_price=8000.00  | from=2026-01-01 | to=NULL
```

---

### 8. **PRICING_RULES** (Pricing Strategy)

**Purpose**: Define dynamic pricing rules (seasonal, occupancy-based, event-based)

| Column | Type | Constraints | Purpose |
|--------|------|-------------|---------|
| `rule_id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique rule identifier |
| `rule_name` | VARCHAR(100) | NOT NULL | Rule display name |
| `rule_type` | ENUM('seasonal', 'occupancy', 'event') | NOT NULL | Rule classification |
| **Seasonal Fields** | | | |
| `season_name` | VARCHAR(50) | NULLABLE | Season name (e.g., "Summer", "Holiday") |
| `season_start_date` | DATE | NULLABLE | Season start date |
| `season_end_date` | DATE | NULLABLE | Season end date |
| **Occupancy Fields** | | | |
| `occupancy_min_percent` | INT | NULLABLE | Min occupancy % (0-100) |
| `occupancy_max_percent` | INT | NULLABLE | Max occupancy % (0-100) |
| **Price Adjustment** | | | |
| `adjustment_type` | ENUM('percentage', 'fixed') | DEFAULT 'percentage' | Adjustment method |
| `adjustment_value` | DECIMAL(10,2) | NOT NULL | Adjustment amount/% |
| **Control** | | | |
| `is_active` | BOOLEAN | DEFAULT TRUE | Rule enabled/disabled |
| `applies_to_room_types` | VARCHAR(255) | NULLABLE | Room types (comma-separated) |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Rule creation time |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last update time |

**Indexes**:
- PRIMARY KEY: `rule_id`
- COMPOSITE: `idx_active (is_active)`
- COMPOSITE: `idx_rule_type (rule_type)`
- COMPOSITE: `idx_dates (season_start_date, season_end_date)`

**Sample Data**:

**Rule 1: High Occupancy Surge**
```
rule_id=1 | rule_name="High Occupancy Surge" | rule_type="occupancy"
occupancy_min=75 | occupancy_max=100 | adjustment_type="percentage" | adjustment_value=25 | is_active=TRUE
```

**Rule 2: Summer Peak Season**
```
rule_id=2 | rule_name="Summer Peak" | rule_type="seasonal"
season_name="Summer" | season_start="2026-06-01" | season_end="2026-08-31" | adjustment_type="percentage" | adjustment_value=40 | is_active=TRUE
```

**Rule 3: Weekend Premium**
```
rule_id=3 | rule_name="Weekend Premium" | rule_type="occupancy"
occupancy_min=50 | occupancy_max=100 | adjustment_type="fixed" | adjustment_value=500 | is_active=TRUE
```

---

### 9. **OCCUPANCY_HISTORY** (Analytics)

**Purpose**: Track daily occupancy rates for reporting and pricing decisions

| Column | Type | Constraints | Purpose |
|--------|------|-------------|---------|
| `history_id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique history record identifier |
| `history_date` | DATE | NOT NULL, UNIQUE | Date of occupancy record |
| `occupancy_percent` | INT | NOT NULL | Occupancy percentage (0-100) |
| `total_rooms` | INT | NOT NULL | Total rooms available that day |
| `occupied_rooms` | INT | NOT NULL | Rooms occupied that day |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |

**Indexes**:
- PRIMARY KEY: `history_id`
- UNIQUE: `history_date`
- COMPOSITE: `idx_date (history_date)`
- COMPOSITE: `idx_occupancy_percent (occupancy_percent)`

**Purpose**: Historical occupancy for analytics and trend analysis

**Sample Data**:
```
history_id=1 | history_date=2026-05-07 | occupancy_percent=85 | total=20 | occupied=17 | time=2026-05-07 23:59:00
history_id=2 | history_date=2026-05-08 | occupancy_percent=60 | total=20 | occupied=12 | time=2026-05-08 23:59:00
```

---

### 10. **PRICING_HISTORY** (Audit Trail)

**Purpose**: Track all dynamic price changes with applied rules (audit log)

| Column | Type | Constraints | Purpose |
|--------|------|-------------|---------|
| `pricing_id` | INT | PRIMARY KEY, AUTO_INCREMENT | Unique pricing change identifier |
| `room_id` | INT | FOREIGN KEY → ROOMS.room_id | Which room |
| `base_price` | DECIMAL(10,2) | NOT NULL | Original base price |
| `adjusted_price` | DECIMAL(10,2) | NOT NULL | Final calculated price |
| `occupancy_percent` | INT | NOT NULL | Occupancy when calculated |
| `applied_rules` | VARCHAR(500) | NULLABLE | Which rules were applied (e.g., "Rule 1, Rule 2") |
| `effective_date` | DATE | NOT NULL | Date this price became effective |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | When calculation was done |

**Indexes**:
- PRIMARY KEY: `pricing_id`
- FOREIGN KEY: `room_id` (ON DELETE CASCADE)
- COMPOSITE: `idx_room_id (room_id)`
- COMPOSITE: `idx_effective_date (effective_date)`
- COMPOSITE: `idx_created_at (created_at)`

**Purpose**: Complete audit trail of all pricing decisions

**Sample Data**:
```
pricing_id=1 | room_id=1 | base=5000 | adjusted=6250 | occupancy=85% | rules="Rule 1: +25%" | effective=2026-05-08
pricing_id=2 | room_id=2 | base=8000 | adjusted=11200 | occupancy=90% | rules="Rule 1: +25%, Rule 3: +500" | effective=2026-05-08
```

---

## 🔗 Relationship Summary

### One-to-Many (1:∞)
| From | To | Constraint |
|------|----|----|
| USERS | BOOKINGS | Each user can have many bookings |
| ROOMS | BOOKINGS | Each room can be booked many times |
| ROOMS | SERVICE_ORDERS | Each room can have many service orders |
| BOOKINGS | SERVICE_ORDERS | Each booking can have many service orders |
| ROOMS | PRICING_HISTORY | Each room has many price change records |

### One-to-One (1:1) with UNIQUE constraint
| From | To | Constraint |
|------|----|----|
| BOOKINGS | PAYMENTS | Each booking has exactly one payment |
| BOOKINGS | REVIEWS | Each booking has at most one review (optional) |
| ROOMS | BASE_ROOM_PRICES | Each room has one current base price |

### Many-to-Many (∞:∞) via Rules Engine
| Between | Via |
|---------|-----|
| PRICING_RULES | ROOMS | Rules are applied to room types (programmatic, not direct table relationship) |

---

## 🔐 Data Integrity & Constraints

### Primary Keys (PK)
- All tables have AUTO_INCREMENT primary keys
- Ensures unique identification of every record

### Foreign Keys (FK)
- **BOOKINGS** → USERS, ROOMS (ON DELETE CASCADE)
- **PAYMENTS** → BOOKINGS (ON DELETE CASCADE, UNIQUE)
- **SERVICE_ORDERS** → ROOMS, BOOKINGS (ON DELETE CASCADE/SET NULL)
- **REVIEWS** → BOOKINGS (ON DELETE CASCADE, UNIQUE)
- **BASE_ROOM_PRICES** → ROOMS (ON DELETE CASCADE, UNIQUE)
- **PRICING_HISTORY** → ROOMS (ON DELETE CASCADE)

### Cascading Deletes
- Delete a **User** → All bookings, payments, reviews deleted
- Delete a **Room** → All bookings, service orders, prices, history deleted
- Delete a **Booking** → Payment and review automatically deleted

### CHECK Constraints
- REVIEWS.rating: Must be 1-5
- OCCUPANCY_HISTORY fields: Percentage 0-100

### UNIQUE Constraints
- USERS.email (no duplicate emails)
- ROOMS.room_number (no duplicate room numbers)
- PAYMENTS.booking_id (one payment per booking)
- REVIEWS.booking_id (one review per booking)
- BASE_ROOM_PRICES.room_id (one price per room)
- OCCUPANCY_HISTORY.history_date (one record per day)

---

## 📊 Indexes (Performance Optimization)

### Core Indexes
```sql
-- USERS
PRIMARY KEY (user_id)
UNIQUE (email)

-- ROOMS  
PRIMARY KEY (room_id)
UNIQUE (room_number)

-- BOOKINGS
PRIMARY KEY (booking_id)
INDEX idx_user_id (user_id)
INDEX idx_room_id (room_id)
INDEX idx_dates (check_in_date, check_out_date)  -- For availability queries
INDEX idx_status (status)  -- For booking status queries
COMPOSITE idx_bookings_user_status (user_id, status)
COMPOSITE idx_bookings_room_status (room_id, status)

-- PAYMENTS
PRIMARY KEY (payment_id)
INDEX idx_booking_id (booking_id)
INDEX idx_status (payment_status)  -- For payment queries
COMPOSITE idx_payments_status (payment_status)

-- SERVICE_ORDERS
PRIMARY KEY (order_id)
INDEX idx_room_id (room_id)
INDEX idx_booking_id (booking_id)
INDEX idx_status (status)

-- REVIEWS
PRIMARY KEY (review_id)
INDEX idx_booking_id (booking_id)
INDEX idx_rating (rating)  -- For rating analysis

-- BASE_ROOM_PRICES
PRIMARY KEY (base_price_id)
INDEX idx_room_id (room_id)
INDEX idx_effective_dates (effective_from, effective_to)

-- PRICING_RULES
PRIMARY KEY (rule_id)
INDEX idx_active (is_active)  -- For fetching active rules
INDEX idx_rule_type (rule_type)
INDEX idx_dates (season_start_date, season_end_date)

-- OCCUPANCY_HISTORY
PRIMARY KEY (history_id)
UNIQUE (history_date)
INDEX idx_date (history_date)
INDEX idx_occupancy_percent (occupancy_percent)

-- PRICING_HISTORY
PRIMARY KEY (pricing_id)
INDEX idx_room_id (room_id)
INDEX idx_effective_date (effective_date)
INDEX idx_created_at (created_at)
```

---

## 🗄️ Storage Configuration

All tables use:
- **Engine**: InnoDB (provides ACID compliance and foreign key support)
- **Character Set**: utf8mb4 (full Unicode support)
- **Collation**: utf8mb4_unicode_ci (case-insensitive Unicode collation)

---

## 📈 Key Workflows

### Booking Workflow
```
1. Guest creates account (USERS table)
   ↓
2. Guest searches available rooms (ROOMS, BOOKINGS, BASE_ROOM_PRICES)
   ↓
3. System calculates dynamic price (PRICING_RULES, OCCUPANCY_HISTORY)
   ↓
4. Guest creates booking (BOOKINGS table)
   ↓
5. Occupancy updated (OCCUPANCY_HISTORY table)
   ↓
6. Dynamic prices recalculated (PRICING_HISTORY table)
   ↓
7. Guest makes payment (PAYMENTS table)
   ↓
8. Guest checks in (BOOKINGS.status = 'checked_in')
   ↓
9. Guest may order services (SERVICE_ORDERS table)
   ↓
10. Guest checks out (BOOKINGS.status = 'checked_out')
    ↓
11. Guest leaves review (REVIEWS table)
```

### Dynamic Pricing Calculation Workflow
```
1. Guest creates booking (triggers after_booking_insert)
   ↓
2. Occupancy percentage calculated
   ↓
3. System checks all active PRICING_RULES
   ↓
4. For matching rules:
   - Apply percentage adjustment, OR
   - Apply fixed amount adjustment
   ↓
5. Final price = base_price + adjustments
   ↓
6. Store in PRICING_HISTORY (audit trail)
   ↓
7. Update ROOMS.current_dynamic_price (cache)
   ↓
8. Next booking sees new price
```

---

## 🔍 Query Examples

### Find Available Rooms for Specific Dates
```sql
SELECT r.room_id, r.room_number, r.room_type, 
       COALESCE(r.current_dynamic_price, r.price) as current_price
FROM rooms r
WHERE r.status = 'available'
  AND room_id NOT IN (
    SELECT room_id FROM bookings 
    WHERE status IN ('confirmed', 'checked_in')
    AND check_in_date <= '2026-05-15' 
    AND check_out_date > '2026-05-10'
  )
ORDER BY current_price ASC;
```

### Get User Booking History
```sql
SELECT b.booking_id, b.room_id, r.room_number, 
       b.check_in_date, b.check_out_date, b.total_price, 
       b.status, p.payment_status, rev.rating
FROM bookings b
JOIN rooms r ON b.room_id = r.room_id
LEFT JOIN payments p ON b.booking_id = p.booking_id
LEFT JOIN reviews rev ON b.booking_id = rev.booking_id
WHERE b.user_id = 5
ORDER BY b.created_at DESC;
```

### Calculate Revenue by Room Type
```sql
SELECT r.room_type, 
       COUNT(b.booking_id) as total_bookings,
       SUM(b.total_price) as total_revenue,
       AVG(b.total_price) as avg_booking_value
FROM bookings b
JOIN rooms r ON b.room_id = r.room_id
WHERE b.status IN ('completed', 'checked_out')
  AND b.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY r.room_type;
```

### Monitor Dynamic Pricing Impact
```sql
SELECT r.room_id, r.room_number,
       ROUND((price.adjusted_price - price.base_price) / price.base_price * 100, 2) as price_adjustment_percent,
       price.occupancy_percent,
       price.applied_rules,
       price.effective_date
FROM pricing_history price
JOIN rooms r ON price.room_id = r.room_id
WHERE price.effective_date >= CURDATE()
ORDER BY price.created_at DESC
LIMIT 50;
```

---

## 📝 Key Features

### ✅ Core Features
- **Multi-user system** (Guests & Admins)
- **Room inventory management** (Real-time status)
- **Booking lifecycle management** (6 status states)
- **Payment processing** (Multiple methods: cash, bkash, nagad, card)
- **Guest service orders** (JSON-based item tracking)
- **Review & rating system** (Guest feedback)

### ✅ Dynamic Pricing Features
- **Base price tracking** (Historical prices)
- **Seasonal rules** (Fixed date ranges)
- **Occupancy-based rules** (Percentage thresholds)
- **Event-based rules** (Custom events)
- **Percentage adjustments** (e.g., +25%)
- **Fixed amount adjustments** (e.g., +₹500)
- **Rule chaining** (Multiple rules apply together)
- **Complete audit trail** (All price changes logged)

### ✅ Data Integrity
- **Referential integrity** (Foreign key constraints)
- **Cascading deletes** (Automatic cleanup)
- **Transaction support** (InnoDB)
- **Unique constraints** (No duplicates)
- **CHECK constraints** (Data validation)

---

## 📚 Implementation Notes

### Database Creation
```bash
mysql -u root < database/schema.sql          # Core tables
mysql -u root < database/pricing_migration.sql  # Pricing tables
mysql -u root < database/pricing_procedures.sql # Stored procedures
mysql -u root < database/pricing_triggers.sql   # Auto-pricing triggers
```

### Stored Procedures
1. **CalculateDynamicPrice()** - Calculate adjusted price for a room
2. **UpdateOccupancyHistory()** - Daily occupancy calculation
3. **ApplyDynamicPricingToAllRooms()** - Batch pricing update

### Database Triggers
1. **after_booking_insert** - Update occupancy when booking created
2. **after_booking_update** - Update occupancy when booking status changes
3. **after_booking_delete** - Update occupancy when booking cancelled
4. **after_pricing_rule_insert** - Recalculate prices when new rule added
5. **after_pricing_rule_update** - Recalculate prices when rule modified

---

## 🎯 Design Principles

1. **Normalization**: Database follows 3NF (Third Normal Form)
2. **Referential Integrity**: All FK relationships enforced
3. **Data Consistency**: Cascading operations maintain consistency
4. **Audit Trail**: All changes are logged (pricing_history, occupancy_history)
5. **Performance**: Strategic indexes on frequently queried columns
6. **Scalability**: InnoDB allows for future partitioning
7. **Security**: Password hashing, encrypted payment methods stored

---

## 📊 Database Statistics

| Metric | Value |
|--------|-------|
| **Total Tables** | 11 |
| **Total Columns** | ~85 |
| **Primary Keys** | 11 |
| **Foreign Keys** | 9 |
| **Unique Constraints** | 7 |
| **Indexes** | 25+ |
| **Views** | 0 |
| **Stored Procedures** | 3 |
| **Triggers** | 5 |

---

## 🔄 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | Core Release | Base tables (Users, Rooms, Bookings, Payments, Services, Reviews) |
| 2.0 | Dynamic Pricing | Added pricing tables, rules, occupancy tracking, audit trail |

---

## 📞 Support

For questions about the database design:
- Check `STRUCTURE.md` for overall system architecture
- See `DYNAMIC_PRICING_README.md` for pricing logic details
- Review stored procedures in `database/pricing_procedures.sql`
- Check triggers in `database/pricing_triggers.sql`

---

**Database Design Complete ✅**  
**Ready for Presentation 🎯**
