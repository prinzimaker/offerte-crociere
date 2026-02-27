-- =============================================================================
-- MSC API Proxy — Schema del database
--
-- Questo file crea tutte le tabelle necessarie per il funzionamento
-- del sistema di logging e della piattaforma admin.
-- =============================================================================

-- Tabella principale dei log API
CREATE TABLE IF NOT EXISTS `api_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `function_name` VARCHAR(255) NOT NULL,
    `data` LONGTEXT NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `http_method` VARCHAR(10) DEFAULT 'POST',
    `headers` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_function` (`function_name`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_function_date` (`function_name`, `created_at`),
    INDEX `idx_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella utenti per la piattaforma admin del Log Viewer
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `display_name` VARCHAR(255) NULL,
    `role` ENUM('admin', 'viewer') NOT NULL DEFAULT 'viewer',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_username` (`username`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserimento utente admin di default (password: admin — DA CAMBIARE in produzione)
-- La password è hashata con password_hash() di PHP usando PASSWORD_DEFAULT (bcrypt)
-- Per generare un nuovo hash: php -r "echo password_hash('nuova_password', PASSWORD_DEFAULT);"
INSERT INTO `admin_users` (`username`, `password_hash`, `display_name`, `role`, `is_active`)
VALUES ('admin', 'admin', 'Amministratore', 'admin', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;
