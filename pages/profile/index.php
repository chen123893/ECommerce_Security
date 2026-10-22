<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "../../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$query = "SELECT users.name, users.email, clients.phone, clients.address, clients.city
          FROM users
          LEFT JOIN clients ON users.id = clients.user_id
          WHERE users.id = $user_id LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: ../auth/logout.php");
    exit;
}

$user = mysqli_fetch_assoc($result);

include "../../includes/header.php";
include "../../includes/navbar.php";

$message = isset($_SESSION['profile_msg']) ? $_SESSION['profile_msg'] : '';
$error = isset($_SESSION['profile_error']) ? $_SESSION['profile_error'] : '';
unset($_SESSION['profile_msg'], $_SESSION['profile_error']);
?>

<section class="section profile-page">
    <div class="container">
        <div class="profile-heading">
            <div>
                <span class="profile-kicker">Account settings</span>
                <h1>My profile</h1>
                <p>Keep your contact and delivery details up to date.</p>
            </div>
            <a href="../home.php" class="profile-back-link"><i class="bi bi-arrow-left"></i> Back to shop</a>
        </div>

        <div class="profile-layout">
            <aside class="card profile-identity">
                <div class="profile-avatar"><i class="bi bi-person"></i></div>
                <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="profile-role"><i class="bi bi-person-badge"></i> <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Client'); ?></span>
                <div class="profile-identity-divider"></div>
                <div class="profile-identity-note"><i class="bi bi-shield-check"></i><span>Your account details are kept private and secure.</span></div>
                <a href="../auth/logout.php" class="profile-logout"><i class="bi bi-box-arrow-right"></i> Log out</a>
            </aside>

            <div class="card profile-details">
                <div class="profile-section-heading">
                    <div class="profile-section-icon"><i class="bi bi-person-lines-fill"></i></div>
                    <div><span class="profile-kicker">Personal details</span><h2>Contact information</h2></div>
                </div>
                    
                    <?php if ($message): ?>
                        <div class="profile-alert profile-alert-success">
                            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="profile-alert profile-alert-error">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="../../actions/profile/update.php" method="POST" class="profile-form">
                        <div class="profile-form-grid">
                            <div>
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div>
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly style="background: #F3F4F6;">
                                <div style="font-size: 0.8rem; color: #9CA3AF; margin-top: 0.25rem;">Email cannot be changed.</div>
                            </div>
                        
                            <div>
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="profile-address-field">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="profile-form-actions">
                            <a href="../orders/index.php" class="btn btn-secondary"><i class="bi bi-box-seam"></i> View my orders</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save changes</button>
                        </div>
                    </form>

                    <hr style="margin: 2.5rem 0 2rem; border-color: #E5E7EB;">

                    <div class="profile-section-heading">
                        <div class="profile-section-icon" style="background: #EFF6FF; color: #2563EB;"><i class="bi bi-shield-lock-fill"></i></div>
                        <div><span class="profile-kicker">Security</span><h2>Change Password</h2></div>
                    </div>

                    <form action="../../actions/profile/change-password.php" method="POST" class="profile-form mt-3" id="profilePasswordForm">
                        <div class="profile-form-grid">
                            <div style="grid-column: 1 / -1;">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required placeholder="Enter your current password" autocomplete="current-password">
                            </div>

                            <div>
                                <label class="form-label">New Password</label>
                                <input type="password" id="profileNewPassword" name="new_password" class="form-control" required placeholder="Min 8 chars with upper, lower, number, symbol" autocomplete="new-password">
                            </div>

                            <div>
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" id="profileConfirmPassword" name="confirm_password" class="form-control" required placeholder="Re-enter new password" autocomplete="new-password">
                            </div>
                        </div>

                        <!-- Real-time Password Strength Criteria -->
                        <div class="password-criteria mt-3" style="font-size: 0.8rem; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 0.75rem 1rem;">
                            <div style="font-weight: 600; color: #475569; margin-bottom: 0.4rem;">Password requirements:</div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.35rem;">
                                <span id="p-rule-length" style="color: #94A3B8;"><i class="bi bi-circle"></i> Min 8 characters</span>
                                <span id="p-rule-upper" style="color: #94A3B8;"><i class="bi bi-circle"></i> Upper (A-Z)</span>
                                <span id="p-rule-lower" style="color: #94A3B8;"><i class="bi bi-circle"></i> Lower (a-z)</span>
                                <span id="p-rule-number" style="color: #94A3B8;"><i class="bi bi-circle"></i> Number (0-9)</span>
                                <span id="p-rule-special" style="color: #94A3B8;"><i class="bi bi-circle"></i> Symbol (!@#$)</span>
                            </div>
                        </div>

                        <div class="profile-form-actions mt-3">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-key"></i> Update Password</button>
                        </div>
                    </form>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const newPwd = document.getElementById('profileNewPassword');
    const rLen = document.getElementById('p-rule-length');
    const rUp = document.getElementById('p-rule-upper');
    const rLow = document.getElementById('p-rule-lower');
    const rNum = document.getElementById('p-rule-number');
    const rSpec = document.getElementById('p-rule-special');

    function check(el, ok) {
        if (!el) return;
        el.style.color = ok ? '#10B981' : '#94A3B8';
        el.innerHTML = (ok ? '<i class="bi bi-check-circle-fill"></i> ' : '<i class="bi bi-circle"></i> ') + el.innerText.trim();
    }

    if (newPwd) {
        newPwd.addEventListener('input', function() {
            const v = newPwd.value;
            check(rLen, v.length >= 8);
            check(rUp, /[A-Z]/.test(v));
            check(rLow, /[a-z]/.test(v));
            check(rNum, /[0-9]/.test(v));
            check(rSpec, /[^a-zA-Z0-9]/.test(v));
        });
    }
});
</script>

<?php
include "../../includes/footer.php";
?>
