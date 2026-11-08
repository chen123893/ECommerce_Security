<?php
include '../../includes/auth.php';
require_admin_role();
require_once '../../../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit("Method not allowed.");
}

if (!csrf_token_is_valid('delete_product', $_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit("Invalid or missing CSRF token.");
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 4294967295],
]);
if (!$id) {
    http_response_code(400);
    exit("Invalid product ID.");
}

include '../../../config/database.php';

try {
    $query = mysqli_prepare($conn, "SELECT image FROM products WHERE id = ?");
    mysqli_stmt_bind_param($query, 'i', $id);
    mysqli_stmt_execute($query);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($query));
    mysqli_stmt_close($query);

    if (!$product) {
        http_response_code(404);
        exit("Product not found.");
    }

    $delete = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
    mysqli_stmt_bind_param($delete, 'i', $id);
    mysqli_stmt_execute($delete);
    $deleted = mysqli_stmt_affected_rows($delete);
    mysqli_stmt_close($delete);
} catch (mysqli_sql_exception $exception) {
    error_log("ShopEase product delete failed (MySQL error " . $exception->getCode() . ") for product $id.");
    http_response_code(500);
    exit("Unable to delete the product. Please try again.");
}

if ($deleted !== 1) {
    http_response_code(404);
    exit("Product not found.");
}

if (!empty($product['image'])) {
    $imagePath = '../../assets/images/products/' . $product['image'];
    if (is_file($imagePath) && !@unlink($imagePath)) {
        error_log("ShopEase could not remove the image for deleted product $id.");
    }
}

header("Location: index.php");
exit;
