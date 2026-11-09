<?php
/**
 * Security audit log, suspicious activity alerts and retention (Member 5, sections 3.11 and 3.12).
 *
 * The log uses its own database connection for two reasons. admin/includes/auth.php runs before a
 * page has opened its connection, and an event must survive when the page rolls back its own
 * transaction. A failed write never interrupts the user's request.
 *
 * Never pass passwords, MFA secrets, reset tokens, cookies or request bodies to these functions.
 * security_sanitize_context() is a second line of defence, not a reason to pass them.
 */

require_once __DIR__ . '/security_audit_config.php';

const SECURITY_OUTCOMES = ['success', 'failure', 'denied', 'blocked', 'info'];

/** Every event the application may record: type => [label, category]. */
function security_event_catalog(): array
{
    return [
        'login_success'                => ['Login succeeded', 'authentication'],
        'login_failure'                => ['Login failed', 'authentication'],
        'login_blocked'                => ['Login blocked by rate limit', 'authentication'],
        'login_mfa_required'           => ['Password accepted, MFA required', 'authentication'],
        'mfa_success'                  => ['MFA code accepted', 'authentication'],
        'mfa_failure'                  => ['MFA code rejected', 'authentication'],
        'mfa_blocked'                  => ['MFA blocked by rate limit', 'authentication'],
        'mfa_enabled'                  => ['MFA enabled', 'account'],
        'mfa_disabled'                 => ['MFA disabled', 'account'],
        'mfa_backup_codes_regenerated' => ['MFA backup codes regenerated', 'account'],
        'logout'                       => ['Logged out', 'authentication'],
        'account_registered'           => ['Customer account registered', 'account'],
        'password_reset_requested'     => ['Password reset requested', 'password'],
        'password_reset_blocked'       => ['Password reset blocked by rate limit', 'password'],
        'password_reset_completed'     => ['Password reset completed', 'password'],
        'password_reset_failed'        => ['Password reset rejected', 'password'],
        'password_changed'             => ['Password changed', 'password'],
        'password_change_failed'       => ['Password change rejected', 'password'],
        'access_denied'                => ['Access denied', 'access_control'],
        'csrf_rejected'                => ['Request rejected: invalid CSRF token', 'access_control'],
        'admin_product_created'        => ['Product created', 'administration'],
        'admin_product_updated'        => ['Product updated', 'administration'],
        'admin_product_deleted'        => ['Product deleted', 'administration'],
        'admin_user_created'           => ['User created', 'administration'],
        'admin_user_updated'           => ['User updated', 'administration'],
        'admin_user_deleted'           => ['User deleted', 'administration'],
        'admin_order_updated'          => ['Order updated', 'administration'],
        'admin_order_deleted'          => ['Order deleted', 'administration'],
        'admin_client_deleted'         => ['Client deleted', 'administration'],
        'suspicious_activity_detected' => ['Suspicious activity detected', 'monitoring'],
        'alert_acknowledged'           => ['Security alert acknowledged', 'monitoring'],
        'audit_retention_purge'        => ['Expired audit records deleted', 'monitoring'],
    ];
}

/** Reads .env directly, because config/database.php cannot be included twice in one request. */
function security_audit_env(): array
{
    static $env = null;
    if ($env !== null) {
        return $env;
    }
    $env = [];
    $path = __DIR__ . '/../.env';
    if (is_readable($path)) {
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $env[trim($name)] = trim($value);
        }
    }
    return $env;
}

/**
 * Opens the audit connection once per request. Returns null when the database is unavailable.
 * AUDIT_DB_USER and AUDIT_DB_PASS allow a separate account with reduced rights.
 */
function security_audit_db(): ?mysqli
{
    static $connection = null;
    static $attempted = false;
    if ($attempted) {
        return $connection;
    }
    $attempted = true;

    $env = security_audit_env();
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $user = $env['AUDIT_DB_USER'] ?? ($env['DB_USER'] ?? 'root');
    $pass = $env['AUDIT_DB_PASS'] ?? ($env['DB_PASS'] ?? '');
    $name = $env['DB_NAME'] ?? 'ecommerce';
    $port = (int)($env['DB_PORT'] ?? 3306);

    // Same port fallback as config/database.php, for XAMPP installs on 3307.
    foreach ([$port, $port === 3307 ? 3306 : 3307] as $candidate) {
        try {
            $db = mysqli_init();
            $db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
            if ($db->real_connect($host, $user, $pass, $name, $candidate)) {
                $db->set_charset('utf8mb4');
                security_audit_ensure_schema($db);
                $connection = $db;
                return $connection;
            }
        } catch (Throwable $exception) {
            continue;
        }
    }
    error_log('ShopEase audit log: database connection unavailable.');
    return null;
}

