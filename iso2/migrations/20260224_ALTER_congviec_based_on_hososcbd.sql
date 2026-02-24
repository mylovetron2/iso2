-- ========================================
-- Migration: Cập nhật congviec_suachua_iso dựa trên hososcbd_iso
-- Tác giả: AI Assistant
-- Ngày: 2026-02-24
-- Mục đích: 
--   - Thay đổi logic: Người dùng chọn hồ sơ SCBD thay vì nhập mavt/somay
--   - hososcbd_stt trở thành NOT NULL (bắt buộc)
--   - Xóa mavt, somay, ten_thietbi (lấy từ hososcbd_iso)
--   - Thêm thietbi_stt (nullable) để tăng tốc query
-- ========================================

USE diavatly_db;

-- =====================================================
-- BƯỚC 1: Tạo bảng mới với cấu trúc cập nhật
-- =====================================================

DROP TABLE IF EXISTS `congviec_suachua_iso_new`;

CREATE TABLE `congviec_suachua_iso_new` (
    `stt` INT(11) AUTO_INCREMENT PRIMARY KEY,
    
    -- Thông tin nhân viên
    `nhanvien_stt` INT(11) NOT NULL COMMENT 'FK → resume.stt (nhân viên thực hiện)',
    `ngay_lam_viec` DATE NOT NULL COMMENT 'Ngày làm việc',
    
    -- ✅ HỒ SƠ SCBD (BẮT BUỘC)
    `hososcbd_stt` INT(11) NOT NULL COMMENT 'FK → hososcbd_iso.stt (BẮT BUỘC)',
    
    -- ✅ THIẾT BỊ (optional, để tăng tốc query, có thể NULL nếu chưa link)
    `thietbi_stt` INT(11) DEFAULT NULL COMMENT 'FK → thietbi_iso.stt (optional cache)',
    
    -- Cấp độ và KPI
    `capdo_stt` INT(11) NOT NULL COMMENT 'FK → capdo_baocuong_iso.stt',
    `kpi_gio_chuan` DECIMAL(5,2) DEFAULT NULL COMMENT 'KPI chuẩn (snapshot tại thời điểm làm)',
    
    -- Công việc thực tế
    `so_gio_lam` DECIMAL(5,2) NOT NULL COMMENT 'Số giờ làm việc thực tế',
    `gio_bat_dau` TIME DEFAULT NULL COMMENT 'Giờ bắt đầu',
    `gio_ket_thuc` TIME DEFAULT NULL COMMENT 'Giờ kết thúc',
    
    -- Mô tả công việc
    `noi_dung_congviec` TEXT COMMENT 'Mô tả chi tiết công việc đã làm',
    `ghi_chu` TEXT COMMENT 'Ghi chú bổ sung',
    
    -- Trạng thái
    `trang_thai` VARCHAR(50) DEFAULT 'Hoàn thành' COMMENT 'Hoàn thành, Đang thực hiện, Tạm dừng',
    
    -- Audit fields
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` VARCHAR(50) DEFAULT NULL,
    `updated_by` VARCHAR(50) DEFAULT NULL,
    
    -- Indexes
    INDEX `idx_nhanvien` (`nhanvien_stt`),
    INDEX `idx_ngay_lam` (`ngay_lam_viec`),
    INDEX `idx_hososcbd` (`hososcbd_stt`),
    INDEX `idx_thietbi` (`thietbi_stt`),
    INDEX `idx_capdo` (`capdo_stt`),
    INDEX `idx_nhanvien_ngay` (`nhanvien_stt`, `ngay_lam_viec`),
    
    -- Foreign Keys
    CONSTRAINT `fk_congviec_hososcbd` 
        FOREIGN KEY (`hososcbd_stt`) 
        REFERENCES `hososcbd_iso`(`stt`)
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
        
    CONSTRAINT `fk_congviec_thietbi` 
        FOREIGN KEY (`thietbi_stt`) 
        REFERENCES `thietbi_iso`(`stt`)
        ON DELETE SET NULL 
        ON UPDATE CASCADE,
        
    CONSTRAINT `fk_congviec_capdo` 
        FOREIGN KEY (`capdo_stt`) 
        REFERENCES `capdo_baocuong_iso`(`stt`)
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
        
    CONSTRAINT `fk_congviec_nhanvien` 
        FOREIGN KEY (`nhanvien_stt`) 
        REFERENCES `resume`(`stt`)
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Công việc sửa chữa - Dựa trên hồ sơ SCBD';

-- =====================================================
-- BƯỚC 2: Migrate dữ liệu cũ (nếu có)
-- =====================================================

-- Migrate từ bảng cũ sang bảng mới
INSERT INTO `congviec_suachua_iso_new` 
    (stt, nhanvien_stt, ngay_lam_viec, hososcbd_stt, thietbi_stt, 
     capdo_stt, kpi_gio_chuan, so_gio_lam, gio_bat_dau, gio_ket_thuc,
     noi_dung_congviec, ghi_chu, trang_thai, created_at, updated_at, created_by)
SELECT 
    cv.stt,
    cv.nhanvien_stt,
    cv.ngay_lam AS ngay_lam_viec,
    cv.hososcbd_stt,
    tb.stt AS thietbi_stt, -- Link đến thietbi_iso bằng cách JOIN
    cv.capdo_stt,
    cv.kpi_gio_chuan,
    cv.so_gio_lam,
    cv.gio_bat_dau,
    cv.gio_ket_thuc,
    cv.noi_dung AS noi_dung_congviec,
    cv.ghi_chu,
    cv.trang_thai,
    cv.created_at,
    cv.updated_at,
    cv.created_by
FROM congviec_suachua_iso cv
LEFT JOIN thietbi_iso tb ON cv.mavt = tb.MAVT AND cv.somay = tb.SOMAY
WHERE cv.hososcbd_stt IS NOT NULL -- Chỉ migrate những record đã có hososcbd_stt
AND EXISTS (SELECT 1 FROM congviec_suachua_iso LIMIT 1); -- Kiểm tra bảng cũ tồn tại

-- =====================================================
-- BƯỚC 3: Kiểm tra dữ liệu
-- =====================================================

SELECT 
    'Bảng cũ' AS nguon,
    COUNT(*) AS so_luong,
    COUNT(DISTINCT hososcbd_stt) AS so_hososcbd
FROM congviec_suachua_iso
WHERE EXISTS (SELECT 1 FROM congviec_suachua_iso LIMIT 1)

UNION ALL

SELECT 
    'Bảng mới' AS nguon,
    COUNT(*) AS so_luong,
    COUNT(DISTINCT hososcbd_stt) AS so_hososcbd
FROM congviec_suachua_iso_new;

-- Hiển thị records không migrate được (không có hososcbd_stt)
SELECT 
    cv.stt,
    cv.nhanvien_ten,
    cv.ngay_lam,
    cv.mavt,
    cv.somay,
    'Thiếu hososcbd_stt' AS canh_bao
FROM congviec_suachua_iso cv
WHERE cv.hososcbd_stt IS NULL
AND EXISTS (SELECT 1 FROM congviec_suachua_iso LIMIT 1);

-- =====================================================
-- BƯỚC 4: Tạo VIEW để lấy thông tin đầy đủ
-- =====================================================

CREATE OR REPLACE VIEW view_congviec_full AS
SELECT 
    cv.stt,
    cv.ngay_lam_viec,
    cv.so_gio_lam,
    cv.gio_bat_dau,
    cv.gio_ket_thuc,
    cv.noi_dung_congviec,
    cv.ghi_chu,
    cv.trang_thai,
    
    -- Thông tin nhân viên
    nv.stt AS nhanvien_stt,
    nv.HOTEN AS ten_nhanvien,
    nv.USERNAME AS ma_nhanvien,
    
    -- Thông tin hồ sơ SCBD
    hs.stt AS hososcbd_stt,
    hs.hoso AS ma_hoso,
    hs.phieu AS so_phieu,
    hs.madv AS ma_donvi,
    dv.tendv AS ten_donvi,
    
    -- Thông tin thiết bị (từ hososcbd_iso)
    hs.mavt,
    hs.somay,
    tb.TENVT AS ten_thietbi,
    tb.MODEL AS model_thietbi,
    tb.DVT AS don_vi_tinh,
    
    -- Thông tin cấp độ
    cd.stt AS capdo_stt,
    cd.ma_capdo,
    cd.ten_capdo,
    cd.kpi_gio_chuan AS kpi_chuan_hientai,
    cv.kpi_gio_chuan AS kpi_luc_lam,
    
    -- Tính toán hiệu suất
    CASE 
        WHEN cv.so_gio_lam > 0 THEN 
            ROUND((COALESCE(cv.kpi_gio_chuan, cd.kpi_gio_chuan) / cv.so_gio_lam) * 100, 2)
        ELSE 0 
    END AS hieu_suat_percent,
    
    -- Audit
    cv.created_at,
    cv.updated_at,
    cv.created_by,
    cv.updated_by
    
FROM congviec_suachua_iso_new cv
INNER JOIN resume nv ON cv.nhanvien_stt = nv.stt
INNER JOIN hososcbd_iso hs ON cv.hososcbd_stt = hs.stt
LEFT JOIN thietbi_iso tb ON hs.mavt = tb.MAVT AND hs.somay = tb.SOMAY
LEFT JOIN donvi_iso dv ON hs.madv = dv.madv
LEFT JOIN capdo_baocuong_iso cd ON cv.capdo_stt = cd.stt
ORDER BY cv.ngay_lam_viec DESC, cv.created_at DESC;

-- =====================================================
-- BƯỚC 5: Cập nhật VIEW thống kê
-- =====================================================

-- VIEW thống kê theo thiết bị (từ hososcbd_iso)
CREATE OR REPLACE VIEW view_kpi_thietbi_thongke AS
SELECT 
    hs.mavt AS mavt_thietbi,
    hs.somay AS somay_thietbi,
    tb.TENVT AS ten_thietbi,
    COUNT(cv.stt) AS so_lan_sua,
    SUM(cv.so_gio_lam) AS tong_gio,
    AVG(cv.so_gio_lam) AS gio_trung_binh,
    COUNT(DISTINCT cv.capdo_stt) AS so_capdo_khac_nhau,
    COUNT(DISTINCT cv.nhanvien_stt) AS so_nhanvien_tham_gia,
    MAX(cv.ngay_lam_viec) AS lan_sua_gan_nhat
FROM congviec_suachua_iso_new cv
INNER JOIN hososcbd_iso hs ON cv.hososcbd_stt = hs.stt
LEFT JOIN thietbi_iso tb ON hs.mavt = tb.MAVT AND hs.somay = tb.SOMAY
GROUP BY hs.mavt, hs.somay, tb.TENVT;

-- =====================================================
-- BƯỚC 6: Cập nhật TRIGGERs với bảng mới
-- =====================================================

DROP TRIGGER IF EXISTS before_insert_congviec_check_gio;
DROP TRIGGER IF EXISTS before_update_congviec_check_gio;

DELIMITER //

CREATE TRIGGER before_insert_congviec_check_gio
BEFORE INSERT ON congviec_suachua_iso_new
FOR EACH ROW
BEGIN
    DECLARE tong_gio DECIMAL(5,2);
    
    -- Tính tổng giờ đã làm trong ngày của nhân viên
    SELECT COALESCE(SUM(so_gio_lam), 0) INTO tong_gio
    FROM congviec_suachua_iso_new
    WHERE nhanvien_stt = NEW.nhanvien_stt
    AND ngay_lam_viec = NEW.ngay_lam_viec;
    
    -- Kiểm tra không vượt quá 8 giờ/ngày
    IF (tong_gio + NEW.so_gio_lam) > 8 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Vượt quá giới hạn 8 giờ làm việc trong ngày';
    END IF;
END//

CREATE TRIGGER before_update_congviec_check_gio
BEFORE UPDATE ON congviec_suachua_iso_new
FOR EACH ROW
BEGIN
    DECLARE tong_gio DECIMAL(5,2);
    
    -- Tính tổng giờ (trừ record đang update)
    SELECT COALESCE(SUM(so_gio_lam), 0) INTO tong_gio
    FROM congviec_suachua_iso_new
    WHERE nhanvien_stt = NEW.nhanvien_stt
    AND ngay_lam_viec = NEW.ngay_lam_viec
    AND stt != OLD.stt;
    
    -- Kiểm tra
    IF (tong_gio + NEW.so_gio_lam) > 8 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Vượt quá giới hạn 8 giờ làm việc trong ngày';
    END IF;
END//

DELIMITER ;

-- =====================================================
-- BƯỚC 7: BACKUP và REPLACE (CHỈ chạy sau khi kiểm tra!)
-- =====================================================

/*
-- Uncomment để thực thi:

-- Backup bảng cũ
RENAME TABLE congviec_suachua_iso TO congviec_suachua_iso_backup_20260224;

-- Đổi tên bảng mới
RENAME TABLE congviec_suachua_iso_new TO congviec_suachua_iso;

-- Xác nhận
SELECT 'Migration hoàn tất!' AS status;
SELECT COUNT(*) AS total_records FROM congviec_suachua_iso;

*/

-- =====================================================
-- ROLLBACK (nếu cần)
-- =====================================================

/*
DROP TABLE IF EXISTS congviec_suachua_iso;
RENAME TABLE congviec_suachua_iso_backup_20260224 TO congviec_suachua_iso;
DROP VIEW IF EXISTS view_congviec_full;
DROP VIEW IF EXISTS view_kpi_thietbi_thongke;
*/

-- =====================================================
-- Kết thúc Migration
-- =====================================================
