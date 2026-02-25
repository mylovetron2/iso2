-- =====================================================
-- Migration: Chuẩn hóa congviec_suachua_iso với hososcbd_iso FK
-- Date: 2026-02-25
-- Description: Loại bỏ duplication mavt/somay, chỉ lưu hososcbd_stt FK
-- =====================================================

-- Lý do: Một hososcbd_iso chỉ có 1 thietbi_iso
-- Công việc sửa chữa LUÔN liên quan đến hồ sơ SCBD
-- → Không cần lưu mavt/somay riêng, lấy qua JOIN hososcbd_iso

-- =====================================================
-- LƯU Ý QUAN TRỌNG:
-- =====================================================
-- 1. ĐỔI TÊN DATABASE phù hợp với môi trường của bạn:
--    USE your_database_name;
--    Hoặc chạy: mysql -u root -p your_database_name < ALTER_congviec_hososcbd_FK.sql
--
-- 2. PHẢI CHẠY MIGRATION TẠO BẢNG TRƯỚC (theo thứ tự):
--    a. mysql -u root -p your_db < migrations/20251121_create_hososcbd_tables.sql
--    b. mysql -u root -p your_db < migrations/20260224_create_kpi_suachua_system_FIXED.sql
--
-- 3. Kiểm tra bảng đã tồn tại:
--    SELECT COUNT(*) FROM hososcbd_iso;     -- Phải có
--    SELECT COUNT(*) FROM congviec_suachua_iso;  -- Phải có
-- =====================================================

-- USE diavatly_db;  -- ← Bỏ comment và thay tên database của bạn

-- =====================================================
-- BƯỚC 0: (OPTIONAL) Kiểm tra điều kiện tiên quyết
-- =====================================================
-- LƯU Ý: Các lệnh kiểm tra dưới đây YÊU CẦU quyền SELECT trên information_schema
-- Nếu gặp lỗi "Access denied to information_schema", comment toàn bộ section này.
-- MySQL sẽ tự động báo lỗi rõ ràng nếu thiếu bảng khi chạy các bước tiếp theo.

/*
-- Kiểm tra bảng hososcbd_iso có tồn tại không
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✓ Bảng hososcbd_iso đã tồn tại'
        ELSE '❌ LỖI: Bảng hososcbd_iso CHƯA được tạo! Chạy migrations/20251121_create_hososcbd_tables.sql TRƯỚC!'
    END AS check_hososcbd,
    COUNT(*) AS table_exists
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'hososcbd_iso';

-- Kiểm tra bảng congviec_suachua_iso có tồn tại không
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✓ Bảng congviec_suachua_iso đã tồn tại'
        ELSE '❌ LỖI: Bảng congviec_suachua_iso CHƯA được tạo! Chạy migrations/20260224_create_kpi_suachua_system_FIXED.sql TRƯỚC!'
    END AS check_congviec,
    COUNT(*) AS table_exists
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'congviec_suachua_iso';

-- Kiểm tra cột hososcbd_stt có tồn tại trong congviec_suachua_iso không
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN '✓ Cột hososcbd_stt đã tồn tại trong congviec_suachua_iso'
        ELSE '⚠️ CẢNH BÁO: Cột hososcbd_stt chưa có trong congviec_suachua_iso'
    END AS check_column,
    COUNT(*) AS column_exists
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'congviec_suachua_iso'
  AND COLUMN_NAME = 'hososcbd_stt';

-- Kiểm tra PRIMARY KEY của hososcbd_iso
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_KEY,
    CASE 
        WHEN COLUMN_KEY = 'PRI' THEN '✓ stt là PRIMARY KEY'
        ELSE '❌ stt KHÔNG phải PRIMARY KEY'
    END AS pk_status
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'hososcbd_iso'
  AND COLUMN_NAME = 'stt';
*/

SELECT '
⚠️  ĐÃ BỎ QUA KIỂM TRA TỰ ĐỘNG (do quyền truy cập information_schema)

📋 VUI LÒNG Tự kiểm tra:
   1. Bảng hososcbd_iso đã được tạo chưa?
      → mysql -u root -p your_db < migrations/20251121_create_hososcbd_tables.sql
   
   2. Bảng congviec_suachua_iso đã được tạo chưa?
      → mysql -u root -p your_db < migrations/20260224_create_kpi_suachua_system_FIXED.sql
   
   3. Nếu chưa, DỪNG và chạy 2 script trên TRƯỚC!
   
✅ Nếu cả 2 bảng đã có, tiếp tục các bước dưới...
' AS important_note;

-- =====================================================
-- BƯỚC 1: Backup bảng hiện tại
-- =====================================================
DROP TABLE IF EXISTS congviec_suachua_iso_backup_20260225;
CREATE TABLE congviec_suachua_iso_backup_20260225 AS 
SELECT * FROM congviec_suachua_iso;

SELECT CONCAT('✓ Đã backup ', COUNT(*), ' records') AS status 
FROM congviec_suachua_iso_backup_20260225;

