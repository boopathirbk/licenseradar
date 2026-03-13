-- ═══════════════════════════════════════════════════════════════════════
-- LicenseRadar — Database Schema
-- MySQL 8.0+ / MariaDB 10.6+ · InnoDB · utf8mb4
-- ═══════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Users ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`      VARCHAR(50)  NOT NULL UNIQUE,
    `email`         VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role`          ENUM('admin','viewer') NOT NULL DEFAULT 'admin',
    `theme`         VARCHAR(5)   NOT NULL DEFAULT 'dark',
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Sessions ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sessions` (
    `id`         CHAR(64)     NOT NULL PRIMARY KEY,
    `user_id`    INT UNSIGNED NOT NULL,
    `ip_address` VARCHAR(45)  DEFAULT NULL,
    `user_agent` VARCHAR(512) DEFAULT NULL,
    `expires_at` TIMESTAMP    NOT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2FA: Email OTP ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `two_factor_email` (
    `id`      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `enabled` TINYINT(1)   NOT NULL DEFAULT 0,
    `code`    VARCHAR(6)   DEFAULT NULL,
    `expires` TIMESTAMP    NULL DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2FA: TOTP ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `two_factor_totp` (
    `id`      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `secret`  VARCHAR(64)  NOT NULL,
    `enabled` TINYINT(1)   NOT NULL DEFAULT 0,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2FA: Passkeys (WebAuthn) ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `passkeys` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL,
    `credential_id` VARBINARY(255) NOT NULL,
    `public_key`    BLOB         NOT NULL,
    `name`          VARCHAR(100) DEFAULT NULL,
    `sign_count`    INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_credential` (`credential_id`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Login Attempts (rate limiting) ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address`   VARCHAR(45)  NOT NULL,
    `attempted_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Audit Log ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED DEFAULT NULL,
    `action`     VARCHAR(50)  NOT NULL,
    `detail`     TEXT         DEFAULT NULL,
    `ip_address` VARCHAR(45)  DEFAULT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_action_time` (`action`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Settings ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key`   VARCHAR(100) NOT NULL PRIMARY KEY,
    `setting_value` TEXT         NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── SKU Pricing ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sku_pricing` (
    `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sku_id`    VARCHAR(100) NOT NULL UNIQUE,
    `sku_name`  VARCHAR(255) NOT NULL,
    `price_usd` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `price_inr` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `is_custom` TINYINT(1)   NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Pre-loaded SKU Prices (common M365 plans) ───────────────────────
INSERT INTO `sku_pricing` (`sku_id`, `sku_name`, `price_usd`, `price_inr`) VALUES
('05e9a617-0261-4cee-bb36-b42a42367b1b', 'Microsoft 365 E5',                57.00, 4750.00),
('06ebc4ee-1bb5-47dd-8120-11324bc54e06', 'Microsoft 365 E3',                36.00, 3000.00),
('4b585984-651b-4235-8c24-3b0f5e89f8e8', 'Microsoft 365 Business Premium',  22.00, 1833.00),
('f245ecc8-75af-4f8e-b61f-27d8114de5f3', 'Microsoft 365 Business Standard', 12.50, 1042.00),
('cbdc14ab-d96c-4c30-b9f4-6ada7cdc1d46', 'Microsoft 365 Business Basic',     6.00,  500.00),
('18181a46-0d4e-45cd-891e-60aabd171b4e', 'Office 365 E1',                    8.00,  667.00),
('6fd2c87f-b296-42f0-b197-1e91e994b900', 'Office 365 E3',                   23.00, 1917.00),
('c7df2760-2c81-4ef7-bb8e-fb5e7aca4c1d', 'Office 365 E5',                   38.00, 3167.00),
('4ef96642-f096-40de-a3e9-d83fb2f90211', 'Exchange Online Plan 1',           4.00,  333.00),
('19ec0d23-8335-4cbd-94ac-6050e5b3d6b2', 'Exchange Online Plan 2',           8.00,  667.00),
('c5928f49-12ba-48f7-ada3-0d743a3601d5', 'Microsoft Teams Essentials',       4.00,  333.00),
('a403ebcc-fae0-4ca2-8c8c-7a907fd6c235', 'Power BI Pro',                    10.00,  833.00)
ON DUPLICATE KEY UPDATE `sku_name` = VALUES(`sku_name`);

SET FOREIGN_KEY_CHECKS = 1;
