<?php
require_once __DIR__ . '/../../includes/session.php';
start_shopease_session();
include "../../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_SESSION['user_id'];
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    
    if (!$name || !$phone || !$address || !$city) {
        $_SESSION['profile_error'] = 'All fields are required.';
        header("Location: ../../pages/profile/index.php");
        exit;
    }
    
    $name = mysqli_real_escape_string($conn, $name);
    $phone = mysqli_real_escape_string($conn, $phone);
    $address = mysqli_real_escape_string($conn, $address);
    $city = mysqli_real_escape_string($conn, $city);
    
    $user_query = "UPDATE users SET name = '$name' WHERE id = $user_id";
    $client_query = "INSERT INTO clients (user_id, phone, address, city)
                     VALUES ($user_id, '$phone', '$address', '$city')
                     ON DUPLICATE KEY UPDATE
                     phone = VALUES(phone), address = VALUES(address), city = VALUES(city)";
    
    if (mysqli_query($conn, $user_query) && mysqli_query($conn, $client_query)) {
        $_SESSION['user_name'] = $name;
        $client_result = mysqli_query($conn, "SELECT id FROM clients WHERE user_id = $user_id LIMIT 1");
        if ($client_result && mysqli_num_rows($client_result) > 0) {
            $_SESSION['client_id'] = mysqli_fetch_assoc($client_result)['id'];
        }
        $_SESSION['profile_msg'] = 'Profile updated successfully.';
    } else {
        $_SESSION['profile_error'] = 'Failed to update profile.';
    }
    
    header("Location: ../../pages/profile/index.php");
    exit;
}
?>
