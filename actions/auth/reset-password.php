<?php
require_once __DIR__ . '/../../includes/session.php';
start_shopease_session();
include "../../config/database.php";
include_once "../../includes/security.php";
require_once "../../includes/security_audit.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($token)) {
        $_SESSION['auth_error'] = 'Invalid password reset request.';
        header("Location: ../../pages/auth/login.php");
        exit;
    }

    if ($password !== $confirmPassword) {
        $_SESSION['auth_error'] = 'Password confirmation does not match.';
        header("Location: ../../pages/auth/reset-password.php?token=" . urlencode($token));
        exit;
    }

    $result = complete_password_reset($conn, $token, $password);

    if (!$result['success']) {
        // The token is never logged, so a rejected attempt cannot be tied to an account.
        security_log_event('password_reset_failed', 'failure');
        $_SESSION['auth_error'] = $result['message'];
        header("Location: ../../pages/auth/reset-password.php?token=" . urlencode($token));
        exit;
    }

    security_log_event('password_reset_completed', 'success', ['identifier' => $result['email'] ?? '']);

    // Success: Route to appropriate login portal based on user role
    if (in_array($result['role'] ?? '', ['Admin', 'Staff'], true)) {
        $_SESSION['admin_auth_success'] = $result['message'];
        header("Location: ../../admin/login.php");
        exit;
    } else {
        $_SESSION['auth_success'] = $result['message'];
        header("Location: ../../pages/auth/login.php");
        exit;
    }
}
?>
