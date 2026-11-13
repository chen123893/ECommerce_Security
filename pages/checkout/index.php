<?php
require_once __DIR__ . '/../../includes/session.php';
start_shopease_session();
require_once "../../includes/csrf.php";
include "../../config/database.php";

if (!isset($_SESSION['client_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
if (empty($_SESSION['cart'])) {
    header("Location: ../cart/index.php");
    exit;
}
$checkoutToken = csrf_token('place_order');

include "../../includes/header.php";
include "../../includes/navbar.php";

$error = isset($_SESSION['checkout_error']) ? $_SESSION['checkout_error'] : '';
unset($_SESSION['checkout_error']);

// Get user and client data
$user_id = (int)$_SESSION['user_id'];
$user_query = "SELECT users.name, users.email, clients.phone, clients.address, clients.city 
               FROM users 
               JOIN clients ON users.id = clients.user_id 
               WHERE users.id = $user_id LIMIT 1";
$user_result = mysqli_query($conn, $user_query);
$user = $user_result ? mysqli_fetch_assoc($user_result) : null;
?>

<section class="section checkout-page">
    <div class="container">
        <div class="checkout-heading">
            <div>
                <span class="checkout-kicker">Almost there</span>
                <h1>Complete your order</h1>
                <p>Review your details and place your order securely.</p>
            </div>
            <a class="checkout-back-link" href="../cart/index.php"><i class="bi bi-arrow-left"></i> Back to cart</a>
        </div>
        
        <?php if ($error): ?>
            <div class="checkout-alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="checkout-layout">
            <div class="checkout-details">
                <div class="card checkout-card">
                    <div class="checkout-card-heading">
                        <span class="checkout-icon"><i class="bi bi-person-lines-fill"></i></span>
                        <div><span class="checkout-step">01</span><h3>Customer information</h3></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6" style="margin-bottom: 1rem;">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" readonly style="background: #F3F4F6;">
                        </div>
                        <div class="col-md-6" style="margin-bottom: 1rem;">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly style="background: #F3F4F6;">
                        </div>
                    </div>
                </div>
                
                <div class="card checkout-card">
                    <div class="checkout-card-heading">
                        <span class="checkout-icon"><i class="bi bi-truck"></i></span>
                        <div><span class="checkout-step">02</span><h3>Shipping information</h3></div>
                    </div>
                    <div class="checkout-note">
                        <i class="bi bi-info-circle-fill"></i> To update your shipping details, please visit your <a href="../profile/index.php" style="color: var(--primary); font-weight: bold;">Profile page</a>.
                    </div>
                    <div class="row">
                        <div class="col-md-6" style="margin-bottom: 1rem;">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" readonly style="background: #F3F4F6;">
                        </div>
                        <div class="col-md-6" style="margin-bottom: 1rem;">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" readonly style="background: #F3F4F6;">
                        </div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" readonly style="background: #F3F4F6;">
                    </div>
                </div>
            </div>
            
            <div class="checkout-sidebar">
                <div class="card checkout-summary">
                    <div class="checkout-summary-heading">
                        <div><span class="checkout-kicker">Your basket</span><h3>Order summary</h3></div>
                        <i class="bi bi-bag-check"></i>
                    </div>
                    
                    <div style="max-height: 300px; overflow-y: auto; padding-right: 1rem; margin-bottom: 1rem;">
                        <?php
                        $subtotal = 0;
                        foreach ($_SESSION['cart'] as $item) {
                            $product_id = $item['id'];
                            $quantity = $item['quantity'];
                            
                            $query = "SELECT name, price, image FROM products WHERE id = $product_id";
                            $result = mysqli_query($conn, $query);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                $product = mysqli_fetch_assoc($result);
                                $item_total = $product['price'] * $quantity;
                                $subtotal += $item_total;
                        ?>
                            <div class="checkout-item">
                                <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1;">
                                    <div class="checkout-item-image">
                                        <img src="../../admin/assets/images/products/<?php echo rawurlencode($product['image'] ?? 'default.jpg'); ?>"
                                            onerror="this.onerror=null;this.src='../../admin/assets/images/products/iphone15.jpg'" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                    </div>
                                    <div class="checkout-item-info">
                                        <div style="font-weight: 600; color: var(--dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div style="color: #6B7280;">Qty: <?php echo $quantity; ?></div>
                                    </div>
                                </div>
                                <div class="checkout-item-price">$<?php echo number_format($item_total, 2); ?></div>
                            </div>
                        <?php
                            }
                        }
                        ?>
                    </div>
                    
                    <div class="checkout-total-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <?php 
                    $delivery = ($subtotal > 0 && $subtotal < 300) ? 4.99 : 0;
                    $total = $subtotal + $delivery;
                    ?>
                    
                    <div class="checkout-total-row checkout-delivery-row">
                        <span>Delivery</span>
                        <span><?php echo $delivery > 0 ? '$'.number_format($delivery, 2) : 'FREE'; ?></span>
                    </div>
                    
                    <div class="checkout-grand-total">
                        <span>Grand Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <form action="../../actions/checkout/place-order.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($checkoutToken); ?>">
                        <button type="submit" class="btn btn-primary checkout-submit"><i class="bi bi-lock-fill"></i> Place order securely</button>
                    </form>
                    
                    <div class="checkout-secure-note">
                        <i class="bi bi-shield-check"></i> Secure checkout · Free delivery over $300
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include "../../includes/footer.php";
?>
