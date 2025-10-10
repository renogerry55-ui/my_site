-- =====================================================
-- Berhad External Sales Data Table
-- File: create_berhad_external_sales_data_table.sql
-- Purpose: Store structured external sales exports linked to Berhad submissions
-- =====================================================

USE `br`;

-- Drop existing table if re-importing
DROP TABLE IF EXISTS `berhad_external_sales_data`;

-- Create table for storing Berhad external sales exports in a structured format
CREATE TABLE `berhad_external_sales_data` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `submission_id` INT(11) UNSIGNED NOT NULL,
  `row_index` INT(11) UNSIGNED NOT NULL,
  `agent_identifier` VARCHAR(100) NOT NULL,
  `outlet_name` VARCHAR(255) NOT NULL,
  `level` VARCHAR(100) DEFAULT NULL,
  `deposit` VARCHAR(100) DEFAULT NULL,
  `deposit_count` VARCHAR(100) DEFAULT NULL,
  `total_deposit` VARCHAR(100) DEFAULT NULL,
  `withdraw_count` VARCHAR(100) DEFAULT NULL,
  `total_withdraw` VARCHAR(100) DEFAULT NULL,
  `total` VARCHAR(100) DEFAULT NULL,
  `saved_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_submission_row` (`submission_id`, `row_index`),
  KEY `idx_submission` (`submission_id`),
  KEY `idx_agent_submission` (`agent_identifier`, `submission_id`),
  CONSTRAINT `fk_external_sales_submission` FOREIGN KEY (`submission_id`) REFERENCES `daily_submissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_external_sales_saved_by` FOREIGN KEY (`saved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
