# Online Payment System - Hotel Management Demo

## Overview

This is a **DEMO online payment system** integrated into the Modern Hotel Management System. It provides a complete payment flow for hotel bookings without processing real payments. Perfect for demonstration, testing, and educational purposes.

## Features

### ✨ Guest Payment Features
- **Multiple Payment Methods**: 
  - 💳 Credit/Debit Card
  - 📱 bKash (Mobile Money)
  - 📱 Nagad (Mobile Money)
  - 💵 Cash Payment
  
- **Secure Payment Interface**:
  - CSRF token protection
  - Responsive design for mobile and desktop
  - Real-time card formatting
  - Expiry date and CVV validation

- **Order Confirmation**:
  - Confirmation number generation
  - Payment receipt with all details
  - Email notification (in actual implementation)
  - Booking status updates

### 🛡️ Admin Payment Management
- **Payment Dashboard**: View all payment transactions
- **Payment Statistics**:
  - Total payments received
  - Total revenue
  - Count of paid, pending, and failed payments
  
- **Payment Filtering**:
  - Filter by payment status (Paid, Pending, Failed)
  - Search by guest name, email, or room number
  - Date-based transaction history

- **Payment Reports**:
  - Detailed payment records with guest information
  - Payment method tracking
  - Transaction timestamps

## How to Use

### For Guests

#### Step 1: Make a Booking
1. Browse available rooms
2. Select check-in and check-out dates
3. Create a booking (status will be "pending" with "pending" payment)

#### Step 2: Proceed to Payment
1. Go to "My Bookings" page
2. Find the booking with pending payment
3. Click the **"Pay Now"** button

#### Step 3: Complete Payment
1. Select a payment method:
   - **Card**: Enter test card details
   - **bKash/Nagad**: Simulated payment
   - **Cash**: Marks as pending (admin approval)

2. For card payments, use:
   - Test Card: `4532 1111 1111 1111`
   - Any future expiry date
   - Any 3-digit CVV

3. Click **"Pay [Amount]"** to complete

#### Step 4: Confirmation
- Receive confirmation number
- View payment receipt with:
  - Booking details
  - Room information
  - Check-in/check-out dates
  - Payment method used
  - Amount paid

### For Admin

#### Step 1: Access Payment Dashboard
1. Log in as admin
2. Navigate to **Admin → Payment Management** (or `/admin/payments.php`)

#### Step 2: View Payment Statistics
- Overview of total payments and revenue
- Quick count of payment statuses

#### Step 3: Filter & Search Payments
- Filter by payment status (Paid/Pending/Failed)
- Search by guest information or room number
- View detailed payment history

#### Step 4: Manage Payments
- Review payment methods used
- Track payment dates and times
- Monitor booking-payment relationships

## Database Schema

### Payments Table
```sql
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    amount DECIMAL(10, 2) NOT NULL,
    method ENUM('cash', 'bkash', 'nagad', 'card') NOT NULL DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking_id (booking_id),
    INDEX idx_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Field Explanations**:
- `payment_id`: Unique identifier for each payment
- `booking_id`: Links to the booking being paid
- `amount`: Payment amount (total booking price)
- `method`: Payment method selected (card, bkash, nagad, cash)
- `payment_status`: Current status (pending, paid, failed)
- `paid_at`: Timestamp when payment was processed
- `created_at`: Timestamp when payment record was created

## Payment Flow Diagram

```
Guest Booking
    ↓
Pending Payment
    ↓
Guest clicks "Pay Now"
    ↓
Payment Selection Page
    ├─ Card Details Entry
    ├─ bKash/Nagad Selection
    └─ Cash Selection
    ↓
Process Payment (Demo)
    ↓
Update Payment Status to "PAID"
    ↓
Update Booking Status to "CONFIRMED"
    ↓
Generate Confirmation Number
    ↓
Show Success Page with Receipt
    ↓
