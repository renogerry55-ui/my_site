-- =====================================================
-- Add Submission Workflow Status
-- File: 003_add_submission_status.sql
-- Import Order: 5
-- =====================================================

USE `br`;

-- Step 1: Add new columns first
ALTER TABLE `daily_submissions`
ADD COLUMN IF NOT EXISTS `batch_code` VARCHAR(50) NULL DEFAULT NULL AFTER `submission_code`,
ADD COLUMN IF NOT EXISTS `submitted_to_hq_at` DATETIME NULL DEFAULT NULL AFTER `created_at`;

-- Step 2: Modify status ENUM to include 'draft' and 'submitted'
ALTER TABLE `daily_submissions`
MODIFY COLUMN `status` ENUM('draft', 'submitted', 'pending', 'verified', 'rejected', 'revised') NOT NULL DEFAULT 'draft'
COMMENT 'draft=saved locally, submitted=sent to HQ, pending=awaiting account review, verified=approved, rejected=rejected, revised=needs changes';

-- Step 3: Update existing 'pending' submissions to 'draft' (so they can be batch submitted)
UPDATE `daily_submissions`
SET `status` = 'draft'
WHERE `status` = 'pending' AND `submitted_to_hq_at` IS NULL;

-- Step 4: Add indexes for batch operations
CREATE INDEX IF NOT EXISTS idx_batch_code ON daily_submissions(batch_code);
CREATE INDEX IF NOT EXISTS idx_manager_status ON daily_submissions(manager_id, status);
CREATE INDEX IF NOT EXISTS idx_submission_date_status ON daily_submissions(submission_date, status);

SELECT 'Table updated successfully! All pending submissions converted to draft status.' as message;
