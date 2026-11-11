<?php
include '../../includes/auth.php';
require_once '../../../includes/csrf.php';
$pageTitle = "Edit Order | ShopEase Admin";
$pageHeading = "Edit Order";

include '../../../config/database.php';

$id = (int)($_GET['id'] ?? 0);
$sql = "SELECT * FROM orders WHERE id = $id";
$result = mysqli_query($conn, $sql);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    header("Location: index.php");
    exit;
}

$clientsQuery = "SELECT clients.id, users.name
                 FROM clients
                 LEFT JOIN users ON clients.user_id = users.id
                 ORDER BY users.name ASC";
$clientsResult = mysqli_query($conn, $clientsQuery);

$productsQuery = "SELECT id, name, price, stock
                  FROM products
                  ORDER BY name ASC";
$productsResult = mysqli_query($conn, $productsQuery);

$itemsQuery = "SELECT order_items.product_id, order_items.quantity, order_items.price
               FROM order_items
               WHERE order_items.order_id = $id";
$itemsResult = mysqli_query($conn, $itemsQuery);

$orderItems = [];
while ($item = mysqli_fetch_assoc($itemsResult)) {
    $orderItems[] = $item;
}

$error = "";

if (isset($_POST['submit'])) {
    if (!csrf_token_is_valid('edit_order', $_POST['csrf_token'] ?? null)) {
        security_log_event('csrf_rejected', 'denied', ['context' => ['form' => 'edit_order']]);
        http_response_code(403);
        exit("Invalid or missing CSRF token.");
    }

    if (!is_admin_role() && (isset($_POST['client_id']) || isset($_POST['product_id']) || isset($_POST['quantity']))) {
        security_log_access_denied('staff_field_tamper', ['field' => 'order_details']);
    }

    $client_id = (int)($_POST['client_id'] ?? 0);
    $status = mysqli_real_escape_string($conn, is_string($_POST['status'] ?? null) ? $_POST['status'] : '');
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    $total_price = 0;

    foreach ($product_ids as $index => $product_id) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantities[$index];
        $productQuery = "SELECT price FROM products WHERE id = $product_id";
        $productResult = mysqli_query($conn, $productQuery);
        $product = mysqli_fetch_assoc($productResult);
        if ($product) {
            $total_price += $product['price'] * $quantity;
        }
    }

    // Get old order status
    $oldStatusSql = "SELECT status FROM orders WHERE id = $id";
    $oldStatusResult = mysqli_query($conn, $oldStatusSql);
    $oldOrder = mysqli_fetch_assoc($oldStatusResult);
    $old_status = $oldOrder['status'];

    // Exact values only, so the status rules below cannot be sidestepped.
    if (!in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
        $error = "Invalid order status.";
    }

    // Enforce business rules
    if ($old_status === 'cancelled' && $status !== 'cancelled') {
        $error = "A cancelled order cannot be changed to another status.";
        $status = 'cancelled';
    } else if ($old_status === 'delivered' && $status !== 'delivered') {
        $error = "A delivered order cannot be changed to another status.";
        $status = 'delivered';
    } else if ($old_status === 'shipped' && $status === 'cancelled') {
        $error = "Shipped orders cannot be cancelled.";
        $status = $old_status;
    }

    // Staff may change only the status: customer, items, prices and total stay as stored.
    if (empty($error) && !is_admin_role() && $old_status === 'shipped' && !in_array($status, ['shipped', 'delivered'], true)) {
        $error = "Staff can only keep a shipped order as shipped or mark it delivered.";
    }

    if (!empty($error) && !is_admin_role()) {
        security_log_access_denied('staff_status_rejected');
    }

    if (empty($error) && !is_admin_role()) {
        $sql = "UPDATE orders SET status = '$status' WHERE id = $id AND status = '$old_status'";

        if (mysqli_query($conn, $sql)) {
            if ($status === 'cancelled' && $old_status !== 'cancelled' && mysqli_affected_rows($conn) > 0) {
                foreach ($orderItems as $item) {
                    $pid = (int)$item['product_id'];
                    $qty = (int)$item['quantity'];
                    mysqli_query($conn, "UPDATE products SET stock = stock + $qty WHERE id = $pid");
                }
            }

            security_log_event('admin_order_updated', 'success', ['target_type' => 'order', 'target_id' => $id, 'context' => ['status' => $status]]);
            header("Location: index.php");
            exit;
        }

        $error = "Database Error: " . mysqli_error($conn);
    }

    if (empty($error)) {
        $sql = "UPDATE orders SET client_id = '$client_id', total_price = '$total_price', status = '$status' WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            // Revert old stock if order was not cancelled
            if ($old_status !== 'cancelled') {
                $oldItemsQuery = "SELECT product_id, quantity FROM order_items WHERE order_id = $id";
                $oldItemsResult = mysqli_query($conn, $oldItemsQuery);
                while ($oldItem = mysqli_fetch_assoc($oldItemsResult)) {
                    $pid = (int)$oldItem['product_id'];
                    $qty = (int)$oldItem['quantity'];
                    mysqli_query($conn, "UPDATE products SET stock = stock + $qty WHERE id = $pid");
                }
            }
            
            mysqli_query($conn, "DELETE FROM order_items WHERE order_id = $id");

            foreach ($product_ids as $index => $product_id) {
                $product_id = (int)$product_id;
                $quantity = (int)$quantities[$index];
                $productQuery = "SELECT price FROM products WHERE id = $product_id";
                $productResult = mysqli_query($conn, $productQuery);
                $product = mysqli_fetch_assoc($productResult);
                if ($product) {
                    $price = $product['price'];
                    $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price)
                                VALUES ('$id', '$product_id', '$quantity', '$price')";
                    mysqli_query($conn, $itemSql);
                    
                    // Deduct new stock if order is not cancelled
                    if ($status !== 'cancelled') {
                        mysqli_query($conn, "UPDATE products SET stock = stock - $quantity WHERE id = $product_id AND stock >= $quantity");
                    }
                }
            }

            security_log_event('admin_order_updated', 'success', ['target_type' => 'order', 'target_id' => $id, 'context' => ['status' => $status]]);
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
                <span class="page-eyebrow">ORDER FULFILLMENT</span>
                <h1>Edit Order #<?= $order['id'] ?></h1>
                <p>Modify status, items, quantities, or assigned client.</p>
            </div>
            <div class="page-actions">
                <a href="index.php" class="btn btn-outline">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Orders</span>
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
                <h2>Modify Order</h2>
                <p class="text-muted">Update order status and product quantities.</p>
            </div>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('edit_order')); ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <fieldset class="d-flex flex-column gap-2" <?= is_admin_role() ? '' : 'disabled'; ?>>
                        <label for="client_id" class="form-label">Client Account <span>*</span></label>
                        <select id="client_id" name="client_id" class="form-select" required>
                            <option value="">Select client...</option>
                            <?php while ($client = mysqli_fetch_array($clientsResult)) { ?>
                                <option value="<?= $client['id']; ?>" <?= ($client['id'] == $order['client_id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($client['name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                        </fieldset>
                    </div>

                    <div class="form-group">
                        <label for="status" class="form-label">Order Status <span>*</span></label>
                        <?php if ($order['status'] === 'cancelled'): ?>
                            <input type="text" class="form-control bg-light text-danger font-weight-bold" value="Cancelled (Final)" readonly>
                            <input type="hidden" name="status" value="cancelled">
                            <small class="text-muted d-block mt-1">This order is cancelled and its status cannot be changed.</small>
                        <?php elseif ($order['status'] === 'delivered'): ?>
                            <input type="text" class="form-control bg-light text-success font-weight-bold" value="Delivered (Final)" readonly>
                            <input type="hidden" name="status" value="delivered">
                            <small class="text-muted d-block mt-1">This order is delivered and its status cannot be changed.</small>
                        <?php else: ?>
                            <select id="status" name="status" class="form-select" required>
                                <option value="pending" <?= ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?= ($order['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?= ($order['status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?= ($order['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                <?php if ($order['status'] !== 'shipped'): ?>
                                    <option value="cancelled" <?= ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                <?php endif; ?>
                            </select>
                            <?php if ($order['status'] === 'shipped'): ?>
                                <small class="text-muted d-block mt-1">Shipped orders can no longer be cancelled.</small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Products list -->
                    <div class="form-group full-width">
                        <fieldset class="d-flex flex-column gap-2" <?= is_admin_role() ? '' : 'disabled'; ?>>
                        <label class="form-label">Order Items <span>*</span></label>
                        <div id="products-container" class="d-flex flex-column gap-3 mb-3">
                            <?php foreach ($orderItems as $item) { ?>
                                <div class="row g-2 align-items-center product-row border p-3 rounded bg-light">
                                    <div class="col-md-6">
                                        <select name="product_id[]" class="form-select" required>
                                            <option value="">Select product...</option>
                                            <?php
                                            mysqli_data_seek($productsResult, 0);
                                            while ($product = mysqli_fetch_array($productsResult)) {
                                            ?>
                                                <option value="<?= $product['id']; ?>" data-price="<?= $product['price']; ?>" <?= ($product['id'] == $item['product_id']) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($product['name']); ?> - $<?= number_format($product['price'], 2); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" name="quantity[]" class="form-control" placeholder="Qty" min="1" value="<?= $item['quantity']; ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-outline text-danger border-danger w-100 remove-product">
                                            <i class="bi bi-trash"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            <?php } ?>

                            <?php if (empty($orderItems)) { ?>
                                <div class="row g-2 align-items-center product-row border p-3 rounded bg-light">
                                    <div class="col-md-6">
                                        <select name="product_id[]" class="form-select" required>
                                            <option value="">Select product...</option>
                                            <?php
                                            mysqli_data_seek($productsResult, 0);
                                            while ($product = mysqli_fetch_array($productsResult)) {
                                            ?>
                                                <option value="<?= $product['id']; ?>" data-price="<?= $product['price']; ?>">
                                                    <?= htmlspecialchars($product['name']); ?> - $<?= number_format($product['price'], 2); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" name="quantity[]" class="form-control" placeholder="Qty" min="1" value="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-outline text-danger border-danger w-100 remove-product">
                                            <i class="bi bi-trash"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                        <button type="button" id="add-product" class="btn btn-outline">
                            <i class="bi bi-plus-lg"></i> Add Another Product
                        </button>
                        </fieldset>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Calculated Order Total</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" id="total_price_display" class="form-control bg-light font-weight-bold" value="<?= number_format($order['total_price'], 2); ?>" readonly>
                        </div>
                        <small class="text-muted mt-1">Calculated automatically based on product unit prices and quantities.</small>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('products-container');
    const addProductButton = document.getElementById('add-product');
    const totalDisplay = document.getElementById('total_price_display');

    function calculateTotal() {
        let total = 0;
        const rows = document.querySelectorAll('.product-row');
        rows.forEach(function(row) {
            const product = row.querySelector('select[name="product_id[]"]');
            const quantity = row.querySelector('input[name="quantity[]"]');
            if (product && product.value && quantity && quantity.value) {
                const selectedOption = product.options[product.selectedIndex];
                const price = parseFloat(selectedOption.dataset.price || 0);
                const qty = parseInt(quantity.value || 0);
                total += price * qty;
            }
        });
        if (totalDisplay) {
            totalDisplay.value = total.toFixed(2);
        }
    }

    if (addProductButton) {
        addProductButton.addEventListener('click', function() {
            const firstRow = document.querySelector('.product-row');
            if (!firstRow) return;
            const newRow = firstRow.cloneNode(true);
            newRow.querySelector('select').value = '';
            newRow.querySelector('input').value = 1;
            container.appendChild(newRow);
        });
    }

    if (container) {
        container.addEventListener('click', function(event) {
            const button = event.target.closest('.remove-product');
            if (!button) return;
            const rows = document.querySelectorAll('.product-row');
            if (rows.length > 1) {
                button.closest('.product-row').remove();
                calculateTotal();
            }
        });

        container.addEventListener('change', calculateTotal);
        container.addEventListener('input', calculateTotal);
    }
});
</script>

<?php include '../../includes/footer.php'; ?>