<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header("Location: ../../pages/home.php");
    exit;
}
include "../../includes/header.php";

$error = isset($_SESSION['auth_error']) ? $_SESSION['auth_error'] : '';
unset($_SESSION['auth_error']);
?>

<main class="auth-page auth-signup-page">
    <div class="auth-shell">
        <aside class="auth-panel">
            <a href="../home.php" class="auth-brand"><i class="bi bi-bag-check-fill"></i> ShopEase</a>
            <div class="auth-panel-copy">
                <span class="auth-kicker">Make shopping personal</span>
                <h1>Everything you like, in one place.</h1>
                <p>Create your account to save your details and make every checkout simpler.</p>
            </div>
            <div class="auth-panel-footer"><i class="bi bi-shield-lock-fill"></i> Enhanced Argon2id / bcrypt security</div>
        </aside>

        <section class="auth-form-panel">
            <div class="auth-form-header">
                <span class="auth-kicker">Join ShopEase</span>
                <h2>Create your account</h2>
                <p>Set up your details once and shop with ease.</p>
            </div>
        
        <?php if ($error): ?>
            <div class="auth-alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
            <form action="../../actions/auth/signup.php" method="POST" class="auth-signup-form" id="signupForm">
                <div class="auth-form-grid">
                    <div class="auth-field">
                        <label class="form-label">Full Name</label>
                        <div class="auth-input-wrap"><i class="bi bi-person"></i><input type="text" name="name" class="form-control" required autocomplete="name" placeholder="Your full name"></div>
                    </div>
                    <div class="auth-field">
                        <label class="form-label">Email Address</label>
                        <div class="auth-input-wrap"><i class="bi bi-envelope"></i><input type="email" name="email" class="form-control" required autocomplete="email" placeholder="you@example.com"></div>
                    </div>
                
                    <div class="auth-field">
                        <label class="form-label">Phone Number</label>
                        <div class="auth-input-wrap"><i class="bi bi-telephone"></i><input type="text" name="phone" class="form-control" required autocomplete="tel" placeholder="Your phone number"></div>
                    </div>
                    <div class="auth-field">
                        <label class="form-label">City</label>
                        <div class="auth-input-wrap"><i class="bi bi-geo-alt"></i><input type="text" name="city" class="form-control" required autocomplete="address-level2" placeholder="Your city"></div>
                    </div>
                </div>
                <div class="auth-field">
                    <label class="form-label">Address</label>
                    <div class="auth-input-wrap"><i class="bi bi-house"></i><input type="text" name="address" class="form-control" required autocomplete="street-address" placeholder="Street and building address"></div>
                </div>
                
                <div class="auth-field auth-password-field mb-2">
                    <label class="form-label">Password</label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-lock"></i>
                        <input type="password" id="signupPassword" name="password" class="form-control" required autocomplete="new-password" placeholder="Create a strong password">
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
                
                <button type="submit" class="btn btn-primary auth-submit mt-3">Create account <i class="bi bi-arrow-right"></i></button>
            </form>
        
            <div class="auth-signup">Already have an account? <a href="login.php">Sign in</a></div>
        </section>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('signupPassword');
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
        });
    }
});
</script>

</body>
</html>
