<?php
include '../../includes/auth.php';
require_once '../../../includes/csrf.php';
$pageTitle = "Products | ShopEase Admin";
$pageHeading = "Products";
$canDelete = ($_SESSION['admin_role'] ?? '') === 'Admin';
$deleteToken = $canDelete ? csrf_token('delete_product') : null;

include '../../../config/database.php';

$sort = $_GET['sort'] ?? 'id';
$orderBy = "products.id DESC";
if ($sort === 'sold') {
    $orderBy = "total_sold DESC, products.id DESC";
}

$query = "SELECT 
            products.id,
            categories.name AS category_name,
            brands.name AS brand_name,
            products.name,
            products.description,
            products.price,
            products.stock,
            products.image,
            products.created_at,
            COALESCE(SUM(CASE WHEN orders.id IS NOT NULL THEN order_items.quantity ELSE 0 END), 0) AS total_sold
          FROM products
          LEFT JOIN categories ON products.category_id = categories.id
          LEFT JOIN brands ON products.brand_id = brands.id
          LEFT JOIN order_items ON products.id = order_items.product_id
          LEFT JOIN orders ON order_items.order_id = orders.id AND orders.status != 'cancelled'
          GROUP BY products.id
          ORDER BY $orderBy";

$result = mysqli_query($conn, $query);

$productsList = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $productsList[] = $row;
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
                <h1>Products</h1>
                <p>Manage product items, pricing, and stock levels.</p>
            </div>

            <div class="page-actions" style="display: flex; gap: 1rem; align-items: center;">
                <a href="?sort=<?= $sort === 'sold' ? 'id' : 'sold' ?>" class="btn btn-outline">
                    <i class="bi bi-sort-numeric-down"></i>
                    <span><?= $sort === 'sold' ? 'Sort by Latest' : 'Sort by Top Sellers' ?></span>
                </a>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>Add Product</span>
                </a>
            </div>
        </div>

        <?php if (!empty($productsList)) { ?>
            <div class="entity-card-grid">
                <?php foreach ($productsList as $product) { 
                    $isLowStock = (int)$product['stock'] <= 5;
                ?>
                    <article class="entity-card">
                        <div class="card-media-wrap">
                            <?php if (!empty($product['image'])) { ?>
                                <img src="/E-Commerce-Management-System/admin/assets/images/products/<?= htmlspecialchars($product['image']); ?>" alt="<?= htmlspecialchars($product['name']); ?>" loading="lazy">
                            <?php } else { ?>
                                <i class="bi bi-box-seam card-media-placeholder"></i>
                            <?php } ?>
                        </div>

                        <div class="entity-card-body">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="entity-subtitle">#<?= $product['id']; ?> &middot; <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                                <?php if (!empty($product['brand_name'])) { ?>
                                    <span class="badge badge-dark"><?= htmlspecialchars($product['brand_name']); ?></span>
                                <?php } ?>
                            </div>

                            <h2 class="entity-title"><?= htmlspecialchars($product['name']); ?></h2>
                            <p class="entity-description"><?= htmlspecialchars($product['description'] ?? 'No description available.'); ?></p>

                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="entity-price">$<?= number_format($product['price'], 2); ?></span>
                                <div class="d-flex flex-column align-items-end gap-1">
                                    <span class="badge <?= $isLowStock ? 'badge-danger' : 'badge-mint' ?>">
                                        <?= $product['stock']; ?> in stock
                                    </span>
                                    <span class="text-muted fw-bold" style="font-size: 0.8rem;"><i class="bi bi-graph-up-arrow"></i> <?= (int)$product['total_sold'] ?> Sold</span>
                                </div>
                            </div>

                            <div class="entity-card-footer">
                                <span class="text-muted small">Updated recently</span>
                                <div class="icon-action-group">
                                    <a href="show.php?id=<?= $product['id']; ?>" class="icon-action action-view" aria-label="View product" title="View Product">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?= $product['id']; ?>" class="icon-action action-edit" aria-label="Edit product" title="Edit Product">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($canDelete) { ?>
                                        <form method="POST" action="delete.php" data-confirm-delete>
                                            <input type="hidden" name="id" value="<?= (int)$product['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($deleteToken); ?>">
                                            <button type="submit" class="icon-action action-delete" aria-label="Delete product" title="Delete Product">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <h3>No Products Found</h3>
                <p>There are currently no products available in your store inventory.</p>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>Add Product</span>
                </a>
            </div>
        <?php } ?>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>
