-- =====================================================
-- Seed Data: Outlets & Expense Categories
-- File: 020_seed_outlets_categories.sql
-- Import Order: 4
-- =====================================================

USE `br`;

-- =====================================================
-- SEED OUTLETS
-- Assign to Manager user (ID = 1)
-- =====================================================

INSERT INTO `outlets` (`outlet_code`, `outlet_name`, `location`, `manager_id`, `status`) VALUES
('OUT-A', 'Outlet A', 'Location A - Main Street', 1, 'active'),
('OUT-B', 'Outlet B', 'Location B - Downtown', 1, 'active'),
('OUT-C', 'Outlet C', 'Location C - Mall Plaza', 1, 'active'),
('OUT-D', 'Outlet D', 'Location D - North District', 1, 'active');

-- =====================================================
-- SEED EXPENSE CATEGORIES
-- MP/BERHAD Categories
-- =====================================================

INSERT INTO `expense_categories` (`category_name`, `category_type`, `description`, `status`) VALUES
-- MP/BERHAD Expenses
('Staff Salary', 'mp_berhad', 'Monthly staff salaries and wages', 'active'),
('Rent', 'mp_berhad', 'Shop rental payment', 'active'),
('Utilities', 'mp_berhad', 'Electricity, water, internet bills', 'active'),
('Transportation', 'mp_berhad', 'Delivery and logistics costs', 'active'),
('Maintenance', 'mp_berhad', 'Equipment and facility maintenance', 'active'),
('Supplies', 'mp_berhad', 'Office and operational supplies', 'active'),
('Marketing', 'mp_berhad', 'Advertising and promotion expenses', 'active'),
('Insurance', 'mp_berhad', 'Business insurance premiums', 'active'),
('Miscellaneous', 'mp_berhad', 'Other miscellaneous expenses', 'active'),

-- MARKET Expenses
('Purchase Goods', 'market', 'Stock and inventory purchases', 'active'),
('Vendor Payment', 'market', 'Payment to suppliers', 'active'),
('Delivery Fees', 'market', 'Market delivery charges', 'active'),
('Packaging', 'market', 'Packaging materials', 'active'),
('Market Rent', 'market', 'Market stall rental', 'active'),
('Market Utilities', 'market', 'Market utilities and services', 'active'),
('Market Supplies', 'market', 'Market operational supplies', 'active'),
('Market Miscellaneous', 'market', 'Other market expenses', 'active');

-- Display seeded data
SELECT '=== OUTLETS ===' as '';
SELECT * FROM `outlets` ORDER BY `outlet_code`;

SELECT '' as '';
SELECT '=== EXPENSE CATEGORIES (MP/BERHAD) ===' as '';
SELECT * FROM `expense_categories` WHERE `category_type` = 'mp_berhad' ORDER BY `id`;

SELECT '' as '';
SELECT '=== EXPENSE CATEGORIES (MARKET) ===' as '';
SELECT * FROM `expense_categories` WHERE `category_type` = 'market' ORDER BY `id`;
