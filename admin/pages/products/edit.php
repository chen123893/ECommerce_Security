<?php
include '../../includes/auth.php';
$pageTitle = "Edit Product | ShopEase Admin";
$pageHeading = "Edit Product";

include '../../../config/database.php';

$id = (int)($_GET['id'] ?? 0);
$query = "SELECT * FROM products WHERE id = $id";
$result = mysqli_query($conn, $query);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    header("Location: index.php");
    exit;
}

$categoriesQuery = "SELECT * FROM categories ORDER BY name ASC";
$categoriesResult = mysqli_query($conn, $categoriesQuery);

$brandsQuery = "SELECT * FROM brands ORDER BY name ASC";
$brandsResult = mysqli_query($conn, $brandsQuery);

$error = "";

if (isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category_id = (int)$_POST['category_id'];
    $brand_id = (int)$_POST['brand_id'];
    $price = is_admin_role() ? (float)($_POST['price'] ?? 0) : (float)$product['price'];
    $stock = (int)$_POST['stock'];

    $oldImage = $product['image'];

    if (!empty($_FILES['image']['name'])) {
        $imageName = $_FILES['image']['name'];
        $imageTmpName = $_FILES['image']['tmp_name'];
        $uploadDirectory = '../../assets/images/products/';

        $imageExtension = pathinfo($imageName, PATHINFO_EXTENSION);
        $newImageName = uniqid() . '.' . $imageExtension;

        if (move_uploaded_file($imageTmpName, $uploadDirectory . $newImageName)) {
            $image = $newImageName;
            if (!empty($oldImage) && file_exists($uploadDirectory . $oldImage)) {
                unlink($uploadDirectory . $oldImage);
            }
        } else {
            $error = "Error uploading image.";
            $image = $oldImage;
        }
    } else {
        $image = $oldImage;
    }

    if (empty($error)) {
        $sql = "UPDATE products SET
                    category_id = '$category_id',
                    brand_id = '$brand_id',
                    name = '$name',
                    description = '$description',
                    price = '$price',
                    stock = '$stock',
                    image = '$image'
                WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            header("Location: index.php");
            exit;
        } else {
            $error = "Database Error: " . mysqli_error($conn);
        }
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
                            <?= is_admin_role() ? '' : 'readonly'; ?>
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