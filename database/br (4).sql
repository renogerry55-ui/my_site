-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2025 at 04:58 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `br`
--

-- --------------------------------------------------------

--
-- Table structure for table `berhad_external_sales_data`
--

CREATE TABLE `berhad_external_sales_data` (
  `id` int(11) UNSIGNED NOT NULL,
  `submission_id` int(11) UNSIGNED NOT NULL,
  `row_index` int(11) UNSIGNED NOT NULL,
  `agent_identifier` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `level` varchar(100) DEFAULT NULL,
  `deposit_count` varchar(100) DEFAULT NULL,
  `total_deposit` varchar(100) DEFAULT NULL,
  `withdraw_count` varchar(100) DEFAULT NULL,
  `total_withdraw` varchar(100) DEFAULT NULL,
  `total` varchar(100) DEFAULT NULL,
  `saved_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `berhad_external_sales_data`
--

INSERT INTO `berhad_external_sales_data` (`id`, `submission_id`, `row_index`, `agent_identifier`, `name`, `level`, `deposit_count`, `total_deposit`, `withdraw_count`, `total_withdraw`, `total`, `saved_by`, `created_at`, `updated_at`) VALUES
(1, 13, 0, 'pasar888', 'outlet A', 'Agent', '515', '1000', '175', '0', '10,782.60', 2, '2025-10-14 05:38:29', '2025-10-14 05:38:29'),
(2, 14, 1, 'senadin8', 'outlet B', 'Agent', '68', '2000', '13', '0', '259', 2, '2025-10-14 05:38:29', '2025-10-14 05:38:29');

-- --------------------------------------------------------

--
-- Table structure for table `daily_submissions`
--

CREATE TABLE `daily_submissions` (
  `id` int(11) UNSIGNED NOT NULL,
  `submission_code` varchar(50) NOT NULL,
  `batch_code` varchar(50) DEFAULT NULL,
  `outlet_id` int(11) UNSIGNED NOT NULL,
  `manager_id` int(11) UNSIGNED NOT NULL,
  `submission_date` date NOT NULL,
  `berhad_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `mp_coba_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `mp_perdana_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `market_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_income` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_expenses` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount_to_hq` decimal(15,2) DEFAULT NULL COMMENT 'Amount manager chooses to send to HQ',
  `cash_kept` decimal(15,2) DEFAULT 0.00 COMMENT 'Cash manager keeps (net_amount - amount_to_hq)',
  `variance_reason` text DEFAULT NULL COMMENT 'Required explanation if amount_to_hq != net_amount',
  `variance_supporting_doc` varchar(255) DEFAULT NULL COMMENT 'Optional supporting document for variance',
  `status` enum('draft','pending','resubmit','submitted_to_finance','recheck','approved','verified','rejected','revised') NOT NULL DEFAULT 'draft',
  `verified_by` int(11) UNSIGNED DEFAULT NULL,
  `approved_by_finance` int(11) UNSIGNED DEFAULT NULL COMMENT 'Finance user who approved',
  `verified_at` datetime DEFAULT NULL,
  `approved_by_finance_at` datetime DEFAULT NULL COMMENT 'When finance approved',
  `returned_to_accountant_at` datetime DEFAULT NULL COMMENT 'When finance sent back to accountant',
  `returned_to_manager_at` datetime DEFAULT NULL COMMENT 'When accountant sent back to manager',
  `submitted_to_hq_at` datetime DEFAULT NULL,
  `submitted_to_finance_at` datetime DEFAULT NULL COMMENT 'When accountant submitted to finance',
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `accountant_notes` text DEFAULT NULL COMMENT 'Notes from accountant to manager or finance',
  `finance_notes` text DEFAULT NULL COMMENT 'Notes from finance to accountant',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_submissions`
--

INSERT INTO `daily_submissions` (`id`, `submission_code`, `batch_code`, `outlet_id`, `manager_id`, `submission_date`, `berhad_sales`, `mp_coba_sales`, `mp_perdana_sales`, `market_sales`, `total_income`, `total_expenses`, `net_amount`, `amount_to_hq`, `cash_kept`, `variance_reason`, `variance_supporting_doc`, `status`, `verified_by`, `approved_by_finance`, `verified_at`, `approved_by_finance_at`, `returned_to_accountant_at`, `returned_to_manager_at`, `submitted_to_hq_at`, `submitted_to_finance_at`, `rejection_reason`, `notes`, `accountant_notes`, `finance_notes`, `created_at`, `updated_at`) VALUES
(13, 'SUB-20251014-001-AC63', 'BATCH-20251014-1-5E84A8', 1, 1, '2025-10-14', 1000.00, 1000.00, 1000.00, 0.00, 3000.00, 30.00, 2970.00, 5000.00, 670.00, 'need money', NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-14 12:29:00', NULL, NULL, '', NULL, NULL, '2025-10-14 04:25:10', '2025-10-14 04:29:00'),
(14, 'SUB-20251014-002-6D71', 'BATCH-20251014-1-5E84A8', 2, 1, '2025-10-14', 2000.00, 2000.00, 2000.00, 0.00, 6000.00, 3300.00, 2700.00, 5000.00, 670.00, 'need money', NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-14 12:29:00', NULL, NULL, '', NULL, NULL, '2025-10-14 04:27:33', '2025-10-14 04:29:00');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) UNSIGNED NOT NULL,
  `submission_id` int(11) UNSIGNED NOT NULL,
  `expense_category_id` int(11) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `is_categorized` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether expense has been categorized by accountant',
  `categorized_by` int(11) UNSIGNED DEFAULT NULL COMMENT 'User ID of accountant who categorized this expense',
  `categorized_at` datetime DEFAULT NULL COMMENT 'Timestamp when expense was categorized',
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' COMMENT 'Approval status by accountant',
  `approved_by` int(11) UNSIGNED DEFAULT NULL COMMENT 'Accountant who approved/rejected',
  `approved_at` datetime DEFAULT NULL COMMENT 'When expense was approved/rejected',
  `rejection_reason` text DEFAULT NULL COMMENT 'Reason for rejection if rejected',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `submission_id`, `expense_category_id`, `amount`, `description`, `receipt_file`, `is_categorized`, `categorized_by`, `categorized_at`, `approval_status`, `approved_by`, `approved_at`, `rejection_reason`, `created_at`) VALUES
(20, 13, 20, 10.00, 'claimed', 'SUB-20251014-001-AC63_68edd0a6e961d.png', 0, NULL, NULL, 'pending', NULL, NULL, NULL, '2025-10-14 04:25:10'),
(21, 13, 20, 20.00, '', 'SUB-20251014-001-AC63_68edd0a6ea32f.png', 0, NULL, NULL, 'pending', NULL, NULL, NULL, '2025-10-14 04:25:10'),
(22, 14, 20, 3000.00, '', 'SUB-20251014-002-6D71_68edd1359e7b2.png', 0, NULL, NULL, 'pending', NULL, NULL, NULL, '2025-10-14 04:27:33'),
(23, 14, 20, 100.00, '', 'SUB-20251014-002-6D71_68edd1359f067.png', 0, NULL, NULL, 'pending', NULL, NULL, NULL, '2025-10-14 04:27:33'),
(24, 14, 20, 200.00, '', 'SUB-20251014-002-6D71_68edd135a0609.png', 0, NULL, NULL, 'pending', NULL, NULL, NULL, '2025-10-14 04:27:33');

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) UNSIGNED NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_type` enum('mp_berhad','market') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `category_name`, `category_type`, `description`, `status`, `created_at`) VALUES
(1, 'Staff Salary', 'mp_berhad', 'Monthly staff salaries and wages', 'active', '2025-10-08 10:11:53'),
(2, 'Rent', 'mp_berhad', 'Shop rental payment', 'active', '2025-10-08 10:11:53'),
(3, 'Utilities', 'mp_berhad', 'Electricity, water, internet bills', 'active', '2025-10-08 10:11:53'),
(4, 'Transportation', 'mp_berhad', 'Delivery and logistics costs', 'active', '2025-10-08 10:11:53'),
(5, 'Maintenance', 'mp_berhad', 'Equipment and facility maintenance', 'active', '2025-10-08 10:11:53'),
(6, 'Supplies', 'mp_berhad', 'Office and operational supplies', 'active', '2025-10-08 10:11:53'),
(7, 'Marketing', 'mp_berhad', 'Advertising and promotion expenses', 'active', '2025-10-08 10:11:53'),
(8, 'Insurance', 'mp_berhad', 'Business insurance premiums', 'active', '2025-10-08 10:11:53'),
(9, 'Miscellaneous', 'mp_berhad', 'Other miscellaneous expenses', 'active', '2025-10-08 10:11:53'),
(10, 'Purchase Goods', 'market', 'Stock and inventory purchases', 'active', '2025-10-08 10:11:53'),
(11, 'Vendor Payment', 'market', 'Payment to suppliers', 'active', '2025-10-08 10:11:53'),
(12, 'Delivery Fees', 'market', 'Market delivery charges', 'active', '2025-10-08 10:11:53'),
(13, 'Packaging', 'market', 'Packaging materials', 'active', '2025-10-08 10:11:53'),
(14, 'Market Rent', 'market', 'Market stall rental', 'active', '2025-10-08 10:11:53'),
(15, 'Market Utilities', 'market', 'Market utilities and services', 'active', '2025-10-08 10:11:53'),
(16, 'Market Supplies', 'market', 'Market operational supplies', 'active', '2025-10-08 10:11:53'),
(17, 'Market Miscellaneous', 'market', 'Other market expenses', 'active', '2025-10-08 10:11:53'),
(18, 'MP_COBA', 'mp_berhad', 'MP_COBA claimed expenses', 'active', '2025-10-09 12:05:33'),
(19, 'BERHAD', 'mp_berhad', 'BERHAD claimed expenses', 'active', '2025-10-09 12:05:33'),
(20, 'Uncategorized', 'mp_berhad', 'Default category for expenses pending accountant categorization', 'active', '2025-10-14 04:15:03');

