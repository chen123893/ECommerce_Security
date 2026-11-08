<?php
include "../config/database.php";
include "../includes/header.php";
include "../includes/navbar.php";
?>

<!-- ================= HERO ================= -->
<section class="home-hero">
    <div class="container home-hero-inner">
        <div class="home-hero-copy">
            <div class="home-hero-kicker-row"><span class="home-eyebrow"><i class="bi bi-stars"></i> Curated tech, delivered simply</span><span class="home-hero-index">01 / 04</span></div>
            <h1>Better tech for the way you live.</h1>
            <p>Discover reliable devices, thoughtful accessories, and everyday upgrades selected for modern life.</p>
            <div class="home-hero-actions">
                <a href="/E-Commerce-Management-System/pages/products/index.php" class="btn btn-primary">Explore products <i class="bi bi-arrow-up-right"></i></a>
                <a href="#categories" class="home-text-link">Browse categories <i class="bi bi-arrow-down"></i></a>
            </div>
        </div>
        <div class="home-hero-media">
            <span class="home-hero-label">ShopEase / New arrivals</span>
            <img src="../admin/assets/images/hero-tech.jpg" alt="Smartphone on a modern laptop workspace">
            <div class="home-hero-caption"><i class="bi bi-lightning-charge-fill"></i><span><strong>Made for your day</strong><small>Smart picks, clear prices</small></span></div>
            <div class="home-hero-float"><span class="home-hero-float-icon"><i class="bi bi-stars"></i></span><span><strong>New season</strong><small>Tech worth keeping</small></span></div>
        </div>
    </div>
</section>

<div class="home-trust-strip">
    <div class="container home-trust-grid">
        <div><i class="bi bi-truck"></i><span><strong>Fast delivery</strong><small>Across the city</small></span></div>
        <div><i class="bi bi-shield-check"></i><span><strong>Original products</strong><small>Quality you can trust</small></span></div>
        <div><i class="bi bi-headset"></i><span><strong>Helpful support</strong><small>Here when you need us</small></span></div>
    </div>
</div>

<!-- ================= CATEGORIES ================= -->
<section id="categories" class="section">
    <div class="container">

        <div class="section-title">
            <h2>Shop by Category</h2>
            <p>Find what you're looking for easily</p>
        </div>

        <div class="row">

            <?php
            $categoryQuery = "SELECT * FROM categories ORDER BY id ASC LIMIT 4";
            $categoryResult = mysqli_query($conn, $categoryQuery);

            if ($categoryResult && mysqli_num_rows($categoryResult) > 0) {

                while ($category = mysqli_fetch_assoc($categoryResult)) {

                    $image = !empty($category['image'])
                        ? $category['image']
                        : 'default.jpg';
            ?>

                <div class="col-md-3">

                <a href="/E-Commerce-Management-System/pages/products/index.php?category=<?php echo $category['id']; ?>"
                    class="category-card">

                        <div class="category-image">

                            <img
                                src="/E-Commerce-Management-System/admin/assets/images/categories/<?php echo htmlspecialchars($image); ?>"
                                alt="<?php echo htmlspecialchars($category['name']); ?>"
                            >

                        </div>

                        <h3>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </h3>

                    </a>

                </div>

            <?php
                }
            }
            ?>

        </div>
<div style="text-align: center; margin-top: 3rem;">
            <a href="/E-Commerce-Management-System/pages/categories/index.php"
               class="btn btn-primary btn-lg">
                View All Categories
            </a>
        </div>




    </div>
</section>

<!-- ================= FEATURED PRODUCTS ================= -->
<section class="section" style="background: var(--background);">
    <div class="container">
        <div class="section-title">
            <h2>Best Sellers</h2>
            <p>Our most popular items this week</p>
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
                      GROUP BY products.id
                      ORDER BY total_sold DESC, products.id DESC
                      LIMIT 4";
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                while ($product = mysqli_fetch_assoc($result)) {
            ?>
                <div class="col-md-3">
                    <div class="product-card">
                        <div class="product-image" style="position: relative;">
                            <?php if ((int)$product['stock'] <= 0): ?>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(220, 38, 38, 0.9); color: white; z-index: 10; padding: 0.5rem 1rem; border-radius: 99px; font-weight: 700; font-size: 0.85rem; letter-spacing: 1px; text-transform: uppercase; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); backdrop-filter: blur(4px); border: 1px solid rgba(255, 255, 255, 0.3); white-space: nowrap;">Out of Stock</div>
                            <?php elseif ((int)$product['total_sold'] > 0): ?>
                                <div style="position: absolute; top: 10px; left: 10px; background: linear-gradient(135deg, #F59E0B, #D97706); color: white; z-index: 10; padding: 0.35rem 0.75rem; border-radius: 99px; font-weight: 700; font-size: 0.75rem; letter-spacing: 1px; text-transform: uppercase; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);"><i class="bi bi-fire"></i> Top Seller</div>
                            <?php endif; ?>
                            <img src="../admin/assets/images/products/<?php echo rawurlencode($product['image'] ?? 'default.jpg'); ?>"
                                onerror="this.onerror=null;this.src='../admin/assets/images/products/iphone15.jpg'" alt="<?php echo htmlspecialchars($product['name']); ?>" style="<?php echo ((int)$product['stock'] <= 0) ? 'opacity: 0.5; filter: grayscale(100%);' : ''; ?>">
                        </div>
                        <div class="product-info">
                            <?php if(!empty($product['brand_name'])): ?>
                                <div class="product-brand"><?php echo htmlspecialchars($product['brand_name']); ?></div>
                            <?php endif; ?>
                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-price">$<?php echo htmlspecialchars($product['price']); ?></div>
                            <div class="product-actions">
                                <a href="/E-Commerce-Management-System/pages/products/details.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary icon-btn" title="View Details"><i class="bi bi-eye"></i></a>
                                <form action="/E-Commerce-Management-System/actions/cart/add.php" method="POST" style="flex:1;">
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
                echo "<p style='text-align:center;width:100%;'>No products found.</p>";
            }
            ?>
        </div>
        <div style="text-align: center; margin-top: 3rem;">
            <a href="/E-Commerce-Management-System/pages/products/index.php" class="btn btn-primary btn-lg">View All Products</a>
        </div>
    </div>
