-- =====================================================
-- Add External ID Columns to Outlets Table
-- File: 005_add_outlet_external_ids.sql
-- Import Order: 7
-- Purpose: Add separate external ID columns for each income stream matching
-- =====================================================

USE `br`;

-- Add external ID columns for different income streams
ALTER TABLE `outlets`
ADD COLUMN IF NOT EXISTS `berhad_agent_id` VARCHAR(100) DEFAULT NULL COMMENT 'Berhad external system Agent ID' AFTER `outlet_name`,
ADD COLUMN IF NOT EXISTS `mp_coba_login_id` VARCHAR(100) DEFAULT NULL COMMENT 'MP Coba external system Login ID' AFTER `berhad_agent_id`,
ADD COLUMN IF NOT EXISTS `mp_perdana_login_id` VARCHAR(100) DEFAULT NULL COMMENT 'MP Perdana external system Login ID' AFTER `mp_coba_login_id`;

-- Add indexes for faster lookup during verification
CREATE INDEX IF NOT EXISTS idx_berhad_agent_id ON outlets(berhad_agent_id);
CREATE INDEX IF NOT EXISTS idx_mp_coba_login_id ON outlets(mp_coba_login_id);
CREATE INDEX IF NOT EXISTS idx_mp_perdana_login_id ON outlets(mp_perdana_login_id);

-- Display success message
SELECT 'External ID columns added to outlets table successfully!' as message;

-- Show current outlets structure
DESCRIBE `outlets`;
