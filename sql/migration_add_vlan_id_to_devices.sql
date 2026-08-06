-- ==============================================================================
-- Migration: Add vlan_id column to devices table
-- Description: Per-device Dynamic VLAN override. If set, this VLAN takes
--              priority over the SSID's VLAN assignment in FreeRADIUS.
-- Run this if the database is already initialized (after initial schema.sql).
-- ==============================================================================

USE `radius`;

-- Add vlan_id column to devices table (safe, uses IF NOT EXISTS via workaround)
ALTER TABLE `devices`
    ADD COLUMN IF NOT EXISTS `vlan_id` INT UNSIGNED NULL
    COMMENT 'Per-device Dynamic VLAN override (1-4094). Overrides SSID VLAN if set.'
    AFTER `description`;

-- Add index for vlan_id
ALTER TABLE `devices`
    ADD INDEX IF NOT EXISTS `idx_vlan_device` (`vlan_id`);

-- Verify
SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'radius'
  AND TABLE_NAME = 'devices'
  AND COLUMN_NAME = 'vlan_id';
