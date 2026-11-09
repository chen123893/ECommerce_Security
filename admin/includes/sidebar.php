<?php
$currentUrl = $_SERVER["REQUEST_URI"];
?>
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-header">
        <a href="/E-Commerce-Management-System/admin/index.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <i class="bi bi-bag-check-fill"></i>
            </div>
            <span>ShopEase</span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-title">Overview</div>
        <a
            href="/E-Commerce-Management-System/admin/index.php"
            class="sidebar-link <?= (strpos($currentUrl, '/admin/index.php') !== false || $currentUrl === '/E-Commerce-Management-System/admin/') ? 'active' : '' ?>"
        >
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <div class="sidebar-section-title">Store Operations</div>
        
        <?php if (($_SESSION['admin_role'] ?? '') === 'Admin') { ?>
        <a
            href="/E-Commerce-Management-System/admin/pages/categories/index.php"
            class="sidebar-link <?= strpos($currentUrl, '/categories/') !== false ? 'active' : '' ?>"
        >
            <i class="bi bi-grid-fill"></i>
            <span>Categories</span>
        </a>

        <a
            href="/E-Commerce-Management-System/admin/pages/Brands/index.php"
            class="sidebar-link <?= strpos($currentUrl, '/Brands/') !== false || strpos($currentUrl, '/brands/') !== false ? 'active' : '' ?>"
        >
            <i class="bi bi-patch-check-fill"></i>
            <span>Brands</span>
        </a>
        <?php } ?>

        <a
            href="/E-Commerce-Management-System/admin/pages/products/index.php"
            class="sidebar-link <?= strpos($currentUrl, '/products/') !== false ? 'active' : '' ?>"
        >
            <i class="bi bi-box-seam-fill"></i>
            <span>Products</span>
        </a>

        <a
            href="/E-Commerce-Management-System/admin/pages/orders/index.php"
            class="sidebar-link <?= strpos($currentUrl, '/orders/') !== false ? 'active' : '' ?>"
        >
            <i class="bi bi-receipt"></i>
            <span>Orders</span>
        </a>

        <a
            href="/E-Commerce-Management-System/admin/pages/clients/index.php"
            class="sidebar-link <?= strpos($currentUrl, '/clients/') !== false ? 'active' : '' ?>"
        >
            <i class="bi bi-person-vcard-fill"></i>
            <span>Clients</span>
        </a>

        <?php if (($_SESSION['admin_role'] ?? '') === 'Admin') { ?>
        <div class="sidebar-section-title">Website & Organization</div>

        <a
            href="/E-Commerce-Management-System/admin/pages/team/index.php"
            class="sidebar-link <?= strpos($currentUrl, '/team/') !== false ? 'active' : '' ?>"
        >
            <i class="bi bi-people-fill"></i>
            <span>Team</span>
        </a>

        <a
            href="/E-Commerce-Management-System/admin/pages/Partners/index.php"
            class="sidebar-link <?= strpos($currentUrl, '/Partners/') !== false || strpos($currentUrl, '/partners/') !== false ? 'active' : '' ?>"
        >
            <i class="bi bi-buildings-fill"></i>
            <span>Partners</span>
        </a>

        <div class="sidebar-section-title">System & Security</div>

        <a
            href="/E-Commerce-Management-System/admin/pages/users/index.php"
            class="sidebar-link <?= strpos($currentUrl, '/users/') !== false ? 'active' : '' ?>"
        >
            <i class="bi bi-person-gear"></i>
            <span>Users</span>
        </a>

        <a
            href="/E-Commerce-Management-System/admin/pages/security/dashboard.php"
            class="sidebar-link <?= strpos($currentUrl, '/security/dashboard') !== false ? 'active' : '' ?>"
        >
            <i class="bi bi-activity"></i>
            <span>Security Monitor</span>
        </a>
        <?php } else { ?>
        <div class="sidebar-section-title">System & Security</div>
        <?php } ?>

        <a
            href="/E-Commerce-Management-System/admin/pages/security/mfa.php"
            class="sidebar-link <?= strpos($currentUrl, '/security/mfa') !== false ? 'active' : '' ?>"
        >
            <i class="bi bi-shield-lock-fill"></i>
            <span>Two-Factor Auth</span>
        </a>
    </nav>
</aside>