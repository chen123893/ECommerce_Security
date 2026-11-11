<?php
include '../../includes/auth.php';
$pageTitle = "Orders | ShopEase Admin";
$pageHeading = "Orders";

include '../../includes/header.php';
include '../../../config/database.php';

$query = 'SELECT
            orders.id,
            users.name AS client_name,
            orders.total_price,
            orders.status,
            orders.created_at
          FROM orders
          LEFT JOIN clients ON orders.client_id = clients.id
          LEFT JOIN users ON clients.user_id = users.id
          ORDER BY orders.id DESC;';

$result = mysqli_query($conn, $query);

$ordersList = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $ordersList[] = $row;
    }
}
?>

<div class="admin-layout">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="admin-content">
        <?php include '../../includes/navbar.php'; ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">ORDER FULFILLMENT</span>
                <h1>Customer Orders</h1>
                <p>Track order statuses, total values, and customer purchases.</p>
            </div>

            <div class="page-actions">
                <?php if (admin_can_access('pages/orders/create.php')) { ?>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>Create Order</span>
                </a>
                <?php } ?>
            </div>
        </div>

        <?php if (!empty($ordersList)) { ?>
            <div class="entity-card-grid">
                <?php foreach ($ordersList as $order) { 
                    $statusClass = 'badge-mint';
                    $st = strtolower($order['status'] ?? 'pending');
                    if ($st === 'pending' || $st === 'processing') $statusClass = 'badge-warning';
                    elseif ($st === 'cancelled') $statusClass = 'badge-danger';
                    elseif ($st === 'shipped') $statusClass = 'badge-info';
                    elseif ($st === 'completed' || $st === 'delivered') $statusClass = 'badge-mint';
                ?>
                    <article class="entity-card">
                        <div class="entity-card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="entity-subtitle">ORDER #<?= $order['id']; ?></span>
                                <span class="badge <?= $statusClass ?>"><?= htmlspecialchars(ucfirst($order['status'])); ?></span>
                            </div>

                            <h2 class="entity-title mb-1"><?= htmlspecialchars($order['client_name'] ?? 'Guest Customer'); ?></h2>
                            
                            <div class="info-row mb-2">
                                <i class="bi bi-calendar3"></i>
                                <span class="small text-muted"><?= htmlspecialchars($order['created_at']); ?></span>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-3 pt-3 border-top">
                                <div>
                                    <span class="d-block text-muted small">Total Price</span>
                                    <span class="entity-price">$<?= number_format($order['total_price'], 2); ?></span>
                                </div>

                                <div class="icon-action-group">
                                    <a href="order_items.php?id=<?= $order['id']; ?>" class="icon-action action-view" aria-label="View order items" title="View Order Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?= $order['id']; ?>" class="icon-action action-edit" aria-label="Edit order status" title="Edit Order Status">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="delete.php?id=<?= $order['id']; ?>" class="icon-action action-delete" aria-label="Delete order" title="Delete Order">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <h3>No Orders Found</h3>
                <p>There are currently no customer orders in the system.</p>
                <?php if (admin_can_access('pages/orders/create.php')) { ?>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>Create Order</span>
                </a>
                <?php } ?>
            </div>
        <?php } ?>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>