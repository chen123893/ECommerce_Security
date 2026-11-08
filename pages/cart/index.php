<?php

include "../../config/database.php";
include "../../includes/header.php";
include "../../includes/navbar.php";

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$subtotal = 0;
?>

<section class="section" style="background: var(--background); min-height: 80vh;">
    <div class="container">
        <h1 style="margin-bottom: 2rem;">Shopping Cart</h1>
        
        <?php if (empty($cart)): ?>
            <div class="card" style="padding: 4rem 2rem; text-align: center;">
                <div style="font-size: 4rem; color: #9CA3AF; margin-bottom: 1rem;"><i class="bi bi-cart-x"></i></div>
                <h2 style="margin-bottom: 1rem;">Your cart is empty</h2>
                <p style="color: var(--text); margin-bottom: 2rem;">Looks like you haven't added anything to your cart yet.</p>
                <a href="../products/index.php" class="btn btn-primary" style="padding: 1rem 2rem;">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card" style="padding: 2rem;">
                        <?php
                        foreach ($cart as $item) {
                            $product_id = $item['id'];
                            $quantity = $item['quantity'];
                            
                            $query = "SELECT * FROM products WHERE id = $product_id";
                            $result = mysqli_query($conn, $query);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                $product = mysqli_fetch_assoc($result);
                                $item_total = $product['price'] * $quantity;
                                $subtotal += $item_total;
                        ?>
                            <div class="cart-item" style="display: flex; align-items: center; gap: 1.5rem; padding-bottom: 1.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid #E5E7EB;">
                                <div class="cart-item-image" style="width: 110px; height: 110px; border-radius: 12px; overflow: hidden; background: #F8FAFC; flex-shrink: 0; display: flex; align-items: center; justify-content: center; padding: 0.75rem; border: 1px solid #F1F5F9;">
                                    <img src="../../admin/assets/images/products/<?php echo rawurlencode($product['image'] ?? 'default.jpg'); ?>"
                                        onerror="this.onerror=null;this.src='../../admin/assets/images/products/iphone15.jpg'" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                </div>
                                <div class="cart-item-details" style="flex: 1;">
                                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1.15rem; color: var(--dark); font-weight: 600;"><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <div style="color: var(--primary); font-weight: 700; font-size: 1.05rem;">$<?php echo number_format($product['price'], 2); ?></div>
                                </div>
                                
                                <div class="cart-item-actions" style="display: flex; align-items: center; gap: 2rem;">
                                    <form action="../../actions/cart/update.php" method="POST" style="display: flex; align-items: center; background: white; border: 1px solid #E5E7EB; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $quantity; ?>" min="1" max="<?php echo $product['stock']; ?>" style="width: 60px; border: none; text-align: center; font-weight: 600; font-size: 1rem; outline: none; padding: 0.5rem 0; background: transparent;" onchange="this.form.submit()">
                                        <button type="submit" style="background: #F9FAFB; border: none; border-left: 1px solid #E5E7EB; padding: 0.5rem 0.75rem; cursor: pointer; color: var(--primary); transition: background 0.2s;" onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#F9FAFB'" title="Update"><i class="bi bi-arrow-clockwise"></i></button>
                                    </form>
                                    
                                    <div style="font-weight: 700; font-size: 1.25rem; color: var(--dark); min-width: 90px; text-align: right;">
                                        $<?php echo number_format($item_total, 2); ?>
                                    </div>
                                    
                                    <form action="../../actions/cart/remove.php" method="POST">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" style="background: none; border: none; color: #EF4444; padding: 0.5rem; cursor: pointer; border-radius: 8px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px;" onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='none'" title="Remove"><i class="bi bi-trash3-fill" style="font-size: 1.25rem;"></i></button>
                                    </form>
                                </div>
                            </div>
                        <?php
                            }
                        }
                        ?>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card" style="padding: 2rem; border-top: 4px solid var(--primary); position: sticky; top: 100px;">
                        <h3 style="margin-bottom: 1.5rem; font-size: 1.5rem; font-weight: 700;">Order Summary</h3>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; color: #4B5563; font-size: 1.05rem;">
                            <span>Subtotal</span>
                            <span style="font-weight: 600;">$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        
                        <?php 
                        $delivery = ($subtotal > 0 && $subtotal < 300) ? 4.99 : 0;
                        $total = $subtotal + $delivery;
                        ?>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; color: #4B5563; padding-bottom: 1.5rem; border-bottom: 1px solid #E5E7EB; font-size: 1.05rem;">
                            <span>Delivery</span>
                            <span style="font-weight: 600;"><?php echo $delivery > 0 ? '$'.number_format($delivery, 2) : '<span style="color: var(--primary);">FREE</span>'; ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: #F8FAFC; padding: 1rem; border-radius: 8px;">
                            <span style="font-weight: 600; font-size: 1.2rem;">Total</span>
                            <span style="color: var(--primary); font-weight: 800; font-size: 1.5rem;">$<?php echo number_format($total, 2); ?></span>
                        </div>
                        
                        <a href="../checkout/index.php" class="btn btn-primary " style="display: flex; width: 88%; r justify-content: center; align-items: center; padding: 1rem; font-size: 1.1rem; font-weight: 600; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);">Proceed to Checkout <i class="bi bi-arrow-right ms-2"></i></a>
                        
                        <div style="text-align: center; margin-top: 1.5rem;">
                            <a href="../products/index.php" style="color: #6B7280; text-decoration: none; font-size: 0.95rem; font-weight: 500; transition: color 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='#6B7280'">or Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
include "../../includes/footer.php";
?>
