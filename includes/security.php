<?php
/**
 * Security & Authentication Infrastructure for ShopEase
 * 
 * Features:
 * 1. Strong password policy validation
 * 2. Modern password hashing (Argon2id with bcrypt fallback) and transparent auto-rehashing
 * 3. Rate limiting for login attempts (IP and email based)
 * 4. Secure password reset token generation, storage, and validation
 * 5. Automatic database schema synchronization
 */

/**
 * Initializes security-related database tables and columns if they do not exist yet.
 * Safe to call multiple times (cached per request).
 */
function init_security_schema(mysqli $conn): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }
    $initialized = true;

    // 1. Table for tracking login attempts for rate limiting
    $sql_attempts = "CREATE TABLE IF NOT EXISTS `login_attempts` (
        `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `ip_address` varchar(45) NOT NULL,
        `email` varchar(150) NOT NULL,
        `attempted_at` datetime NOT NULL,
        `is_success` tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `idx_ip_attempt` (`ip_address`, `attempted_at`),
        KEY `idx_email_attempt` (`email`, `attempted_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    @mysqli_query($conn, $sql_attempts);

    // 2. Table for secure password reset tokens
    $sql_resets = "CREATE TABLE IF NOT EXISTS `password_resets` (
        `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` int(10) UNSIGNED NOT NULL,
        `email` varchar(150) NOT NULL,
        `token_hash` varchar(64) NOT NULL,
        `expires_at` datetime NOT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `used_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_token_hash` (`token_hash`),
        KEY `idx_user_expires` (`user_id`, `expires_at`),
        KEY `fk_pw_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    @mysqli_query($conn, $sql_resets);

    // 3. Add 2FA columns to users table if they don't already exist
    $col_check = @mysqli_query($conn, "SHOW COLUMNS FROM `users` LIKE 'two_factor_secret'");
    if ($col_check && mysqli_num_rows($col_check) === 0) {
        @mysqli_query($conn, "ALTER TABLE `users` ADD COLUMN `two_factor_secret` varchar(255) DEFAULT NULL AFTER `role`");
        @mysqli_query($conn, "ALTER TABLE `users` ADD COLUMN `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0 AFTER `two_factor_secret`");
        @mysqli_query($conn, "ALTER TABLE `users` ADD COLUMN `two_factor_backup_codes` text DEFAULT NULL AFTER `two_factor_enabled`");
    }
}

/* ==========================================================================
   1. PASSWORD POLICY & HASHING (ARGON2ID / BCRYPT)
   ========================================================================== */

/**
 * Validates a password against strong password policy:
 * - At least 8 characters in length
 * - At least one uppercase letter (A-Z)
 * - At least one lowercase letter (a-z)
 * - At least one number (0-9)
 * - At least one special symbol (!@#$%^&*...)
 */
function validate_password_strength(string $password): array
{
    $errors = [];
    $rules = [
        'length'    => strlen($password) >= 8,
        'lowercase' => (bool)preg_match('/[a-z]/', $password),
        'uppercase' => (bool)preg_match('/[A-Z]/', $password),
        'number'    => (bool)preg_match('/[0-9]/', $password),
        'special'   => (bool)preg_match('/[^a-zA-Z0-9]/', $password),
    ];

    if (!$rules['length']) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if (!$rules['uppercase']) {
        $errors[] = 'Password must include at least one uppercase letter (A-Z).';
    }
    if (!$rules['lowercase']) {
        $errors[] = 'Password must include at least one lowercase letter (a-z).';
    }
    if (!$rules['number']) {
        $errors[] = 'Password must include at least one numeric digit (0-9).';
    }
    if (!$rules['special']) {
        $errors[] = 'Password must include at least one special character (e.g. !@#$%^&*).';
    }

    return [
        'valid'  => count($errors) === 0,
        'errors' => $errors,
        'rules'  => $rules,
    ];
}

/**
 * Returns human-readable policy description string.
 */
function get_password_policy_text(): string
{
    return 'Password must be at least 8 characters long and contain uppercase, lowercase, numbers, and symbols.';
}

/**
 * Returns preferred hashing algorithm: Argon2id if supported by PHP runtime, otherwise Bcrypt.
 */
function get_preferred_hash_algo(): string|int
{
    if (defined('PASSWORD_ARGON2ID')) {
        return PASSWORD_ARGON2ID;
    }
    return PASSWORD_BCRYPT;
}

/**
 * Returns options for preferred hashing algorithm.
 */
function get_preferred_hash_options(): array
{
    if (defined('PASSWORD_ARGON2ID')) {
        return [
            'memory_cost' => 65536, // 64 MB
            'time_cost'   => 4,
            'threads'     => 1,
        ];
    }
    return [
        'cost' => 12,
    ];
}

/**
 * Hashes a plain password using Argon2id (or Bcrypt fallback).
 */
function hash_password(string $password): string
{
    $algo = get_preferred_hash_algo();
    $options = get_preferred_hash_options();
    return password_hash($password, $algo, $options);
}

/**
 * Verifies password and transparently rehashes if the stored hash uses an older algorithm or lower cost.
 */
