<?php
require_once __DIR__ . '/../../includes/session.php';
start_shopease_session();
include "../../config/database.php";
include_once "../../includes/security.php";

$token = trim($_GET['token'] ?? '');
$resetRecord = verify_password_reset_token($conn, $token);

$error = isset($_SESSION['auth_error']) ? $_SESSION['auth_error'] : '';
unset($_SESSION['auth_error']);

include "../../includes/header.php";
?>

<main class="auth-page">
    <div class="auth-shell">
        <aside class="auth-panel">
            <a href="../home.php" class="auth-brand"><i class="bi bi-bag-check-fill"></i> ShopEase</a>
            <div class="auth-panel-copy">
                <span class="auth-kicker">Account Security</span>
                <h1>Set a new password.</h1>
                <p>Choose a strong, unique password to protect your personal information and orders.</p>
            </div>
            <div class="auth-panel-footer"><i class="bi bi-shield-check"></i> High-entropy SHA-256 validation</div>
        </aside>

        <section class="auth-form-panel">
            <div class="auth-form-header">
                <span class="auth-kicker">Step 2 of 2</span>
                <h2>Reset Password</h2>
                <p>Enter your new password below.</p>
            </div>
        
        <?php if ($error): ?>
            <div class="auth-alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!$resetRecord): ?>
            <div class="auth-alert" style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; border-radius: 10px; flex-direction: column; align-items: flex-start; gap: 0.6rem;">
                <div style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="bi bi-x-circle-fill" style="color: #EF4444; font-size: 1.2rem;"></i> Invalid or Expired Token
                </div>
                <div style="font-size: 0.9rem; line-height: 1.5;">
                    This password reset link is invalid, has expired (after 30 minutes), or has already been used. Please submit a new request.
                </div>
                <a href="forgot-password.php" class="btn btn-primary" style="margin-top: 0.5rem; padding: 0.5rem 1rem; font-size: 0.9rem;">
                    Request New Reset Link &rarr;
                </a>
            </div>
        <?php else: ?>
            <form action="../../actions/auth/reset-password.php" method="POST" id="resetPasswordForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="auth-field">
                    <label class="form-label">Account</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($resetRecord['email']); ?>" readonly style="background: #F8FAFC; color: #64748B;">
                </div>

                <div class="auth-field auth-password-field mb-2">
                    <label class="form-label">New Password</label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password" id="newPassword" name="password" class="form-control" required autocomplete="new-password" placeholder="Create your new password">
                    </div>

                    <!-- Real-time Password Strength Criteria -->
                    <div class="password-criteria mt-2" style="font-size: 0.8rem; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 0.75rem 1rem;">
                        <div style="font-weight: 600; color: #475569; margin-bottom: 0.4rem;">Password must contain:</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.35rem;">
                            <span id="rule-length" style="color: #94A3B8;"><i class="bi bi-circle"></i> Min 8 characters</span>
                            <span id="rule-upper" style="color: #94A3B8;"><i class="bi bi-circle"></i> Upper case (A-Z)</span>
                            <span id="rule-lower" style="color: #94A3B8;"><i class="bi bi-circle"></i> Lower case (a-z)</span>
                            <span id="rule-number" style="color: #94A3B8;"><i class="bi bi-circle"></i> Number (0-9)</span>
                            <span id="rule-special" style="color: #94A3B8; grid-column: span 2;"><i class="bi bi-circle"></i> Special symbol (!@#$%^&*)</span>
                        </div>
                    </div>
                </div>

                <div class="auth-field auth-password-field">
                    <label class="form-label">Confirm New Password</label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-shield-check"></i>
                        <input type="password" id="confirmPassword" name="confirm_password" class="form-control" required autocomplete="new-password" placeholder="Re-enter your new password">
                    </div>
                    <div id="match-feedback" style="font-size: 0.8rem; margin-top: 0.35rem; display: none;"></div>
                </div>
                
                <button type="submit" class="btn btn-primary auth-submit">Update Password <i class="bi bi-check-lg"></i></button>
            </form>
        <?php endif; ?>
        
            <div class="auth-signup"><a href="login.php">Back to Sign In</a></div>
        </section>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('newPassword');
    const confirmInput = document.getElementById('confirmPassword');
    const matchFeedback = document.getElementById('match-feedback');
    const ruleLength = document.getElementById('rule-length');
    const ruleUpper = document.getElementById('rule-upper');
    const ruleLower = document.getElementById('rule-lower');
    const ruleNumber = document.getElementById('rule-number');
    const ruleSpecial = document.getElementById('rule-special');

    function updateRule(element, isValid) {
        if (!element) return;
        if (isValid) {
            element.style.color = '#10B981';
            element.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + element.innerText.trim();
        } else {
            element.style.color = '#94A3B8';
            element.innerHTML = '<i class="bi bi-circle"></i> ' + element.innerText.trim();
        }
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const val = passwordInput.value;
            updateRule(ruleLength, val.length >= 8);
            updateRule(ruleUpper, /[A-Z]/.test(val));
            updateRule(ruleLower, /[a-z]/.test(val));
            updateRule(ruleNumber, /[0-9]/.test(val));
            updateRule(ruleSpecial, /[^a-zA-Z0-9]/.test(val));
            checkMatch();
        });
    }

    function checkMatch() {
        if (!confirmInput || !passwordInput || !matchFeedback) return;
        if (confirmInput.value === '') {
            matchFeedback.style.display = 'none';
            return;
        }
        matchFeedback.style.display = 'block';
        if (passwordInput.value === confirmInput.value) {
            matchFeedback.style.color = '#10B981';
            matchFeedback.innerHTML = '<i class="bi bi-check-circle-fill"></i> Passwords match.';
        } else {
            matchFeedback.style.color = '#EF4444';
            matchFeedback.innerHTML = '<i class="bi bi-x-circle-fill"></i> Passwords do not match.';
        }
    }

    if (confirmInput) {
        confirmInput.addEventListener('input', checkMatch);
    }
});
</script>

</body>
</html>