-- --------------------------------------------------------

--
-- Table structure for table `manager_cash_on_hand`
--

CREATE TABLE `manager_cash_on_hand` (
  `id` int(11) UNSIGNED NOT NULL,
  `manager_id` int(11) UNSIGNED NOT NULL,
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Current accumulated cash balance',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last time balance was updated',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `manager_cash_on_hand`
--

INSERT INTO `manager_cash_on_hand` (`id`, `manager_id`, `current_balance`, `last_updated`, `created_at`) VALUES
(1, 1, 670.00, '2025-10-14 04:29:00', '2025-10-14 04:17:13');

-- --------------------------------------------------------

--
-- Table structure for table `mp_coba_external_sales_data`
--

CREATE TABLE `mp_coba_external_sales_data` (
  `id` int(11) UNSIGNED NOT NULL,
  `submission_id` int(11) UNSIGNED NOT NULL,
  `row_index` int(11) UNSIGNED NOT NULL,
  `login_id` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `downline_sales` varchar(100) DEFAULT NULL,
  `agent_sales` varchar(100) DEFAULT NULL,
  `agent_comm` varchar(100) DEFAULT NULL,
  `agent_payout` varchar(100) DEFAULT NULL,
  `agent_tax` varchar(100) DEFAULT NULL,
  `agent_balance` varchar(100) DEFAULT NULL,
  `manager_sales` varchar(100) DEFAULT NULL,
  `manager_comm` varchar(100) DEFAULT NULL,
  `manager_strike` varchar(100) DEFAULT NULL,
  `manager_tax` varchar(100) DEFAULT NULL,
  `company_sales` varchar(100) DEFAULT NULL,
  `company_payout` varchar(100) DEFAULT NULL,
  `company_tax` varchar(100) DEFAULT NULL,
  `manager_earned_comm` varchar(100) DEFAULT NULL,
  `manager_profit` varchar(100) DEFAULT NULL,
  `manager_earned_comm_profit` varchar(100) DEFAULT NULL,
  `company_profit` varchar(100) DEFAULT NULL,
  `saved_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `outlets`
--

CREATE TABLE `outlets` (
  `id` int(11) UNSIGNED NOT NULL,
  `outlet_code` varchar(20) NOT NULL,
  `outlet_name` varchar(100) NOT NULL,
  `berhad_agent_id` varchar(100) DEFAULT NULL COMMENT 'Berhad external system Agent ID',
  `mp_coba_login_id` varchar(100) DEFAULT NULL COMMENT 'MP Coba external system Login ID',
  `mp_perdana_login_id` varchar(100) DEFAULT NULL COMMENT 'MP Perdana external system Login ID',
  `location` varchar(255) DEFAULT NULL,
  `manager_id` int(11) UNSIGNED NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `outlets`
--

INSERT INTO `outlets` (`id`, `outlet_code`, `outlet_name`, `berhad_agent_id`, `mp_coba_login_id`, `mp_perdana_login_id`, `location`, `manager_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'OUT-A', 'Outlet A', 'pasar888', 'suncity', 'petronas', 'Location A - Main Street', 1, 'active', '2025-10-08 10:11:53', '2025-10-13 08:35:40'),
(2, 'OUT-B', 'Outlet B', 'senadin8', 'br1 ', 'br1 ', 'Location B - Downtown', 1, 'active', '2025-10-08 10:11:53', '2025-10-13 08:36:28'),
(3, 'OUT-C', 'Outlet C', 'pasar81', 'chrisbr333', 'chrisbr2', 'Location C - Mall Plaza', 1, 'active', '2025-10-08 10:11:53', '2025-10-13 08:37:28'),
(4, 'OUT-D', 'Outlet D', NULL, NULL, NULL, 'Location D - North District', 1, 'active', '2025-10-08 10:11:53', '2025-10-08 10:11:53');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('manager','account','ceo','admin','finance') NOT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `username`, `password_hash`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Chris Manager ', 'manager@mysite.com', 'manager', '$argon2id$v=19$m=65536,t=4,p=1$eWpaazhXaWdrWk41eU4yTw$3MTO2I4nskA8Wc3Q5XltOdhj8m4BkuIuFs1ZMacXeTE', 'manager', 'active', '2025-10-14 17:07:09', '2025-10-08 08:55:51', '2025-10-14 09:07:09'),
(2, 'Sarah Accountant', 'account@mysite.com', 'accountant', '$argon2id$v=19$m=65536,t=4,p=1$TFhid1YvMzl1bTJEU1kyTw$XQ+TLktwyfL/goKrb2v33g7d10gy5bTx5j++8icehuA', 'account', 'active', '2025-10-14 17:05:42', '2025-10-08 08:55:51', '2025-10-14 09:05:42'),
(3, 'Michael CEO', 'ceo@mysite.com', 'ceo', '$argon2id$v=19$m=65536,t=4,p=1$VWl6T1hCR1VBeUJPWnpZWg$jrrmxzG7FPNNvcucr16l9xKZCcOjgwPxs7e1hfv4AE4', 'ceo', 'active', '2025-10-10 20:27:32', '2025-10-08 08:55:52', '2025-10-10 12:27:32'),
(4, 'admin', 'admin@mysite.com', 'admin', '$argon2id$v=19$m=65536,t=4,p=1$Q1lncURyUHc2Ly5XNlRHRA$fgUoE2zJmmThayhC2TAKIs+G/9Qx3S2nv9X1E++QcD4', 'admin', 'active', NULL, '2025-10-08 08:55:53', '2025-10-13 08:59:33'),
(5, 'Finance Officer', 'finance@mysite.com', 'finance', '$argon2id$v=19$m=65536,t=4,p=1$eWpaazhXaWdrWk41eU4yTw$3MTO2I4nskA8Wc3Q5XltOdhj8m4BkuIuFs1ZMacXeTE', 'finance', 'active', NULL, '2025-10-13 10:05:11', '2025-10-14 04:04:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `berhad_external_sales_data`
--
ALTER TABLE `berhad_external_sales_data`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_submission_row` (`submission_id`,`row_index`),
  ADD KEY `idx_submission` (`submission_id`),
  ADD KEY `idx_agent_submission` (`agent_identifier`,`submission_id`),
  ADD KEY `fk_external_sales_saved_by` (`saved_by`);

--
-- Indexes for table `daily_submissions`
--
ALTER TABLE `daily_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_submission_code` (`submission_code`),
  ADD KEY `idx_outlet_id` (`outlet_id`),
  ADD KEY `idx_manager_id` (`manager_id`),
  ADD KEY `idx_submission_date` (`submission_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_submission_verifier` (`verified_by`),
  ADD KEY `idx_submission_outlet_date` (`outlet_id`,`submission_date`),
  ADD KEY `idx_submission_manager_status` (`manager_id`,`status`),
  ADD KEY `idx_batch_code` (`batch_code`),
  ADD KEY `idx_manager_status` (`manager_id`,`status`),
  ADD KEY `idx_submission_date_status` (`submission_date`,`status`),
  ADD KEY `idx_submitted_to_finance_at` (`submitted_to_finance_at`),
  ADD KEY `idx_approved_by_finance` (`approved_by_finance`),
  ADD KEY `idx_status_finance` (`status`,`submitted_to_finance_at`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_submission_id` (`submission_id`),
  ADD KEY `idx_category_id` (`expense_category_id`),
  ADD KEY `idx_is_categorized` (`is_categorized`),
  ADD KEY `idx_categorized_by` (`categorized_by`),
  ADD KEY `fk_expense_approved_by` (`approved_by`),
  ADD KEY `idx_approval_status` (`approval_status`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_type` (`category_type`);

--
-- Indexes for table `manager_cash_on_hand`
--
ALTER TABLE `manager_cash_on_hand`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_manager_cash` (`manager_id`),
  ADD KEY `idx_manager_id` (`manager_id`);

--
-- Indexes for table `mp_coba_external_sales_data`
--
ALTER TABLE `mp_coba_external_sales_data`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_mp_coba_submission_row` (`submission_id`,`row_index`),
  ADD KEY `idx_mp_coba_submission` (`submission_id`),
  ADD KEY `idx_mp_coba_login_id` (`login_id`),
  ADD KEY `idx_mp_coba_full_name` (`full_name`),
  ADD KEY `fk_mp_coba_external_sales_saved_by` (`saved_by`);

--
-- Indexes for table `outlets`
--
ALTER TABLE `outlets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_outlet_code` (`outlet_code`),
  ADD KEY `idx_manager_id` (`manager_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_berhad_agent_id` (`berhad_agent_id`),
  ADD KEY `idx_mp_coba_login_id` (`mp_coba_login_id`),
  ADD KEY `idx_mp_perdana_login_id` (`mp_perdana_login_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD UNIQUE KEY `unique_username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `berhad_external_sales_data`
--
ALTER TABLE `berhad_external_sales_data`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `daily_submissions`
--
ALTER TABLE `daily_submissions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `manager_cash_on_hand`
--
ALTER TABLE `manager_cash_on_hand`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `mp_coba_external_sales_data`
--
ALTER TABLE `mp_coba_external_sales_data`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `outlets`
--
ALTER TABLE `outlets`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `berhad_external_sales_data`
--
ALTER TABLE `berhad_external_sales_data`
  ADD CONSTRAINT `fk_external_sales_saved_by` FOREIGN KEY (`saved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_external_sales_submission` FOREIGN KEY (`submission_id`) REFERENCES `daily_submissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `daily_submissions`
--
ALTER TABLE `daily_submissions`
  ADD CONSTRAINT `fk_submission_finance_approver` FOREIGN KEY (`approved_by_finance`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_submission_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_submission_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_submission_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expense_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_expense_categorized_by` FOREIGN KEY (`categorized_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_expense_category` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`),
  ADD CONSTRAINT `fk_expense_submission` FOREIGN KEY (`submission_id`) REFERENCES `daily_submissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `manager_cash_on_hand`
--
ALTER TABLE `manager_cash_on_hand`
  ADD CONSTRAINT `fk_cash_on_hand_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mp_coba_external_sales_data`
--
ALTER TABLE `mp_coba_external_sales_data`
  ADD CONSTRAINT `fk_mp_coba_external_sales_saved_by` FOREIGN KEY (`saved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mp_coba_external_sales_submission` FOREIGN KEY (`submission_id`) REFERENCES `daily_submissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `outlets`
--
ALTER TABLE `outlets`
  ADD CONSTRAINT `fk_outlet_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
