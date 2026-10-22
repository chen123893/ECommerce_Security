<?php
// === MEMBER 4: SECURE HOST CONFIGURATION & SECRETS ISOLATION ===
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0 || empty($line)) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

loadEnv(__DIR__ . '/../.env');

$host     = $_ENV['DB_HOST'] ?? '127.0.0.1';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';
$dbname   = $_ENV['DB_NAME'] ?? 'ecommerce';
$port     = (int)($_ENV['DB_PORT'] ?? 3306);

// Attempt primary port, catch exception if unavailable and fallback to secondary port
try {
    $conn = new mysqli($host, $username, $password, $dbname, $port);
} catch (mysqli_sql_exception $e) {
    $fallbackPort = ($port === 3307) ? 3306 : 3307;
    try {
        $conn = new mysqli($host, $username, $password, $dbname, $fallbackPort);
    } catch (mysqli_sql_exception $e2) {
        error_log("Secure DB Connection Error: " . $e2->getMessage());
        die('Database connection failed. Please contact the administrator.');
    }
}

$conn->set_charset('utf8mb4');
?>