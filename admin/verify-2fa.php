<?php
require_once __DIR__ . '/../includes/session.php';
start_shopease_session(true);

if (empty($_SESSION['2fa_pending']) || empty($_SESSION['2fa_pending_admin_id'])) {
    header("Location: login.php");
    exit;
}

include '../config/database.php';
include_once '../includes/security.php';
include_once '../includes/totp.php';
require_once '../includes/security_audit.php';

$adminId = (int)$_SESSION['2fa_pending_admin_id'];
$adminEmail = $_SESSION['2fa_pending_admin_email'] ?? '';
$adminName = $_SESSION['2fa_pending_admin_name'] ?? 'Admin';
$ip = get_client_ip();

$error = "";

// Fetch 2FA secret and backup codes
$stmt = mysqli_prepare($conn, "SELECT two_factor_secret, two_factor_backup_codes FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $adminId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user || empty($user['two_factor_secret'])) {
    // 2FA data missing; reset and redirect to login
    unset($_SESSION['2fa_pending'], $_SESSION['2fa_pending_admin_id']);
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['admin_name'] = $adminName;
    $_SESSION['admin_email'] = $adminEmail;
    $_SESSION['admin_role'] = $_SESSION['2fa_pending_admin_role'] ?? 'Admin';
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel_2fa'])) {
        unset(
            $_SESSION['2fa_pending'],
            $_SESSION['2fa_pending_admin_id'],
            $_SESSION['2fa_pending_admin_name'],
            $_SESSION['2fa_pending_admin_email'],
            $_SESSION['2fa_pending_admin_role']
        );
        header("Location: login.php");
        exit;
    }

    $code = trim($_POST['code'] ?? '');

    // Rate limiting for 2FA checks (protects 6-digit code against brute-force)
    $rateCheck = check_login_rate_limit($conn, "2fa_{$adminEmail}", $ip);
    if (!$rateCheck['allowed']) {
        security_log_event('mfa_blocked', 'blocked', ['identifier' => $adminEmail, 'user_id' => $adminId]);
        $error = $rateCheck['message'];
    } elseif ($code === "") {
        $error = "Please enter your 6-digit authenticator code or emergency backup code.";
    } else {
        $isValid = false;
        $isBackupCode = false;

        // 1. Try TOTP 6-digit code verification
        if (strlen($code) === 6 && ctype_digit($code)) {
            $isValid = TOTP::verifyCode($user['two_factor_secret'], $code);
        }

        // 2. If not matched, try emergency backup code
        if (!$isValid && !empty($user['two_factor_backup_codes'])) {
            $backupResult = TOTP::verifyAndConsumeBackupCode($user['two_factor_backup_codes'], $code);
            if ($backupResult !== false) {
                $isValid = true;
                $isBackupCode = true;
                // Update consumed backup code in database
                $updateStmt = mysqli_prepare($conn, "UPDATE users SET two_factor_backup_codes = ? WHERE id = ?");
                mysqli_stmt_bind_param($updateStmt, "si", $backupResult['updated_json'], $adminId);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);
            }
        }

        if ($isValid) {
            record_login_attempt($conn, "2fa_{$adminEmail}", $ip, true);
            record_login_attempt($conn, $adminEmail, $ip, true);

            session_regenerate_id(true);
            $_SESSION['admin_id'] = $adminId;
            $_SESSION['admin_name'] = $_SESSION['2fa_pending_admin_name'];
            $_SESSION['admin_email'] = $_SESSION['2fa_pending_admin_email'];
            $_SESSION['admin_role'] = $_SESSION['2fa_pending_admin_role'];

            unset(
                $_SESSION['2fa_pending'],
                $_SESSION['2fa_pending_admin_id'],
                $_SESSION['2fa_pending_admin_name'],
                $_SESSION['2fa_pending_admin_email'],
                $_SESSION['2fa_pending_admin_role']
            );

            security_log_event('mfa_success', 'success', ['identifier' => $adminEmail, 'context' => ['method' => $isBackupCode ? 'recovery' : 'authenticator']]);

            if ($isBackupCode) {
                $_SESSION['admin_auth_warning'] = "You signed in using an emergency backup recovery code. That code is now consumed.";
            }

            header("Location: index.php");
            exit;
        } else {
            record_login_attempt($conn, "2fa_{$adminEmail}", $ip, false);
            security_log_event('mfa_failure', 'failure', ['identifier' => $adminEmail, 'user_id' => $adminId]);
            $postCheck = check_login_rate_limit($conn, "2fa_{$adminEmail}", $ip);
            if (!$postCheck['allowed']) {
                $error = $postCheck['message'];
            } else {
                $error = "Invalid verification code. Please check your authenticator app and try again.";
            }
        }
    }
}

$pageTitle = "Two-Factor Verification | ShopEase Admin";
include './includes/header.php';
?>

<div class="login-page-body">
    <div class="login-card-container">
        <div class="login-brand-header">
            <div class="login-brand-icon" style="background: rgba(25, 169, 116, 0.15); color: #19A974;">
                <i class="bi bi-shield-check"></i>
            </div>
            <h1>Two-Factor <span>Verification</span></h1>
            <p>Admin Security Verification</p>
            <div class="login-badge">
                <i class="bi bi-person-check-fill"></i>
                <span><?= htmlspecialchars($adminName) ?> (<?= htmlspecialchars($adminEmail) ?>)</span>
            </div>
        </div>

        <form method="POST" action="">
            <div class="form-group mb-4">
                <label for="code" class="form-label font-weight-bold">Authentication Code <span>*</span></label>
                <div class="login-input-wrap">
                    <input
                        type="text"
                        id="code"
                        name="code"
                        class="form-control"
                        placeholder="123456 or XXXX-XXXX"
                        maxlength="19"
                        autocomplete="one-time-code"
                        inputmode="numeric"
                        autofocus
                        required
                        style="letter-spacing: 0.25em; font-size: 1.15rem; font-weight: 700; text-align: center;"
                    >
                    <i class="bi bi-phone login-input-icon"></i>
                </div>
                <div style="font-size: 0.82rem; color: #64748B; margin-top: 0.5rem; line-height: 1.4;">
                    Enter the 6-digit code from Google Authenticator, Microsoft Authenticator, or an 8-character backup recovery code.
                </div>
            </div>

            <button type="submit" name="submit" class="login-btn-primary mb-2">
                <span>Verify & Sign In</span>
                <i class="bi bi-arrow-right"></i>
            </button>

            <button type="submit" name="cancel_2fa" class="btn btn-outline w-100" style="padding: 0.75rem; border-radius: 12px; font-weight: 600; font-size: 0.9rem;">
                <i class="bi bi-x-circle me-1"></i> Cancel and Sign Out
            </button>

            <?php if (!empty($error)) { ?>
                <div class="alert alert-danger mt-4 mb-0">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span><?= htmlspecialchars($error); ?></span>
                </div>
            <?php } ?>
        </form>

        <div class="login-footer-info">
            <i class="bi bi-shield-lock-fill"></i>
            <span>RFC 6238 TOTP Protection • Rate Limited Against Brute Force</span>
        </div>
    </div>
</div>

<?php include './includes/footer.php'; ?>
