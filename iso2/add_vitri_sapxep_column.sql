-- Thêm cột vị trí sắp xếp vào bảng vật tư thanh lý
ALTER TABLE `vattu_thanh_ly_iso` 
ADD COLUMN `vi_tri_sap_xep` INT NULL DEFAULT 999 COMMENT 'Vị trí sắp xếp (số nhỏ hiển thị trước)' AFTER `phanloai_id`;

-- Tạo index cho cột vi_tri_sap_xep để tăng tốc độ sắp xếp
ALTER TABLE `vattu_thanh_ly_iso`
ADD INDEX `idx_vi_tri_sap_xep` (`vi_tri_sap_xep`);
