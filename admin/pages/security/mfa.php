<?php
include '../../includes/auth.php';
require_admin();

$pageTitle = "Two-Factor Authentication | ShopEase Admin";
$pageHeading = "Two-Factor Authentication (MFA)";

include '../../../config/database.php';
include_once '../../../includes/security.php';
include_once '../../../includes/totp.php';

$userId = (int)$_SESSION['admin_id'];

// Fetch current user security details
$stmt = mysqli_prepare($conn, "SELECT id, name, email, role, password, two_factor_enabled, two_factor_secret, two_factor_backup_codes FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$error = "";
$success = "";
$showBackupCodes = false;
$plainBackupCodes = [];

// Handle 2FA Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'enable_confirm') {
        $secret = $_SESSION['mfa_setup_secret'] ?? '';
        $code = trim($_POST['code'] ?? '');
        $backupCodes = $_SESSION['mfa_setup_backup_codes'] ?? null;

        if (empty($secret) || empty($backupCodes)) {
            $error = "Session expired during MFA setup. Please refresh and try again.";
        } elseif (!TOTP::verifyCode($secret, $code)) {
            $error = "The verification code you entered is invalid. Please make sure the clock on your device is accurate and try again.";
        } else {
            // Enable 2FA in database
            $enableStmt = mysqli_prepare($conn, "UPDATE users SET two_factor_enabled = 1, two_factor_secret = ?, two_factor_backup_codes = ? WHERE id = ?");
            mysqli_stmt_bind_param($enableStmt, "ssi", $secret, $backupCodes['hashed_records'], $userId);
            if (mysqli_stmt_execute($enableStmt)) {
                mysqli_stmt_close($enableStmt);
                security_log_event('mfa_enabled', 'success', ['target_type' => 'user', 'target_id' => $userId]);
                $success = "Two-Factor Authentication has been successfully enabled for your administrative account!";
                $user['two_factor_enabled'] = 1;
                $user['two_factor_secret'] = $secret;
                $user['two_factor_backup_codes'] = $backupCodes['hashed_records'];
                $showBackupCodes = true;
                $plainBackupCodes = $backupCodes['plain_codes'];
                unset($_SESSION['mfa_setup_secret'], $_SESSION['mfa_setup_backup_codes']);
            } else {
                mysqli_stmt_close($enableStmt);
                $error = "Failed to update security profile in database.";
            }
        }
    } elseif ($action === 'disable_2fa') {
        $currentPassword = $_POST['current_password'] ?? '';
        $code = trim($_POST['code'] ?? '');

        if (!password_verify($currentPassword, $user['password'])) {
            $error = "Incorrect current password. Two-factor authentication was not disabled.";
        } elseif (!TOTP::verifyCode($user['two_factor_secret'], $code)) {
            $error = "Invalid 6-digit authenticator code. MFA disabling requires confirmation from your authenticator device.";
        } else {
            $disableStmt = mysqli_prepare($conn, "UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL, two_factor_backup_codes = NULL WHERE id = ?");
            mysqli_stmt_bind_param($disableStmt, "i", $userId);
            if (mysqli_stmt_execute($disableStmt)) {
                mysqli_stmt_close($disableStmt);
                security_log_event('mfa_disabled', 'success', ['target_type' => 'user', 'target_id' => $userId]);
                $success = "Two-Factor Authentication has been successfully disabled.";
                $user['two_factor_enabled'] = 0;
                $user['two_factor_secret'] = null;
                $user['two_factor_backup_codes'] = null;
            } else {
                mysqli_stmt_close($disableStmt);
                $error = "Failed to disable MFA in database.";
            }
        }
    } elseif ($action === 'regenerate_backup_codes') {
        $currentPassword = $_POST['current_password'] ?? '';
        if (!password_verify($currentPassword, $user['password'])) {
            $error = "Incorrect current password. Backup codes were not regenerated.";
        } else {
            $newBackupCodes = TOTP::generateBackupCodes(8);
            $regenStmt = mysqli_prepare($conn, "UPDATE users SET two_factor_backup_codes = ? WHERE id = ?");
            mysqli_stmt_bind_param($regenStmt, "si", $newBackupCodes['hashed_records'], $userId);
            if (mysqli_stmt_execute($regenStmt)) {
                mysqli_stmt_close($regenStmt);
                security_log_event('mfa_backup_codes_regenerated', 'success', ['target_type' => 'user', 'target_id' => $userId]);
                $success = "New emergency backup recovery codes have been generated. Previous backup codes are now invalidated.";
                $user['two_factor_backup_codes'] = $newBackupCodes['hashed_records'];
                $showBackupCodes = true;
                $plainBackupCodes = $newBackupCodes['plain_codes'];
            } else {
                mysqli_stmt_close($regenStmt);
                $error = "Failed to regenerate backup codes.";
            }
        }
    }
}

