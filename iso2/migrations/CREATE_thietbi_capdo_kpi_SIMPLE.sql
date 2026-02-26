-- ========================================
-- Script: XÓA VÀ TẠO LẠI BẢNG KPI - CLEAN START
-- Ngày: 2026-02-25
-- Mục đích: Xóa tất cả bảng liên quan và tạo lại từ đầu
-- ========================================

USE diavatly_db;

-- =====================================================
-- BƯỚC 1: XÓA SẠCH TẤT CẢ BẢNG LIÊN QUAN
-- =====================================================

-- Xóa các bảng backup cũ
DROP TABLE IF EXISTS thietbi_capdo_kpi_iso_backup_20260224;
DROP TABLE IF EXISTS thietbi_capdo_kpi_iso_backup;
DROP TABLE IF EXISTS thietbi_capdo_kpi_iso_new;
DROP TABLE IF EXISTS congviec_suachua_iso;  -- Xóa bảng phụ thuộc trước

-- Xóa bảng chính
DROP TABLE IF EXISTS thietbi_capdo_kpi_iso;
DROP TABLE IF EXISTS capdo_baocuong_iso;

SELECT 'Đã xóa tất cả bảng cũ' AS status;

-- =====================================================
-- BƯỚC 2: TẠO BẢNG CAPDO_BAOCUONG_ISO (3 cấp độ)
-- =====================================================

