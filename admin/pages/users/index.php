<?php
include '../../includes/auth.php';
require_admin_role();
$pageTitle = "Users | ShopEase Admin";
$pageHeading = "Users";

include '../../includes/header.php';
include '../../../config/database.php';
include_once '../../../includes/security.php';

$query = 'SELECT * FROM users ORDER BY id DESC;';
$result = mysqli_query($conn, $query);

$usersList = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $usersList[] = $row;
    }
}
?>

<div class="admin-layout">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="admin-content">
        <?php include '../../includes/navbar.php'; ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">SYSTEM & ACCESS CONTROL</span>
                <h1>System Users</h1>
                <p>Manage administrative accounts, staff, and customer accounts.</p>
            </div>

            <div class="page-actions">
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i>
                    <span>Add User</span>
                </a>
            </div>
        </div>

        <?php if (!empty($usersList)) { ?>
            <div class="entity-card-grid">
                <?php foreach ($usersList as $user) { 
                    $roleClass = 'badge-mint';
                    if ($user['role'] === 'Admin') $roleClass = 'badge-mint';
                    elseif ($user['role'] === 'Staff') $roleClass = 'badge-dark';
                    else $roleClass = 'badge-info';
                ?>
                    <article class="entity-card">
                        <div class="entity-card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="admin-avatar" style="width: 48px; height: 48px; font-size: 1.25rem;">
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge <?= $roleClass ?>"><?= htmlspecialchars($user['role'] ?? 'User') ?></span>
                                    <?php if (!empty($user['two_factor_enabled'])) { ?>
                                        <span class="badge" style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; font-size: 0.7rem;" title="Two-Factor Authentication is Active">
                                            <i class="bi bi-shield-check"></i> 2FA
                                        </span>
                                    <?php } ?>
                                </div>
                            </div>

                            <span class="entity-subtitle">USER #<?= $user['id']; ?></span>
                            <h2 class="entity-title mb-1"><?= htmlspecialchars($user['name']); ?></h2>

                            <div class="info-row mb-3">
                                <i class="bi bi-envelope"></i>
                                <span class="text-truncate"><?= htmlspecialchars($user['email']); ?></span>
                            </div>

                            <div class="entity-card-footer">
                                <span class="text-muted small">ID: <?= $user['id'] ?></span>
                                <div class="icon-action-group">
                                    <a href="edit.php?id=<?= $user['id']; ?>" class="icon-action action-edit" aria-label="Edit user" title="Edit User">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="delete.php?id=<?= $user['id']; ?>" class="icon-action action-delete" aria-label="Delete user" title="Delete User">
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
                    <i class="bi bi-person-gear"></i>
                </div>
                <h3>No Users Found</h3>
                <p>There are currently no user accounts in the system.</p>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i>
                    <span>Add User</span>
                </a>
            </div>
        <?php } ?>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>