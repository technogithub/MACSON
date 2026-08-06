-- ==============================================================================
-- Enterprise Radius & Multi-SSID MAC Address Management System - Database Schema
-- Database: radius (MariaDB 10.11+)
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `radius` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `radius`;

-- Disable FK checks during table creation / drop
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- Drop tables in correct dependency order (children first)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `device_ssids`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `radius_log`;
DROP TABLE IF EXISTS `radacct`;
DROP TABLE IF EXISTS `nas`;
DROP TABLE IF EXISTS `devices`;
DROP TABLE IF EXISTS `ssids`;
DROP TABLE IF EXISTS `users`;

-- ------------------------------------------------------------------------------
-- 1. Table: ssids (Master Multi-SSID Inventory & Dynamic VLAN Definitions)
-- ------------------------------------------------------------------------------
CREATE TABLE `ssids` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ssid_name` VARCHAR(64) NOT NULL UNIQUE COMMENT 'SSID Name broadcasted by Access Points',
  `vlan_id` INT UNSIGNED NULL COMMENT 'Dynamic IEEE 802.1Q VLAN Tag ID (1-4094)',
  `description` VARCHAR(255) NULL COMMENT 'SSID usage notes and zone assignment',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_ssid_status` (`ssid_name`, `status`),
  INDEX `idx_vlan_id` (`vlan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Table: devices (MAC Address Device Inventory)
-- ------------------------------------------------------------------------------
CREATE TABLE `devices` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `mac_address` VARCHAR(17) NOT NULL COMMENT 'Sanitized MAC: AA:BB:CC:DD:EE:FF',
  `raw_mac` VARCHAR(17) NOT NULL COMMENT 'Raw input MAC address before sanitization',
  `ssid` VARCHAR(64) NOT NULL DEFAULT 'ALL' COMMENT 'Primary/Legacy SSID or ALL',
  `device_name` VARCHAR(100) NOT NULL,
  `location` VARCHAR(150) NULL,
  `description` TEXT NULL,
  `vlan_id` INT UNSIGNED NULL COMMENT 'Per-device Dynamic VLAN override (1-4094). If set, overrides the SSID VLAN for this MAC.',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_mac_ssid` (`mac_address`, `ssid`),
  INDEX `idx_mac_ssid_status` (`mac_address`, `ssid`, `status`),
  INDEX `idx_status` (`status`),
  INDEX `idx_vlan_device` (`vlan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. Table: device_ssids (Multi-SSID Pivot Table for Device Authorizations)
-- ------------------------------------------------------------------------------
CREATE TABLE `device_ssids` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `device_id` BIGINT UNSIGNED NOT NULL,
  `ssid_id` BIGINT UNSIGNED NULL COMMENT 'FK to ssids.id. NULL means authorized for ALL SSIDs',
  `is_all` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = authorized for ALL SSIDs',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ssid_id`) REFERENCES `ssids` (`id`) ON DELETE CASCADE,
  INDEX `idx_device_id` (`device_id`),
  INDEX `idx_ssid_id` (`ssid_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. Table: radius_log (FreeRADIUS Authentication & Accounting Logs)
-- ------------------------------------------------------------------------------
CREATE TABLE `radius_log` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `log_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mac_address` VARCHAR(17) NOT NULL,
  `ssid` VARCHAR(64) NULL COMMENT 'SSID extracted from Called-Station-Id',
  `username` VARCHAR(64) NULL,
  `nas_ip` VARCHAR(45) NOT NULL COMMENT 'NAS Access Point / Controller IP',
  `nas_port` VARCHAR(30) NULL,
  `auth_result` ENUM('ACCEPT', 'REJECT') NOT NULL,
  `reason` VARCHAR(255) NULL,
  INDEX `idx_log_date` (`log_date`),
  INDEX `idx_mac` (`mac_address`),
  INDEX `idx_ssid` (`ssid`),
  INDEX `idx_nas_ip` (`nas_ip`),
  INDEX `idx_auth_result` (`auth_result`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. Table: users (Laravel Authentication & Role Management)
-- ------------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('Super Admin', 'Operator') NOT NULL DEFAULT 'Operator',
  `remember_token` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. Table: audit_logs (Security & Admin Activity Tracking)
-- ------------------------------------------------------------------------------
CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NULL,
  `user_email` VARCHAR(150) NULL,
  `action` VARCHAR(50) NOT NULL COMMENT 'CREATE, UPDATE, DELETE, IMPORT, EXPORT, LOGIN',
  `module` VARCHAR(50) NOT NULL COMMENT 'DEVICES, SSIDS, USERS, AUTH, SYSTEM',
  `description` TEXT NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_action` (`action`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. Standard FreeRADIUS SQL Integration Tables (radacct, nas)
-- ------------------------------------------------------------------------------
CREATE TABLE `radacct` (
  `radacctid` BIGINT(21) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `acctsessionid` VARCHAR(64) NOT NULL DEFAULT '',
  `acctuniqueid` VARCHAR(32) NOT NULL DEFAULT '',
  `username` VARCHAR(64) NOT NULL DEFAULT '',
  `realm` VARCHAR(64) DEFAULT '',
  `nasipaddress` VARCHAR(15) NOT NULL DEFAULT '',
  `nasportid` VARCHAR(50) DEFAULT NULL,
  `nasporttype` VARCHAR(32) DEFAULT NULL,
  `acctstarttime` DATETIME DEFAULT NULL,
  `acctupdatetime` DATETIME DEFAULT NULL,
  `acctstoptime` DATETIME DEFAULT NULL,
  `acctinterval` INT(12) DEFAULT NULL,
  `acctsessiontime` INT(12) UNSIGNED DEFAULT NULL,
  `acctauthentic` VARCHAR(32) DEFAULT NULL,
  `connectinfo_start` VARCHAR(50) DEFAULT NULL,
  `connectinfo_stop` VARCHAR(50) DEFAULT NULL,
  `acctinputoctets` BIGINT(20) DEFAULT NULL,
  `acctoutputoctets` BIGINT(20) DEFAULT NULL,
  `calledstationid` VARCHAR(50) NOT NULL DEFAULT '',
  `callingstationid` VARCHAR(50) NOT NULL DEFAULT '',
  `acctterminatecause` VARCHAR(32) NOT NULL DEFAULT '',
  `servicetype` VARCHAR(32) DEFAULT NULL,
  `framedprotocol` VARCHAR(32) DEFAULT NULL,
  `framedipaddress` VARCHAR(15) NOT NULL DEFAULT '',
  INDEX `callingstationid` (`callingstationid`),
  INDEX `nasipaddress` (`nasipaddress`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `nas` (
  `id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `nasname` VARCHAR(128) NOT NULL,
  `shortname` VARCHAR(32) DEFAULT NULL,
  `type` VARCHAR(30) DEFAULT 'other',
  `ports` INT(5) DEFAULT NULL,
  `secret` VARCHAR(60) NOT NULL DEFAULT 'secret',
  `server` VARCHAR(64) DEFAULT NULL,
  `community` VARCHAR(50) DEFAULT NULL,
  `description` VARCHAR(200) DEFAULT 'RADIUS Client NAS',
  INDEX `nasname` (`nasname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable FK checks
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------------------
-- Seed Initial Admin Credentials (Password: Admin@123456)
-- ------------------------------------------------------------------------------
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Super Administrator', 'admin@radius.local', '$2y$12$K8JdM0gD1E8X7gL4J0N8u.R6J8L0gD1E8X7gL4J0N8u.R6J8L0gD1', 'Super Admin'),
('Operator User', 'operator@radius.local', '$2y$12$K8JdM0gD1E8X7gL4J0N8u.R6J8L0gD1E8X7gL4J0N8u.R6J8L0gD1', 'Operator');

-- ------------------------------------------------------------------------------
-- Seed Initial Master SSIDs with Dynamic VLAN Tags
-- ------------------------------------------------------------------------------
INSERT INTO `ssids` (`id`, `ssid_name`, `vlan_id`, `description`, `status`) VALUES
(1, 'SSID-Staff', 10, 'Corporate Staff High-Speed Network', 'active'),
(2, 'SSID-IoT', 20, 'IoT Devices & Smart Sensors Network', 'active'),
(3, 'SSID-VIP', 30, 'Executive & VIP Guest Dedicated WiFi', 'active'),
(4, 'SSID-Guest', 40, 'Public Guest WiFi (Rate-Limited)', 'active');

-- ------------------------------------------------------------------------------
-- Seed Initial Multi-SSID Demo Devices
-- ------------------------------------------------------------------------------
INSERT INTO `devices` (`id`, `mac_address`, `raw_mac`, `ssid`, `device_name`, `location`, `description`, `status`) VALUES
(1, 'AA:BB:CC:DD:EE:01', 'aa-bb-cc-dd-ee-01', 'SSID-Staff', 'Executive Laptop Dell XPS', 'Floor 3 - HQ', 'Staff & VIP Authorized Device', 'active'),
(2, 'AA:BB:CC:DD:EE:02', 'aabbccddee02', 'ALL', 'Cisco IP Phone 8841', 'Meeting Room A', 'VoIP Phone - Authorized for ALL SSIDs', 'active'),
(3, 'AA:BB:CC:DD:EE:03', 'AA:BB:CC:DD:EE:03', 'SSID-Guest', 'Guest Tablet iPad Pro', 'Lobby Reception', 'Guest Network Only Device', 'inactive');

-- Seed Pivot Authorization Mappings
-- Device 1 -> SSID-Staff (VLAN 10) and SSID-VIP (VLAN 30)
INSERT INTO `device_ssids` (`device_id`, `ssid_id`, `is_all`) VALUES
(1, 1, 0),
(1, 3, 0),
(2, NULL, 1),
(3, 4, 0);
