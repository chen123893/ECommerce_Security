<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "../../config/database.php";
include_once "../../includes/security.php";
require_once "../../includes/security_audit.php";

if (!isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        security_log_access_denied('not_signed_in');
    }
    header("Location: ../../pages/auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)$_SESSION['user_id'];
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['profile_error'] = 'All password fields are required.';
        header("Location: ../../pages/profile/index.php");
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        $_SESSION['profile_error'] = 'New password and confirmation do not match.';
        header("Location: ../../pages/profile/index.php");
        exit;
    }

    // Validate password policy
    $policy = validate_password_strength($newPassword);
    if (!$policy['valid']) {
        $_SESSION['profile_error'] = implode(' ', $policy['errors']);
        header("Location: ../../pages/profile/index.php");
        exit;
    }

    // Fetch user current password
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        security_log_event('password_change_failed', 'failure', ['context' => ['reason' => 'current_credential_incorrect']]);
        $_SESSION['profile_error'] = 'Your current password was entered incorrectly.';
        header("Location: ../../pages/profile/index.php");
        exit;
    }

    // Hash new password using Argon2id / bcrypt
    $hashedNewPassword = hash_password($newPassword);

    $updateStmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($updateStmt, "si", $hashedNewPassword, $userId);

    if (mysqli_stmt_execute($updateStmt)) {
        mysqli_stmt_close($updateStmt);
        security_log_event('password_changed', 'success');
        $_SESSION['profile_msg'] = 'Your password has been changed successfully with enhanced security hashing.';
    } else {
        mysqli_stmt_close($updateStmt);
        $_SESSION['profile_error'] = 'Failed to update password. Please try again.';
    }

    header("Location: ../../pages/profile/index.php");
    exit;
}
?>
