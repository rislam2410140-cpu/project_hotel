-- Hotel Management System - Seed Data

-- Insert Admin User
INSERT INTO users (name, email, phone, password_hash, role) VALUES
('Admin User', 'admin@hotel.com', '+88-01700-000001', '$2y$10$ZKRu8.Z6NL5WX.q5z8.5R.cJcJZJZJZJZJZJZJZJZJZJZJZJZJZJZJ', 'admin');

-- Insert Guest User
INSERT INTO users (name, email, phone, password_hash, role) VALUES
('John Guest', 'guest@hotel.com', '+88-01700-000002', '$2y$10$ZKRu8.Z6NL5WX.q5z8.5R.cJcJZJZJZJZJZJZJZJZJZJZJZJZJZJZJ', 'guest');

-- Insert additional guest users
INSERT INTO users (name, email, phone, password_hash, role) VALUES
('Sarah Johnson', 'sarah@gmail.com', '+88-01700-111111', '$2y$10$ZKRu8.Z6NL5WX.q5z8.5R.cJcJZJZJZJZJZJZJZJZJZJZJZJZJZJZJ', 'guest'),
('Ahmed Khan', 'ahmed@gmail.com', '+88-01700-222222', '$2y$10$ZKRu8.Z6NL5WX.q5z8.5R.cJcJZJZJZJZJZJZJZJZJZJZJZJZJZJZJ', 'guest');

-- Insert Rooms
INSERT INTO rooms (room_number, room_type, price, status, capacity) VALUES
('101', 'Single', 50.00, 'available', 1),
('102', 'Single', 50.00, 'available', 1),
('103', 'Double', 75.00, 'available', 2),
('104', 'Double', 75.00, 'available', 2),
('105', 'Deluxe', 120.00, 'available', 3),
('106', 'Deluxe', 120.00, 'available', 3),
('107', 'Suite', 200.00, 'available', 4),
('108', 'Suite', 200.00, 'available', 4);

-- Insert sample booking (completed)
INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price, status) 
VALUES (2, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), 150.00, 'completed');

-- Insert payment for completed booking
INSERT INTO payments (booking_id, amount, method, payment_status, paid_at)
VALUES (1, 150.00, 'card', 'paid', NOW());

-- Insert review for completed booking
INSERT INTO reviews (booking_id, rating, comment)
VALUES (1, 5, 'Excellent service and comfortable rooms!');

-- Insert pending booking
INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price, status)
VALUES (2, 3, DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 150.00, 'pending');

-- Insert payment for pending booking
INSERT INTO payments (booking_id, amount, method, payment_status)
VALUES (2, 150.00, 'cash', 'pending');

-- Insert sample service order
INSERT INTO service_orders (room_id, booking_id, items, total_price, status)
VALUES (3, 2, '["Breakfast", "Room Cleaning", "Extra Towels"]', 35.00, 'delivered');
