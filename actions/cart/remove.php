<?php
require_once __DIR__ . '/../../includes/session.php';
start_shopease_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['product_id'])) {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : (int)$_GET['product_id'];
    
    if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
}

header("Location: ../../pages/cart/index.php");
exit;
?>
