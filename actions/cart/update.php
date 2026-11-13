<?php
require_once __DIR__ . '/../../includes/session.php';
start_shopease_session();
include "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    
    if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
        if ($quantity > 0) {

            $query = "SELECT stock FROM products WHERE id = $product_id";
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $product = mysqli_fetch_assoc($result);
                $stock = (int)$product['stock'];
                
                if ($quantity > $stock) {
                    $quantity = $stock;
                }
                
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            }
        } else {
            // Remove if quantity is 0 or less
            unset($_SESSION['cart'][$product_id]);
        }
    }
}

header("Location: ../../pages/cart/index.php");
exit;
?>
