<?php
include '../../includes/auth.php';
require_once __DIR__ . '/../../includes/product-validation.php';
$pageTitle = "Add Product | ShopEase Admin";
$pageHeading = "Add Product";

include '../../../config/database.php';

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
        [$error, $imageExtension] = validateProductImageUpload($imageUpload, true);
    }
    if ($error !== '') {
        http_response_code(422);
    }
}

if ($isSubmitted && $error === '') {
    $category_id = (int)$_POST['category_id'];
    $brand_id = (int)$_POST['brand_id'];
    $uploadDirectory = '../../assets/images/products/';
    if (!is_dir($uploadDirectory)) {
        @mkdir($uploadDirectory, 0755, true);
    }

    $newImageName = bin2hex(random_bytes(16)) . '.' . $imageExtension;

    if (@move_uploaded_file($imageUpload['tmp_name'], $uploadDirectory . $newImageName)) {
        $sql = "INSERT INTO products (category_id, brand_id, name, description, price, stock, image)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt
                && mysqli_stmt_bind_param($stmt, 'iisssis', $category_id, $brand_id, $name, $description, $price, $stock, $newImageName)
                && mysqli_stmt_execute($stmt)) {
                security_log_event('admin_product_created', 'success', ['target_type' => 'product', 'target_id' => mysqli_insert_id($conn)]);
                mysqli_stmt_close($stmt);
                header("Location: index.php");
                exit;
            }
            $errorCode = $stmt ? mysqli_stmt_errno($stmt) : mysqli_errno($conn);
        } catch (mysqli_sql_exception $exception) {
            $errorCode = $exception->getCode();
        }

        error_log("ShopEase product create failed (MySQL error $errorCode).");
        if (!@unlink($uploadDirectory . $newImageName)) {
            error_log("ShopEase could not remove the upload after a failed product create.");
        }
        http_response_code($errorCode === 1452 ? 422 : 500);
        $error = "Unable to save the product. Please check the details and try again.";
    } else {
        error_log("ShopEase could not store the image for a new product.");
        http_response_code(500);
        $error = "Error uploading product image.";
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
                <h1>Add New Product</h1>
                <p>Add a new item to your store inventory.</p>
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
                <p class="text-muted">Fill in the product information below.</p>
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
                            placeholder="e.g. iPhone 15 Pro Max 256GB"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="category_id" class="form-label">Category <span>*</span></label>
                        <select id="category_id" name="category_id" class="form-select" required>
                            <option value="">Select category</option>
                            <?php while ($category = mysqli_fetch_array($categoriesResult)) { ?>
                                <option value="<?= $category['id']; ?>">
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
                                <option value="<?= $brand['id']; ?>">
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
                            placeholder="0.00"
                            step="0.01"
                            min="0"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="stock" class="form-label">Initial Stock Quantity <span>*</span></label>
                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            class="form-control"
                            placeholder="10"
                            min="0"
                            required
                        >
                    </div>

                    <div class="form-group full-width">
                        <label for="image" class="form-label">Product Image <span>*</span></label>
                        <input
                            type="file"
                            id="image"
                            name="image"
                            class="form-control"
                            accept="image/*"
                            required
                        >
                    </div>

                    <div class="form-group full-width">
                        <label for="description" class="form-label">Description <span>*</span></label>
                        <textarea
                            id="description"
                            name="description"
                            class="form-control"
                            placeholder="Enter detailed description of the product..."
                            required
                        ></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Image Preview</label>
                        <div class="image-preview-container" style="height: 220px;">
                            <div class="image-preview-placeholder">
                                <i class="bi bi-box-seam fs-1 text-primary"></i>
                                <p class="m-0 small text-muted">Select an image file above to see live preview</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="index.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        <span>Create Product</span>
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>