/** Creates the audit tables on first use. database/migration_security_audit.sql holds the same definitions. */
function security_audit_ensure_schema(mysqli $db): void
{
    $db->query("CREATE TABLE IF NOT EXISTS `security_audit_log` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `event_type` varchar(50) NOT NULL,
        `category` varchar(20) NOT NULL,
        `outcome` enum('success','failure','denied','blocked','info') NOT NULL,
        `actor_user_id` int(10) UNSIGNED DEFAULT NULL,
        `actor_role` varchar(20) DEFAULT NULL,
        `identifier_hash` char(64) DEFAULT NULL,
        `identifier_masked` varchar(160) DEFAULT NULL,
        `ip_address` varchar(45) NOT NULL,
        `http_method` varchar(10) NOT NULL,
        `request_path` varchar(255) NOT NULL,
        `target_type` varchar(30) DEFAULT NULL,
        `target_id` bigint(20) UNSIGNED DEFAULT NULL,
        `context` text DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_audit_created` (`created_at`),
        KEY `idx_audit_type_created` (`event_type`, `created_at`),
        KEY `idx_audit_outcome_created` (`outcome`, `created_at`),
        KEY `idx_audit_identifier` (`identifier_hash`, `created_at`),
        KEY `idx_audit_ip` (`ip_address`, `created_at`),
        KEY `idx_audit_actor` (`actor_user_id`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $db->query("CREATE TABLE IF NOT EXISTS `security_alerts` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `rule_key` varchar(50) NOT NULL,
        `severity` enum('medium','high') NOT NULL,
        `subject_key` char(64) NOT NULL,
        `subject_display` varchar(160) NOT NULL,
        `open_key` char(64) DEFAULT NULL,
        `event_count` int(10) UNSIGNED NOT NULL,
        `threshold_value` int(10) UNSIGNED NOT NULL,
        `window_minutes` int(10) UNSIGNED NOT NULL,
        `first_seen_at` datetime NOT NULL,
        `last_seen_at` datetime NOT NULL,
        `status` enum('open','acknowledged') NOT NULL DEFAULT 'open',
        `acknowledged_by` int(10) UNSIGNED DEFAULT NULL,
        `acknowledged_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_alert_open` (`open_key`),
        KEY `idx_alert_subject` (`rule_key`, `subject_key`),
        KEY `idx_alert_status` (`status`, `last_seen_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

/** Keyed hash used to correlate an account across events without storing the full email. */
function security_audit_hmac(string $value): string
{
    $key = security_audit_env()['AUDIT_HASH_KEY'] ?? 'shopease-local-audit-key';
    return hash_hmac('sha256', $value, $key);
}

function security_mask_identifier(string $identifier): string
{
    $value = strtolower(trim($identifier));
    // A user can type a password into the email box, so only a valid address is kept in any form.
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return '[not an email address]';
    }
    [$local, $domain] = explode('@', $value, 2);
    return substr(substr($local, 0, 1) . '***@' . $domain, 0, 160);
}

function security_mask_ip(string $ip): string
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        $parts[3] = 'x';
        return implode('.', $parts);
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return implode(':', array_slice(explode(':', $ip), 0, 3)) . ':…';
    }
    return '[unknown]';
}

/** Returns JSON of safe scalar values only: sensitive keys are dropped and secret-looking values redacted. */
function security_sanitize_context(array $context): ?string
{
    $clean = [];
    foreach ($context as $key => $value) {
        if (count($clean) >= 10) {
            break;
        }
        $key = substr(preg_replace('/[^a-zA-Z0-9_]/', '', (string)$key), 0, 40);
        if ($key === '' || preg_match('/pass|pwd|secret|token|otp|cookie|session|authorization|backup|cvv|card|(^|_)codes?$/i', $key)) {
            continue;
        }
        if (is_string($value)) {
            $value = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value);
            $value = preg_replace('/\$(2[aby]|argon2[a-z]*)\$\S+|\b[a-fA-F0-9]{32,}\b|\b\d{13,19}\b/', '[redacted]', $value);
            $value = mb_substr($value, 0, 200);
        } elseif (!is_int($value) && !is_float($value) && !is_bool($value)) {
            continue;
        }
        $clean[$key] = $value;
    }
    if (!$clean) {
        return null;
    }
    return json_encode($clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: null;
}

