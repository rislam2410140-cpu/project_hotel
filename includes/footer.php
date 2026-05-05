    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>About <?php echo SITE_NAME; ?></h4>
                <p><?php echo SITE_DESC; ?></p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <a href="<?php echo app_url('index.php'); ?>">Home</a>
                <a href="<?php echo app_url('public/rooms.php'); ?>">Browse Rooms</a>
                <a href="<?php echo app_url('guest/login.php'); ?>">Guest Login</a>
                <a href="<?php echo app_url('admin/login.php'); ?>">Admin Login</a>
            </div>
            <div class="footer-section">
                <h4>Contact Us</h4>
                <p>📞 +88-01700-123456</p>
                <p>📧 info@luxehotel.com</p>
                <p>📍 123 Luxury Street, City Center</p>
            </div>
            <div class="footer-section">
                <h4>Follow Us</h4>
                <a href="#">Facebook</a>
                <a href="#">Twitter</a>
                <a href="#">Instagram</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="<?php echo app_url('assets/app.js'); ?>"></script>
