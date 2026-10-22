<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "../../config/database.php";
include_once "../../includes/security.php";

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = get_client_ip();

    if ($email === '' || $password === '') {
        $_SESSION['auth_error'] = 'Please enter both your email address and password.';
        header("Location: ../../pages/auth/login.php");
        exit;
    }

    // 1. Check Rate Limiting before processing
    $rateCheck = check_login_rate_limit($conn, $email, $ip);
    if (!$rateCheck['allowed']) {
        $_SESSION['auth_error'] = $rateCheck['message'];
        header("Location: ../../pages/auth/login.php");
        exit;
    }

    // 2. Query user with parameterized prepared statement
    $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    $allowed_roles = ['Admin', 'Staff', 'Client'];

    if ($user && in_array($user['role'], $allowed_roles, true) && verify_and_rehash_password($conn, $password, $user['password'], (int)$user['id'])) {
        // Record successful login (clears failed attempts counter)
        record_login_attempt($conn, $email, $ip, true);

        $user_id = (int)$user['id'];

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        unset($_SESSION['client_id']);

        $client_stmt = mysqli_prepare($conn, "SELECT id FROM clients WHERE user_id = ? LIMIT 1");
        mysqli_stmt_bind_param($client_stmt, "i", $user_id);
        mysqli_stmt_execute($client_stmt);
        $client_result = mysqli_stmt_get_result($client_stmt);

        if ($client_result && mysqli_num_rows($client_result) > 0) {
            $client = mysqli_fetch_assoc($client_result);
            $_SESSION['client_id'] = $client['id'];
        } elseif ($user['role'] === 'Client') {
            session_unset();
            $_SESSION['auth_error'] = 'Customer profile not found.';
            header("Location: ../../pages/auth/login.php");
            exit;
        }
        mysqli_stmt_close($client_stmt);

        if (isset($_SESSION['user_id'])) {
            header("Location: ../../pages/home.php");
            exit;
        }
    } else {
        // Record failed attempt
        record_login_attempt($conn, $email, $ip, false);

        // Check if now locked
        $postCheck = check_login_rate_limit($conn, $email, $ip);
        if (!$postCheck['allowed']) {
            $_SESSION['auth_error'] = $postCheck['message'];
        } else {
            $_SESSION['auth_error'] = 'Invalid email or password.';
        }
    }

    header("Location: ../../pages/auth/login.php");
    exit;
}
?>