/** Returns [user id, role] for the current request, preferring the admin session inside /admin/. */
function security_audit_actor(string $path): array
{
    $session = $_SESSION ?? [];
    $admin = isset($session['admin_id']) ? [(int)$session['admin_id'], $session['admin_role'] ?? null] : null;
    $user = isset($session['user_id']) ? [(int)$session['user_id'], $session['user_role'] ?? null] : null;
    if (strpos($path, '/admin/') !== false) {
        return $admin ?? $user ?? [null, null];
    }
    return $user ?? $admin ?? [null, null];
}

/**
 * Records one security event.
 *
 * $details accepts: identifier (email of the account concerned), user_id and role (when the session
 * does not hold them yet), target_type, target_id, and context (short non-sensitive key/value pairs).
 * Returns false when the event could not be stored.
 */
function security_log_event(string $eventType, string $outcome, array $details = []): bool
{
    $catalog = security_event_catalog();
    if (!isset($catalog[$eventType]) || !in_array($outcome, SECURITY_OUTCOMES, true)) {
        error_log('ShopEase audit log: rejected an unknown event type or outcome.');
        return false;
    }

    try {
        $db = security_audit_db();
        if ($db === null) {
            return false;
        }

        // The query string is left out on purpose: reset links carry their token there.
        $path = substr((string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'cli'), 0, 255);
        $method = substr((string)($_SERVER['REQUEST_METHOD'] ?? 'CLI'), 0, 10);
        // REMOTE_ADDR only: X-Forwarded-For is supplied by the client and could forge the record.
        $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'), 0, 45);

        [$sessionUserId, $sessionRole] = security_audit_actor($path);
        $userId = $details['user_id'] ?? $sessionUserId;
        $userId = $userId === null ? null : (int)$userId;
        $role = $details['role'] ?? $sessionRole;
        $role = $role === null ? null : substr((string)$role, 0, 20);

        $identifierHash = null;
        $identifierMasked = null;
        if (isset($details['identifier']) && trim((string)$details['identifier']) !== '') {
            $normalized = strtolower(trim((string)$details['identifier']));
            $identifierHash = security_audit_hmac($normalized);
            $identifierMasked = security_mask_identifier($normalized);
        }

        $targetType = isset($details['target_type']) ? substr((string)$details['target_type'], 0, 30) : null;
        $targetId = isset($details['target_id']) ? (int)$details['target_id'] : null;
        $context = security_sanitize_context($details['context'] ?? []);
        $category = $catalog[$eventType][1];

        $stmt = $db->prepare("INSERT INTO security_audit_log
            (event_type, category, outcome, actor_user_id, actor_role, identifier_hash, identifier_masked,
             ip_address, http_method, request_path, target_type, target_id, context)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            'sssisssssssis',
            $eventType, $category, $outcome, $userId, $role, $identifierHash, $identifierMasked,
            $ip, $method, $path, $targetType, $targetId, $context
        );
        $stmt->execute();
        $stmt->close();

        security_evaluate_rules($db, $eventType, $identifierHash, $identifierMasked, $ip, $userId);

        if (mt_rand(1, 100) === 1) {
            security_audit_purge();
        }
        return true;
    } catch (Throwable $exception) {
        error_log('ShopEase audit log: write failed (error ' . $exception->getCode() . ').');
        return false;
    }
}

/** Shorthand for a request refused by an authorization check. Member 2's checks can call this too. */
function security_log_access_denied(string $reason, array $context = []): void
{
    security_log_event('access_denied', 'denied', ['context' => ['reason' => $reason] + $context]);
}

/**
 * Applies every enabled rule that watches this event type.
 * An open alert is updated in place, so repeated events cannot create duplicates.
 */
