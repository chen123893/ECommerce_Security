<?php
require_once __DIR__ . '/../../includes/session.php';
start_shopease_session();
if (isset($_SESSION['user_id'])) {
    header("Location: ../../pages/home.php");
    exit;
}
include "../../includes/header.php";

$error = isset($_SESSION['auth_error']) ? $_SESSION['auth_error'] : '';
$success = isset($_SESSION['auth_success']) ? $_SESSION['auth_success'] : '';
$devLink = isset($_SESSION['auth_dev_reset_link']) ? $_SESSION['auth_dev_reset_link'] : '';
unset($_SESSION['auth_error'], $_SESSION['auth_success'], $_SESSION['auth_dev_reset_link']);
?>

<main class="auth-page">
    <div class="auth-shell">
        <aside class="auth-panel">
            <a href="../home.php" class="auth-brand"><i class="bi bi-bag-check-fill"></i> ShopEase</a>
            <div class="auth-panel-copy">
                <span class="auth-kicker">Account Recovery</span>
                <h1>Reset your password safely.</h1>
                <p>We will generate a time-limited, cryptographically secured link to verify your identity.</p>
            </div>
            <div class="auth-panel-footer"><i class="bi bi-shield-lock-fill"></i> Cryptographic SHA-256 tokens</div>
        </aside>

        <section class="auth-form-panel">
            <div class="auth-form-header">
                <span class="auth-kicker">Trouble signing in?</span>
                <h2>Forgot Password</h2>
                <p>Enter your account email to receive reset instructions.</p>
            </div>
        
        <?php if ($success): ?>
            <div class="auth-alert" style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; border-radius: 10px;">
                <i class="bi bi-check-circle-fill" style="color: #10B981;"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($devLink): ?>
            <div class="auth-alert" style="background: #EFF6FF; border: 1px solid #BFDBFE; color: #1E40AF; border-radius: 10px; flex-direction: column; align-items: flex-start; gap: 0.5rem;">
                <div style="font-weight: 700; display: flex; align-items: center; gap: 0.4rem;">
                    <i class="bi bi-info-circle-fill"></i> Development / Local Environment Notice:
                </div>
                <div style="font-size: 0.85rem; word-break: break-all;">
                    In local development without an SMTP mail server, click the simulated reset link below:
                </div>
                <a href="<?php echo htmlspecialchars($devLink); ?>" class="btn btn-sm btn-primary" style="font-size: 0.85rem; padding: 0.35rem 0.85rem; margin-top: 0.25rem;">
                    Open Password Reset Link &rarr;
                </a>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="auth-alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <form action="../../actions/auth/forgot-password.php" method="POST">
            <div class="auth-field">
                <label class="form-label">Email Address</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-envelope"></i>
                    <input type="email" name="email" class="form-control" required placeholder="you@example.com" autocomplete="email" autofocus>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary auth-submit">Send Reset Link <i class="bi bi-arrow-right"></i></button>
        </form>
        
            <div class="auth-signup">Remember your password? <a href="login.php">Back to Sign In</a></div>
        </section>
    </div>
</main>

</body>
</html>
