<?php
// === MEMBER 4: HTTP SECURITY RESPONSE HEADERS ===
header("X-Frame-Options: DENY");                                        // Prevents Clickjacking
header("X-Content-Type-Options: nosniff");                              // Blocks MIME-confusion attacks
header("Referrer-Policy: strict-origin-when-cross-origin");             // Protects path leakage
header("Strict-Transport-Security: max-age=31536000; includeSubDomains"); // Enforces HTTPS/HSTS

// === MEMBER 4: SECURE SESSION COOKIE ATTRIBUTES ===
ini_set('session.cookie_httponly', 1);                                  // Mitigates XSS cookie theft
ini_set('session.cookie_secure', 1);                                    // Restricts cookie to HTTPS
ini_set('session.cookie_samesite', 'Strict');                           // Mitigates CSRF attacks
ini_set('session.gc_maxlifetime', 900);                                 // 15-minute garbage collection
ini_set('session.cookie_lifetime', 900);                                // 15-minute cookie lifespan

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === MEMBER 4: IDLE SESSION TIMEOUT (15 MINUTES) ===
$timeout_duration = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['LAST_ACTIVITY'] = time();

$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)$item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopEase</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/E-Commerce-Management-System/assets/css/style.css?v=checkout-2">
    <link rel="stylesheet" href="/E-Commerce-Management-System/assets/css/navbar.css?v=1">
    <link rel="stylesheet" href="/E-Commerce-Management-System/assets/css/footer.css?v=1">
</head>
<body>