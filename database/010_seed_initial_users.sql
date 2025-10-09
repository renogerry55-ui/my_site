-- =====================================================
-- Database Seed: Initial Users
-- File: 010_seed_initial_users.sql
-- Import Order: 2
-- =====================================================
-- TEMPORARY: Using PASSWORD() for initial setup
-- On first login, will auto-upgrade to Argon2id
-- =====================================================

USE `br`;

-- Clear existing users (for re-import)
TRUNCATE TABLE `users`;

-- Insert sample users for each role
-- Using a simple hash - will be upgraded to Argon2id on first login
-- Password for all: Password!234

INSERT INTO `users` (`name`, `email`, `username`, `password_hash`, `role`, `status`) VALUES
(
  'John Manager',
  'manager@mysite.com',
  'manager',
  '$2y$10$abcdefghijklmnopqrstuuO8vJ3YzR.h8JVJ3L8K9XGqLZqJh2G1K',
  'manager',
  'active'
),
(
  'Sarah Accountant',
  'account@mysite.com',
  'accountant',
  '$2y$10$abcdefghijklmnopqrstuuO8vJ3YzR.h8JVJ3L8K9XGqLZqJh2G1K',
  'account',
  'active'
),
(
  'Michael CEO',
  'ceo@mysite.com',
  'ceo',
  '$2y$10$abcdefghijklmnopqrstuuO8vJ3YzR.h8JVJ3L8K9XGqLZqJh2G1K',
  'ceo',
  'active'
),
(
  'Admin User',
  'admin@mysite.com',
  'admin',
  '$2y$10$abcdefghijklmnopqrstuuO8vJ3YzR.h8JVJ3L8K9XGqLZqJh2G1K',
  'admin',
  'active'
);

-- Display inserted users
SELECT
  id,
  name,
  email,
  username,
  role,
  status,
  created_at
FROM `users`
ORDER BY `role`, `id`;
