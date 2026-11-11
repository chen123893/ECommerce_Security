<?php
include "../../config/database.php";
include "../../includes/header.php";
include "../../includes/navbar.php";

$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = $_GET['sort'] ?? '';
?>

<section class="section" style="background: var(--background); padding: 3rem 0;">
    <div class="container">
        <div class="section-title">
            <h2 style="font-size: 2.5rem;">Our Products</h2>
            <p>Discover our complete collection of premium tech products.</p>
        </div>
        
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem; justify-content: center;">
            <a href="index.php" class="btn <?php echo ($category_id == 0) ? 'btn-primary' : 'btn-secondary'; ?>" style="border-radius: 99px;">All Products</a>
            <?php
            $cat_query = "SELECT * FROM categories";
            $cat_result = mysqli_query($conn, $cat_query);
            if ($cat_result) {
                while ($cat = mysqli_fetch_assoc($cat_result)) {
                    $active = ($category_id == $cat['id']) ? 'btn-primary' : 'btn-secondary';
                    echo '<a href="index.php?category='.$cat['id'].'&sort='.htmlspecialchars($sort).'" class="btn '.$active.'" style="border-radius: 99px;">'.htmlspecialchars($cat['name']).'</a>';
                }
            }
            ?>
        </div>
        
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem; justify-content: center;">
            <a href="index.php?category=<?= $category_id ?>" class="btn <?= ($sort !== 'bestselling') ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius: 99px; font-weight: 600;"><i class="bi bi-stars"></i> Latest</a>
            <a href="index.php?category=<?= $category_id ?>&sort=bestselling" class="btn <?= ($sort === 'bestselling') ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="border-radius: 99px; font-weight: 600;"><i class="bi bi-fire"></i> Best Selling</a>
        </div>
        
        <div class="row">
            <?php
            $query = "SELECT products.*, categories.name AS category_name, brands.name AS brand_name,
                      COALESCE(SUM(CASE WHEN orders.id IS NOT NULL THEN order_items.quantity ELSE 0 END), 0) AS total_sold
                      FROM products 
                      LEFT JOIN categories ON products.category_id = categories.id 
                      LEFT JOIN brands ON products.brand_id = brands.id
                      LEFT JOIN order_items ON products.id = order_items.product_id
                      LEFT JOIN orders ON order_items.order_id = orders.id AND orders.status != 'cancelled'
                      ";
            $where = [];
            if ($category_id > 0) {
                $where[] = "products.category_id = $category_id";
            }
            if (!empty($where)) {
                $query .= " WHERE " . implode(" AND ", $where);
            }
            $query .= " GROUP BY products.id";
            
            if ($sort === 'bestselling') {
                $query .= " ORDER BY total_sold DESC, products.id DESC";
            } else {
                $query .= " ORDER BY products.id DESC";
            }
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                while ($product = mysqli_fetch_assoc($result)) {
            ?>
                <div class="col-md-3" style="margin-bottom: 1.5rem;">
                    <div class="product-card">
                        <div class="product-image" style="position: relative;">
                            <?php if ((int)$product['stock'] <= 0): ?>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(220, 38, 38, 0.9); color: white; z-index: 10; padding: 0.5rem 1rem; border-radius: 99px; font-weight: 700; font-size: 0.85rem; letter-spacing: 1px; text-transform: uppercase; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); backdrop-filter: blur(4px); border: 1px solid rgba(255, 255, 255, 0.3); white-space: nowrap;">Out of Stock</div>
                            <?php elseif ((int)$product['total_sold'] >= 3 || ((int)$product['total_sold'] > 0 && $sort === 'bestselling')): ?>
                                <div style="position: absolute; top: 10px; left: 10px; background: linear-gradient(135deg, #F59E0B, #D97706); color: white; z-index: 10; padding: 0.35rem 0.75rem; border-radius: 99px; font-weight: 700; font-size: 0.75rem; letter-spacing: 1px; text-transform: uppercase; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);"><i class="bi bi-fire"></i> Top Seller</div>
                            <?php endif; ?>
                            <img src="../../admin/assets/images/products/<?php echo rawurlencode($product['image'] ?? 'default.jpg'); ?>"
                                onerror="this.onerror=null;this.src='../../admin/assets/images/products/iphone15.jpg'" alt="<?php echo htmlspecialchars($product['name']); ?>" style="<?php echo ((int)$product['stock'] <= 0) ? 'opacity: 0.5; filter: grayscale(100%);' : ''; ?>">
                        </div>
                        <div class="product-info">
                            <?php if(!empty($product['brand_name'])): ?>
                                <div class="product-brand"><?php echo htmlspecialchars($product['brand_name']); ?></div>
                            <?php endif; ?>
                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div style="font-size: 0.8rem; color: var(--text); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            <div class="product-price">$<?php echo htmlspecialchars($product['price']); ?></div>
                            <div class="product-actions">
                                <a href="details.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary icon-btn" title="View Details"><i class="bi bi-eye"></i></a>
                                <form action="../../actions/cart/add.php" method="POST" style="flex:1;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <?php if ((int)$product['stock'] > 0): ?>
                                        <button type="submit" class="btn btn-primary" style="width:100%;"><i class="bi bi-cart-plus"></i> Add</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary" style="width:100%; cursor: not-allowed; background: #F3F4F6; color: #9CA3AF; border-color: #F3F4F6; font-weight: 600; padding-left: 0; padding-right: 0;" disabled>Sold Out</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
                }
            } else {
                echo "<div style='width: 100%; text-align: center; padding: 4rem 0;'><h3>No products found in this category.</h3></div>";
            }
            ?>
        </div>
    </div>
</section>

<?php
include "../../includes/footer.php";
?>
