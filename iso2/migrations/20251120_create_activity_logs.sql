-- Migration: Create activity_logs table for tracking all database operations
-- Date: 2025-11-20

CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `username` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(100) NOT NULL,
    `action` ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    `record_id` INT DEFAULT NULL,
    `old_data` TEXT DEFAULT NULL,
    `new_data` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_table_name` (`table_name`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
