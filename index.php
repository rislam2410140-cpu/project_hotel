<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Premium Hotel Experience</title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
</head>
<body>
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <h1>Welcome to <?php echo SITE_NAME; ?></h1>
        <p>Experience luxury, comfort, and exceptional service</p>
        <div class="hero-buttons">
            <a href="<?php echo app_url('public/rooms.php'); ?>" class="btn btn-primary">Browse Rooms</a>
            <a href="<?php echo app_url('login.php'); ?>" class="btn btn-primary" style="background: white; color: var(--primary);">Login</a>
        </div>
    </section>

    <!-- Featured Rooms -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">✨ Our Room Types</h2>
            <p class="section-subtitle">Choose from our carefully curated selection of rooms and suites</p>
            
            <div class="grid grid-4">
                <div class="card">
                    <h3>Single Room</h3>
                    <p style="margin: 1rem 0; font-size: 0.9rem; color: var(--text-light);">Perfect for solo travelers</p>
                    <p style="font-size: 1.5rem; color: var(--primary); font-weight: bold;">$50 <span style="font-size: 0.8rem; color: var(--text-light);">/night</span></p>
                    <ul style="margin: 1rem 0; color: var(--text-light); font-size: 0.9rem;">
                        <li>✓ 1 Double Bed</li>
                        <li>✓ Free WiFi</li>
                        <li>✓ AC & TV</li>
                    </ul>
                </div>
                <div class="card">
                    <h3>Double Room</h3>
                    <p style="margin: 1rem 0; font-size: 0.9rem; color: var(--text-light);">Great for couples</p>
                    <p style="font-size: 1.5rem; color: var(--primary); font-weight: bold;">$75 <span style="font-size: 0.8rem; color: var(--text-light);">/night</span></p>
                    <ul style="margin: 1rem 0; color: var(--text-light); font-size: 0.9rem;">
                        <li>✓ 2 Double Beds</li>
                        <li>✓ Free WiFi</li>
                        <li>✓ Mini Bar</li>
                    </ul>
                </div>
                <div class="card">
                    <h3>Deluxe Room</h3>
                    <p style="margin: 1rem 0; font-size: 0.9rem; color: var(--text-light);">Spacious & comfortable</p>
                    <p style="font-size: 1.5rem; color: var(--primary); font-weight: bold;">$120 <span style="font-size: 0.8rem; color: var(--text-light);">/night</span></p>
                    <ul style="margin: 1rem 0; color: var(--text-light); font-size: 0.9rem;">
                        <li>✓ King Bed</li>
                        <li>✓ City View</li>
                        <li>✓ Hot Tub</li>
                    </ul>
                </div>
                <div class="card">
                    <h3>Suite</h3>
                    <p style="margin: 1rem 0; font-size: 0.9rem; color: var(--text-light);">Ultimate luxury</p>
                    <p style="font-size: 1.5rem; color: var(--primary); font-weight: bold;">$200 <span style="font-size: 0.8rem; color: var(--text-light);">/night</span></p>
                    <ul style="margin: 1rem 0; color: var(--text-light); font-size: 0.9rem;">
                        <li>✓ Living Area</li>
                        <li>✓ Premium View</li>
                        <li>✓ Concierge</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Facilities -->
    <section class="section" style="background: var(--light);">
        <div class="container">
            <h2 class="section-title">🏛️ Our Facilities</h2>
            <p class="section-subtitle">World-class amenities at your fingertips</p>
            
            <div class="grid grid-3">
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📡</div>
                    <h4>Free WiFi</h4>
                    <p style="color: var(--text-light);">High-speed internet throughout the property</p>
                </div>
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🏊</div>
                    <h4>Swimming Pool</h4>
                    <p style="color: var(--text-light);">Olympic-size pool with heated waters</p>
                </div>
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🍽️</div>
                    <h4>Restaurant</h4>
                    <p style="color: var(--text-light);">Award-winning dining experience</p>
                </div>
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💪</div>
                    <h4>Fitness Center</h4>
                    <p style="color: var(--text-light);">Modern gym with personal trainers</p>
                </div>
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🚗</div>
                    <h4>Parking</h4>
                    <p style="color: var(--text-light);">Secure underground parking available</p>
                </div>
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🔒</div>
                    <h4>24/7 Security</h4>
                    <p style="color: var(--text-light);">Advanced security systems everywhere</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How Booking Works -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">📋 How Booking Works</h2>
            <p class="section-subtitle">Simple, secure, and convenient</p>
            
            <div class="grid grid-3">
                <div class="card" style="text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem;">1️⃣</div>
                    <h4>Create Account</h4>
                    <p style="color: var(--text-light);">Sign up as a guest in seconds</p>
                </div>
                <div class="card" style="text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem;">2️⃣</div>
                    <h4>Browse Rooms</h4>
                    <p style="color: var(--text-light);">Explore our available rooms and rates</p>
                </div>
                <div class="card" style="text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem;">3️⃣</div>
                    <h4>Select Dates</h4>
                    <p style="color: var(--text-light);">Pick your check-in and check-out dates</p>
                </div>
                <div class="card" style="text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem;">4️⃣</div>
                    <h4>Confirm & Pay</h4>
                    <p style="color: var(--text-light);">Secure payment and instant confirmation</p>
                </div>
                <div class="card" style="text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem;">5️⃣</div>
                    <h4>Check In</h4>
                    <p style="color: var(--text-light);">Arrive and settle into your room</p>
                </div>
                <div class="card" style="text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem;">6️⃣</div>
                    <h4>Leave a Review</h4>
                    <p style="color: var(--text-light);">Share your experience with us</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="section" style="background: var(--light);">
        <div class="container">
            <h2 class="section-title">📞 Get In Touch</h2>
            <p class="section-subtitle">Have questions? We're here to help!</p>
            
            <div class="grid grid-3">
                <div class="card" style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">📍</div>
                    <h4>Address</h4>
                    <p style="color: var(--text-light);">123 Luxury Street<br>City Center, Country</p>
                </div>
                <div class="card" style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">📞</div>
                    <h4>Phone</h4>
                    <p style="color: var(--text-light);">+88-01700-123456<br>Available 24/7</p>
                </div>
                <div class="card" style="text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">📧</div>
                    <h4>Email</h4>
                    <p style="color: var(--text-light);">info@luxehotel.com<br>support@luxehotel.com</p>
                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
