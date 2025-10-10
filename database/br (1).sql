-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 10, 2025 at 01:33 PM
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
  `status` enum('draft','submitted','pending','verified','rejected','revised') NOT NULL DEFAULT 'draft',
  `verified_by` int(11) UNSIGNED DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `submitted_to_hq_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_submissions`
--

INSERT INTO `daily_submissions` (`id`, `submission_code`, `batch_code`, `outlet_id`, `manager_id`, `submission_date`, `berhad_sales`, `mp_coba_sales`, `mp_perdana_sales`, `market_sales`, `total_income`, `total_expenses`, `net_amount`, `status`, `verified_by`, `verified_at`, `submitted_to_hq_at`, `rejection_reason`, `notes`, `created_at`, `updated_at`) VALUES
(7, 'SUB-20251009-001-1E19', 'BATCH-20251009-1-42DCF6', 1, 1, '2025-10-09', 100.00, 50.00, 50.00, 25.00, 225.00, 30.00, 195.00, 'pending', NULL, NULL, '2025-10-09 18:34:17', NULL, '', '2025-10-09 08:50:20', '2025-10-09 10:34:17'),
(8, 'SUB-20251009-002-1015', 'BATCH-20251009-1-42DCF6', 2, 1, '2025-10-09', 23123.00, 231231.00, 123123.00, 23123.00, 400600.00, 147870.00, 252730.00, 'pending', NULL, NULL, '2025-10-09 18:34:17', NULL, '', '2025-10-09 10:22:59', '2025-10-09 10:34:17'),
(9, 'SUB-20251009-003-FAE3', 'BATCH-20251009-1-C1268B', 3, 1, '2025-10-09', 1213123.00, 231231.00, 231231.00, 31232.00, 1706817.00, 2443.00, 1704374.00, 'pending', NULL, NULL, '2025-10-09 20:07:30', NULL, 'TESTING FOR ACCOUNTING APPROVAL', '2025-10-09 12:06:57', '2025-10-09 12:07:30'),
(10, 'SUB-20251010-001-7979', NULL, 1, 1, '2025-10-10', 1111.00, 1123123.00, 12312.00, 123.00, 1136669.00, 1123.00, 1135546.00, 'draft', NULL, NULL, NULL, NULL, '', '2025-10-10 03:42:53', '2025-10-10 03:42:53');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `submission_id`, `expense_category_id`, `amount`, `description`, `receipt_file`, `created_at`) VALUES
(8, 7, 8, 30.00, '', 'SUB-20251009-001-1E19_68e7774c34946.png', '2025-10-09 08:50:20'),
(9, 8, 5, 12312.00, '', 'SUB-20251009-002-1015_68e78d0362af0.png', '2025-10-09 10:22:59'),
(10, 8, 8, 123.00, '', 'SUB-20251009-002-1015_68e78d0364d19.png', '2025-10-09 10:22:59'),
(11, 8, 12, 12312.00, '', 'SUB-20251009-002-1015_68e78d03658cb.png', '2025-10-09 10:22:59'),
(12, 8, 17, 123123.00, '', 'SUB-20251009-002-1015_68e78d03675db.png', '2025-10-09 10:22:59'),
(13, 9, 19, 1212.00, 'claimed', 'SUB-20251009-003-FAE3_68e7a56106682.png', '2025-10-09 12:06:57'),
(14, 9, 18, 1231.00, 'claimed', 'SUB-20251009-003-FAE3_68e7a56106fac.png', '2025-10-09 12:06:57'),
(15, 10, 19, 1123.00, '', 'SUB-20251010-001-7979_68e880bd3ba83.png', '2025-10-10 03:42:53');

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
(18, 'MP', 'mp_berhad', 'MP claimed expenses', 'active', '2025-10-09 12:05:33'),
(19, 'BERHAD', 'mp_berhad', 'BERHAD claimed expenses', 'active', '2025-10-09 12:05:33');

-- --------------------------------------------------------

--
-- Table structure for table `outlets`
--

CREATE TABLE `outlets` (
  `id` int(11) UNSIGNED NOT NULL,
  `outlet_code` varchar(20) NOT NULL,
  `outlet_name` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `manager_id` int(11) UNSIGNED NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `outlets`
--

INSERT INTO `outlets` (`id`, `outlet_code`, `outlet_name`, `location`, `manager_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'OUT-A', 'Outlet A', 'Location A - Main Street', 1, 'active', '2025-10-08 10:11:53', '2025-10-08 10:11:53'),
(2, 'OUT-B', 'Outlet B', 'Location B - Downtown', 1, 'active', '2025-10-08 10:11:53', '2025-10-08 10:11:53'),
(3, 'OUT-C', 'Outlet C', 'Location C - Mall Plaza', 1, 'active', '2025-10-08 10:11:53', '2025-10-08 10:11:53'),
(4, 'OUT-D', 'Outlet D', 'Location D - North District', 1, 'active', '2025-10-08 10:11:53', '2025-10-08 10:11:53');

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
  `role` enum('manager','account','ceo','admin') NOT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `username`, `password_hash`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Chris Manager ', 'manager@mysite.com', 'manager', '$argon2id$v=19$m=65536,t=4,p=1$eWpaazhXaWdrWk41eU4yTw$3MTO2I4nskA8Wc3Q5XltOdhj8m4BkuIuFs1ZMacXeTE', 'manager', 'active', '2025-10-10 19:26:46', '2025-10-08 08:55:51', '2025-10-10 11:26:46'),
(2, 'Sarah Accountant', 'account@mysite.com', 'accountant', '$argon2id$v=19$m=65536,t=4,p=1$TFhid1YvMzl1bTJEU1kyTw$XQ+TLktwyfL/goKrb2v33g7d10gy5bTx5j++8icehuA', 'account', 'active', '2025-10-10 19:27:40', '2025-10-08 08:55:51', '2025-10-10 11:27:40'),
(3, 'Michael CEO', 'ceo@mysite.com', 'ceo', '$argon2id$v=19$m=65536,t=4,p=1$VWl6T1hCR1VBeUJPWnpZWg$jrrmxzG7FPNNvcucr16l9xKZCcOjgwPxs7e1hfv4AE4', 'ceo', 'active', '2025-10-08 17:13:24', '2025-10-08 08:55:52', '2025-10-08 09:13:24'),
(4, 'Admin User', 'admin@mysite.com', 'admin', '$argon2id$v=19$m=65536,t=4,p=1$Q1lncURyUHc2Ly5XNlRHRA$fgUoE2zJmmThayhC2TAKIs+G/9Qx3S2nv9X1E++QcD4', 'admin', 'active', NULL, '2025-10-08 08:55:53', '2025-10-08 08:55:53');

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
  ADD KEY `idx_submission_date_status` (`submission_date`,`status`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_submission_id` (`submission_id`),
  ADD KEY `idx_category_id` (`expense_category_id`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_type` (`category_type`);

--
-- Indexes for table `outlets`
--
ALTER TABLE `outlets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_outlet_code` (`outlet_code`),
  ADD KEY `idx_manager_id` (`manager_id`),
  ADD KEY `idx_status` (`status`);

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
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_submissions`
--
ALTER TABLE `daily_submissions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `outlets`
--
ALTER TABLE `outlets`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  ADD CONSTRAINT `fk_submission_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_submission_outlet` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_submission_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expense_category` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`),
  ADD CONSTRAINT `fk_expense_submission` FOREIGN KEY (`submission_id`) REFERENCES `daily_submissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `outlets`
--
ALTER TABLE `outlets`
  ADD CONSTRAINT `fk_outlet_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
