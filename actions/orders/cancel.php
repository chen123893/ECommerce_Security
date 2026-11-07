<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "../../config/database.php";
require_once "../../includes/security_audit.php";
require_once "../../includes/csrf.php";

if (!isset($_SESSION['client_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        security_log_access_denied('not_signed_in');
    }
    header("Location: ../../pages/auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_token_is_valid('cancel_order', $_POST['csrf_token'] ?? null)) {
        security_log_event('csrf_rejected', 'denied', ['context' => ['form' => 'cancel_order']]);
        http_response_code(403);
        exit("Invalid or missing CSRF token.");
    }

    $client_id = (int)$_SESSION['client_id'];
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    
    // Cancellation time limit: 2 hours
    $time_limit_hours = 2;
    
    // Check if order belongs to the client and get its status and creation time
    $query = "SELECT id, status, created_at FROM orders WHERE id = $order_id AND client_id = $client_id";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $order = mysqli_fetch_assoc($result);
        
        if ($order['status'] !== 'pending') {
            $_SESSION['order_error'] = "You can only cancel pending orders.";
            header("Location: ../../pages/orders/index.php");
            exit;
        }
        
        $created_at = strtotime($order['created_at']);
        $current_time = time();
        $hours_diff = ($current_time - $created_at) / 3600;
        
        if ($hours_diff > $time_limit_hours) {
            $_SESSION['order_error'] = "The cancellation period ($time_limit_hours hours) has expired.";
            header("Location: ../../pages/orders/index.php");
            exit;
        }
        
        // Update order status
        $update_query = "UPDATE orders SET status = 'cancelled' WHERE id = $order_id AND client_id = $client_id AND status = 'pending'";
        if (mysqli_query($conn, $update_query) && mysqli_affected_rows($conn) > 0) {
            
            // Restore stock
            $items_query = "SELECT product_id, quantity FROM order_items WHERE order_id = $order_id";
            $items_result = mysqli_query($conn, $items_query);
            
            if ($items_result && mysqli_num_rows($items_result) > 0) {
                while ($item = mysqli_fetch_assoc($items_result)) {
                    $pid = (int)$item['product_id'];
                    $qty = (int)$item['quantity'];
                    
                    $stock_update = "UPDATE products SET stock = stock + $qty WHERE id = $pid";
                    mysqli_query($conn, $stock_update);
                }
            }
            
            $_SESSION['order_success'] = "Order #ORD-" . str_pad($order_id, 5, '0', STR_PAD_LEFT) . " has been successfully cancelled.";
        } else {
            $_SESSION['order_error'] = "Failed to cancel order. It may have already been processed.";
        }
    } else {
        // Same response whether the order is missing or belongs to someone else.
        security_log_access_denied('order_not_owned');
        http_response_code(403);
        exit('Order not found or access denied.');
    }
}

header("Location: ../../pages/orders/index.php");
exit;
?>
