-- =====================================================
-- Manager System Tables
-- File: 002_create_manager_tables.sql
-- Import Order: 3
-- =====================================================

USE `br`;

-- =====================================================
-- OUTLETS TABLE
-- =====================================================
DROP TABLE IF EXISTS `outlets`;

CREATE TABLE `outlets` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `outlet_code` VARCHAR(20) NOT NULL,
  `outlet_name` VARCHAR(100) NOT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `manager_id` INT(11) UNSIGNED NOT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_outlet_code` (`outlet_code`),
  KEY `idx_manager_id` (`manager_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_outlet_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- EXPENSE CATEGORIES TABLE
-- =====================================================
DROP TABLE IF EXISTS `expense_categories`;

CREATE TABLE `expense_categories` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(100) NOT NULL,
  `category_type` ENUM('mp_berhad', 'market') NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category_type` (`category_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DAILY SUBMISSIONS TABLE
-- =====================================================
DROP TABLE IF EXISTS `daily_submissions`;

CREATE TABLE `daily_submissions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `submission_code` VARCHAR(50) NOT NULL,
  `outlet_id` INT(11) UNSIGNED NOT NULL,
  `manager_id` INT(11) UNSIGNED NOT NULL,
  `submission_date` DATE NOT NULL,

  -- INCOME: Berhad Sales
  `berhad_sales` DECIMAL(15,2) NOT NULL DEFAULT 0.00,

  -- INCOME: MP Sales (2 categories)
  `mp_coba_sales` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `mp_perdana_sales` DECIMAL(15,2) NOT NULL DEFAULT 0.00,

  -- INCOME: Market
  `market_sales` DECIMAL(15,2) NOT NULL DEFAULT 0.00,

  -- AUTO CALCULATED TOTALS
  `total_income` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_expenses` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `net_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,

  -- STATUS & WORKFLOW
  `status` ENUM('pending', 'verified', 'rejected', 'revised') NOT NULL DEFAULT 'pending',
  `verified_by` INT(11) UNSIGNED DEFAULT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,

  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_submission_code` (`submission_code`),
  KEY `idx_outlet_id` (`outlet_id`),
  KEY `idx_manager_id` (`manager_id`),
  KEY `idx_submission_date` (`submission_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_submission_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_submission_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_submission_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- EXPENSES TABLE (Line Items)
-- =====================================================
DROP TABLE IF EXISTS `expenses`;

CREATE TABLE `expenses` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `submission_id` INT(11) UNSIGNED NOT NULL,
  `expense_category_id` INT(11) UNSIGNED NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `description` VARCHAR(255) DEFAULT NULL,
  `receipt_file` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_submission_id` (`submission_id`),
  KEY `idx_category_id` (`expense_category_id`),
  CONSTRAINT `fk_expense_submission` FOREIGN KEY (`submission_id`) REFERENCES `daily_submissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_expense_category` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX idx_submission_outlet_date ON daily_submissions(outlet_id, submission_date);
CREATE INDEX idx_submission_manager_status ON daily_submissions(manager_id, status);
