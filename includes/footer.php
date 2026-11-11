<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-col">
                <h3><i class="bi bi-bag-check-fill" style="color: var(--primary);"></i> ShopEase</h3>
                <p>Your ultimate destination for quality products. Simple shopping, fast delivery, and certified quality.</p>
                <div class="social-links">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-twitter-x"></i></a>
                </div>
            </div>
            
            <div class="footer-col">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="/E-Commerce-Management-System/pages/home.php">Home</a></li>
                    <li><a href="/E-Commerce-Management-System/pages/products/index.php">Products</a></li>
                    <li><a href="/E-Commerce-Management-System/pages/home.php#about">About Us</a></li>
                    <li><a href="/E-Commerce-Management-System/pages/home.php#contact">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h3>Customer Service</h3>
                <ul class="footer-links">
                    <li><a href="/E-Commerce-Management-System/pages/auth/login.php">My Account</a></li>
                    <li><a href="/E-Commerce-Management-System/pages/orders/index.php">Order History</a></li>
                    <li><a href="/E-Commerce-Management-System/pages/cart/index.php">Shopping Cart</a></li>
                    <li><a href="/E-Commerce-Management-System/pages/privacy/index.php">Privacy Notice</a></li>
                    <li><a href="/E-Commerce-Management-System/pages/security/index.php">Account Security</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h3>Contact Info</h3>
                <ul class="footer-links">
                    <li><i class="bi bi-geo-alt"></i> 123 Tech Street, City</li>
                    <li><i class="bi bi-telephone"></i> +1 234 567 890</li>
                    <li><i class="bi bi-envelope"></i> support@shopease.com</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            &copy; <?= date('Y') ?> ShopEase. All rights reserved.
        </div>
    </div>
</footer>

<script>
    // Simple mobile menu toggle just in case
    document.addEventListener('click', function(e) {
        var navLinks = document.getElementById('navLinks');
        var menuBtn = document.querySelector('.mobile-menu-btn');
        if (navLinks && navLinks.classList.contains('active') && !navLinks.contains(e.target) && !menuBtn.contains(e.target)) {
            navLinks.classList.remove('active');
        }
    });
</script>
<script src="/E-Commerce-Management-System/assets/js/team-slider.js"></script>
</body>
</html>
