<?php
require_once __DIR__ . '/../../includes/session.php';
start_shopease_session();
include "../../config/database.php";
include_once "../../includes/security.php";
require_once "../../includes/security_audit.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $ip = get_client_ip();

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['auth_error'] = 'Please provide a valid email address.';
        header("Location: ../../pages/auth/forgot-password.php");
        exit;
    }

    $result = request_password_reset($conn, $email, $ip);

    if ($result['status'] === 'rate_limited') {
        security_log_event('password_reset_blocked', 'blocked', ['identifier' => $email]);
        $_SESSION['auth_error'] = $result['message'];
        header("Location: ../../pages/auth/forgot-password.php");
        exit;
    }

    security_log_event('password_reset_requested', 'info', ['identifier' => $email, 'context' => ['account_found' => !empty($result['user'])]]);

    $_SESSION['auth_success'] = 'If an account exists with that email address, password reset instructions have been dispatched. The reset link is valid for 30 minutes.';

    if (!empty($result['plain_token']) && !empty($result['user'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $resetUrl = "{$scheme}://{$host}/E-Commerce-Management-System/pages/auth/reset-password.php?token=" . urlencode($result['plain_token']);

        // Dispatch email notification via mail()
        $to = $email;
        $subject = "ShopEase - Password Reset Request";
        $body = "Hello " . $result['user']['name'] . ",\r\n\r\n"
              . "We received a request to reset your password for your ShopEase account.\r\n"
              . "You can reset your password by opening the link below:\r\n\r\n"
              . $resetUrl . "\r\n\r\n"
              . "This link is valid for 30 minutes and can only be used once.\r\n"
              . "If you did not request this password reset, please ignore this message.\r\n\r\n"
              . "Regards,\r\nShopEase Security Team";

        $headers = "From: no-reply@shopease.com\r\n"
                 . "Reply-To: support@shopease.com\r\n"
                 . "X-Mailer: PHP/" . phpversion();

        // Only attempt mail() in production / non-local environments to avoid Windows localhost socket timeouts
        $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true) || (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false);
        if (!$isLocal) {
            @mail($to, $subject, $body, $headers);
        }

        // Store dev reset link in session for instant local testing
        $_SESSION['auth_dev_reset_link'] = $resetUrl;
    }

    header("Location: ../../pages/auth/forgot-password.php");
    exit;
}
?>
