-- ============================================================================
-- ShopEase E-Commerce Security Enhancements Migration
-- 1. Strong Password Policy & Argon2id/Bcrypt Support
-- 2. Login Rate Limiting Table
-- 3. Secure Password Reset Tokens Table
-- 4. Admin Two-Factor Authentication (MFA / TOTP)
-- ============================================================================

USE `ecommerce`;

-- 1. Add Two-Factor Authentication columns to `users` table
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `two_factor_secret` varchar(255) DEFAULT NULL AFTER `role`,
  ADD COLUMN IF NOT EXISTS `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0 AFTER `two_factor_secret`,
  ADD COLUMN IF NOT EXISTS `two_factor_backup_codes` text DEFAULT NULL AFTER `two_factor_enabled`;

-- 2. Create `login_attempts` table for IP and account login rate limiting
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(150) NOT NULL,
  `attempted_at` datetime NOT NULL,
  `is_success` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ip_attempt` (`ip_address`, `attempted_at`),
  KEY `idx_email_attempt` (`email`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Create `password_resets` table for high-entropy SHA-256 tokens
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(150) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_token_hash` (`token_hash`),
  KEY `idx_user_expires` (`user_id`, `expires_at`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
