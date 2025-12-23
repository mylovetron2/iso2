-- Create mo_iso table
CREATE TABLE IF NOT EXISTS `mo_iso` (
    `stt` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `mamo` VARCHAR(200) NOT NULL COMMENT 'Mã mỏ',
    `tenmo` TEXT NOT NULL COMMENT 'Tên mỏ',
    INDEX `idx_mamo` (`mamo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Quản lý mỏ dầu khí';
