<?php
/**
 * Password Reset Helper Class
 * Manages password reset token generation, validation, and cleanup
 */

class PasswordReset {
    private static $token_expiry = 3600; // 1 hour in seconds
    
    /**
     * Generate a password reset token for a user
     * @param PDO $pdo Database connection
     * @param int $user_id User ID
     * @return array Contains 'token' and 'reset_link'
     */
    public static function generateToken($pdo, $user_id) {
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', time() + self::$token_expiry);
        
        // Store token
        $stmt = $pdo->prepare("
            INSERT INTO password_reset_tokens (user_id, token, token_hash, expires_at) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $token, $token_hash, $expires_at]);
        
        return [
            'token' => $token,
            'expires_in_seconds' => self::$token_expiry
        ];
    }
    
    /**
     * Validate a password reset token
     * @param PDO $pdo Database connection
     * @param string $token Reset token from URL
     * @return array|false Token record if valid, false otherwise
     */
    public static function validateToken($pdo, $token) {
        $token_hash = hash('sha256', $token);
        $stmt = $pdo->prepare("
            SELECT user_id, token_id FROM password_reset_tokens 
            WHERE token_hash = ? AND expires_at > NOW() AND used_at IS NULL 
            LIMIT 1
        ");
        $stmt->execute([$token_hash]);
        return $stmt->fetch();
    }
    
    /**
     * Reset user password using a valid token
     * @param PDO $pdo Database connection
     * @param string $token Reset token
     * @param string $new_password New password (plain text)
     * @return bool True if successful
     */
    public static function resetPassword($pdo, $token, $new_password) {
        $token_record = self::validateToken($pdo, $token);
        
        if (!$token_record) {
            return false;
        }
        
        try {
            // Hash new password
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            // Update password
            $stmt = $pdo->prepare("
                UPDATE users SET password_hash = ? WHERE user_id = ?
            ");
            $stmt->execute([$password_hash, $token_record['user_id']]);
            
            // Mark token as used
            $token_hash = hash('sha256', $token);
            $stmt = $pdo->prepare("
                UPDATE password_reset_tokens SET used_at = NOW() 
                WHERE token_hash = ?
            ");
            $stmt->execute([$token_hash]);
            
            return true;
        } catch (Exception $e) {
            error_log('Password reset error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean up expired tokens
     * @param PDO $pdo Database connection
     * @return int Number of tokens deleted
     */
    public static function cleanupExpiredTokens($pdo) {
        $stmt = $pdo->prepare("
            DELETE FROM password_reset_tokens 
            WHERE expires_at < NOW() AND used_at IS NULL
        ");
        $stmt->execute();
        return $stmt->rowCount();
    }
    
    /**
     * Send password reset email (for future implementation)
     * @param string $to_email Recipient email
     * @param string $user_name User name
     * @param string $reset_link Reset link with token
     * @return bool True if sent successfully
     */
    public static function sendResetEmail($to_email, $user_name, $reset_link) {
        // This is a placeholder for future email implementation
        // In production, use PHPMailer or similar library
        
        $subject = "Password Reset Request - " . SITE_NAME;
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: " . SITE_NAME . " <noreply@" . parse_url(SITE_URL, PHP_URL_HOST) . ">" . "\r\n";
        
        $message = "
        <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #6366f1;'>Password Reset Request</h2>
                    
                    <p>Hi <strong>" . htmlspecialchars($user_name) . "</strong>,</p>
                    
                    <p>We received a request to reset your password. Click the link below to set a new password:</p>
                    
                    <p style='margin: 30px 0;'>
                        <a href='" . htmlspecialchars($reset_link) . "' 
                           style='display: inline-block; padding: 12px 24px; background: #6366f1; 
                                  color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>
                            Reset Password
                        </a>
                    </p>
                    
                    <p><small>Or copy this link: <br><code>" . htmlspecialchars($reset_link) . "</code></small></p>
                    
                    <p style='color: #666; font-size: 14px;'>
                        <strong>⏰ This link expires in 1 hour.</strong><br>
                        If you didn't request this, you can ignore this email.
                    </p>
                    
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'>
                    
                    <p style='color: #999; font-size: 12px; text-align: center;'>
                        © " . date('Y') . " " . SITE_NAME . ". All rights reserved.
                    </p>
                </div>
            </body>
        </html>
        ";
        
        // Uncomment to actually send emails:
        // return mail($to_email, $subject, $message, $headers);
        
        return true; // Placeholder
    }
}
?>
