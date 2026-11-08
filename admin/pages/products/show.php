<?php
include '../../includes/auth.php';
$pageTitle = "Product Details | ShopEase Admin";
$pageHeading = "Product Details";

include '../../../config/database.php';

$id = (int)($_GET['id'] ?? 0);
$query = mysqli_prepare($conn, "SELECT
            products.*,
            categories.name AS category_name,
            brands.name AS brand_name
          FROM products
          LEFT JOIN categories ON products.category_id = categories.id
          LEFT JOIN brands ON products.brand_id = brands.id
          WHERE products.id = ?");

mysqli_stmt_bind_param($query, 'i', $id);
mysqli_stmt_execute($query);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($query));
mysqli_stmt_close($query);

if (!$product) {
    header("Location: index.php");
    exit;
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
                <h1><?= htmlspecialchars($product['name']) ?></h1>
                <p>Product specification and inventory overview.</p>
            </div>

            <div class="page-actions">
                <a href="index.php" class="btn btn-outline">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back</span>
                </a>
                <a href="edit.php?id=<?= $product['id'] ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i>
                    <span>Edit Product</span>
                </a>
            </div>
        </div>

        <div class="form-card max-w-none" style="max-width: 860px;">
            <div class="row g-4 align-items-center">
                <div class="col-md-5">
                    <div class="card-media-wrap rounded" style="height: 260px;">
                        <?php if (!empty($product['image'])) { ?>
                            <img src="/E-Commerce-Management-System/admin/assets/images/products/<?= htmlspecialchars($product['image']); ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                        <?php } else { ?>
                            <i class="bi bi-box-seam card-media-placeholder"></i>
                        <?php } ?>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge badge-mint">Product #<?= $product['id'] ?></span>
                        <span class="badge badge-dark"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></span>
                        <?php if (!empty($product['brand_name'])) { ?>
                            <span class="badge badge-dark"><?= htmlspecialchars($product['brand_name']) ?></span>
                        <?php } ?>
                    </div>

                    <h2 class="h3 font-weight-bold text-dark mb-2"><?= htmlspecialchars($product['name']) ?></h2>
                    <p class="text-muted mb-3"><?= htmlspecialchars($product['description'] ?? 'No description provided.') ?></p>

                    <div class="d-flex align-items-center gap-4 pt-3 border-top">
                        <div>
                            <span class="d-block text-muted small">Price</span>
                            <strong class="h3 font-weight-bold text-dark">$<?= number_format($product['price'], 2) ?></strong>
                        </div>
                        <div class="border-start ps-4">
                            <span class="d-block text-muted small">Current Stock</span>
                            <strong class="h4 font-weight-bold <?= $product['stock'] <= 5 ? 'text-danger' : 'text-success' ?>">
                                <?= $product['stock'] ?> items left
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>
