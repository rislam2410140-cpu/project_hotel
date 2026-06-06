<?php
/**
 * Admin View - Password Reset Tokens Log
 * Allows admins to monitor password reset activities
 */

require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../includes/PasswordReset.php';

$tokens = [];
$stats = [
    'pending' => 0,
    'used' => 0,
    'expired' => 0
];

try {
    // Get all recent reset tokens
    $stmt = $pdo->prepare("
        SELECT 
            prt.token_id,
            u.user_id,
            u.name,
            u.email,
            u.role,
            prt.created_at,
            prt.expires_at,
            prt.used_at,
            CASE 
                WHEN prt.used_at IS NOT NULL THEN 'used'
                WHEN prt.expires_at < NOW() THEN 'expired'
                ELSE 'pending'
            END as status
        FROM password_reset_tokens prt
        JOIN users u ON prt.user_id = u.user_id
        ORDER BY prt.created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $tokens = $stmt->fetchAll();
    
    // Calculate stats
    foreach ($tokens as $token) {
        $stats[$token['status']]++;
    }
    
    // Clean up expired tokens (optional)
    PasswordReset::cleanupExpiredTokens($pdo);
    
} catch (Exception $e) {
    error_log('Password reset log error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Log - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo app_url('assets/style.css'); ?>">
    <script src="<?php echo app_url('assets/dark_mode.js'); ?>"></script>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h2 class="section-title">Password Reset Tokens Log</h2>
            <p class="section-subtitle">Monitor password reset activities and security</p>

            <!-- Stats -->
            <div class="grid grid-3" style="margin-bottom: 3rem;">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">⏳ Pending Tokens</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['used']; ?></div>
                    <div class="stat-label">✅ Used Tokens</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['expired']; ?></div>
                    <div class="stat-label">❌ Expired Tokens</div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent Password Reset Requests</h3>
                    <span style="font-size: 0.85rem; color: var(--text-light);">Last 50 requests</span>
                </div>

                <?php if (empty($tokens)): ?>
                    <p style="text-align: center; color: var(--text-light); padding: 2rem;">
                        No password reset requests yet.
                    </p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Requested</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tokens as $token): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($token['name']); ?></strong>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($token['email']); ?>">
                                            <?php echo htmlspecialchars($token['email']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $token['role']; ?>">
                                            <?php echo ucfirst($token['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y H:i', strtotime($token['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($token['status'] === 'used'): ?>
                                            <span class="badge badge-completed">✓ Used</span>
                                            <br><small style="color: var(--text-light);">
                                                <?php echo date('M d, Y H:i', strtotime($token['used_at'])); ?>
                                            </small>
                                        <?php elseif ($token['status'] === 'expired'): ?>
                                            <span class="badge badge-cancelled">✕ Expired</span>
                                            <br><small style="color: var(--text-light);">
                                                <?php echo date('M d, Y H:i', strtotime($token['expires_at'])); ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="badge badge-pending">⏳ Pending</span>
                                            <br><small style="color: var(--text-light);">
                                                Expires: <?php echo date('M d, Y H:i', strtotime($token['expires_at'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo app_url('admin/users.php?search=' . urlencode($token['email'])); ?>" 
                                           class="btn btn-sm btn-secondary">View User</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                    <p style="font-size: 0.9rem; color: var(--text-light);">
                        <strong>ℹ️ Info:</strong>
                        <br>• Pending tokens are valid for 1 hour
                        <br>• Expired tokens are automatically deleted after use
                        <br>• Each token can only be used once
                        <br>• Monitor for unusual reset request patterns
                    </p>
                </div>
            </div>

            <!-- Security Notes -->
            <div class="card" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%); border-left: 4px solid var(--primary);">
                <h3 style="color: var(--primary); margin-bottom: 1rem;">🔐 Security Notes</h3>
                <ul style="margin-left: 1.5rem; color: var(--text-light); line-height: 1.8;">
                    <li><strong>Tokens are hashed</strong> - Plain tokens are never stored in database</li>
                    <li><strong>One-time use</strong> - Each token can only be used once</li>
                    <li><strong>Time-limited</strong> - Tokens expire after 1 hour</li>
                    <li><strong>CSRF protected</strong> - All forms include CSRF tokens</li>
                    <li><strong>Rate limiting</strong> - Consider implementing for production</li>
                    <li><strong>Email verification</strong> - Sent via email (demo shows on screen)</li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="<?php echo app_url('admin/dashboard.php'); ?>" class="btn btn-secondary">Back to Dashboard</a>
                <a href="<?php echo app_url('admin/users.php'); ?>" class="btn btn-secondary">View All Users</a>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
