<?php

session_start();


require_once __DIR__ . '/../includes/security_audit.php';
if (isset($_SESSION['admin_id'])) {
    security_log_event('logout', 'success', ['context' => ['portal' => 'admin']]);
}

// Remove all session variables
$_SESSION = [];


// Destroy session cookie
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );

}


// Destroy session
session_destroy();


// Go back to login
header("Location: login.php");
exit;

?>