<?php
include "../../includes/header.php";
include "../../includes/navbar.php";
?>

<main class="section">
    <div class="container info-page">
        <h1>Account security</h1>
        <p>Guidance for protecting a ShopEase account.</p>

        <h2>Passwords</h2>
        <p>ShopEase requires at least 8 characters with uppercase and lowercase letters, a number and a symbol. A longer password is stronger.</p>
        <ul>
            <li>Use a password that is not used on any other website.</li>
            <li>Store passwords in a password manager.</li>
            <li>Do not share a password with anyone, including ShopEase staff.</li>
        </ul>

        <h2>Multi-factor authentication</h2>
        <p>Multi-factor authentication (MFA) adds a 6-digit code from an authenticator app to the sign-in process. It is available for Admin and Staff accounts under <strong>Two-Factor Auth</strong> in the admin portal. Customer accounts do not currently offer MFA.</p>
        <ul>
            <li>Enable MFA on every Admin and Staff account.</li>
            <li>Store backup codes offline, away from the password.</li>
            <li>Do not give an authenticator code to anyone who asks for it.</li>
        </ul>

        <h2>Phishing</h2>
        <p>Phishing messages imitate a trusted organisation to obtain passwords or payment details. ShopEase support does not ask for passwords or authenticator codes.</p>
        <ul>
            <li>Check the website address before signing in.</li>
            <li>Do not open links in unexpected messages about an account or order.</li>
            <li>Ignore password reset messages that were not requested. Each reset link expires after 30 minutes.</li>
        </ul>

        <h2>Suspicious activity</h2>
        <p>Warning signs include orders that were not placed, profile details that have changed, and a lockout message on an account that was not in use. An account locks for up to 15 minutes after 5 failed sign-ins.</p>
        <ol>
            <li>Change the password on the <a href="/E-Commerce-Management-System/pages/profile/index.php">profile page</a>. If sign-in fails, use <a href="/E-Commerce-Management-System/pages/auth/forgot-password.php">Forgot password</a>.</li>
            <li>Review recent activity on the <a href="/E-Commerce-Management-System/pages/orders/index.php">order history page</a>.</li>
            <li>Report the date, time and details to <a href="mailto:support@shopease.com">support@shopease.com</a>. Do not include a password in the report.</li>
        </ol>

        <p><a href="/E-Commerce-Management-System/pages/privacy/index.php">Privacy notice</a></p>
    </div>
</main>

<?php include "../../includes/footer.php"; ?>