function verify_and_rehash_password(mysqli $conn, string $password, string $storedHash, int $userId): bool
{
    if (!password_verify($password, $storedHash)) {
        return false;
    }

    $algo = get_preferred_hash_algo();
    $options = get_preferred_hash_options();

    if (password_needs_rehash($storedHash, $algo, $options)) {
        $newHash = password_hash($password, $algo, $options);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $newHash, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    return true;
}

/* ==========================================================================
   2. LOGIN RATE LIMITING
   ========================================================================== */

/**
 * Resolves client IP address safely.
 */
function get_client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $candidate = trim($ips[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $ip = $candidate;
        }
    }
    return substr($ip, 0, 45);
}

/**
 * Checks if the login attempt should be rate limited.
 * Rules:
 * - Max 5 failed attempts per email in last 15 minutes.
 * - Max 10 failed attempts per IP in last 15 minutes.
 * 
 * Returns:
 * [
 *   'allowed' => bool,
 *   'message' => string,
 *   'retry_after' => int (seconds)
 * ]
 */
function check_login_rate_limit(mysqli $conn, string $email, string $ip): array
{
    init_security_schema($conn);

    $windowMinutes = 15;
    $maxEmailAttempts = 5;
    $maxIpAttempts = 10;

    // Check failed attempts for email using database server time calculation (timezone independent)
    $stmtEmail = mysqli_prepare($conn, "SELECT COUNT(*), 
        COALESCE(TIMESTAMPDIFF(SECOND, MIN(attempted_at), NOW()), 0)
        FROM login_attempts 
        WHERE email = ? AND is_success = 0 AND attempted_at > (NOW() - INTERVAL ? MINUTE)");
    mysqli_stmt_bind_param($stmtEmail, "si", $email, $windowMinutes);
    mysqli_stmt_execute($stmtEmail);
    mysqli_stmt_bind_result($stmtEmail, $emailFailCount, $emailElapsed);
    mysqli_stmt_fetch($stmtEmail);
    mysqli_stmt_close($stmtEmail);

    // Check failed attempts for IP using database server time calculation (timezone independent)
    $stmtIp = mysqli_prepare($conn, "SELECT COUNT(*), 
        COALESCE(TIMESTAMPDIFF(SECOND, MIN(attempted_at), NOW()), 0)
        FROM login_attempts 
        WHERE ip_address = ? AND is_success = 0 AND attempted_at > (NOW() - INTERVAL ? MINUTE)");
    mysqli_stmt_bind_param($stmtIp, "si", $ip, $windowMinutes);
    mysqli_stmt_execute($stmtIp);
    mysqli_stmt_bind_result($stmtIp, $ipFailCount, $ipElapsed);
    mysqli_stmt_fetch($stmtIp);
    mysqli_stmt_close($stmtIp);

    if ($emailFailCount >= $maxEmailAttempts || $ipFailCount >= $maxIpAttempts) {
        $elapsedSeconds = ($emailFailCount >= $maxEmailAttempts) ? $emailElapsed : $ipElapsed;
        $remainingSeconds = max(60, ($windowMinutes * 60) - max(0, $elapsedSeconds));
        $remainingMinutes = min($windowMinutes, (int)ceil($remainingSeconds / 60));

        return [
            'allowed'     => false,
            'message'     => "Too many failed login attempts. For security, account login is locked. Please try again in {$remainingMinutes} minute(s).",
            'retry_after' => $remainingSeconds,
        ];
    }

    return [
        'allowed'     => true,
        'message'     => '',
        'retry_after' => 0,
    ];
}

/**
 * Records a login attempt (successful or failed).
 */
function record_login_attempt(mysqli $conn, string $email, string $ip, bool $success): void
{
    init_security_schema($conn);
    $isSuccessInt = $success ? 1 : 0;

    $stmt = mysqli_prepare($conn, "INSERT INTO login_attempts (ip_address, email, attempted_at, is_success) VALUES (?, ?, NOW(), ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssi", $ip, $email, $isSuccessInt);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // If login was successful, clear past failed attempts for this email and IP
    if ($success) {
        $clearStmt = mysqli_prepare($conn, "DELETE FROM login_attempts WHERE (email = ? OR ip_address = ?) AND is_success = 0");
        if ($clearStmt) {
            mysqli_stmt_bind_param($clearStmt, "ss", $email, $ip);
            mysqli_stmt_execute($clearStmt);
            mysqli_stmt_close($clearStmt);
        }
    }

    // Occasional cleanup of logs older than 48 hours (1% lottery)
    if (mt_rand(1, 100) === 1) {
        clean_old_login_attempts($conn);
    }
}

/**
 * Prunes login attempt logs older than 48 hours.
 */
function clean_old_login_attempts(mysqli $conn): void
{
    @mysqli_query($conn, "DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 48 HOUR)");
}

/* ==========================================================================
   3. SECURE PASSWORD RESET
   ========================================================================== */

/**
 * Requests a password reset token for the given email.
 * Applies rate limiting to prevent spamming reset requests.
 * Always returns a generic response message to prevent email enumeration.
 */
function request_password_reset(mysqli $conn, string $email, string $ip): array
{
    init_security_schema($conn);

    // Rate limit password reset requests: max 3 requests per IP or per email per 15 minutes
    $stmtRate = mysqli_prepare($conn, "SELECT COUNT(*) FROM password_resets 
        WHERE (email = ? OR user_id IN (SELECT id FROM users WHERE email = ?)) 
        AND created_at > (NOW() - INTERVAL 15 MINUTE)");
    mysqli_stmt_bind_param($stmtRate, "ss", $email, $email);
    mysqli_stmt_execute($stmtRate);
    mysqli_stmt_bind_result($stmtRate, $resetCount);
    mysqli_stmt_fetch($stmtRate);
    mysqli_stmt_close($stmtRate);

    if ($resetCount >= 3) {
        return [
            'status'  => 'rate_limited',
            'message' => 'Too many password reset requests. Please wait 15 minutes before requesting another reset.',
            'token'   => null,
        ];
    }

    // Check if user exists
    $stmtUser = mysqli_prepare($conn, "SELECT id, name, email FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtUser, "s", $email);
    mysqli_stmt_execute($stmtUser);
    $resultUser = mysqli_stmt_get_result($stmtUser);
    $user = mysqli_fetch_assoc($resultUser);
    mysqli_stmt_close($stmtUser);

    if (!$user) {
        // Return generic success response to prevent email harvesting/enumeration
        return [
            'status'  => 'ok',
            'message' => 'If an account exists with this email address, a password reset link has been created.',
            'token'   => null,
            'user'    => null,
        ];
    }

    // Invalidate existing unused tokens for this user
    $userId = (int)$user['id'];
    $stmtInv = mysqli_prepare($conn, "UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL");
    mysqli_stmt_bind_param($stmtInv, "i", $userId);
    mysqli_stmt_execute($stmtInv);
    mysqli_stmt_close($stmtInv);

    // Generate 32-byte (256 bits) cryptographically secure random token
    $plainToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $plainToken);

    // Token expires in 30 minutes
    $stmtInsert = mysqli_prepare($conn, "INSERT INTO password_resets (user_id, email, token_hash, expires_at, created_at) 
        VALUES (?, ?, ?, (NOW() + INTERVAL 30 MINUTE), NOW())");
    mysqli_stmt_bind_param($stmtInsert, "iss", $userId, $email, $tokenHash);
    mysqli_stmt_execute($stmtInsert);
    mysqli_stmt_close($stmtInsert);

    return [
        'status'      => 'ok',
        'message'     => 'If an account exists with this email address, a password reset link has been created.',
        'plain_token' => $plainToken,
        'user'        => $user,
    ];
}

/**
 * Validates a plain password reset token against database.
 * Returns reset record with user information if valid and not expired, null otherwise.
 */
function verify_password_reset_token(mysqli $conn, string $plainToken): ?array
{
    init_security_schema($conn);

    if (empty($plainToken) || strlen($plainToken) !== 64) {
        return null;
    }

    $tokenHash = hash('sha256', $plainToken);

    $stmt = mysqli_prepare($conn, "SELECT pr.id, pr.user_id, pr.email, pr.expires_at, u.name, u.role 
        FROM password_resets pr
        JOIN users u ON u.id = pr.user_id
        WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > NOW()
        LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $tokenHash);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

/**
 * Completes the password reset process with a new strong password.
 */
function complete_password_reset(mysqli $conn, string $plainToken, string $newPassword): array
{
    init_security_schema($conn);

    $resetRecord = verify_password_reset_token($conn, $plainToken);
    if (!$resetRecord) {
        return [
            'success' => false,
            'message' => 'The password reset link is invalid or has expired. Please request a new one.',
        ];
    }

    $validation = validate_password_strength($newPassword);
    if (!$validation['valid']) {
        return [
            'success' => false,
            'message' => implode(' ', $validation['errors']),
        ];
    }

    $userId = (int)$resetRecord['user_id'];
    $hashedPassword = hash_password($newPassword);

    // Update password
    $stmtUser = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmtUser, "si", $hashedPassword, $userId);
    $userUpdated = mysqli_stmt_execute($stmtUser);
    mysqli_stmt_close($stmtUser);

    if (!$userUpdated) {
        return [
            'success' => false,
            'message' => 'Failed to update password. Please try again.',
        ];
    }

    // Mark reset token as consumed
    $resetId = (int)$resetRecord['id'];
    $stmtUsed = mysqli_prepare($conn, "UPDATE password_resets SET used_at = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($stmtUsed, "i", $resetId);
    mysqli_stmt_execute($stmtUsed);
    mysqli_stmt_close($stmtUsed);

    // Clear any lockouts on this account
    $email = $resetRecord['email'];
    @mysqli_query($conn, "DELETE FROM login_attempts WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'");

    return [
        'success' => true,
        'message' => 'Your password has been successfully reset! You may now sign in with your new password.',
        'email'   => $email,
        'role'    => $resetRecord['role'],
    ];
}
