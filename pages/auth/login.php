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
unset($_SESSION['auth_error'], $_SESSION['auth_success']);
?>

<main class="auth-page">
    <div class="auth-shell">
        <aside class="auth-panel">
            <a href="../home.php" class="auth-brand"><i class="bi bi-bag-check-fill"></i> ShopEase</a>
            <div class="auth-panel-copy">
                <span class="auth-kicker">Your everyday shopping space</span>
                <h1>Good to see you again.</h1>
                <p>Pick up where you left off and keep your favorite finds within reach.</p>
            </div>
            <div class="auth-panel-footer"><i class="bi bi-shield-check"></i> Secure account access</div>
        </aside>

        <section class="auth-form-panel">
            <div class="auth-form-header">
                <span class="auth-kicker">Welcome back</span>
                <h2>Sign in to ShopEase</h2>
                <p>Enter your details to continue to your account.</p>
            </div>
        
        <?php if ($success): ?>
            <div class="auth-alert" style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; border-radius: 10px;">
                <i class="bi bi-check-circle-fill" style="color: #10B981;"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="auth-alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <form action="../../actions/auth/login.php" method="POST">
            <div class="auth-field">
                <label class="form-label">Email Address</label>
                <div class="auth-input-wrap"><i class="bi bi-envelope"></i><input type="email" name="email" class="form-control" required placeholder="you@example.com" autocomplete="email"></div>
            </div>
            
            <div class="auth-field auth-password-field">
                <div class="auth-label-row">
                    <label class="form-label">Password</label>
                    <a href="forgot-password.php" class="auth-help-link">Forgot password?</a>
                </div>
                <div class="auth-input-wrap"><i class="bi bi-lock"></i><input type="password" name="password" class="form-control" required placeholder="Enter your password" autocomplete="current-password"></div>
            </div>
            
            <button type="submit" class="btn btn-primary auth-submit">Sign in <i class="bi bi-arrow-right"></i></button>
        </form>
        
            <div class="auth-signup">New to ShopEase? <a href="signup.php">Create an account</a></div>
        </section>
    </div>
</main>

</body>
</html>
