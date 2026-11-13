<?php
require_once __DIR__ . '/../../includes/session.php';
start_shopease_session();
include "../../config/database.php";
require_once "../../includes/csrf.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['client_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

include "../../includes/header.php";
include "../../includes/navbar.php";

$client_id = (int)$_SESSION['client_id'];

$order_error = isset($_SESSION['order_error']) ? $_SESSION['order_error'] : '';
$order_success = isset($_SESSION['order_success']) ? $_SESSION['order_success'] : '';
unset($_SESSION['order_error'], $_SESSION['order_success']);
?>

<section class="section" style="background: var(--background); min-height: 80vh;">
    <div class="container">
        <h1 style="margin-bottom: 2rem;">My Orders</h1>
        
        <?php if ($order_error): ?>
            <div class="alert alert-danger" style="background: #FEE2E2; color: #DC2626; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($order_error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($order_success): ?>
            <div class="alert alert-success" style="background: #D1FAE5; color: #059669; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($order_success); ?>
            </div>
        <?php endif; ?>
        
        <?php
        // Try to get orders, using simple mysqli
        $query = "SELECT * FROM orders WHERE client_id = $client_id ORDER BY id DESC";
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($order = mysqli_fetch_assoc($result)) {
                $order_id = $order['id'];
                $total_amount = $order['total_price'] ?? 0;
                $status = $order['status'] ?? 'pending';
                // Some status colors
                $status_color = '#9CA3AF'; // default gray
                $status_bg = '#F3F4F6';
                if ($status == 'pending') { $status_color = '#D97706'; $status_bg = '#FEF3C7'; }
                if ($status == 'processing') { $status_color = '#2563EB'; $status_bg = '#DBEAFE'; }
                if ($status == 'shipped') { $status_color = '#7C3AED'; $status_bg = '#EDE9FE'; }
                if ($status == 'delivered' || $status == 'completed') { $status_color = '#059669'; $status_bg = '#D1FAE5'; }
                if ($status == 'cancelled') { $status_color = '#DC2626'; $status_bg = '#FEE2E2'; }
        ?>
                <div class="card" style="margin-bottom: 1.5rem; overflow: visible;">
                    <div style="background: #F9FAFB; padding: 1.5rem; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <div style="font-size: 0.85rem; color: #6B7280; margin-bottom: 0.25rem;">Order Number</div>
                            <div style="font-weight: 700; color: var(--dark);">#ORD-<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></div>
                        </div>
                        <div>
                            <div style="font-size: 0.85rem; color: #6B7280; margin-bottom: 0.25rem;">Total Amount</div>
                            <div style="font-weight: 700; color: var(--primary);">$<?php echo number_format((float)$total_amount, 2); ?></div>
                        </div>
                        <div>
                            <span style="display: inline-block; padding: 0.35rem 1rem; border-radius: 99px; font-size: 0.85rem; font-weight: 600; background: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>; text-transform: capitalize;">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                            <?php 
                            if ($status == 'pending' && isset($order['created_at'])) {
                                $created_at = strtotime($order['created_at']);
                                $current_time = time();
                                $hours_diff = ($current_time - $created_at) / 3600;
                                
                                if ($hours_diff <= 2) {
                            ?>
                                    <button type="button" class="btn btn-secondary btn-sm" style="color: #DC2626; border-color: #FCA5A5; background: white;" onclick="openCancelModal(<?php echo $order_id; ?>)"><i class="bi bi-x-circle"></i> Cancel</button>
                            <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div style="padding: 1.5rem;">
                        <h5 style="margin-bottom: 1rem; font-size: 1rem;">Order Items</h5>
                        
                        <?php
                        // Get order items
                        $items_query = "SELECT order_items.*, products.name, products.image 
                                        FROM order_items 
                                        JOIN products ON order_items.product_id = products.id 
                                        WHERE order_items.order_id = $order_id";
                        $items_result = mysqli_query($conn, $items_query);
                        
                        if ($items_result && mysqli_num_rows($items_result) > 0) {
                            while ($item = mysqli_fetch_assoc($items_result)) {
                        ?>
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #F3F4F6;">
                                <div style="width: 60px; height: 60px; background: var(--background); border-radius: 8px; display: flex; align-items: center; justify-content: center; padding: 0.25rem;">
                                    <img src="../../admin/assets/images/products/<?php echo rawurlencode($item['image'] ?? 'default.jpg'); ?>"
                                        onerror="this.onerror=null;this.src='../../admin/assets/images/products/iphone15.jpg'" alt="<?php echo htmlspecialchars($item['name']); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: var(--dark);"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div style="font-size: 0.85rem; color: #6B7280;">Qty: <?php echo htmlspecialchars($item['quantity'] ?? 1); ?> &times; $<?php echo htmlspecialchars($item['price']); ?></div>
                                </div>
                                <div style="font-weight: 600;">
                                    $<?php echo number_format((float)($item['price']) * (int)($item['quantity'] ?? 1), 2); ?>
                                </div>
                            </div>
                        <?php
                            }
                        }
                        ?>
                    </div>
                </div>
        <?php
            }
        } else {
        ?>
            <div class="card" style="padding: 4rem 2rem; text-align: center;">
                <div style="font-size: 4rem; color: #9CA3AF; margin-bottom: 1rem;"><i class="bi bi-box-seam"></i></div>
                <h2 style="margin-bottom: 1rem;">No orders yet</h2>
                <p style="color: var(--text); margin-bottom: 2rem;">You haven't placed any orders with us yet.</p>
                <a href="../products/index.php" class="btn btn-primary" style="padding: 1rem 2rem;">Start Shopping</a>
            </div>
        <?php
        }
        ?>
    </div>
</section>

<!-- Custom Cancel Modal -->
<style>
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.4);
    z-index: 1050;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.3s ease;
}
.modal-overlay.active {
    display: flex;
    opacity: 1;
}
.modal-content {
    background: white;
    padding: 2.5rem 2rem;
    border-radius: 16px;
    width: 90%;
    max-width: 420px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    text-align: center;
    transform: scale(0.95);
    transition: transform 0.3s ease;
}
.modal-overlay.active .modal-content {
    transform: scale(1);
}
.modal-icon {
    font-size: 3.5rem;
    color: #DC2626;
    margin-bottom: 1rem;
    line-height: 1;
}
.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.75rem;
}
.modal-text {
    color: #6B7280;
    margin-bottom: 2rem;
    font-size: 1rem;
    line-height: 1.5;
}
.modal-actions {
    display: flex;
    gap: 1rem;
}
.modal-actions .btn {
    flex: 1;
    padding: 0.75rem;
    font-weight: 600;
}
</style>

<div class="modal-overlay" id="cancelModal">
    <div class="modal-content">
        <div class="modal-icon"><i class="bi bi-exclamation-circle"></i></div>
        <div class="modal-title">Cancel Order?</div>
        <div class="modal-text">Are you sure you want to cancel this order? This action cannot be undone and your items will be released back to stock.</div>
        <form action="../../actions/orders/cancel.php" method="POST" class="modal-actions">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('cancel_order')); ?>">
            <input type="hidden" name="order_id" id="modalOrderId" value="">
            <button type="button" class="btn btn-secondary" onclick="closeCancelModal()">Keep Order</button>
            <button type="submit" class="btn btn-primary" style="background: #DC2626; border-color: #DC2626; box-shadow: 0 4px 6px rgba(220, 38, 38, 0.2);">Yes, Cancel It</button>
        </form>
    </div>
</div>

<script>
function openCancelModal(orderId) {
    document.getElementById('modalOrderId').value = orderId;
    document.getElementById('cancelModal').classList.add('active');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}
function closeCancelModal() {
    document.getElementById('cancelModal').classList.remove('active');
    document.body.style.overflow = '';
}
</script>

<?php
include "../../includes/footer.php";
?>
