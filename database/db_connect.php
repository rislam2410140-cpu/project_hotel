<?php
require_once __DIR__ . '/../config.php';

class Database {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}

$pdo = Database::getConnection();

// Ensure demo users exist and have known passwords (helps during demo/testing)
try {
    // If users table doesn't exist, skip
    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
    if (count($tables) > 0) {
        // Ensure admin user exists and password matches demo
        $demoAdminEmail = 'admin@hotel.com';
        $demoGuestEmail = 'guest@hotel.com';
        $demoAdminPass = 'Admin123';
        $demoGuestPass = 'Guest123';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        // Admin
        $stmt->execute([$demoAdminEmail]);
        $admin = $stmt->fetch();
        if (!$admin) {
            $hashAdmin = password_hash($demoAdminPass, PASSWORD_BCRYPT);
            $ins = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'admin')");
            $ins->execute(['Admin User', $demoAdminEmail, '+88-01700-000001', $hashAdmin]);
        } else {
            // If user exists but password doesn't verify with demo password, update it so demo works
            if (!password_verify($demoAdminPass, $admin['password_hash'])) {
                $hashAdmin = password_hash($demoAdminPass, PASSWORD_BCRYPT);
                $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $upd->execute([$hashAdmin, $admin['user_id']]);
            }
        }

        // Guest
        $stmt->execute([$demoGuestEmail]);
        $guest = $stmt->fetch();
        if (!$guest) {
            $hashGuest = password_hash($demoGuestPass, PASSWORD_BCRYPT);
            $ins = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'guest')");
            $ins->execute(['John Guest', $demoGuestEmail, '+88-01700-000002', $hashGuest]);
        } else {
            if (!password_verify($demoGuestPass, $guest['password_hash'])) {
                $hashGuest = password_hash($demoGuestPass, PASSWORD_BCRYPT);
                $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $upd->execute([$hashGuest, $guest['user_id']]);
            }
        }
    }
} catch (Exception $e) {
    // Ignore errors during demo seeding
}
?>