Guest Sees Updated Booking Status
```

## File Structure

```
modern_hotel_management/
├── guest/
│   ├── payment.php              # Payment form and processing
│   ├── payment_success.php      # Payment confirmation page
│   └── my_bookings.php          # Updated with "Pay Now" button
├── admin/
│   └── payments.php             # Admin payment dashboard
└── database/
    └── schema.sql               # Database schema (already includes payments table)
```

## Security Features

✅ **CSRF Protection**
- All payment forms include CSRF tokens
- Token verification on form submission

✅ **Input Validation**
- Card number format validation
- Payment method validation
- Booking ownership verification

✅ **Data Protection**
- Session-based user authentication
- Payment records linked to authenticated users
- Admin-only access to payment management

✅ **SQL Security**
- Prepared statements prevent SQL injection
- Parameterized queries throughout

## Testing the System

### Test Scenarios

#### Scenario 1: Card Payment
1. Make a booking
2. Go to payment page
3. Select "Card"
4. Enter card name and number
5. Enter expiry date and CVV
6. Click Pay
7. See success confirmation

#### Scenario 2: bKash/Nagad Payment
1. Make a booking
2. Go to payment page
3. Select "bKash" or "Nagad"
4. No additional details needed (demo)
5. Click Pay
6. See success confirmation

#### Scenario 3: Cash Payment
1. Make a booking
2. Go to payment page
3. Select "Cash"
4. Click Pay
5. Payment marked as pending (admin approval workflow)

#### Scenario 4: Admin View
1. Log in as admin
2. Visit `/admin/payments.php`
3. See all payment transactions
4. Filter by status or search
5. View detailed payment records

### Test Credentials

**Admin User**:
- Email: `admin@hotel.com`
- Password: `Admin123`

**Guest User**:
- Email: `guest@hotel.com`
- Password: `Guest123`

## Future Enhancement Possibilities

This demo system can be extended to integrate with real payment gateways:

### Real Payment Integration
- **Stripe Integration**: Credit/debit card payments
- **PayPal Integration**: PayPal and alternative payment methods
- **bKash/Nagad**: Real mobile money integration
- **Square/Razorpay**: POS and online payments

### Additional Features
- Partial refunds
- Payment installments
- Subscription payments
- Invoice generation and download
- Multi-currency support
- Payment retry mechanism
- Webhook notifications

## Troubleshooting

### "Invalid booking ID"
- Ensure you're accessing payment from a valid booking
- Check that booking belongs to logged-in user

### "Payment already completed"
- Booking was already paid
- View booking details in "My Bookings"

### Admin can't see payments
- Ensure logged in as admin user
- Check user role in database
- Verify access permissions

## Developer Notes

### Adding a New Payment Method

1. **Update Database** (if needed):
```sql
ALTER TABLE payments MODIFY method ENUM('cash', 'bkash', 'nagad', 'card', 'new_method');
```

2. **Update payment.php**:
Add method option in payment methods section:
```html
<div class="method-option">
    <input type="radio" id="method_new" name="payment_method" value="new_method" required>
    <label for="method_new" class="method-label">New Method</label>
</div>
```

3. **Handle in payment_success.php**:
```php
$method_emoji = [
    // ... existing methods
    'new_method' => '🎯'
];
```

### Modifying Payment Form

Edit `guest/payment.php` to customize:
- Payment methods available
- Form fields and validation
- Styling and layout
- Success/error messages

### Viewing Payment Logs

Check payment records in database:
```sql
SELECT * FROM payments ORDER BY created_at DESC;
```

## Support & Documentation

- Main README: See `README.md`
- Database Design: See `DATABASE_DESIGN.md`
- Project Structure: See `STRUCTURE.md`
- Hotel Management System: See main project documentation

---

**Note**: This is a DEMO system designed for educational and demonstration purposes. 
For production use, implement actual payment gateway integration with appropriate PCI compliance.

Last Updated: 2026-06-06
Version: 1.0
