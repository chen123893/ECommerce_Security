<?php
include '../../includes/auth.php';
require_once __DIR__ . '/../../includes/product-validation.php';
$pageTitle = "Edit Product | ShopEase Admin";
$pageHeading = "Edit Product";

include '../../../config/database.php';

$id = (int)($_GET['id'] ?? 0);
$query = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
mysqli_stmt_bind_param($query, 'i', $id);
mysqli_stmt_execute($query);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($query));
mysqli_stmt_close($query);

if (!$product) {
    header("Location: index.php");
    exit;
}

$categoriesQuery = "SELECT * FROM categories ORDER BY name ASC";
$categoriesResult = mysqli_query($conn, $categoriesQuery);

$brandsQuery = "SELECT * FROM brands ORDER BY name ASC";
$brandsResult = mysqli_query($conn, $brandsQuery);

$error = "";
$isSubmitted = isset($_POST['submit']);
$price = $_POST['price'] ?? null;
$stock = $_POST['stock'] ?? null;
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$imageUpload = $_FILES['image'] ?? [];

if ($isSubmitted) {
    $error = validateProductPriceAndStock($price, $stock);
    if ($error === '' && (!is_string($name) || !is_string($description))) {
        $error = 'Enter the product name and description as text.';
    }
    if ($error === '') {
        [$error, $imageExtension] = validateProductImageUpload($imageUpload, false);
    }
    if ($error !== '') {
        http_response_code(422);
    }
}

if ($isSubmitted && $error === '') {
    $category_id = (int)$_POST['category_id'];
    $brand_id = (int)$_POST['brand_id'];
    $oldImage = $product['image'];
    $image = $oldImage;
    $uploadDirectory = '../../assets/images/products/';

    if ($imageExtension !== null) {
        $newImageName = bin2hex(random_bytes(16)) . '.' . $imageExtension;

        if (@move_uploaded_file($imageUpload['tmp_name'], $uploadDirectory . $newImageName)) {
            $image = $newImageName;
        } else {
            error_log("ShopEase could not store the replacement image for product $id.");
            http_response_code(500);
            $error = "Error uploading image.";
        }
    }

    if (empty($error)) {
        $sql = "UPDATE products SET
                    category_id = ?,
                    brand_id = ?,
                    name = ?,
                    description = ?,
                    price = ?,
                    stock = ?,
                    image = ?
                WHERE id = ?";

        try {
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt
                && mysqli_stmt_bind_param($stmt, 'iisssisi', $category_id, $brand_id, $name, $description, $price, $stock, $image, $id)
                && mysqli_stmt_execute($stmt)) {
                security_log_event('admin_product_updated', 'success', ['target_type' => 'product', 'target_id' => $id]);
                mysqli_stmt_close($stmt);
                $oldImagePath = $uploadDirectory . $oldImage;
                if ($image !== $oldImage && !empty($oldImage) && is_file($oldImagePath) && !@unlink($oldImagePath)) {
                    error_log("ShopEase could not remove the replaced image for product $id.");
                }
                header("Location: index.php");
                exit;
            }
            $errorCode = $stmt ? mysqli_stmt_errno($stmt) : mysqli_errno($conn);
        } catch (mysqli_sql_exception $exception) {
            $errorCode = $exception->getCode();
        }

        error_log("ShopEase product update failed (MySQL error $errorCode) for product $id.");
        if ($image !== $oldImage && !@unlink($uploadDirectory . $image)) {
            error_log("ShopEase could not remove the upload after a failed update for product $id.");
        }
        http_response_code($errorCode === 1452 ? 422 : 500);
        $error = "Unable to save the product. Please check the details and try again.";
    }
}

include '../../includes/header.php';
?>

<div class="admin-layout">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="admin-content">
        <?php include '../../includes/navbar.php'; ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">INVENTORY & CATALOG</span>
                <h1>Edit Product #<?= $product['id'] ?></h1>
                <p>Modify pricing, stock, category, or product image.</p>
            </div>
            <div class="page-actions">
                <a href="index.php" class="btn btn-outline">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Products</span>
                </a>
            </div>
        </div>

        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php } ?>

        <div class="form-card">
            <div class="form-header">
                <h2>Product Details</h2>
                <p class="text-muted">Update product information below.</p>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="name" class="form-label">Product Name <span>*</span></label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control"
                            value="<?= htmlspecialchars($product['name']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="category_id" class="form-label">Category <span>*</span></label>
                        <select id="category_id" name="category_id" class="form-select" required>
                            <option value="">Select category</option>
                            <?php while ($category = mysqli_fetch_array($categoriesResult)) { ?>
                                <option value="<?= $category['id']; ?>" <?= $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($category['name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="brand_id" class="form-label">Brand <span>*</span></label>
                        <select id="brand_id" name="brand_id" class="form-select" required>
                            <option value="">Select brand</option>
                            <?php while ($brand = mysqli_fetch_array($brandsResult)) { ?>
                                <option value="<?= $brand['id']; ?>" <?= $brand['id'] == $product['brand_id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($brand['name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="price" class="form-label">Price ($) <span>*</span></label>
                        <input
                            type="number"
                            id="price"
                            name="price"
                            class="form-control"
                            value="<?= htmlspecialchars($product['price']); ?>"
                            step="0.01"
                            min="0"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="stock" class="form-label">Stock Quantity <span>*</span></label>
                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            class="form-control"
                            value="<?= htmlspecialchars($product['stock']); ?>"
                            min="0"
                            required
                        >
                    </div>

                    <div class="form-group full-width">
                        <label for="image" class="form-label">Update Product Image</label>
                        <input
                            type="file"
                            id="image"
                            name="image"
                            class="form-control"
                            accept="image/*"
                        >
                        <small class="text-muted mt-1">Leave empty to keep current image.</small>
                    </div>

                    <div class="form-group full-width">
                        <label for="description" class="form-label">Description <span>*</span></label>
                        <textarea
                            id="description"
                            name="description"
                            class="form-control"
                            required
                        ><?= htmlspecialchars($product['description']); ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Current Product Image</label>
                        <div class="image-preview-container" style="height: 220px;">
                            <?php if (!empty($product['image'])) { ?>
                                <img src="../../assets/images/products/<?= htmlspecialchars($product['image']); ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                            <?php } else { ?>
                                <div class="image-preview-placeholder">
                                    <i class="bi bi-box-seam fs-1 text-muted"></i>
                                    <p class="m-0 small text-muted">No image uploaded</p>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="index.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>
