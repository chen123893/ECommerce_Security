<?php
require_once __DIR__ . '/../../includes/session.php';
start_shopease_session();
include "../../config/database.php";
include_once "../../includes/security.php";
require_once "../../includes/security_audit.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    unset($_SESSION['auth_dev_reset_link'], $_SESSION['auth_local_reset_link']);
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
        $baseUrl = rtrim($_ENV['SHOPEASE_BASE_URL'] ?? 'https://localhost:8443/E-Commerce-Management-System', '/');
        $resetUrl = $baseUrl . '/pages/auth/reset-password.php?token=' . urlencode($result['plain_token']);

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

        $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
        $localHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
            && strtolower($_SERVER['HTTP_HOST'] ?? '') === 'localhost:8443';

        if (($_ENV['SHOPEASE_DEV_RESET_LINK'] ?? '') === '1' && $isLocal && $localHttps) {
            $_SESSION['auth_local_reset_link'] = $resetUrl;
        } elseif (!$isLocal && !empty($_ENV['SHOPEASE_BASE_URL'])) {
            @mail($to, $subject, $body, $headers);
        }
    }

    header("Location: ../../pages/auth/forgot-password.php");
    exit;
}
?>
