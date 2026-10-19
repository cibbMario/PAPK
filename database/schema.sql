-- PAPK Member Management System Database Schema
-- ------------------------------------------------------------
-- This script creates all tables required for the PAPK website.
-- Execute it with the MySQL user that has CREATE/DROP privileges.

-- Users table (authentication & role management)
CREATE TABLE IF NOT EXISTS `users` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`     VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role`         ENUM('admin','user') NOT NULL DEFAULT 'user',
    `email`        VARCHAR(100) NULL,
    `failed_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Members table (core member data)
CREATE TABLE IF NOT EXISTS `members` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nrp_nia`          VARCHAR(30) NOT NULL UNIQUE,
    `name`             VARCHAR(100) NOT NULL,
    `rank`             VARCHAR(50) NOT NULL,
    `unit`             VARCHAR(100) NOT NULL,
    `status`           ENUM('aktif','non-aktif','pensiun') NOT NULL DEFAULT 'aktif',
    `photo_path`       VARCHAR(255) NULL,
    `address`          TEXT NULL,
    `phone`            VARCHAR(20) NULL,
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Member families (optional, for storing family data)
CREATE TABLE IF NOT EXISTS `member_families` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `member_id`     INT UNSIGNED NOT NULL,
    `relation`      VARCHAR(30) NOT NULL, -- e.g., 'spouse', 'child'
    `name`          VARCHAR(100) NOT NULL,
    `birth_date`    DATE NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Change requests submitted by users (pending approval by admin)
CREATE TABLE IF NOT EXISTS `change_requests` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `member_id`     INT UNSIGNED NOT NULL,
    `requested_by`  INT UNSIGNED NOT NULL,
    `field`         VARCHAR(50) NOT NULL,      -- which column to change
    `old_value`     TEXT NULL,
    `new_value`     TEXT NOT NULL,
    `status`        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_note`    TEXT NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Announcements / internal news
CREATE TABLE IF NOT EXISTS `announcements` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title`       VARCHAR(150) NOT NULL,
    `content`     TEXT NOT NULL,
    `created_by`  INT UNSIGNED NOT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity log (audit trail)
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`      INT UNSIGNED NOT NULL,
    `action`       VARCHAR(255) NOT NULL,
    `details`      TEXT NULL,
    `ip_address`   VARCHAR(45) NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes for faster search on members
CREATE INDEX idx_members_name ON `members`(`name`);
CREATE INDEX idx_members_rank ON `members`(`rank`);
CREATE INDEX idx_members_unit ON `members`(`unit`);
CREATE INDEX idx_members_status ON `members`(`status`);

-- Optional: Table for storing notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`      INT UNSIGNED NOT NULL,
    `type`         VARCHAR(50) NOT NULL, -- e.g., 'change_approved'
    `message`      TEXT NOT NULL,
    `is_read`      TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- End of schema
