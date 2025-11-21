-- Migration: Create lichsudn_iso table for audit logging
-- Date: 2025-11-21
-- Purpose: Track all changes to hososcbd_iso records

CREATE TABLE IF NOT EXISTS `lichsudn_iso` (
    `stt` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL COMMENT 'User thực hiện',
    `action` VARCHAR(20) NOT NULL COMMENT 'CREATE/UPDATE/DELETE/HANDOVER',
    `table_name` VARCHAR(100) DEFAULT 'hososcbd_iso' COMMENT 'Tên bảng',
    `record_id` INT(11) DEFAULT NULL COMMENT 'ID bản ghi bị tác động',
    `maql` VARCHAR(100) DEFAULT NULL COMMENT 'Mã quản lý',
    `phieu` VARCHAR(20) DEFAULT NULL COMMENT 'Số phiếu',
    `mavt` VARCHAR(80) DEFAULT NULL COMMENT 'Mã vật tư',
    `somay` VARCHAR(80) DEFAULT NULL COMMENT 'Số máy',
    `madv` VARCHAR(80) DEFAULT NULL COMMENT 'Mã đơn vị',
    `description` TEXT COMMENT 'Mô tả chi tiết thay đổi',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP address',
    `user_agent` VARCHAR(255) DEFAULT NULL COMMENT 'Browser info',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_action` (`action`),
    INDEX `idx_record_id` (`record_id`),
    INDEX `idx_maql` (`maql`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Lịch sử thao tác dữ liệu';
