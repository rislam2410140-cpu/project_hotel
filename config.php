<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hotel_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// App Path Configuration
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

// Determine a stable base path by searching upward from the script location
// until a known assets file exists (assets/style.css). This makes app_url()
// work for files inside subfolders (guest/, admin/, public/).
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
$candidate = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$found = false;

// Try current and parent directories up to document root
while (true) {
    // Build a filesystem path to check for assets/style.css
    $checkPath = $docRoot . ($candidate === '/' || $candidate === '' ? '' : $candidate) . '/assets/style.css';
    if ($docRoot && file_exists($checkPath)) {
        $found = true;
        break;
    }

    $parent = dirname($candidate);
    if ($parent === $candidate || $candidate === '' || $candidate === '.') {
        break;
    }
    $candidate = $parent;
}

// Fallback to previous index.php based logic if assets not found
if (!$found) {
    $basePath = '';
    $marker = '/index.php';
    if ($scriptName !== '') {
        if (str_ends_with($scriptName, $marker)) {
            $basePath = substr($scriptName, 0, -strlen($marker));
        } else {
            $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');
        }
    }
    if ($basePath === '/' || $basePath === '\\') {
        $basePath = '';
    }
    define('BASE_PATH', $basePath);
} else {
    // Ensure BASE_PATH uses forward slashes and is empty for document root
    $candidate = $candidate === '/' ? '' : $candidate;
    define('BASE_PATH', $candidate);
}

// Site Configuration
define('SITE_NAME', 'Luxe Hotel');
define('SITE_URL', ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_PATH);
define('SITE_DESC', 'Experience luxury and comfort at our 5-star hotel');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Configuration
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
session_start();

function app_url(string $path = ''): string {
    $normalized = ltrim($path, '/');
    $prefix = BASE_PATH === '' ? '' : BASE_PATH;
    return $normalized === '' ? ($prefix === '' ? '/' : $prefix . '/') : $prefix . '/' . $normalized;
}

function redirect_to(string $path): void {
    header('Location: ' . app_url($path));
    exit;
}

function ucfirst_word(string $str): string {
    return ucfirst(str_replace('_', ' ', $str));
}

function set_flash(string $type, string $msg): void {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_msg'] = $msg;
}

function get_flash(): ?array {
    if (!isset($_SESSION['flash_msg'])) {
        return null;
    }

    $flash = [
        'type' => $_SESSION['flash_type'] ?? 'info',
        'msg' => $_SESSION['flash_msg']
    ];

    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

    return $flash;
}

function format_price(float $price): string {
    return '$' . number_format($price, 2);
}

function format_date(string $date): string {
    return date('M d, Y', strtotime($date));
}

function get_status_color(string $status): string {
    $colors = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'confirmed' => 'bg-blue-100 text-blue-800',
        'checked_in' => 'bg-purple-100 text-purple-800',
        'checked_out' => 'bg-green-100 text-green-800',
        'completed' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
        'available' => 'bg-green-100 text-green-800',
        'occupied' => 'bg-purple-100 text-purple-800',
        'cleaning' => 'bg-yellow-100 text-yellow-800'
    ];

    return $colors[$status] ?? 'bg-gray-100 text-gray-800';
}
?>
