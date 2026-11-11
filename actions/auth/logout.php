<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/security_audit.php';
if (isset($_SESSION['user_id'])) {
    security_log_event('logout', 'success', ['context' => ['portal' => 'customer']]);
}

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

header("Location: ../../pages/home.php");
exit;
?>
