<?php
session_start();

if (isset($_SESSION['admin_id']) && in_array($_SESSION['admin_role'] ?? '', ['Admin', 'Staff'], true)) {
    header("Location: index.php");
    exit;
}

include '../config/database.php';
include_once '../includes/security.php';

$error = "";
$success = isset($_SESSION['admin_auth_success']) ? $_SESSION['admin_auth_success'] : "";
unset($_SESSION['admin_auth_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = get_client_ip();

    // 1. Validation
    if ($email === "" || $password === "") {
        $error = "Please enter your email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // 2. Rate Limiting Check
        $rateCheck = check_login_rate_limit($conn, $email, $ip);
        if (!$rateCheck['allowed']) {
            $error = $rateCheck['message'];
        } else {
            // 3. User lookup with prepared statement
            $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role, two_factor_enabled, two_factor_secret, two_factor_backup_codes FROM users WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($user && in_array($user['role'], ['Admin', 'Staff'], true) && verify_and_rehash_password($conn, $password, $user['password'], (int)$user['id'])) {
                // If 2FA is active, initiate second factor challenge
                if (!empty($user['two_factor_enabled'])) {
                    $_SESSION['2fa_pending'] = true;
                    $_SESSION['2fa_pending_admin_id'] = (int)$user['id'];
                    $_SESSION['2fa_pending_admin_name'] = $user['name'];
                    $_SESSION['2fa_pending_admin_email'] = $user['email'];
                    $_SESSION['2fa_pending_admin_role'] = $user['role'];

                    header("Location: verify-2fa.php");
                    exit;
                }

                // Normal login (2FA not enabled)
                record_login_attempt($conn, $email, $ip, true);

                session_regenerate_id(true);
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['name'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['admin_role'] = $user['role'];
                header("Location: index.php");
                exit;
            } else {
                record_login_attempt($conn, $email, $ip, false);

                // Check if account is now locked
                $postCheck = check_login_rate_limit($conn, $email, $ip);
                if (!$postCheck['allowed']) {
                    $error = $postCheck['message'];
                } else {
                    $error = "Invalid email or password.";
                }
            }
        }
    }
}

$pageTitle = "Admin Portal Sign In | ShopEase";
include './includes/header.php';
?>

<div class="login-page-body">
    <div class="login-card-container">
        <div class="login-brand-header">
            <div class="login-brand-icon">
                <i class="bi bi-bag-check-fill"></i>
            </div>
            <h1>Shop<span>Ease</span></h1>
            <p>E-Commerce Administration Portal</p>
            <div class="login-badge">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Authorized Staff & Admins Only</span>
            </div>
        </div>

        <?php if (!empty($success)) { ?>
            <div class="alert alert-success mt-2 mb-3" style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; border-radius: 12px; padding: 0.85rem 1rem; display: flex; align-items: center; gap: 0.6rem;">
                <i class="bi bi-check-circle-fill" style="color: #10B981; font-size: 1.1rem;"></i>
                <span><?= htmlspecialchars($success); ?></span>
            </div>
        <?php } ?>

        <form method="POST" action="">
            <div class="form-group mb-3">
                <label for="email" class="form-label font-weight-bold">Email Address <span>*</span></label>
                <div class="login-input-wrap">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="admin@shopease.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>"
                        autocomplete="email"
                        required
                    >
                    <i class="bi bi-envelope login-input-icon"></i>
                </div>
            </div>

            <div class="form-group mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label font-weight-bold mb-0">Password <span>*</span></label>
                    <a href="/E-Commerce-Management-System/pages/auth/forgot-password.php" style="font-size: 0.82rem; color: #19A974; text-decoration: none; font-weight: 600;">
                        Forgot password?
                    </a>
                </div>
                <div class="login-input-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <i class="bi bi-lock login-input-icon"></i>
                    <button type="button" class="toggle-password-btn" id="togglePassword" aria-label="Toggle password visibility" title="Show/Hide Password">
                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="submit" class="login-btn-primary">
                <span>Sign In to Dashboard</span>
                <i class="bi bi-arrow-right"></i>
            </button>

            <!-- Error message located cleanly at bottom of form below input controls & button -->
            <?php if (!empty($error)) { ?>
                <div class="alert alert-danger mt-4 mb-0">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span><?= htmlspecialchars($error); ?></span>
                </div>
            <?php } ?>
        </form>

        <div class="login-footer-info">
            <i class="bi bi-shield-check"></i>
            <span>Argon2id / bcrypt Protection • Multi-Factor Authentication Available</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    if (togglePasswordBtn && passwordInput && togglePasswordIcon) {
        togglePasswordBtn.addEventListener('click', function() {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            togglePasswordIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }
});
</script>

<?php include './includes/footer.php'; ?>