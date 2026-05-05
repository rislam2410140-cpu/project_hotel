<?php
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h2 class="section-title">About Luxe Hotel</h2>
            <p class="section-subtitle">Your destination for luxury and comfort</p>

            <div class="grid grid-2">
                <div>
                    <div class="card">
                        <h3>Our Story</h3>
                        <p>
                            Luxe Hotel has been a beacon of hospitality for over two decades. Founded in 2001, we've grown 
                            from a small boutique hotel to one of the region's most trusted luxury accommodations.
                        </p>
                        <p style="margin-top: 1rem;">
                            Our mission is simple: to provide unforgettable experiences through exceptional service, 
                            world-class amenities, and genuine hospitality that makes every guest feel at home.
                        </p>
                    </div>
                </div>

                <div>
                    <div class="card">
                        <h3>Why Choose Us?</h3>
                        <ul style="list-style: none;">
                            <li style="padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                                ✓ <strong>Prime Location</strong> - City center with easy access to attractions
                            </li>
                            <li style="padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                                ✓ <strong>Expert Staff</strong> - Trained professionals with 15+ years average experience
                            </li>
                            <li style="padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                                ✓ <strong>Modern Facilities</strong> - Recently renovated with latest amenities
                            </li>
                            <li style="padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                                ✓ <strong>Competitive Rates</strong> - Best value for luxury accommodation
                            </li>
                            <li style="padding: 0.75rem 0;">
                                ✓ <strong>Awards & Recognition</strong> - Multiple industry accolades
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top: 2rem;">
                <h3>Our Commitment to Excellence</h3>
                <p>
                    Every guest is important to us. From the moment you arrive to the moment you leave, 
                    our team is dedicated to ensuring your stay exceeds expectations. We believe that 
                    hospitality is not just about providing rooms – it's about creating memories.
                </p>
            </div>

            <div class="grid grid-3" style="margin-top: 2rem;">
                <div class="stat-card">
                    <div class="stat-number">50+</div>
                    <div class="stat-label">Rooms</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">20+</div>
                    <div class="stat-label">Years Experience</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">Happy Guests</div>
                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
