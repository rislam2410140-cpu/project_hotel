-- Combined database dump for Hotel Management System
-- Includes schema and seed data in a single file

DROP DATABASE IF EXISTS hotel_management;
CREATE DATABASE hotel_management;
USE hotel_management;

-- Schema
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('guest', 'admin') NOT NULL DEFAULT 'guest',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) UNIQUE NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    status ENUM('available', 'occupied', 'cleaning') NOT NULL DEFAULT 'available',
    capacity INT NOT NULL DEFAULT 2,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_room_id (room_id),
    INDEX idx_dates (check_in_date, check_out_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE service_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    booking_id INT NULL,
    items JSON NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'preparing', 'delivered') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL,
    INDEX idx_room_id (room_id),
    INDEX idx_booking_id (booking_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking_id (booking_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_bookings_user_status ON bookings(user_id, status);
CREATE INDEX idx_bookings_room_status ON bookings(room_id, status);
CREATE INDEX idx_payments_status ON payments(payment_status);

-- Seed data
INSERT INTO users (name, email, phone, password_hash, role) VALUES
('Admin User', 'admin@hotel.com', '+88-01700-000001', '$2y$10$ZKRu8.Z6NL5WX.q5z8.5R.cJcJZJZJZJZJZJZJZJZJZJZJZJZJZJZJZJ', 'admin');

INSERT INTO users (name, email, phone, password_hash, role) VALUES
('John Guest', 'guest@hotel.com', '+88-01700-000002', '$2y$10$ZKRu8.Z6NL5WX.q5z8.5R.cJcJZJZJZJZJZJZJZJZJZJZJZJZJZJZJZJ', 'guest');

INSERT INTO users (name, email, phone, password_hash, role) VALUES
('Sarah Johnson', 'sarah@gmail.com', '+88-01700-111111', '$2y$10$ZKRu8.Z6NL5WX.q5z8.5R.cJcJZJZJZJZJZJZJZJZJZJZJZJZJZJZJZJ', 'guest'),
('Ahmed Khan', 'ahmed@gmail.com', '+88-01700-222222', '$2y$10$ZKRu8.Z6NL5WX.q5z8.5R.cJcJZJZJZJZJZJZJZJZJZJZJZJZJZJZJZJ', 'guest');

INSERT INTO rooms (room_number, room_type, price, status, capacity) VALUES
('101', 'Single', 50.00, 'available', 1),
('102', 'Single', 50.00, 'available', 1),
('103', 'Double', 75.00, 'available', 2),
('104', 'Double', 75.00, 'available', 2),
('105', 'Deluxe', 120.00, 'available', 3),
('106', 'Deluxe', 120.00, 'available', 3),
('107', 'Suite', 200.00, 'available', 4),
('108', 'Suite', 200.00, 'available', 4);

INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price, status) 
VALUES (2, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), 150.00, 'completed');

INSERT INTO payments (booking_id, amount, method, payment_status, paid_at)
VALUES (1, 150.00, 'card', 'paid', NOW());

INSERT INTO reviews (booking_id, rating, comment)
VALUES (1, 5, 'Excellent service and comfortable rooms!');

INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price, status)
VALUES (2, 3, DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 150.00, 'pending');

INSERT INTO payments (booking_id, amount, method, payment_status)
VALUES (2, 150.00, 'cash', 'pending');

INSERT INTO service_orders (room_id, booking_id, items, total_price, status)
VALUES (3, 2, '["Breakfast", "Room Cleaning", "Extra Towels"]', 35.00, 'delivered');
