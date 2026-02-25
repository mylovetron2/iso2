-- =====================================================
-- Migration: Hệ thống quản lý công việc sửa chữa với KPI theo cấp độ
-- Date: 2026-02-24 (FIXED version - errno 150)
-- Description: Tạo các bảng quản lý công việc sửa chữa, KPI theo cấp độ bảo dưỡng
-- FIX: Tách FK constraints riêng để tránh errno 150
-- =====================================================

-- =====================================================
-- BƯỚC 1: Tạo bảng cấp độ bảo dưỡng (KHÔNG có FK)
-- =====================================================
CREATE TABLE IF NOT EXISTS `capdo_baocuong_iso` (
    `stt` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `ma_capdo` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Mã cấp độ: CAP1, CAP2, CAP3',
    `ten_capdo` VARCHAR(100) NOT NULL COMMENT 'Tên cấp độ: Bảo dưỡng cấp 1/2/3',
    `mo_ta` TEXT COMMENT 'Mô tả chi tiết công việc của cấp độ',
    `kpi_gio_chuan` DECIMAL(5,2) NOT NULL COMMENT 'Số giờ KPI chuẩn cho cấp độ này',
    `mau_sac` VARCHAR(20) DEFAULT '#4CAF50' COMMENT 'Mã màu hiển thị (hex)',
    `thu_tu` INT(3) DEFAULT 1 COMMENT 'Thứ tự hiển thị',
    `trang_thai` TINYINT(1) DEFAULT 1 COMMENT '1=Kích hoạt, 0=Vô hiệu',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_ma_capdo` (`ma_capdo`),
    INDEX `idx_trang_thai` (`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cấp độ bảo dưỡng với KPI chuẩn';

SELECT '✓ Bước 1: Đã tạo bảng capdo_baocuong_iso' AS status;

-- =====================================================
-- BƯỚC 2: Insert dữ liệu mẫu cho 3 cấp độ bảo dưỡng
-- =====================================================
INSERT INTO `capdo_baocuong_iso` 
    (`ma_capdo`, `ten_capdo`, `mo_ta`, `kpi_gio_chuan`, `mau_sac`, `thu_tu`) 
VALUES
    ('CAP1', 'Bảo dưỡng Cấp 1', 
     'Bảo dưỡng định kỳ cơ bản: kiểm tra, vệ sinh, bôi trơn, thay dầu', 
     2.00, '#4CAF50', 1),
     
    ('CAP2', 'Bảo dưỡng Cấp 2', 
     'Bảo dưỡng trung cấp: kiểm tra chi tiết, điều chỉnh, thay thế phụ tùng nhỏ', 
     4.00, '#FF9800', 2),
     
    ('CAP3', 'Bảo dưỡng Cấp 3', 
     'Bảo dưỡng nâng cao: đại tu, sửa chữa lớn, thay thế linh kiện chính', 
     8.00, '#F44336', 3)
ON DUPLICATE KEY UPDATE 
    `ten_capdo` = VALUES(`ten_capdo`),
    `mo_ta` = VALUES(`mo_ta`),
    `kpi_gio_chuan` = VALUES(`kpi_gio_chuan`);

SELECT CONCAT('✓ Bước 2: Đã insert ', ROW_COUNT(), ' cấp độ bảo dưỡng') AS status;

-- =====================================================
-- BƯỚC 3: Tạo bảng liên kết thiết bị với cấp độ KPI (KHÔNG có FK)
-- =====================================================
CREATE TABLE IF NOT EXISTS `thietbi_capdo_kpi_iso` (
    `stt` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `mavt` VARCHAR(80) NOT NULL COMMENT 'Mã vật tư thiết bị',
    `somay` VARCHAR(80) NOT NULL COMMENT 'Serial number',
    `capdo_stt` INT(11) NOT NULL COMMENT 'Link đến capdo_baocuong_iso.stt',
    `kpi_gio_du_kien` DECIMAL(5,2) NOT NULL COMMENT 'Số giờ KPI dự kiến cho thiết bị này',
    `ghi_chu` TEXT COMMENT 'Ghi chú đặc thù của thiết bị',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY `unique_thietbi_capdo` (`mavt`, `somay`, `capdo_stt`),
    INDEX `idx_mavt` (`mavt`),
    INDEX `idx_somay` (`somay`),
    INDEX `idx_capdo_stt` (`capdo_stt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Liên kết thiết bị với cấp độ KPI';

SELECT '✓ Bước 3: Đã tạo bảng thietbi_capdo_kpi_iso' AS status;

-- =====================================================
-- BƯỚC 4: Tạo bảng công việc sửa chữa hàng ngày (KHÔNG có FK)
-- =====================================================
CREATE TABLE IF NOT EXISTS `congviec_suachua_iso` (
    `stt` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `nhanvien_stt` INT(11) NOT NULL COMMENT 'Link đến resume.stt (nhân viên)',
    `nhanvien_ten` VARCHAR(100) NOT NULL COMMENT 'Tên nhân viên (copy từ resume)',
    `ngay_lam` DATE NOT NULL COMMENT 'Ngày làm việc',
    
    -- Thông tin thiết bị
    `mavt` VARCHAR(80) NOT NULL COMMENT 'Mã vật tư thiết bị',
    `somay` VARCHAR(80) NOT NULL COMMENT 'Serial number',
    `ten_thietbi` VARCHAR(255) DEFAULT '' COMMENT 'Tên thiết bị (copy từ thietbi_iso)',
    
    -- Cấp độ và KPI
    `capdo_stt` INT(11) NOT NULL COMMENT 'Link đến capdo_baocuong_iso.stt',
    `capdo_ten` VARCHAR(100) NOT NULL COMMENT 'Tên cấp độ (copy)',
    `kpi_gio_chuan` DECIMAL(5,2) NOT NULL COMMENT 'KPI chuẩn của cấp độ (copy)',
    
    -- Công việc thực tế
    `noi_dung` TEXT NOT NULL COMMENT 'Nội dung công việc sửa chữa',
    `so_gio_lam` DECIMAL(5,2) NOT NULL COMMENT 'Số giờ làm việc thực tế',
    `gio_bat_dau` TIME DEFAULT NULL COMMENT 'Giờ bắt đầu',
    `gio_ket_thuc` TIME DEFAULT NULL COMMENT 'Giờ kết thúc',
    
    -- Trạng thái
    `trang_thai` VARCHAR(50) DEFAULT 'Đang thực hiện' COMMENT 'Đang thực hiện, Hoàn thành, Tạm dừng',
    `ghi_chu` TEXT COMMENT 'Ghi chú bổ sung',
    
    -- Liên kết hồ sơ
    `hososcbd_stt` INT(11) DEFAULT NULL COMMENT 'Link đến hososcbd_iso.stt (nếu có)',
    
    -- Người tạo và thời gian
    `created_by` VARCHAR(80) DEFAULT '' COMMENT 'Username người tạo',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_nhanvien` (`nhanvien_stt`),
    INDEX `idx_ngay_lam` (`ngay_lam`),
    INDEX `idx_mavt` (`mavt`),
    INDEX `idx_capdo` (`capdo_stt`),
    INDEX `idx_trang_thai` (`trang_thai`),
    INDEX `idx_nhanvien_ngay` (`nhanvien_stt`, `ngay_lam`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Công việc sửa chữa hàng ngày của nhân viên';

SELECT '✓ Bước 4: Đã tạo bảng congviec_suachua_iso' AS status;

-- =====================================================
-- BƯỚC 5: Thêm Foreign Key Constraints (SAU khi tất cả bảng đã tạo)
-- =====================================================
-- FK cho thietbi_capdo_kpi_iso
ALTER TABLE `thietbi_capdo_kpi_iso`
    ADD CONSTRAINT `fk_thietbi_kpi_capdo` 
    FOREIGN KEY (`capdo_stt`) REFERENCES `capdo_baocuong_iso`(`stt`) 
    ON DELETE CASCADE ON UPDATE CASCADE;

SELECT '✓ Bước 5a: Đã thêm FK thietbi_capdo_kpi_iso → capdo_baocuong_iso' AS status;

-- FK cho congviec_suachua_iso
ALTER TABLE `congviec_suachua_iso`
    ADD CONSTRAINT `fk_congviec_capdo` 
    FOREIGN KEY (`capdo_stt`) REFERENCES `capdo_baocuong_iso`(`stt`) 
    ON DELETE RESTRICT ON UPDATE CASCADE;

SELECT '✓ Bước 5b: Đã thêm FK congviec_suachua_iso → capdo_baocuong_iso' AS status;

-- =====================================================
-- BƯỚC 6: Tạo view thống kê công việc theo nhân viên
-- =====================================================
CREATE OR REPLACE VIEW `view_congviec_nhanvien_thongke` AS
SELECT 
    cv.nhanvien_stt,
    cv.nhanvien_ten,
    cv.ngay_lam,
    COUNT(*) AS so_cong_viec,
    SUM(cv.so_gio_lam) AS tong_so_gio,
    ROUND(8.0 - SUM(cv.so_gio_lam), 2) AS gio_con_lai,
    CASE 
        WHEN SUM(cv.so_gio_lam) > 8 THEN 'Vượt giờ'
        WHEN SUM(cv.so_gio_lam) = 8 THEN 'Đủ giờ'
        ELSE 'Còn giờ trống'
    END AS trang_thai_gio
FROM congviec_suachua_iso cv
GROUP BY cv.nhanvien_stt, cv.nhanvien_ten, cv.ngay_lam;

SELECT '✓ Bước 6: Đã tạo VIEW view_congviec_nhanvien_thongke' AS status;

-- =====================================================
-- BƯỚC 7: Tạo view thống kê KPI theo thiết bị
-- =====================================================
CREATE OR REPLACE VIEW `view_kpi_thietbi_thongke` AS
SELECT 
    cv.mavt,
    cv.somay,
    cv.ten_thietbi,
    cv.capdo_stt,
    cv.capdo_ten,
    cv.kpi_gio_chuan,
    COUNT(*) AS so_lan_sua,
    SUM(cv.so_gio_lam) AS tong_gio_thuc_te,
    ROUND(AVG(cv.so_gio_lam), 2) AS gio_trung_binh,
    ROUND((cv.kpi_gio_chuan / AVG(cv.so_gio_lam)) * 100, 2) AS hieu_suat_percent,
    CASE 
        WHEN AVG(cv.so_gio_lam) <= cv.kpi_gio_chuan THEN 'Đạt KPI'
        WHEN AVG(cv.so_gio_lam) <= cv.kpi_gio_chuan * 1.2 THEN 'Gần đạt'
        ELSE 'Chưa đạt'
    END AS danh_gia_kpi
FROM congviec_suachua_iso cv
GROUP BY cv.mavt, cv.somay, cv.ten_thietbi, cv.capdo_stt, cv.capdo_ten, cv.kpi_gio_chuan;

SELECT '✓ Bước 7: Đã tạo VIEW view_kpi_thietbi_thongke' AS status;

-- =====================================================
-- BƯỚC 8: Tạo view thống kê theo cấp độ
-- =====================================================
CREATE OR REPLACE VIEW `view_thongke_theo_capdo` AS
SELECT 
    cd.stt AS capdo_stt,
    cd.ma_capdo,
    cd.ten_capdo,
    cd.kpi_gio_chuan AS kpi_chuan,
    COUNT(cv.stt) AS so_cong_viec,
    SUM(cv.so_gio_lam) AS tong_gio,
    ROUND(AVG(cv.so_gio_lam), 2) AS gio_trung_binh,
    ROUND((cd.kpi_gio_chuan / AVG(cv.so_gio_lam)) * 100, 2) AS hieu_suat_percent
FROM capdo_baocuong_iso cd
LEFT JOIN congviec_suachua_iso cv ON cd.stt = cv.capdo_stt
GROUP BY cd.stt, cd.ma_capdo, cd.ten_capdo, cd.kpi_gio_chuan;

SELECT '✓ Bước 8: Đã tạo VIEW view_thongke_theo_capdo' AS status;

-- =====================================================
-- BƯỚC 9: Tạo trigger kiểm tra giới hạn 8 giờ/ngày
-- =====================================================
DELIMITER $$

DROP TRIGGER IF EXISTS `before_insert_congviec_check_8h`$$
CREATE TRIGGER `before_insert_congviec_check_8h`
BEFORE INSERT ON `congviec_suachua_iso`
FOR EACH ROW
BEGIN
    DECLARE total_gio DECIMAL(5,2);
    
    -- Tính tổng giờ đã làm trong ngày của nhân viên này
    SELECT COALESCE(SUM(so_gio_lam), 0) INTO total_gio
    FROM congviec_suachua_iso
    WHERE nhanvien_stt = NEW.nhanvien_stt
      AND ngay_lam = NEW.ngay_lam;
    
    -- Kiểm tra nếu tổng giờ + giờ mới > 8h thì báo lỗi
    IF (total_gio + NEW.so_gio_lam) > 8.0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Lỗi: Tổng số giờ làm việc trong ngày vượt quá 8 giờ';
    END IF;
END$$

DROP TRIGGER IF EXISTS `before_update_congviec_check_8h`$$
CREATE TRIGGER `before_update_congviec_check_8h`
BEFORE UPDATE ON `congviec_suachua_iso`
FOR EACH ROW
BEGIN
    DECLARE total_gio DECIMAL(5,2);
    
    -- Tính tổng giờ (trừ record đang update)
    SELECT COALESCE(SUM(so_gio_lam), 0) INTO total_gio
    FROM congviec_suachua_iso
    WHERE nhanvien_stt = NEW.nhanvien_stt
      AND ngay_lam = NEW.ngay_lam
      AND stt != NEW.stt;
    
    -- Kiểm tra
    IF (total_gio + NEW.so_gio_lam) > 8.0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Lỗi: Tổng số giờ làm việc trong ngày vượt quá 8 giờ';
    END IF;
END$$

DELIMITER ;

SELECT '✓ Bước 9: Đã tạo TRIGGERs kiểm tra 8h/ngày' AS status;

-- =====================================================
-- BƯỚC 10: VERIFY kết quả
-- =====================================================
SELECT '========== KẾT QUẢ TẠO BẢNG ==========' AS info;
SELECT 'capdo_baocuong_iso' AS table_name, COUNT(*) AS record_count FROM capdo_baocuong_iso
UNION ALL
SELECT 'thietbi_capdo_kpi_iso', COUNT(*) FROM thietbi_capdo_kpi_iso
UNION ALL
SELECT 'congviec_suachua_iso', COUNT(*) FROM congviec_suachua_iso;

-- Kiểm tra FK constraints
SELECT '========== FOREIGN KEYs ==========' AS info;
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('thietbi_capdo_kpi_iso', 'congviec_suachua_iso')
  AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Kiểm tra VIEWs
SELECT '========== VIEWs ==========' AS info;
SHOW FULL TABLES WHERE Table_Type = 'VIEW';

-- Kiểm tra TRIGGERs
SELECT '========== TRIGGERs ==========' AS info;
SHOW TRIGGERS LIKE 'congviec_suachua_iso';

SELECT '
✅ HOÀN THÀNH! Đã tạo thành công:
   - 3 bảng (capdo_baocuong_iso, thietbi_capdo_kpi_iso, congviec_suachua_iso)
   - 2 FK constraints
   - 3 VIEWs thống kê
   - 2 TRIGGERs kiểm tra 8h/ngày
   - 3 cấp độ bảo dưỡng (CAP1, CAP2, CAP3)
' AS summary;
