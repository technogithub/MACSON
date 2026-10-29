CREATE DATABASE IF NOT EXISTS radius;
USE radius;

-- Tabel Users untuk Dashboard Web (Laravel)
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `email_verified_at` TIMESTAMP NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('Super Admin', 'Operator') DEFAULT 'Operator',
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Devices untuk MAB (MAC Authentication Bypass)
CREATE TABLE IF NOT EXISTS `devices` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `mac_address` VARCHAR(17) NOT NULL UNIQUE,
    `device_name` VARCHAR(100) NOT NULL,
    `location` VARCHAR(100) NULL,
    `description` TEXT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Custom Log Autentikasi (Accept & Reject)
CREATE TABLE IF NOT EXISTS `radius_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `log_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `mac_address` VARCHAR(17) NOT NULL,
    `nas_ip` VARCHAR(45) NOT NULL,
    `result` VARCHAR(50) NOT NULL,
    `reason` VARCHAR(255) NULL,
    INDEX `idx_mac` (`mac_address`),
    INDEX `idx_date` (`log_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Standar FreeRADIUS: radacct (Untuk Accounting / Log Trafik & Durasi)
CREATE TABLE IF NOT EXISTS `radacct` (
    `radacctid` BIGINT(21) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `acctsessionid` VARCHAR(64) NOT NULL DEFAULT '',
    `acctuniqueid` VARCHAR(32) NOT NULL DEFAULT '',
    `username` VARCHAR(64) NOT NULL DEFAULT '',
    `realm` VARCHAR(64) DEFAULT '',
    `nasipaddress` VARCHAR(15) NOT NULL DEFAULT '',
    `nasportid` VARCHAR(32) DEFAULT NULL,
    `nasporttype` VARCHAR(32) DEFAULT NULL,
    `acctstarttime` DATETIME NULL DEFAULT NULL,
    `acctupdatetime` DATETIME NULL DEFAULT NULL,
    `acctstoptime` DATETIME NULL DEFAULT NULL,
    `acctinterval` INT(12) DEFAULT NULL,
    `acctsessiontime` INT(12) UNSIGNED DEFAULT NULL,
    `acctauthentic` VARCHAR(32) DEFAULT NULL,
    `connectinfo_start` VARCHAR(128) DEFAULT NULL,
    `connectinfo_stop` VARCHAR(128) DEFAULT NULL,
    `acctinputoctets` BIGINT(20) DEFAULT NULL,
    `acctoutputoctets` BIGINT(20) DEFAULT NULL,
    `calledstationid` VARCHAR(50) NOT NULL DEFAULT '',
    `callingstationid` VARCHAR(50) NOT NULL DEFAULT '',
    `acctterminatecause` VARCHAR(32) NOT NULL DEFAULT '',
    `servicetype` VARCHAR(32) DEFAULT NULL,
    `framedprotocol` VARCHAR(32) DEFAULT NULL,
    `framedipaddress` VARCHAR(15) NOT NULL DEFAULT '',
    `framedipv6address` VARCHAR(45) NOT NULL DEFAULT '',
    `framedipv6prefix` VARCHAR(45) NOT NULL DEFAULT '',
    `framedinterfaceid` VARCHAR(44) NOT NULL DEFAULT '',
    `delegatedipv6prefix` VARCHAR(45) NOT NULL DEFAULT '',
    INDEX `username` (`username`),
    INDEX `framedipaddress` (`framedipaddress`),
    INDEX `framedipv6address` (`framedipv6address`),
    INDEX `framedipv6prefix` (`framedipv6prefix`),
    INDEX `framedinterfaceid` (`framedinterfaceid`),
    INDEX `delegatedipv6prefix` (`delegatedipv6prefix`),
    INDEX `acctsessionid` (`acctsessionid`),
    INDEX `acctsessiontime` (`acctsessiontime`),
    INDEX `acctstarttime` (`acctstarttime`),
    INDEX `acctinterval` (`acctinterval`),
    INDEX `acctstoptime` (`acctstoptime`),
    INDEX `nasipaddress` (`nasipaddress`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Standar FreeRADIUS: radpostauth (Dipanggil secara default oleh rlm_sql)
CREATE TABLE IF NOT EXISTS `radpostauth` (
    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(64) NOT NULL DEFAULT '',
    `pass` VARCHAR(64) NOT NULL DEFAULT '',
    `reply` VARCHAR(32) NOT NULL DEFAULT '',
    `authdate` TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    `class` VARCHAR(64) NOT NULL DEFAULT '',
    INDEX `username` (`username`),
    INDEX `class` (`class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;