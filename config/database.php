<?php
$host = '127.0.0.1';
$username = 'root';
$password = '';
$dbname = 'ecommerce';
$port = 3307;

$conn = @new mysqli($host, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    // Fallback to default port 3306 if 3307 is not configured
    $conn = @new mysqli('localhost', $username, $password, $dbname, 3306);
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }
}
$conn->set_charset('utf8mb4');
?>