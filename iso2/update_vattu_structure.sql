-- Script cập nhật cấu trúc bảng vattu_thanh_ly_iso
-- Chuyển từ cấu trúc cũ sang cấu trúc mới với đa ngôn ngữ

-- Backup dữ liệu cũ trước khi update
CREATE TABLE IF NOT EXISTS `vattu_thanh_ly_iso_backup` AS SELECT * FROM `vattu_thanh_ly_iso`;

-- Thêm các cột mới
ALTER TABLE `vattu_thanh_ly_iso` 
ADD COLUMN `ten_tienganh` TEXT DEFAULT NULL COMMENT 'Tên tiếng Anh' AFTER `mavattu`,
ADD COLUMN `ten_tiengnga` TEXT DEFAULT NULL COMMENT 'Tên tiếng Nga' AFTER `ten_tienganh`,
ADD COLUMN `ten_tiengviet` TEXT DEFAULT NULL COMMENT 'Tên tiếng Việt' AFTER `ten_tiengnga`,
ADD COLUMN `dactinhkt_tiengnga` TEXT DEFAULT NULL COMMENT 'Đặc tính kỹ thuật tiếng Nga' AFTER `ten_tiengviet`,
ADD COLUMN `dactinhkt_tiengviet` TEXT DEFAULT NULL COMMENT 'Đặc tính kỹ thuật tiếng Việt' AFTER `dactinhkt_tiengnga`,
ADD COLUMN `dvt_tiengnga` VARCHAR(50) DEFAULT NULL COMMENT 'Đơn vị tính tiếng Nga' AFTER `dactinhkt_tiengviet`,
ADD COLUMN `dvt_tiengviet` VARCHAR(50) DEFAULT NULL COMMENT 'Đơn vị tính tiếng Việt' AFTER `dvt_tiengnga`;

-- Copy dữ liệu từ cột cũ sang cột mới (nếu có dữ liệu)
UPDATE `vattu_thanh_ly_iso` 
SET `ten_tiengviet` = `tenkyhieuvt`,
    `dvt_tiengviet` = `dvt`
WHERE `tenkyhieuvt` IS NOT NULL;

-- Xóa cột cũ (tùy chọn - comment lại nếu muốn giữ)
-- ALTER TABLE `vattu_thanh_ly_iso` DROP COLUMN `tenkyhieuvt`;
-- ALTER TABLE `vattu_thanh_ly_iso` DROP COLUMN `dvt`;