function security_evaluate_rules(mysqli $db, string $eventType, ?string $identifierHash, ?string $identifierMasked, string $ip, ?int $userId): void
{
    foreach (security_audit_settings()['rules'] as $ruleKey => $rule) {
        if (empty($rule['enabled']) || !in_array($eventType, $rule['events'], true)) {
            continue;
        }

        if ($rule['group_by'] === 'identifier') {
            if ($identifierHash === null) {
                continue;
            }
            [$column, $types, $value, $display] = ['identifier_hash', 's', $identifierHash, (string)$identifierMasked];
        } elseif ($rule['group_by'] === 'actor_or_ip' && $userId !== null) {
            [$column, $types, $value, $display] = ['actor_user_id', 'i', $userId, 'User #' . $userId];
        } else {
            [$column, $types, $value, $display] = ['ip_address', 's', $ip, security_mask_ip($ip)];
        }

        $subjectKey = security_audit_hmac($ruleKey . '|' . $column . '|' . $value);

        // Events from before the last acknowledgement were already reviewed and do not count again.
        $stmt = $db->prepare("SELECT MAX(acknowledged_at) FROM security_alerts WHERE rule_key = ? AND subject_key = ?");
        $stmt->bind_param('ss', $ruleKey, $subjectKey);
        $stmt->execute();
        $stmt->bind_result($lastAcknowledged);
        $stmt->fetch();
        $stmt->close();

        $events = $rule['events'];
        $placeholders = implode(',', array_fill(0, count($events), '?'));
        $windowMinutes = (int)$rule['window_minutes'];
        $ackFloor = $lastAcknowledged ?? '1970-01-02 00:00:00';

        $stmt = $db->prepare("SELECT COUNT(*), MIN(created_at) FROM security_audit_log
            WHERE event_type IN ($placeholders) AND $column = ?
              AND created_at > (NOW() - INTERVAL ? MINUTE) AND created_at > ?");
        $stmt->bind_param(str_repeat('s', count($events)) . $types . 'is', ...array_merge($events, [$value, $windowMinutes, $ackFloor]));
        $stmt->execute();
        $stmt->bind_result($eventCount, $firstSeen);
        $stmt->fetch();
        $stmt->close();

        $threshold = (int)$rule['threshold'];
        if ($eventCount < $threshold) {
            continue;
        }

        $openKey = hash('sha256', $ruleKey . '|' . $subjectKey);
        $severity = $rule['severity'];
        $stmt = $db->prepare("INSERT INTO security_alerts
            (rule_key, severity, subject_key, subject_display, open_key, event_count, threshold_value, window_minutes, first_seen_at, last_seen_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE event_count = VALUES(event_count), last_seen_at = NOW()");
        $stmt->bind_param('sssssiiis', $ruleKey, $severity, $subjectKey, $display, $openKey, $eventCount, $threshold, $windowMinutes, $firstSeen);
        $stmt->execute();
        $isNewAlert = $stmt->affected_rows === 1;
        $stmt->close();

        if ($isNewAlert) {
            security_log_event('suspicious_activity_detected', 'info', [
                'context' => ['rule' => $ruleKey, 'subject' => $display, 'events' => $eventCount],
            ]);
        }
    }
}

/** Deletes audit events and acknowledged alerts older than the retention period. Returns [events, alerts]. */
function security_audit_purge(): array
{
    $db = security_audit_db();
    if ($db === null) {
        return [0, 0];
    }
    $days = (int)security_audit_settings()['retention_days'];

    $stmt = $db->prepare("DELETE FROM security_audit_log WHERE created_at < (NOW() - INTERVAL ? DAY)");
    $stmt->bind_param('i', $days);
    $stmt->execute();
    $events = $stmt->affected_rows;
    $stmt->close();

    $stmt = $db->prepare("DELETE FROM security_alerts WHERE status = 'acknowledged' AND acknowledged_at < (NOW() - INTERVAL ? DAY)");
    $stmt->bind_param('i', $days);
    $stmt->execute();
    $alerts = $stmt->affected_rows;
    $stmt->close();

    if ($events > 0 || $alerts > 0) {
        security_log_event('audit_retention_purge', 'info', ['context' => ['events_deleted' => $events, 'alerts_deleted' => $alerts]]);
    }
    return [$events, $alerts];
}