CREATE TABLE capdo_baocuong_iso (
    stt INT(11) AUTO_INCREMENT PRIMARY KEY,
    ma_capdo VARCHAR(20) NOT NULL UNIQUE COMMENT 'CAP1, CAP2, CAP3',
    ten_capdo VARCHAR(100) NOT NULL,
    mo_ta TEXT,
    kpi_gio_chuan DECIMAL(5,2) NOT NULL COMMENT 'KPI giờ chuẩn (2h, 4h, 8h)',
    mau_sac VARCHAR(20) DEFAULT '#4CAF50',
    thu_tu INT(3) DEFAULT 1,
    trang_thai TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_ma_capdo (ma_capdo),
    INDEX idx_trang_thai (trang_thai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Cấp độ bảo dưỡng với KPI chuẩn';

-- Insert 3 cấp độ mặc định
INSERT INTO capdo_baocuong_iso (ma_capdo, ten_capdo, kpi_gio_chuan, mau_sac, thu_tu, mo_ta)
VALUES 
    ('CAP1', 'Bảo dưỡng Cấp 1', 2.00, '#4CAF50', 1, 'Bảo dưỡng nhẹ, kiểm tra định kỳ hàng ngày/tuần'),
    ('CAP2', 'Bảo dưỡng Cấp 2', 4.00, '#FF9800', 2, 'Bảo dưỡng trung bình, thay linh kiện nhỏ'),
    ('CAP3', 'Bảo dưỡng Cấp 3', 8.00, '#F44336', 3, 'Bảo dưỡng nặng, đại tu toàn bộ');

SELECT 'Đã tạo bảng capdo_baocuong_iso' AS status;

-- =====================================================
-- BƯỚC 3: TẠO BẢNG THIETBI_CAPDO_KPI_ISO
-- =====================================================

CREATE TABLE thietbi_capdo_kpi_iso (
    stt INT(11) AUTO_INCREMENT PRIMARY KEY,
    thietbi_stt INT(11) NOT NULL COMMENT 'Link đến thietbi_iso.stt',
    capdo_stt INT(11) NOT NULL COMMENT 'Link đến capdo_baocuong_iso.stt',
    kpi_gio_du_kien DECIMAL(5,2) NOT NULL COMMENT 'KPI riêng cho thiết bị này',
    ghi_chu TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(50),
    updated_by VARCHAR(50),
    
    -- Indexes
    INDEX idx_thietbi (thietbi_stt),
    INDEX idx_capdo (capdo_stt),
    
    -- Unique: mỗi thiết bị chỉ có 1 KPI cho mỗi cấp độ
    UNIQUE KEY uk_thietbi_capdo (thietbi_stt, capdo_stt)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='KPI riêng cho từng thiết bị theo cấp độ';

SELECT 'Đã tạo bảng thietbi_capdo_kpi_iso' AS status;

-- =====================================================
-- BƯỚC 4: VERIFY KẾT QUẢ
-- =====================================================

SELECT 
    'capdo_baocuong_iso' AS table_name,
    COUNT(*) AS total_records,
    GROUP_CONCAT(CONCAT(ma_capdo, '=', kpi_gio_chuan, 'h') ORDER BY thu_tu SEPARATOR ', ') AS data
FROM capdo_baocuong_iso

UNION ALL

SELECT 
    'thietbi_capdo_kpi_iso' AS table_name,
    COUNT(*) AS total_records,
    'Empty - Ready for data' AS data;

-- Hiển thị cấu trúc bảng
SHOW CREATE TABLE capdo_baocuong_iso;
SHOW CREATE TABLE thietbi_capdo_kpi_iso;

-- =====================================================
-- VÍ DỤ SỬ DỤNG SAU KHI TẠO XONG
-- =====================================================

/*
-- 1. Xem tất cả cấp độ
SELECT * FROM capdo_baocuong_iso ORDER BY thu_tu;

-- 2. Thiết lập KPI riêng cho thiết bị
INSERT INTO thietbi_capdo_kpi_iso (thietbi_stt, capdo_stt, kpi_gio_du_kien, ghi_chu)
VALUES (
    (SELECT stt FROM thietbi_iso WHERE MAVT = 'TB001' AND SOMAY = 'M001' LIMIT 1),
    1,  -- CAP1
    3.0,  -- 3 giờ thay vì 2 giờ chuẩn
    'Thiết bị phức tạp, cần thêm thời gian'
);

-- 3. Xem KPI của thiết bị
SELECT 
    kpi.kpi_gio_du_kien AS kpi_rieng,
    cd.kpi_gio_chuan AS kpi_chuan,
    cd.ten_capdo,
    tb.MAVT,
    tb.SOMAY,
    kpi.ghi_chu
FROM thietbi_capdo_kpi_iso kpi
JOIN thietbi_iso tb ON kpi.thietbi_stt = tb.stt
JOIN capdo_baocuong_iso cd ON kpi.capdo_stt = cd.stt
WHERE tb.MAVT = 'TB001' AND tb.SOMAY = 'M001';

-- 4. Lấy KPI khi nhập công việc (ưu tiên KPI riêng, fallback KPI chuẩn)
SELECT 
    COALESCE(kpi.kpi_gio_du_kien, cd.kpi_gio_chuan) AS kpi_su_dung,
    cd.ten_capdo
FROM capdo_baocuong_iso cd
LEFT JOIN thietbi_capdo_kpi_iso kpi 
    ON kpi.capdo_stt = cd.stt 
    AND kpi.thietbi_stt = (SELECT stt FROM thietbi_iso WHERE MAVT = 'TB001' AND SOMAY = 'M001')
WHERE cd.stt = 1;  -- CAP1

-- 5. Update KPI
UPDATE thietbi_capdo_kpi_iso 
SET kpi_gio_du_kien = 3.5,
    ghi_chu = 'Điều chỉnh sau thực tế'
WHERE thietbi_stt = (SELECT stt FROM thietbi_iso WHERE MAVT = 'TB001' AND SOMAY = 'M001')
AND capdo_stt = 1;

-- 6. Delete KPI riêng (sẽ dùng lại KPI chuẩn)
DELETE FROM thietbi_capdo_kpi_iso 
WHERE thietbi_stt = (SELECT stt FROM thietbi_iso WHERE MAVT = 'TB001' AND SOMAY = 'M001')
AND capdo_stt = 1;
*/