-- =====================================================
-- BƯỚC 2: Xóa các records không có hososcbd_stt
-- =====================================================
DELETE FROM congviec_suachua_iso WHERE hososcbd_stt IS NULL;

SELECT CONCAT('✓ Đã xóa ', ROW_COUNT(), ' records không có hồ sơ SCBD') AS status;

-- =====================================================
-- BƯỚC 3: DROP các cột không cần thiết
-- =====================================================
-- Bỏ mavt, somay, ten_thietbi vì có thể lấy từ hososcbd_iso
ALTER TABLE congviec_suachua_iso
    DROP COLUMN IF EXISTS mavt,
    DROP COLUMN IF EXISTS somay,
    DROP COLUMN IF EXISTS ten_thietbi,
    DROP COLUMN IF EXISTS thietbi_stt;

SELECT '✓ Đã xóa các cột mavt, somay, ten_thietbi, thietbi_stt' AS status;

-- =====================================================
-- BƯỚC 4: Thay đổi hososcbd_stt thành NOT NULL
-- =====================================================
ALTER TABLE congviec_suachua_iso
    MODIFY COLUMN hososcbd_stt INT(11) NOT NULL COMMENT 'Link đến hososcbd_iso.stt (BẮT BUỘC)';

SELECT '✓ hososcbd_stt đã thành NOT NULL' AS status;

-- =====================================================
-- BƯỚC 5: Thêm Foreign Key Constraint
-- =====================================================
-- Đảm bảo referential integrity: mọi công việc phải có hồ sơ SCBD hợp lệ
ALTER TABLE congviec_suachua_iso
    ADD CONSTRAINT fk_congviec_hososcbd 
    FOREIGN KEY (hososcbd_stt) 
    REFERENCES hososcbd_iso(stt)
    ON DELETE RESTRICT     -- Không cho xóa hồ sơ nếu còn công việc
    ON UPDATE CASCADE;     -- Tự động cập nhật khi stt hồ sơ thay đổi

SELECT '✓ Đã thêm FK constraint: hososcbd_stt → hososcbd_iso.stt' AS status;

-- =====================================================
-- BƯỚC 6: Thêm Index để tối ưu JOIN
-- =====================================================
-- Index hososcbd_stt đã có sẵn, không cần thêm

-- =====================================================
-- BƯỚC 7: Cập nhật VIEW thống kê KPI theo thiết bị
-- =====================================================
-- VIEW mới sẽ JOIN qua hososcbd_iso để lấy thông tin thiết bị

DROP VIEW IF EXISTS view_kpi_thietbi_thongke;

CREATE VIEW view_kpi_thietbi_thongke AS
SELECT 
    hs.mavt,
    hs.somay,
    hs.model AS ten_thietbi,
    cv.capdo_stt,
    cv.capdo_ten,
    cv.kpi_gio_chuan,
    COUNT(cv.stt) AS so_lan_sua,
    SUM(cv.so_gio_lam) AS tong_gio_thuc_te,
    ROUND(AVG(cv.so_gio_lam), 2) AS gio_trung_binh,
    ROUND((cv.kpi_gio_chuan / AVG(cv.so_gio_lam)) * 100, 2) AS hieu_suat_percent,
    CASE 
        WHEN AVG(cv.so_gio_lam) <= cv.kpi_gio_chuan THEN 'Đạt KPI'
        WHEN AVG(cv.so_gio_lam) <= cv.kpi_gio_chuan * 1.2 THEN 'Gần đạt'
        ELSE 'Chưa đạt'
    END AS danh_gia_kpi
FROM congviec_suachua_iso cv
JOIN hososcbd_iso hs ON cv.hososcbd_stt = hs.stt
GROUP BY hs.mavt, hs.somay, hs.model, cv.capdo_stt, cv.capdo_ten, cv.kpi_gio_chuan;

SELECT '✓ Đã cập nhật VIEW: view_kpi_thietbi_thongke với JOIN hososcbd_iso' AS status;

-- =====================================================
-- BƯỚC 8: Tạo VIEW mới - Danh sách công việc với thông tin đầy đủ
-- =====================================================
DROP VIEW IF EXISTS view_congviec_full_info;

