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

function q(string $sql, array $p = []): array {
    $st = db()->prepare($sql); $st->execute($p); return $st->fetchAll();
}
function qrow(string $sql, array $p = []): array|false {
    $st = db()->prepare($sql); $st->execute($p); return $st->fetch();
}
function qval(string $sql, array $p = []): mixed {
    $st = db()->prepare($sql); $st->execute($p); return $st->fetchColumn();
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
function exec_q(string $sql, array $p = []): int {
    $st = db()->prepare($sql); $st->execute($p); return (int)$st->rowCount();
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

// ── Indian Number System ────────────────────────────────────────────────────
// Formats: 1,00,000  /  ₹1,00,000.00
function indian_number(float|null $v, int $dec = 2): string {
    if ($v === null) return '—';
    $neg = $v < 0;
    $v   = abs($v);
    $int = (int)floor($v);
    $frac = $dec > 0 ? '.' . str_pad((string)round(($v - $int) * pow(10,$dec)), $dec, '0', STR_PAD_LEFT) : '';
    // Indian grouping: last 3 digits, then groups of 2
    $str = (string)$int;
    if (strlen($str) > 3) {
        $last3 = substr($str, -3);
        $rest  = substr($str, 0, -3);
        $rest  = strrev(implode(',', str_split(strrev($rest), 2)));
        $str   = $rest . ',' . $last3;
    }
    return ($neg ? '-' : '') . $str . $frac;
}
function inr(float|null $v, int $dec = 2): string {
    if ($v === null) return '—';
    return '₹' . indian_number($v, $dec);
}
function inr_short(float|null $v): string {
    if ($v === null) return '—';
    $neg = $v < 0 ? '-' : '';
    $v = abs($v);
    if ($v >= 1e7)  return $neg . '₹' . indian_number($v/1e7, 2) . ' Cr';
    if ($v >= 1e5)  return $neg . '₹' . indian_number($v/1e5, 2) . ' L';
    if ($v >= 1e3)  return $neg . '₹' . indian_number($v/1e3, 2) . ' K';
    return $neg . '₹' . indian_number($v, 2);
}

// ── Auth Guard ──────────────────────────────────────────────────────────────
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php'); exit;
    }
}
function current_user(): array { return $_SESSION['user'] ?? []; }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    if (($_POST['_csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
        http_response_code(403); die('CSRF check failed.');
    }
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


// Read current page params
$page  = $_GET['page']  ?? 'dashboard';
$year  = $_GET['year']  ?? null;
$month   = (int)($_GET['month'] ?? 0);

$tab   = $_GET['tab']   ?? '';

if ($year === null) $year = default_year();
if ($year !== 'all') $year = (int)$year;


// ── Year / Filter helpers ───────────────────────────────────────────────────
// Default to the latest year with actual data
function default_year(): int {
    $y = (int)qval("
        SELECT MAX(yr) FROM (
            SELECT MAX(YEAR(sale_date))     yr FROM sales_egg
            UNION ALL SELECT MAX(YEAR(sale_date))     FROM sales_feed
            UNION ALL SELECT MAX(YEAR(purchase_date)) FROM exp_chick
            UNION ALL SELECT MAX(YEAR(purchase_date)) FROM exp_feed_ingredient
        ) t");
    return $y ?: (int)date('Y');
}

function all_years(): array {
    return q("SELECT DISTINCT yr FROM (
        SELECT YEAR(sale_date)     yr FROM sales_egg
        UNION SELECT YEAR(sale_date)     FROM sales_feed
        UNION SELECT YEAR(sale_date)     FROM sales_culling
        UNION SELECT YEAR(purchase_date) FROM exp_chick
        UNION SELECT YEAR(purchase_date) FROM exp_feed_ingredient
        UNION SELECT YEAR(payment_date)  FROM exp_salary
        UNION SELECT YEAR(txn_date)      FROM loan_transaction
    ) t ORDER BY yr DESC");
}

// Build WHERE clause for year filter (supports 'all')
function year_where(string $col, string $yearParam = 'year'): string {
    global $year;
    return $year === 'all' ? '1=1' : "YEAR($col) = $year";
}
function year_param(): array {
    global $year;
    return $year === 'all' ? [] : [$year];
}
function year_cond(string $col): string {
    global $year;
    return $year === 'all' ? '' : " AND YEAR($col) = $year";
}