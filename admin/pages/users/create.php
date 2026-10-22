<?php
include '../../includes/auth.php';
require_admin_role();
$pageTitle = "Add User | ShopEase Admin";
$pageHeading = "Add User";

include '../../../config/database.php';
include_once '../../../includes/security.php';

$error = "";

if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (!in_array($role, ['Admin', 'Staff', 'Client'], true)) {
        $error = "Invalid role selected.";
    } else {
        $policy = validate_password_strength($password);
        if (!$policy['valid']) {
            $error = implode(' ', $policy['errors']);
        } else {
            $hashedPassword = hash_password($password);
            $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashedPassword, $role);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: index.php");
                exit;
            } else {
                $error = "Database Error: " . mysqli_error($conn);
            }
        }
    }
}

include '../../includes/header.php';
?>

<div class="admin-layout">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="admin-content">
        <?php include '../../includes/navbar.php'; ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">SYSTEM & ACCESS CONTROL</span>
                <h1>Add New User</h1>
                <p>Create a new administrator, staff, or client user account.</p>
            </div>
            <div class="page-actions">
                <a href="index.php" class="btn btn-outline">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Users</span>
                </a>
            </div>
        </div>

        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php } ?>

        <div class="form-card">
            <div class="form-header">
                <h2>Account Information</h2>
                <p class="text-muted">Enter login details and assign account permission role.</p>
            </div>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name <span>*</span></label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Alex Morgan" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address <span>*</span></label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="user@shopease.com" required>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password <span>*</span></label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                        <div style="font-size: 0.78rem; color: #64748B; margin-top: 0.35rem;">
                            Min 8 characters with upper, lower, number, and special symbol.
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="role" class="form-label">User Role <span>*</span></label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="">Select user role...</option>
                            <option value="Admin">Administrator (Full Access)</option>
                            <option value="Staff">Staff (Operations)</option>
                            <option value="Client">Client Account</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="index.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i>
                        <span>Create Account</span>
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>