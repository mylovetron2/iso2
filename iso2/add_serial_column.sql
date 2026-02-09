-- Thêm cột số serial vào bảng vật tư thanh lý
ALTER TABLE `vattu_thanh_ly_iso` 
ADD COLUMN `so_serial` VARCHAR(100) NULL COMMENT 'Số serial/số hiệu' AFTER `mavattu`;

-- Tạo index cho cột so_serial để tăng tốc độ tìm kiếm
ALTER TABLE `vattu_thanh_ly_iso`
ADD INDEX `idx_so_serial` (`so_serial`);