</section>

<!-- ================= BRANDS ================= -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Top Brands</h2>
            <p>We work with the best in the industry</p>
        </div>
        <div class="row">
            <?php
            $query = "SELECT * FROM brands LIMIT 4";
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                while ($brand = mysqli_fetch_assoc($result)) {
            ?>
                <div class="col-md-3">
                    <div class="brand-card card">
                        <div class="brand-image">
                            <img src="../admin/assets/images/brands/<?php echo rawurlencode($brand['logo'] ?? 'default.png'); ?>"
                                onerror="this.hidden=true" alt="<?php echo htmlspecialchars($brand['name']); ?>">
                        </div>
                        <h4 style="margin-top: 1rem; margin-bottom: 0; font-size: 1.1rem;"><?php echo htmlspecialchars($brand['name']); ?></h4>
                    </div>
                </div>
            <?php
                }
            } else {
                echo "<p style='text-align:center;width:100%;'>No brands found.</p>";
            }
            ?>
        </div>
<div style="text-align: center; margin-top: 3rem;">
    <a href="/E-Commerce-Management-System/pages/brands/index.php"
    class="btn btn-primary btn-lg">
        View All Brands
    </a>
</div>

    </div>
</section>

<!-- ================= WHY CHOOSE US ================= -->
<section class="section" style="background: var(--background);">
    <div class="container">
        <div class="section-title">
            <h2>Why Choose Us</h2>
            <p>What makes ShopEase different</p>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="card" style="padding: 2rem; text-align: center; height: 100%;">
                    <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem;"><i class="bi bi-truck"></i></div>
                    <h4>Fast Delivery</h4>
                    <p style="color: var(--text); font-size: 0.9rem;">We ensure your products arrive as quickly as possible with our express delivery partners.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="padding: 2rem; text-align: center; height: 100%;">
                    <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem;"><i class="bi bi-shield-check"></i></div>
                    <h4>100% Secure</h4>
                    <p style="color: var(--text); font-size: 0.9rem;">Your payments are protected with industry-leading encryption and security protocols.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="padding: 2rem; text-align: center; height: 100%;">
                    <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem;"><i class="bi bi-award"></i></div>
                    <h4>Original Products</h4>
                    <p style="color: var(--text); font-size: 0.9rem;">We only sell 100% genuine and original products sourced directly from manufacturers.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="padding: 2rem; text-align: center; height: 100%;">
                    <div style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem;"><i class="bi bi-headset"></i></div>
                    <h4>24/7 Support</h4>
                    <p style="color: var(--text); font-size: 0.9rem;">Our dedicated customer support team is available around the clock to help you.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= PARTNERS ================= -->
<section class="section">

    <div class="container">

        <div class="section-title">
            <h2>Our Partners</h2>
            <p>Trusted companies we collaborate with</p>
        </div>


        <div class="partners-grid">

            <?php

            $query = "SELECT * FROM partners LIMIT 3";
            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {

                while ($partner = mysqli_fetch_assoc($result)) {

            ?>

                <div class="partner-card">

                    <div class="partner-image">

                        <img
                            src="../admin/assets/images/partners/<?php echo htmlspecialchars($partner['logo']); ?>"
                            alt="<?php echo htmlspecialchars($partner['name']); ?>"
                        >

                    </div>

                </div>

            <?php

                }

            } else {

                echo "<p style='text-align:center;width:100%;'>
                        No partners found.
                      </p>";

            }

            ?>

        </div>


        <div class="partners-button">

            <a
                href="/E-Commerce-Management-System/pages/partners/index.php"
                class="btn btn-primary btn-lg"
            >
                View All Partners
            </a>

        </div>


    </div>

</section>

<!-- ================= TEAM ================= -->

