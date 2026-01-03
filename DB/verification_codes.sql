-- ============================================
-- Tabel verification_codes untuk email verification
-- Support untuk: Register, Forgot Password, dll
-- ============================================

CREATE TABLE IF NOT EXISTS `verification_codes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `code` VARCHAR(10) NOT NULL,
    `type` ENUM('register', 'password_reset', 'email_change') NOT NULL DEFAULT 'register',
    `user_id` INT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email_type` (`email`, `type`),
    INDEX `idx_code` (`code`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabel pending_registrations untuk menyimpan data registrasi sementara
-- Data akan dipindah ke users setelah verifikasi email
-- ============================================

CREATE TABLE IF NOT EXISTS `pending_registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `verification_code` VARCHAR(10) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tambahan settings untuk email verification
-- Jalankan setelah tabel settings ada
-- ============================================

INSERT INTO `settings` (`key`, `value`, `group`, `created_at`, `updated_at`) VALUES
('email_verification_enabled', '1', 'email', NOW(), NOW()),
('email_verification_code_length', '6', 'email', NOW(), NOW()),
('email_verification_expiry_minutes', '15', 'email', NOW(), NOW()),
('email_verification_resend_cooldown', '60', 'email', NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();
