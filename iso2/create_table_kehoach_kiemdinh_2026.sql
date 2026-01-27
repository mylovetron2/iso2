-- Bảng lưu Kế hoạch chuẩn chỉnh, kiểm định ĐVLTH năm 2026
-- Dựa vào file: VSP.2026.21058.3.Kế-hoạch-chuẩn-chinh_-kiểm-định-ĐVLTH-2026.html

CREATE TABLE IF NOT EXISTS `kehoach_kiemdinh_2026_iso` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY COMMENT 'ID tự động tăng',
    `stt` VARCHAR(50) DEFAULT NULL COMMENT 'Số thứ tự',
    `ten_thietbi` VARCHAR(500) DEFAULT NULL COMMENT 'Tên thiết bị',
    `ky_hieu` VARCHAR(200) DEFAULT NULL COMMENT 'Ký hiệu',
    `hang_sanxuat` VARCHAR(200) DEFAULT NULL COMMENT 'Hãng sản xuất',
    `so_may` VARCHAR(200) DEFAULT NULL COMMENT 'Số máy/Serial Number',
    `thang_thuchien` VARCHAR(50) DEFAULT NULL COMMENT 'Tháng thực hiện trong năm 2026',
    `donvi_thuchien` VARCHAR(300) DEFAULT NULL COMMENT 'Đơn vị thực hiện kiểm định',
    `ghichu` TEXT DEFAULT NULL COMMENT 'Ghi chú',
    `nam_kehoach` INT(4) DEFAULT 2026 COMMENT 'Năm kế hoạch',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày tạo',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Ngày cập nhật',
    
    INDEX `idx_ten_thietbi` (`ten_thietbi`(191)),
    INDEX `idx_so_may` (`so_may`(191)),
    INDEX `idx_thang_thuchien` (`thang_thuchien`),
    INDEX `idx_nam_kehoach` (`nam_kehoach`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kế hoạch chuẩn chỉnh kiểm định ĐVLTH năm 2026';
