<?php
require_once __DIR__ . '/../../includes/session.php';
start_shopease_session();
require_once "../../includes/csrf.php";
require_once "../../includes/security_audit.php";

if (!isset($_SESSION['client_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        security_log_access_denied('not_signed_in');
    }
    header("Location: ../../pages/auth/login.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit("Method not allowed.");
}
if (!csrf_token_is_valid('place_order', $_POST['csrf_token'] ?? null)) {
    security_log_event('csrf_rejected', 'denied', ['context' => ['form' => 'place_order']]);
    http_response_code(403);
    exit("Invalid or missing CSRF token.");
}
if (empty($_SESSION['cart'])) {
    header("Location: ../../pages/cart/index.php");
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include "../../config/database.php";

$client_id = (int)$_SESSION['client_id'];
$cart = $_SESSION['cart'];
ksort($cart, SORT_NUMERIC);
$subtotal = 0;
$items = [];

try {
    mysqli_begin_transaction($conn);

    // Keep stock and prices stable until the order is saved.
    $lookup = mysqli_prepare($conn, "SELECT name, price, stock FROM products WHERE id = ? FOR UPDATE");
    mysqli_stmt_bind_param($lookup, "i", $product_id);
    foreach ($cart as $item) {
        $product_id = (int)$item['id'];
        $quantity = (int)$item['quantity'];
        if ($quantity <= 0) {
            continue;
        }

        mysqli_stmt_execute($lookup);
        $product = mysqli_fetch_assoc(mysqli_stmt_get_result($lookup));
        if (!$product) {
            throw new DomainException("One of the products in your cart is invalid.");
        }
        if ($quantity > $product['stock']) {
            throw new DomainException("Insufficient stock for product: " . $product['name'] . ". Only " . $product['stock'] . " available.");
        }

        $subtotal += $product['price'] * $quantity;
        $items[] = ['id' => $product_id, 'quantity' => $quantity, 'price' => $product['price']];
    }
    mysqli_stmt_close($lookup);

    if (empty($items)) {
        throw new DomainException("Your items are out of stock.");
    }

    $delivery = ($subtotal > 0 && $subtotal < 300) ? 4.99 : 0;
    $total = number_format($subtotal + $delivery, 2, '.', '');
    $order = mysqli_prepare($conn, "INSERT INTO orders (client_id, total_price, status) VALUES (?, ?, 'pending')");
    mysqli_stmt_bind_param($order, "is", $client_id, $total);
    mysqli_stmt_execute($order);
    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($order);

    $insertItem = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($insertItem, "iiis", $order_id, $product_id, $quantity, $price);
    $updateStock = mysqli_prepare($conn, "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
    mysqli_stmt_bind_param($updateStock, "iii", $quantity, $product_id, $quantity);

    foreach ($items as $item) {
        $product_id = $item['id'];
        $quantity = $item['quantity'];
        $price = $item['price'];

        mysqli_stmt_execute($insertItem);
        mysqli_stmt_execute($updateStock);
        if (mysqli_stmt_affected_rows($updateStock) !== 1) {
            throw new DomainException("Stock changed while placing your order. Please review your cart.");
        }
    }
    mysqli_stmt_close($insertItem);
    mysqli_stmt_close($updateStock);
    mysqli_commit($conn);
} catch (DomainException | mysqli_sql_exception $exception) {
    try {
        mysqli_rollback($conn);
    } catch (mysqli_sql_exception $rollbackException) {
        error_log("ShopEase checkout rollback failed (MySQL error " . $rollbackException->getCode() . ").");
    }

    if ($exception instanceof DomainException) {
        $_SESSION['checkout_error'] = $exception->getMessage();
    } else {
        error_log("ShopEase checkout failed (MySQL error " . $exception->getCode() . ").");
        $_SESSION['checkout_error'] = "Unable to place your order. Please try again.";
    }

    header("Location: ../../pages/checkout/index.php");
    exit;
}

$_SESSION['cart'] = [];
header("Location: ../../pages/orders/index.php");
exit;