CREATE VIEW view_congviec_full_info AS
SELECT 
    cv.stt,
    cv.nhanvien_stt,
    cv.nhanvien_ten,
    cv.ngay_lam,
    
    -- Thông tin hồ sơ SCBD
    cv.hososcbd_stt,
    hs.phieu AS so_phieu,
    hs.maql,
    hs.hoso AS ma_hoso,
    
    -- Thông tin thiết bị (từ hososcbd)
    hs.mavt,
    hs.somay,
    hs.model AS ten_thietbi,
    hs.vitrimaybd AS vi_tri,
    
    -- Thông tin đơn vị
    hs.madv,
    dv.tendv AS ten_donvi,
    
    -- Cấp độ & KPI
    cv.capdo_stt,
    cv.capdo_ten,
    cv.kpi_gio_chuan,
    
    -- Công việc
    cv.noi_dung,
    cv.so_gio_lam,
    cv.gio_bat_dau,
    cv.gio_ket_thuc,
    cv.trang_thai,
    cv.ghi_chu,
    
    -- Hiệu suất
    ROUND((cv.kpi_gio_chuan / cv.so_gio_lam) * 100, 2) AS hieu_suat_percent,
    CASE 
        WHEN cv.so_gio_lam <= cv.kpi_gio_chuan THEN 'Đạt KPI'
        WHEN cv.so_gio_lam <= cv.kpi_gio_chuan * 1.2 THEN 'Gần đạt'
        ELSE 'Chưa đạt'
    END AS danh_gia,
    
    cv.created_by,
    cv.created_at,
    cv.updated_at
FROM congviec_suachua_iso cv
JOIN hososcbd_iso hs ON cv.hososcbd_stt = hs.stt
LEFT JOIN donvi_iso dv ON hs.madv = dv.madv;

SELECT '✓ Đã tạo VIEW mới: view_congviec_full_info (thông tin đầy đủ)' AS status;

-- =====================================================
-- BƯỚC 9: VERIFY kết quả
-- =====================================================
-- Kiểm tra cấu trúc bảng mới
SELECT '========== CẤU TRÚC BẢNG MỚI ==========' AS info;
SHOW CREATE TABLE congviec_suachua_iso;

-- Kiểm tra FK constraint
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    UPDATE_RULE,
    DELETE_RULE
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'congviec_suachua_iso'
  AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Kiểm tra VIEW mới
SELECT '========== VIEWs ==========' AS info;
SELECT COUNT(*) AS total_records FROM view_kpi_thietbi_thongke;
SELECT COUNT(*) AS total_records FROM view_congviec_full_info;

-- Sample data từ VIEW mới
SELECT * FROM view_congviec_full_info LIMIT 5;

-- =====================================================
-- ROLLBACK (nếu cần)
-- =====================================================
/*
-- Khôi phục từ backup
DROP TABLE IF EXISTS congviec_suachua_iso;
RENAME TABLE congviec_suachua_iso_backup_20260225 TO congviec_suachua_iso;

-- Xóa backup sau khi verify OK
DROP TABLE IF EXISTS congviec_suachua_iso_backup_20260225;
*/

-- =====================================================
-- VÍ DỤ SỬ DỤNG SAU KHI MIGRATE
-- =====================================================
/*
-- 1. Nhập công việc mới (chỉ cần hososcbd_stt)
INSERT INTO congviec_suachua_iso (
    nhanvien_stt, 
    nhanvien_ten,
    ngay_lam,
    hososcbd_stt,        -- Chọn hồ sơ SCBD → tự động có mavt/somay
    capdo_stt,
    capdo_ten,
    kpi_gio_chuan,
    noi_dung,
    so_gio_lam,
    created_by
) VALUES (
    123,
    'Nguyễn Văn A',
    '2026-02-25',
    456,                 -- hososcbd_stt từ dropdown
    1,                   -- CAP1
    'Bảo dưỡng Cấp 1',
    2.00,
    'Bảo dưỡng định kỳ: vệ sinh, bôi trơn',
    2.5,
    'admin'
);

-- 2. Xem công việc với thông tin đầy đủ (JOIN auto)
SELECT 
    nhanvien_ten,
    ngay_lam,
    so_phieu,
    mavt,
    somay,
    ten_thietbi,
    capdo_ten,
    so_gio_lam,
    hieu_suat_percent,
    danh_gia
FROM view_congviec_full_info
WHERE nhanvien_stt = 123
  AND ngay_lam BETWEEN '2026-02-01' AND '2026-02-28'
ORDER BY ngay_lam DESC;

-- 3. Thống kê KPI theo thiết bị
SELECT 
    mavt,
    somay,
    ten_thietbi,
    capdo_ten,
    so_lan_sua,
    gio_trung_binh,
    kpi_gio_chuan,
    hieu_suat_percent,
    danh_gia_kpi
FROM view_kpi_thietbi_thongke
WHERE mavt = 'TB001'
ORDER BY capdo_stt;

-- 4. Tìm hồ sơ SCBD để chọn khi nhập công việc
SELECT 
    hs.stt AS hososcbd_stt,
    hs.phieu,
    hs.maql,
    hs.mavt,
    hs.somay,
    hs.model AS ten_thietbi,
    hs.vitrimaybd,
    dv.tendv,
    hs.ngayyc,
    hs.trang_thai
FROM hososcbd_iso hs
LEFT JOIN donvi_iso dv ON hs.madv = dv.madv
WHERE hs.mavt LIKE '%TB001%'
   OR hs.somay LIKE '%M001%'
ORDER BY hs.ngayyc DESC
LIMIT 20;
*/
