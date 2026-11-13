<?php
require_once __DIR__ . '/../../includes/session.php';
start_shopease_session();
include "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if ($product_id > 0 && $quantity > 0) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Verify stock with simple query
        $query = "SELECT stock FROM products WHERE id = $product_id";
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $product = mysqli_fetch_assoc($result);
            $stock = (int)$product['stock'];
            
            if ($stock > 0) {
                // If product already in cart, update quantity
                $current_qty = isset($_SESSION['cart'][$product_id]) ? (int)$_SESSION['cart'][$product_id]['quantity'] : 0;
                $new_qty = $current_qty + $quantity;
                
                // Ensure we don't exceed stock
                if ($new_qty > $stock) {
                    $new_qty = $stock;
                }
                
                $_SESSION['cart'][$product_id] = [
                    'id' => $product_id,
                    'quantity' => $new_qty
                ];
            } else {
                $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../../pages/cart/index.php';
                if (strpos($referer, '?') !== false) {
                    $referer .= '&error=out_of_stock';
                } else {
                    $referer .= '?error=out_of_stock';
                }
                header("Location: $referer");
                exit;
            }
        }
    }
}

// Redirect back
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../../pages/cart/index.php';
// Clean up existing error param if present to avoid duplication
$referer = preg_replace('/(\?|&)error=out_of_stock/', '', $referer);
header("Location: $referer");
exit;
?>
