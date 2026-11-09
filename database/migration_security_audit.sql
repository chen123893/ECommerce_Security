-- ============================================================================
-- ShopEase Member 5: security audit log and suspicious activity alerts
-- The application also creates these tables on first use (includes/security_audit.php).
-- Keep both definitions the same.
-- ============================================================================

USE `ecommerce`;

CREATE TABLE IF NOT EXISTS `security_audit_log` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `security_alerts` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
