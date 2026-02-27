<?php
session_start();

// Database configuration - update as needed
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'kurinji_poultry');
define('DB_USER', 'root');
define('DB_PASS', '');

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function q(string $sql, array $params = []): array {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function qval(string $sql, array $params = []): mixed {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchColumn();
}

// Indian Number Formatting Function
function formatIndian(float|null $v): string {
    if ($v === null) return '—';
    if ($v == 0) return '0';
    
    $isNegative = $v < 0;
    $v = abs($v);
    
    // Split into integer and decimal parts
    $parts = explode('.', number_format($v, 2));
    $intPart = $parts[0];
    $decPart = $parts[1] ?? '00';
    
    // Remove existing commas (from number_format)
    $intPart = str_replace(',', '', $intPart);
    
    // Format integer part in Indian style
    $len = strlen($intPart);
    if ($len <= 3) {
        $formatted = $intPart;
    } else {
        $lastThree = substr($intPart, -3);
        $remaining = substr($intPart, 0, -3);
        $formatted = $lastThree;
        
        while (strlen($remaining) > 0) {
            $len = strlen($remaining);
            if ($len > 2) {
                $formatted = substr($remaining, -2) . ',' . $formatted;
                $remaining = substr($remaining, 0, -2);
            } else {
                $formatted = $remaining . ',' . $formatted;
                break;
            }
        }
    }
    
    $result = ($isNegative ? '-' : '') . $formatted . '.' . $decPart;
    return $result;
}

function money(float|null $v): string {
    if ($v === null) return '—';
    return '₹' . formatIndian($v);
}

function num(float|null $v, int $d = 2): string {
    if ($v === null) return '—';
    // Round to specified decimal places
    $v = round($v, $d);
    if ($v == intval($v) && $d > 0) {
        $v = intval($v);
    }
    return formatIndian($v);
}

// Login check function
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit;
    }
}

// Mock user authentication (replace with database user table as needed)
function authenticateUser(string $username, string $password): bool {
    // For demo: hardcoded admin credentials
    // In production, replace with database lookup and password hashing
    return $username === 'admin' && $password === 'admin123';
}

$page    = $_GET['page']    ?? 'dashboard';
$year    = (int)($_GET['year'] ?? date('Y'));
$month   = (int)($_GET['month'] ?? 0);
