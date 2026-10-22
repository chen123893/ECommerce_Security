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
$port     = (int)($_ENV['DB_PORT'] ?? 3307);

// Attempt connection on primary port (3307), fallback to default port (3306)
$conn = @new mysqli($host, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    $conn = @new mysqli('localhost', $username, $password, $dbname, 3306);
    if ($conn->connect_error) {
        error_log("Secure DB Connection Error: " . $conn->connect_error);
        die('Database connection failed. Please contact the administrator.');
    }
}

$conn->set_charset('utf8mb4');
?>