<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db_connect.php';

$error = '';
$success = '';
$step = 'email'; // email or token

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'guest') {
        redirect_to('guest/dashboard.php');
    } elseif ($_SESSION['role'] === 'admin') {
        redirect_to('admin/dashboard.php');
    }
}

// Check if password reset token is valid and in URL
$reset_token = $_GET['token'] ?? null;
if ($reset_token) {
    $step = 'reset';
    
    // Validate token from GET parameter
    try {
        $token_hash = hash('sha256', $reset_token);
        $stmt = $pdo->prepare("
            SELECT user_id, expires_at, used_at FROM password_reset_tokens 
            WHERE token_hash = ? AND expires_at > NOW() AND used_at IS NULL 
            LIMIT 1
        ");
        $stmt->execute([$token_hash]);
        $token_record = $stmt->fetch();
        
        if (!$token_record) {
            $step = 'email';
            $error = 'Invalid or expired password reset link. Please request a new one.';
        }
    } catch (Exception $e) {
        $step = 'email';
        $error = 'An error occurred. Please try again.';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token mismatch. Please try again.';
    } elseif ($step === 'email') {
        // Handle email submission for password reset
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Please enter your email address.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT user_id, name, email FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Generate secure reset token
                    $reset_token = bin2hex(random_bytes(32));
                    $token_hash = hash('sha256', $reset_token);
                    $expires_at = date('Y-m-d H:i:s', time() + 3600); // Valid for 1 hour
                    
                    // Store token in database
                    $stmt = $pdo->prepare("
                        INSERT INTO password_reset_tokens (user_id, token, token_hash, expires_at) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$user['user_id'], $reset_token, $token_hash, $expires_at]);
                    
                    // Generate reset link
                    $reset_link = SITE_URL . app_url('forgot_password.php?token=' . urlencode($reset_token));
                    
                    // For demonstration: display the link instead of sending email
                    // In production, use PHPMailer or similar to send email
                    $_SESSION['demo_reset_link'] = $reset_link;
                    $_SESSION['demo_user_email'] = $user['email'];
                    
                    $success = 'Instructions have been sent to your email. In demo mode, the reset link is displayed below.';
                } else {
                    // For security, don't reveal if email exists
                    $success = 'If an account exists with that email, password reset instructions have been sent.';
                }
            } catch (Exception $e) {
                $error = 'An error occurred. Please try again later.';
                error_log('Password reset error: ' . $e->getMessage());
            }
        }
    } elseif ($step === 'reset') {
        // Handle password reset
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($new_password) || empty($confirm_password)) {
            $error = 'Please fill in all fields.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            try {
                // Hash new password
                $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                
                // Update password and mark token as used
                $stmt = $pdo->prepare("
                    UPDATE users SET password_hash = ? WHERE user_id = ?
                ");
                $stmt->execute([$password_hash, $token_record['user_id']]);
                
                $stmt = $pdo->prepare("
                    UPDATE password_reset_tokens SET used_at = NOW() 
                    WHERE token_hash = ?
                ");
                $stmt->execute([$token_hash]);
                
                set_flash('success', 'Your password has been reset successfully. You can now login with your new password.');
                redirect_to('login.php');
            } catch (Exception $e) {
                $error = 'An error occurred while resetting your password. Please try again.';
                error_log('Password reset update error: ' . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $step === 'reset' ? 'Reset Password' : 'Forgot Password'; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
    <style>
        .password-strength {
            margin-top: 0.5rem;
            padding: 0.5rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            display: none;
        }
        .password-strength.weak {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
            display: block;
        }
        .password-strength.fair {
            background: rgba(245, 158, 11, 0.1);
            color: #92400e;
            display: block;
        }
        .password-strength.good {
            background: rgba(34, 197, 94, 0.1);
            color: #166534;
            display: block;
        }
        .demo-reset-link {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 0.625rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        .demo-reset-link a {
            word-break: break-all;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <div style="max-width: 450px; margin: 0 auto;">
                <div class="card">
                    <?php if ($step === 'email'): ?>
                        <h2 style="text-align: center; margin-bottom: 0.5rem;">Forgot Password</h2>
                        <p style="text-align: center; color: var(--text-light); margin-bottom: 2rem;">
                            Enter your email to receive a password reset link
                        </p>

                        <?php if ($error): ?>
                            <div class="flash-message flash-error">
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="flash-message flash-success">
                                <span><?php echo htmlspecialchars($success); ?></span>
                            </div>
                            
                            <?php if (isset($_SESSION['demo_reset_link'])): ?>
                                <div class="demo-reset-link">
                                    <strong style="color: var(--primary);">📧 Demo Mode - Password Reset Link:</strong>
                                    <p style="margin-top: 0.75rem; font-size: 0.9rem;">
                                        <a href="<?php echo htmlspecialchars($_SESSION['demo_reset_link']); ?>" target="_blank">
                                            Click here to reset your password
                                        </a>
                                    </p>
                                    <p style="font-size: 0.875rem; color: var(--text-light); margin-top: 0.75rem;">
                                        This link expires in 1 hour. In production, this link would be sent via email.
                                    </p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" required placeholder="your@email.com">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
                        </form>

                        <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid var(--border);">

                        <p style="text-align: center; color: var(--text-light);">
                            Remember your password? <a href="<?php echo app_url('login.php'); ?>">Back to login</a>
                        </p>

                    <?php elseif ($step === 'reset'): ?>
                        <h2 style="text-align: center; margin-bottom: 0.5rem;">Reset Your Password</h2>
                        <p style="text-align: center; color: var(--text-light); margin-bottom: 2rem;">
                            Enter your new password below
                        </p>

                        <?php if ($error): ?>
                            <div class="flash-message flash-error">
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" id="new_password" required 
                                       placeholder="At least 6 characters"
                                       onkeyup="checkPasswordStrength(this.value)">
                                <div id="password-strength" class="password-strength"></div>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <input type="password" name="confirm_password" required 
                                       placeholder="Re-enter your password">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                        </form>

                        <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid var(--border);">

                        <p style="text-align: center; color: var(--text-light); font-size: 0.9rem;">
                            This link expires in 1 hour. If expired, <a href="<?php echo app_url('forgot_password.php'); ?>">request a new one</a>
                        </p>

                    <?php endif; ?>

                    <p style="text-align: center; margin-top: 1rem;">
                        <a href="<?php echo app_url('index.php'); ?>" class="btn btn-secondary btn-sm btn-block">Back to Home</a>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>

    <script>
        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('password-strength');
            let strength = 0;

            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;

            strengthDiv.classList.remove('weak', 'fair', 'good');

            if (password.length === 0) {
                strengthDiv.style.display = 'none';
            } else if (strength <= 2) {
                strengthDiv.classList.add('weak');
                strengthDiv.textContent = '⚠️ Weak password';
            } else if (strength <= 3) {
                strengthDiv.classList.add('fair');
                strengthDiv.textContent = '⚡ Fair password';
            } else {
                strengthDiv.classList.add('good');
                strengthDiv.textContent = '✓ Strong password';
            }
        }
    </script>
</body>
</html>
