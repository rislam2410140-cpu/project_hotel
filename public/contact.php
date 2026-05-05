<?php
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h2 class="section-title">Contact Us</h2>
            <p class="section-subtitle">Get in touch with our team</p>

            <div class="grid grid-2">
                <div>
                    <div class="card">
                        <h3>📍 Address</h3>
                        <p style="margin: 1rem 0; color: var(--text-light);">
                            123 Luxury Street<br>
                            City Center<br>
                            Country, Postal Code
                        </p>

                        <h3 style="margin-top: 2rem;">📞 Phone</h3>
                        <p style="margin: 1rem 0; color: var(--text-light);">
                            <strong>Main Line:</strong> +88-01700-123456<br>
                            <strong>Reservations:</strong> +88-01700-123457<br>
                            <strong>Support:</strong> +88-01700-123458<br>
                            <span style="font-size: 0.9rem;">Available 24/7</span>
                        </p>

                        <h3 style="margin-top: 2rem;">📧 Email</h3>
                        <p style="margin: 1rem 0; color: var(--text-light);">
                            <strong>Reservations:</strong> <a href="mailto:info@luxehotel.com">info@luxehotel.com</a><br>
                            <strong>Support:</strong> <a href="mailto:support@luxehotel.com">support@luxehotel.com</a>
                        </p>
                    </div>
                </div>

                <div>
                    <div class="card">
                        <h3>Send us a Message</h3>
                        <form onsubmit="alert('Thank you for your message! We will get back to you soon.'); return false;">
                            <div class="form-group">
                                <label>Your Name</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label>Subject</label>
                                <input type="text" name="subject" required>
                            </div>
                            <div class="form-group">
                                <label>Message</label>
                                <textarea name="message" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top: 2rem;">
                <h3>Business Hours</h3>
                <table>
                    <tr>
                        <td><strong>Monday - Friday</strong></td>
                        <td>8:00 AM - 6:00 PM</td>
                    </tr>
                    <tr>
                        <td><strong>Saturday - Sunday</strong></td>
                        <td>10:00 AM - 4:00 PM</td>
                    </tr>
                    <tr>
                        <td><strong>Front Desk</strong></td>
                        <td>24/7 Available</td>
                    </tr>
                </table>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
