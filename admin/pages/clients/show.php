<?php
include "../../includes/auth.php";
include "../../../config/database.php";

if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET["id"];

$query = mysqli_prepare(
    $conn,
    "SELECT
        clients.*,
        users.name,
        users.email
     FROM clients
     INNER JOIN users
        ON clients.user_id = users.id
     WHERE clients.id = ?"
);

mysqli_stmt_bind_param($query, "i", $id);
mysqli_stmt_execute($query);
$result = mysqli_stmt_get_result($query);
$client = mysqli_fetch_assoc($result);

if (!$client) {
    header("Location: index.php");
    exit;
}

$pageTitle = "Client Profile | ShopEase Admin";
$pageHeading = "Client Profile";

include "../../includes/header.php";
?>

<div class="admin-layout">
    <?php include "../../includes/sidebar.php"; ?>

    <main class="admin-content">
        <?php include "../../includes/navbar.php"; ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">CUSTOMER DIRECTORY</span>
                <h1>Client Profile #<?= $client['id'] ?></h1>
                <p>Review contact information and account details.</p>
            </div>

            <div class="page-actions">
                <a href="index.php" class="btn btn-outline">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Clients</span>
                </a>
                <?php if (admin_can_access('pages/clients/edit.php')) { ?>
                <a href="edit.php?id=<?= $client['id'] ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i>
                    <span>Edit Client</span>
                </a>
                <?php } ?>
            </div>
        </div>

        <div class="form-card max-w-none" style="max-width: 800px;">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="admin-avatar" style="width: 64px; height: 64px; font-size: 1.75rem;">
                    <?= strtoupper(substr($client['name'], 0, 1)) ?>
                </div>
                <div>
                    <span class="badge badge-mint mb-1">CLIENT #<?= $client['id'] ?></span>
                    <h2 class="h3 font-weight-bold text-dark m-0"><?= htmlspecialchars($client['name']) ?></h2>
                    <span class="text-muted"><?= htmlspecialchars($client['email']) ?></span>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <div class="form-control bg-light"><?= htmlspecialchars($client['phone'] ?? 'N/A') ?></div>
                </div>

                <div class="form-group">
                    <label class="form-label">City</label>
                    <div class="form-control bg-light"><?= htmlspecialchars($client['city'] ?? 'N/A') ?></div>
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Address</label>
                    <div class="form-control bg-light"><?= htmlspecialchars($client['address'] ?? 'N/A') ?></div>
                </div>

                <?php if (isset($client['created_at'])) { ?>
                <div class="form-group">
                    <label class="form-label">Registration Date</label>
                    <div class="form-control bg-light"><?= htmlspecialchars($client['created_at']) ?></div>
                </div>
                <?php } ?>
            </div>
        </div>
    </main>
</div>

<?php include "../../includes/footer.php"; ?>