<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db_connect.php';

$error = '';
$success = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'guest') {
        redirect_to('guest/dashboard.php');
    } elseif ($_SESSION['role'] === 'admin') {
        redirect_to('admin/dashboard.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            // Query without role constraint - let the database tell us the user's role
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Set session variables including detected role
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Route to appropriate dashboard based on detected role
                if ($user['role'] === 'guest') {
                    redirect_to('guest/dashboard.php');
                } elseif ($user['role'] === 'admin') {
                    redirect_to('admin/dashboard.php');
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
</head>
<body>
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <div style="max-width: 400px; margin: 0 auto;">
                <div class="card">
                    <h2 style="text-align: center; margin-bottom: 2rem;">Login to Your Account</h2>

                    <?php if ($error): ?>
                        <div class="flash-message flash-error">
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Login</button>
                    </form>

                    <p style="text-align: center; margin-top: 1.5rem; color: var(--text-light);">
                        Don't have an account? <a href="<?php echo app_url('guest/signup.php'); ?>">Sign up here</a>
                    </p>

                    <p style="text-align: center; margin-top: 1rem; color: var(--text-light); font-size: 0.9rem;">
                        Demo: guest@hotel.com / Guest123 or admin@hotel.com / Admin123
                    </p>

                    <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid var(--border);">

                    <p style="text-align: center; color: var(--text-light);">
                        <a href="<?php echo app_url('index.php'); ?>" class="btn btn-secondary btn-sm btn-block">Back to Home</a>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
