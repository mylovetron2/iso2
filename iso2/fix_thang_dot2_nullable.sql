-- Sửa cột thang_dot2 cho phép NULL
ALTER TABLE `kehoach_kiemdinh_2026_iso` 
MODIFY COLUMN `thang_dot2` INT(11) NULL DEFAULT NULL;
