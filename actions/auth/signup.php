<?php
require_once __DIR__ . '/../../includes/session.php';
start_shopease_session();
include "../../config/database.php";
include_once "../../includes/security.php";
require_once "../../includes/security_audit.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic field validation
    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$phone || !$address || !$city) {
        $_SESSION['auth_error'] = 'Please complete all required fields with valid details.';
        header("Location: ../../pages/auth/signup.php");
        exit;
    }

    // Strong password policy validation
    $passwordCheck = validate_password_strength($password);
    if (!$passwordCheck['valid']) {
        $_SESSION['auth_error'] = implode(' ', $passwordCheck['errors']);
        header("Location: ../../pages/auth/signup.php");
        exit;
    }
    
    // Check if email already registered using prepared statement
    $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($check_stmt, "s", $email);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $exists = mysqli_num_rows($check_result) > 0;
    mysqli_stmt_close($check_stmt);
    
    if ($exists) {
        $_SESSION['auth_error'] = 'That email address is already registered. Please sign in or use a different email.';
        header("Location: ../../pages/auth/signup.php");
        exit;
    }

    // Modern secure password hashing (Argon2id if supported, otherwise Bcrypt)
    $hashed_password = hash_password($password);

    // Insert user with prepared statement
    $user_stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'Client')");
    mysqli_stmt_bind_param($user_stmt, "sss", $name, $email, $hashed_password);
    
    if (mysqli_stmt_execute($user_stmt)) {
        $user_id = mysqli_insert_id($conn);
        mysqli_stmt_close($user_stmt);
        
        // Insert client record
        $client_stmt = mysqli_prepare($conn, "INSERT INTO clients (user_id, phone, address, city) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($client_stmt, "isss", $user_id, $phone, $address, $city);
        
        if (mysqli_stmt_execute($client_stmt)) {
            $client_id = mysqli_insert_id($conn);
            mysqli_stmt_close($client_stmt);
            
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'Client';
            $_SESSION['client_id'] = $client_id;
            security_log_event('account_registered', 'success', ['identifier' => $email]);
            
            header("Location: ../../pages/home.php");
            exit;
        } else {
            mysqli_stmt_close($client_stmt);
            // Cleanup incomplete user record
            $del_stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
            mysqli_stmt_bind_param($del_stmt, "i", $user_id);
            mysqli_stmt_execute($del_stmt);
            mysqli_stmt_close($del_stmt);

            $_SESSION['auth_error'] = 'Registration failed due to client details error. Please try again.';
        }
    } else {
        mysqli_stmt_close($user_stmt);
        $_SESSION['auth_error'] = 'Registration failed. Please try again.';
    }
    
    header("Location: ../../pages/auth/signup.php");
    exit;
}
?>
