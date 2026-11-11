<?php
include '../../includes/auth.php';
$pageTitle = "Clients | ShopEase Admin";
$pageHeading = "Clients";

include '../../includes/header.php';
include '../../../config/database.php';

$query = 'SELECT
            clients.id,
            users.name,
            users.email,
            clients.phone,
            clients.city,
            clients.address,
            clients.created_at
          FROM clients
          JOIN users ON clients.user_id = users.id
          ORDER BY clients.id DESC;';

$result = mysqli_query($conn, $query);

$clientsList = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $clientsList[] = $row;
    }
}
?>

<div class="admin-layout">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="admin-content">
        <?php include '../../includes/navbar.php'; ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">CUSTOMER RELATIONS</span>
                <h1>Clients</h1>
                <p>Manage customer profiles, addresses, and contact details.</p>
            </div>

            <div class="page-actions">
                <?php if (admin_can_access('pages/clients/create.php')) { ?>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>Add Client</span>
                </a>
                <?php } ?>
            </div>
        </div>

        <?php if (!empty($clientsList)) { ?>
            <div class="entity-card-grid">
                <?php foreach ($clientsList as $client) { ?>
                    <article class="entity-card">
                        <div class="entity-card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="admin-avatar" style="width: 48px; height: 48px; font-size: 1.25rem;">
                                    <?= strtoupper(substr($client['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h2 class="entity-title m-0"><?= htmlspecialchars($client['name']); ?></h2>
                                    <span class="entity-subtitle m-0">CLIENT #<?= $client['id']; ?></span>
                                </div>
                            </div>

                            <div class="info-row">
                                <i class="bi bi-envelope"></i>
                                <span class="text-truncate"><?= htmlspecialchars($client['email']); ?></span>
                            </div>

                            <div class="info-row">
                                <i class="bi bi-telephone"></i>
                                <span><?= htmlspecialchars($client['phone'] ?? 'N/A'); ?></span>
                            </div>

                            <div class="info-row">
                                <i class="bi bi-geo-alt"></i>
                                <span><?= htmlspecialchars(($client['city'] ?? '') . ($client['address'] ? ', ' . $client['address'] : '')); ?></span>
                            </div>

                            <div class="entity-card-footer">
                                <span class="badge badge-mint">Registered Client</span>
                                <div class="icon-action-group">
                                    <a href="show.php?id=<?= $client['id']; ?>" class="icon-action action-view" aria-label="View client" title="View Client Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (admin_can_access('pages/clients/edit.php')) { ?>
                                    <a href="edit.php?id=<?= $client['id']; ?>" class="icon-action action-edit" aria-label="Edit client" title="Edit Client">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php } ?>
                                    <a href="delete.php?id=<?= $client['id']; ?>" class="icon-action action-delete" aria-label="Delete client" title="Delete Client">
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
                    <i class="bi bi-person-vcard"></i>
                </div>
                <h3>No Clients Found</h3>
                <p>There are currently no customer accounts registered in your store.</p>
                <?php if (admin_can_access('pages/clients/create.php')) { ?>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>Add Client</span>
                </a>
                <?php } ?>
            </div>
        <?php } ?>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>