<section class="section" style="background: var(--background);">

    <div class="container">

        <div class="section-title">
            <h2>Meet Our Team</h2>
            <p>The people behind ShopEase</p>
        </div>

        <div class="team-carousel">

            <div class="team-carousel-track" id="teamCarouselTrack">

                <?php
                $query = "SELECT * FROM team ORDER BY id ASC";
                $result = mysqli_query($conn, $query);

                if ($result && mysqli_num_rows($result) > 0) {

                    while ($member = mysqli_fetch_assoc($result)) {

                        $teamImage = !empty($member['image'])
                            ? $member['image']
                            : 'ahmed.jpg';
                ?>

                    <div class="team-carousel-slide">

                        <div class="team-card">

                            <div class="team-image">

                                <img
                                    src="/E-Commerce-Management-System/admin/assets/images/team/<?php echo htmlspecialchars($teamImage); ?>"
                                    alt="<?php echo htmlspecialchars($member['name']); ?>"
                                >

                            </div>

                            <div class="team-info">

                                <h4>
                                    <?php echo htmlspecialchars($member['name']); ?>
                                </h4>

                                <div class="team-position">
                                    <?php echo htmlspecialchars($member['position'] ?? 'Team Member'); ?>
                                </div>

                                <p>
                                    <?php echo htmlspecialchars($member['description'] ?? ''); ?>
                                </p>

                                <div class="team-socials">

                                    <a href="<?php echo htmlspecialchars($member['facebook'] ?? '#'); ?>"
                                       class="icon-btn">
                                        <i class="bi bi-facebook"></i>
                                    </a>

                                    <a href="<?php echo htmlspecialchars($member['instagram'] ?? '#'); ?>"
                                       class="icon-btn">
                                        <i class="bi bi-instagram"></i>
                                    </a>

                                    <a href="<?php echo htmlspecialchars($member['linkedin'] ?? '#'); ?>"
                                       class="icon-btn">
                                        <i class="bi bi-linkedin"></i>
                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php
                    }
                }
                ?>

            </div>

        </div>

    </div>

</section>

<!-- ================= ABOUT US ================= -->
<section id="about" class="section">
    <div class="container">
        <div class="row align-items-center about-layout">
            <div class="col-md-6">
                <img class="about-image" src="../admin/assets/images/about-tech.jpg" alt="Laptop on a modern workspace">
            </div>
            <div class="col-md-6" style="padding-left: 2rem;">
                <div style="color: var(--primary); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem;">About Us</div>
                <h2 style="font-size: 2.5rem; margin-bottom: 1.5rem;">Your Ultimate Destination for Smart Tech</h2>
                <p style="font-size: 1.1rem; margin-bottom: 1.5rem; color: var(--text);">Welcome to ShopEase! We specialize in bringing you the latest smartphones, premium wireless earbuds, and cutting-edge electronic devices. We are committed to offering 100% original products, competitive prices, and a seamless shopping experience.</p>
                <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500;"><i class="bi bi-check-circle-fill text-primary" style="color: var(--primary);"></i> Top Quality</div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500;"><i class="bi bi-check-circle-fill text-primary" style="color: var(--primary);"></i> Fast Support</div>
                </div>
                <a href="#contact" class="btn btn-primary">Contact Us</a>
            </div>
        </div>
    </div>
</section>

<!-- ================= CONTACT US ================= -->
<section id="contact" class="section contact-section">
    <div class="container">
        <div class="contact-heading">
            <div>
                <span class="home-eyebrow">Let's talk</span>
                <h2>Have a question? <span>We are here to help.</span></h2>
            </div>
            <p>Whether you need help choosing a product or tracking an order, our team is ready to help.</p>
        </div>
        <div class="contact-layout">
            <div class="contact-info">
                <div class="contact-info-item"><i class="bi bi-envelope"></i><span><small>Email us</small><strong>support@shopease.com</strong></span></div>
                <div class="contact-info-item"><i class="bi bi-telephone"></i><span><small>Call us</small><strong>+1 234 567 890</strong></span></div>
                <div class="contact-info-item"><i class="bi bi-geo-alt"></i><span><small>Visit us</small><strong>123 Tech Street, City</strong></span></div>
                <div class="contact-response"><i class="bi bi-clock"></i> Usually replies within one business day</div>
            </div>
            <div class="card contact-form-card">
                <form action="#" method="POST">
                    <div class="contact-form-grid">
                        <div><label class="form-label">Your Name</label><input type="text" class="form-control" name="name" required placeholder="Your full name"></div>
                        <div><label class="form-label">Your Email</label><input type="email" class="form-control" name="email" required placeholder="you@example.com"></div>
                    </div>
                    <div class="contact-form-field"><label class="form-label">Subject</label><input type="text" class="form-control" name="subject" required placeholder="How can we help?"></div>
                    <div class="contact-form-field"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="5" required placeholder="Tell us a little more..."></textarea></div>
                    <button type="submit" class="btn btn-primary contact-submit">Send message <i class="bi bi-arrow-up-right"></i></button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php
include "../includes/footer.php";
?>
