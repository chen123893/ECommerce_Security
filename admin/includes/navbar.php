<nav class="admin-navbar">
    <div class="navbar-left">
        <button
            type="button"
            class="sidebar-toggle"
            aria-label="Toggle navigation"
            aria-controls="admin-sidebar"
            aria-expanded="false"
        >
            <i class="bi bi-list"></i>
        </button>

        <div class="page-title-wrap">
            <h1><?= htmlspecialchars($pageHeading ?? ($pageTitle ?? 'Dashboard')) ?></h1>
        </div>
    </div>

    <div class="navbar-right">
        <a
            href="/E-Commerce-Management-System/index.php"
            class="website-link"
            target="_blank"
            title="View Live Website"
        >
            <i class="bi bi-globe2"></i>
            <span>View Website</span>
        </a>

        <div class="admin-profile" tabindex="0">
            <div class="admin-avatar">
                <?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?>
            </div>

            <div class="admin-info">
                <span class="admin-name">
                    <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
                </span>
                <span class="admin-role">
                    <?= htmlspecialchars($_SESSION['admin_role'] ?? 'Admin') ?>
                </span>
            </div>

            <i class="bi bi-chevron-down ms-1 text-muted" style="font-size: 0.75rem;"></i>

            <div class="profile-dropdown-menu">
                <a href="/E-Commerce-Management-System/admin/index.php" class="dropdown-item">
                    <i class="bi bi-person"></i>
                    Profile & Overview
                </a>
                <a href="/E-Commerce-Management-System/admin/pages/security/mfa.php" class="dropdown-item">
                    <i class="bi bi-shield-lock"></i>
                    Two-Factor Auth
                </a>
                <a href="/E-Commerce-Management-System/admin/logout.php" class="dropdown-item logout">
                    <i class="bi bi-box-arrow-right"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>
</nav>