// If MFA is not yet enabled, prepare a new secret & backup codes for setup
if (empty($user['two_factor_enabled'])) {
    if (empty($_SESSION['mfa_setup_secret'])) {
        $_SESSION['mfa_setup_secret'] = TOTP::generateSecret(16);
        $_SESSION['mfa_setup_backup_codes'] = TOTP::generateBackupCodes(8);
    }
    $setupSecret = $_SESSION['mfa_setup_secret'];
    $otpAuthUri = TOTP::getOtpAuthUri($user['email'], $setupSecret, 'ShopEase');
}

$remainingBackupCodes = TOTP::countRemainingBackupCodes($user['two_factor_backup_codes'] ?? null);

include '../../includes/header.php';
?>

<div class="admin-layout">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="admin-content">
        <?php include '../../includes/navbar.php'; ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">ACCOUNT SECURITY</span>
                <h1>Two-Factor Authentication (MFA)</h1>
                <p>Protect your administrative account with time-based one-time passwords (TOTP).</p>
            </div>
            <div class="page-actions">
                <a href="/E-Commerce-Management-System/admin/index.php" class="btn btn-outline">
                    <i class="bi bi-arrow-left"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>

        <?php if (!empty($success)) { ?>
            <div class="alert alert-success mb-4" style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; border-radius: 12px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
                <i class="bi bi-check-circle-fill" style="color: #10B981; font-size: 1.3rem;"></i>
                <span style="font-weight: 500;"><?= htmlspecialchars($success) ?></span>
            </div>
        <?php } ?>

        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger mb-4" style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; border-radius: 12px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
                <i class="bi bi-exclamation-triangle-fill" style="color: #EF4444; font-size: 1.3rem;"></i>
                <span style="font-weight: 500;"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php } ?>

        <?php if ($showBackupCodes && !empty($plainBackupCodes)) { ?>
            <!-- Backup Codes Modal / Alert Box -->
            <div class="card mb-4" style="background: #FFFBEB; border: 1.5px solid #FDE68A; border-radius: 16px; padding: 1.5rem;">
                <div class="d-flex align-items-center gap-2 mb-2" style="color: #92400E; font-weight: 700; font-size: 1.1rem;">
                    <i class="bi bi-key-fill" style="font-size: 1.3rem;"></i>
                    <span>Important: Save Your Emergency Recovery Backup Codes</span>
                </div>
                <p style="color: #78350F; font-size: 0.9rem; margin-bottom: 1rem;">
                    If you ever lose access to your authenticator app, each of these backup codes can be used once to access your admin account. Store them in a secure place (such as a password manager).
                </p>
                <div style="background: #FFFFFF; border: 1px solid #FCD34D; border-radius: 10px; padding: 1.25rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.75rem; font-family: monospace; font-size: 1.05rem; font-weight: 700; letter-spacing: 0.1em; color: #1E293B;">
                    <?php foreach ($plainBackupCodes as $bCode) { ?>
                        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 0.4rem 0.6rem; border-radius: 6px; text-align: center;">
                            <?= htmlspecialchars($bCode) ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <?php if (!empty($user['two_factor_enabled'])) { ?>
            <!-- MFA Active State -->
            <div class="form-card mb-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 54px; height: 54px; border-radius: 16px; background: #ECFDF5; border: 1px solid #A7F3D0; color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <div>
                            <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.25rem; color: #0F172A;">Two-Factor Authentication is Active</h2>
                            <span class="badge badge-mint"><i class="bi bi-check-circle me-1"></i> Enhanced Protection Enabled</span>
                        </div>
                    </div>
                    <div style="font-size: 0.9rem; color: #64748B;">
                        <strong><?= $remainingBackupCodes ?></strong> unused emergency backup code(s) remaining.
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 1.25rem;">
                            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem;"><i class="bi bi-arrow-repeat me-1 text-primary"></i> Regenerate Backup Codes</h3>
                            <p style="font-size: 0.85rem; color: #64748B; margin-bottom: 1rem;">
                                Invalidate previous emergency codes and create 8 new single-use recovery codes.
                            </p>
                            <form method="POST">
                                <input type="hidden" name="action" value="regenerate_backup_codes">
                                <div class="mb-2">
                                    <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required style="font-size: 0.88rem;">
                                </div>
                                <button type="submit" class="btn btn-outline" style="font-size: 0.88rem; width: 100%;">
                                    <i class="bi bi-key"></i> Generate 8 New Codes
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div style="background: #FEF2F2; border: 1px solid #FEE2E2; border-radius: 12px; padding: 1.25rem;">
                            <h3 style="font-size: 1rem; font-weight: 700; color: #991B1B; margin-bottom: 0.5rem;"><i class="bi bi-shield-slash me-1"></i> Disable Two-Factor Authentication</h3>
                            <p style="font-size: 0.85rem; color: #7F1D1D; margin-bottom: 1rem;">
                                Disabling MFA reduces account security to password-only verification.
                            </p>
                            <form method="POST">
                                <input type="hidden" name="action" value="disable_2fa">
                                <div class="mb-2">
                                    <input type="password" name="current_password" class="form-control mb-2" placeholder="Enter current password" required style="font-size: 0.88rem;">
                                    <input type="text" name="code" class="form-control" placeholder="Current 6-digit 2FA code" maxlength="6" required style="font-size: 0.88rem; letter-spacing: 0.1em;">
                                </div>
                                <button type="submit" class="btn btn-danger" style="font-size: 0.88rem; width: 100%; background: #DC2626; border: none;" onclick="return confirm('Are you sure you want to disable Two-Factor Authentication?');">
                                    <i class="bi bi-shield-x"></i> Disable 2FA
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php } else { ?>
            <!-- MFA Setup Flow -->
            <div class="form-card">
                <div class="form-header border-bottom pb-3 mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 50px; height: 50px; border-radius: 14px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">
                            <i class="bi bi-shield-exclamation"></i>
                        </div>
                        <div>
                            <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0; color: #0F172A;">Setup Authenticator App (RFC 6238 TOTP)</h2>
                            <p class="text-muted mb-0" style="font-size: 0.88rem;">Follow the two simple steps below to secure your administrator login.</p>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Step 1: Scan QR or Enter Key -->
                    <div class="col-lg-6">
                        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 16px; padding: 1.5rem; height: 100%;">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span style="width: 28px; height: 28px; border-radius: 50%; background: #19A974; color: #FFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">1</span>
                                <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; color: #1E293B;">Scan QR Code in Authenticator App</h3>
                            </div>
                            <p style="font-size: 0.88rem; color: #64748B; margin-bottom: 1.25rem;">
                                Open <strong>Google Authenticator</strong>, <strong>Microsoft Authenticator</strong>, <strong>Authy</strong>, or your preferred authenticator app, and scan the QR code below:
                            </p>

                            <!-- QR Code Container -->
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 12px; padding: 1.25rem; max-width: 240px; margin: 0 auto 1.25rem;">
                                <div id="qrcode"></div>
                                <div style="font-size: 0.75rem; color: #94A3B8; margin-top: 0.5rem; font-weight: 600;">ShopEase Admin MFA</div>
                            </div>

                            <!-- Manual Key Entry -->
                            <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 10px; padding: 0.85rem 1rem;">
                                <div style="font-size: 0.75rem; font-weight: 700; color: #64748B; text-transform: uppercase; margin-bottom: 0.25rem;">
                                    Or enter key manually:
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <code id="setupSecretCode" style="font-size: 1.1rem; font-weight: 700; color: #0F172A; letter-spacing: 0.15em;">
                                        <?= htmlspecialchars(chunk_split($setupSecret, 4, ' ')) ?>
                                    </code>
                                    <button type="button" class="btn btn-sm btn-outline" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($setupSecret) ?>'); this.innerText='Copied!';" style="font-size: 0.78rem; padding: 0.25rem 0.6rem;">
                                        Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Confirm Code -->
                    <div class="col-lg-6">
                        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 16px; padding: 1.5rem; height: 100%;">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span style="width: 28px; height: 28px; border-radius: 50%; background: #19A974; color: #FFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">2</span>
                                <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; color: #1E293B;">Verify & Activate</h3>
                            </div>
                            <p style="font-size: 0.88rem; color: #64748B; margin-bottom: 1.25rem;">
                                Enter the 6-digit confirmation code displayed on your authenticator app to complete setup:
                            </p>

                            <form method="POST">
                                <input type="hidden" name="action" value="enable_confirm">
                                <div class="form-group mb-3">
                                    <label class="form-label font-weight-bold">6-Digit Code <span>*</span></label>
                                    <input
                                        type="text"
                                        name="code"
                                        class="form-control"
                                        placeholder="000000"
                                        maxlength="6"
                                        inputmode="numeric"
                                        pattern="[0-9]{6}"
                                        required
                                        style="letter-spacing: 0.35em; font-size: 1.4rem; font-weight: 700; text-align: center; max-width: 240px; margin: 0 auto;"
                                    >
                                </div>

                                <button type="submit" class="btn btn-primary w-100" style="padding: 0.75rem; font-size: 1rem; border-radius: 12px; font-weight: 700;">
                                    <i class="bi bi-shield-check me-1"></i> Verify Code & Enable MFA
                                </button>
                            </form>

                            <div class="mt-4 p-3" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 10px; font-size: 0.82rem; color: #64748B;">
                                <i class="bi bi-info-circle-fill text-primary me-1"></i> Once enabled, you will be prompted for your 6-digit authenticator code on each administrative sign in.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Embedded QR Code Generator -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const qrContainer = document.getElementById('qrcode');
                const otpUri = <?= json_encode($otpAuthUri) ?>;
                if (qrContainer && typeof QRCode !== 'undefined') {
                    new QRCode(qrContainer, {
                        text: otpUri,
                        width: 170,
                        height: 170,
                        colorDark: "#0B132B",
                        colorLight: "#FFFFFF",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                } else if (qrContainer) {
                    // Fallback to SVG if external CDN blocked
                    qrContainer.innerHTML = '<div style="font-size:0.8rem; padding: 1rem; text-align: center; color: #64748B;"><i class="bi bi-qr-code" style="font-size: 3rem; display:block; margin-bottom: 0.5rem; color: #19A974;"></i>Enter the manual key above into your authenticator app.</div>';
                }
            });
            </script>
        <?php } ?>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>
