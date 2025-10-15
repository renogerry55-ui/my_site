-- =====================================================
-- MP Coba External Sales Data Table
-- File: create_mp_coba_external_sales_data_table.sql
-- Purpose: Store structured external sales exports linked to MP Coba submissions
-- =====================================================

USE `br`;

-- Drop existing table if re-importing
DROP TABLE IF EXISTS `mp_coba_external_sales_data`;

-- Create table for storing MP Coba external sales exports in a structured format (19 columns)
CREATE TABLE `mp_coba_external_sales_data` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `submission_id` INT(11) UNSIGNED NOT NULL,
  `row_index` INT(11) UNSIGNED NOT NULL,

  -- Column 1: Login ID
  `login_id` VARCHAR(100) NOT NULL,

  -- Column 2: Full Name (Outlet/Agent Name)
  `full_name` VARCHAR(255) NOT NULL,

  -- Column 3: Downline Sales
  `downline_sales` VARCHAR(100) DEFAULT NULL,

  -- Column 4: Agent Sales
  `agent_sales` VARCHAR(100) DEFAULT NULL,

  -- Column 5: Agent Comm
  `agent_comm` VARCHAR(100) DEFAULT NULL,

  -- Column 6: Agent Payout
  `agent_payout` VARCHAR(100) DEFAULT NULL,

  -- Column 7: Agent Tax
  `agent_tax` VARCHAR(100) DEFAULT NULL,

  -- Column 8: Agent Balance
  `agent_balance` VARCHAR(100) DEFAULT NULL,

  -- Column 9: Manager Sales
  `manager_sales` VARCHAR(100) DEFAULT NULL,

  -- Column 10: Manager Comm
  `manager_comm` VARCHAR(100) DEFAULT NULL,

  -- Column 11: Manager Strike
  `manager_strike` VARCHAR(100) DEFAULT NULL,

  -- Column 12: Manager Tax
  `manager_tax` VARCHAR(100) DEFAULT NULL,

  -- Column 13: Company Sales
  `company_sales` VARCHAR(100) DEFAULT NULL,

  -- Column 14: Company Payout
  `company_payout` VARCHAR(100) DEFAULT NULL,

  -- Column 15: Company Tax
  `company_tax` VARCHAR(100) DEFAULT NULL,

  -- Column 16: Manager Earned Comm
  `manager_earned_comm` VARCHAR(100) DEFAULT NULL,

  -- Column 17: Manager Profit
  `manager_profit` VARCHAR(100) DEFAULT NULL,

  -- Column 18: Manager Earned Comm & Profit
  `manager_earned_comm_profit` VARCHAR(100) DEFAULT NULL,

  -- Column 19: Company Profit
  `company_profit` VARCHAR(100) DEFAULT NULL,

  -- Metadata
  `saved_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mp_coba_submission_row` (`submission_id`, `row_index`),
  KEY `idx_mp_coba_submission` (`submission_id`),
  KEY `idx_mp_coba_login_id` (`login_id`),
  KEY `idx_mp_coba_full_name` (`full_name`),
  CONSTRAINT `fk_mp_coba_external_sales_submission` FOREIGN KEY (`submission_id`) REFERENCES `daily_submissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mp_coba_external_sales_saved_by` FOREIGN KEY (`saved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Display success message
SELECT 'MP Coba External Sales Data table created successfully!' as message;
