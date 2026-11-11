<?php
include "../../includes/header.php";
include "../../includes/navbar.php";
?>

<main class="section">
    <div class="container info-page">
        <h1>Privacy notice</h1>
        <p>How ShopEase handles account, order and security data.</p>

        <h2>Account and order data</h2>
        <p>ShopEase stores the name, email address, phone number, delivery address and city entered at registration, together with order history. This data is used to operate customer accounts and fulfil orders. Passwords are stored only as one-way hashes.</p>

        <h2>Security event data</h2>
        <p>ShopEase records security events to detect account attacks and investigate misuse. Recorded events include sign-ins, failed sign-ins, password resets, refused requests and administrative changes.</p>
        <p>Each record contains:</p>
        <ul>
            <li>Event type, date, time and outcome</li>
            <li>User ID and role, when signed in</li>
            <li>A masked email address and a keyed hash of the address</li>
            <li>Network (IP) address</li>
            <li>Request method and page path</li>
            <li>ID of the record affected by an administrative change</li>
        </ul>

        <h2>Data excluded from security records</h2>
        <p>Security records do not contain passwords, multi-factor authentication secrets or codes, password reset tokens, session cookies, form contents or URL query strings.</p>

        <h2>Access</h2>
        <p>Only accounts with the Admin role can open the security monitor. Email addresses and network addresses are partially masked on that screen.</p>

        <h2>Retention</h2>
        <ul>
            <li>Security events: deleted after 90 days</li>
            <li>Acknowledged security alerts: deleted 90 days after acknowledgement</li>
            <li>Login rate-limit records: deleted after 48 hours</li>
            <li>Password reset links: expire after 30 minutes</li>
        </ul>

        <h2>Requests and questions</h2>
        <p>To ask about or request a correction to personal data, contact <a href="mailto:support@shopease.com">support@shopease.com</a>.</p>

        <h2>Status of this notice</h2>
        <p>This notice describes how the application handles data. It has not been assessed for compliance with the Personal Data Protection Act 2010 or any other law.</p>
    </div>
</main>

<?php include "../../includes/footer.php"; ?>
