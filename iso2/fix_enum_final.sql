-- ============================================================================
-- FIX ENUM TRANGTHAI - FINAL SOLUTION
-- ============================================================================
-- Date: 2026-03-20
-- ENUM hiện tại: enum('cho_nhan','da_nhan','hoan_thanh')
-- ENUM mới: enum('da_nhan','dang_kiem_dinh','da_giao')
-- 
-- CHIẾN LƯỢC: Expand → Update → Shrink
-- ============================================================================

USE diavatly_db;

-- ============================================================================
-- BƯỚC 1: BACKUP (BẮT BUỘC!)
-- ============================================================================
CREATE TABLE IF NOT EXISTS giao_nhan_thietbi_iso_backup_20260320 
AS SELECT * FROM giao_nhan_thietbi_iso;

SELECT 'Backup created successfully!' as status;

-- ============================================================================
-- BƯỚC 2: EXPAND ENUM - Thêm giá trị mới vào ENUM cũ
-- ============================================================================
-- Kết hợp cả cũ và mới: cho_nhan, da_nhan, hoan_thanh, dang_kiem_dinh, da_giao
ALTER TABLE giao_nhan_thietbi_iso
MODIFY COLUMN trangthai ENUM(
    'cho_nhan', 
    'da_nhan', 
    'hoan_thanh', 
    'dang_kiem_dinh', 
    'da_giao'
) NOT NULL DEFAULT 'da_nhan';

SELECT 'ENUM expanded successfully!' as status;

-- ============================================================================
-- BƯỚC 3: KIỂM TRA DỮ LIỆU TRƯỚC KHI UPDATE
-- ============================================================================
SELECT 
    trangthai, 
    COUNT(*) as count,
    CASE 
        WHEN trangthai = 'cho_nhan' THEN '→ Will change to: da_nhan'
        WHEN trangthai = 'da_nhan' THEN '→ Keep unchanged: da_nhan'
        WHEN trangthai = 'hoan_thanh' THEN '→ Will change to: da_giao'
        ELSE '→ Unknown mapping!'
    END as mapping
FROM giao_nhan_thietbi_iso 
GROUP BY trangthai;

-- ============================================================================
-- BƯỚC 4: UPDATE DỮ LIỆU (GIỜ SẼ THÀNH CÔNG!)
-- ============================================================================
-- Map: cho_nhan → da_nhan
UPDATE giao_nhan_thietbi_iso 
SET trangthai = 'da_nhan' 
WHERE trangthai = 'cho_nhan';

SELECT CONCAT('Updated ', ROW_COUNT(), ' rows: cho_nhan → da_nhan') as status;

-- Map: hoan_thanh → da_giao  
UPDATE giao_nhan_thietbi_iso 
SET trangthai = 'da_giao' 
WHERE trangthai = 'hoan_thanh';

SELECT CONCAT('Updated ', ROW_COUNT(), ' rows: hoan_thanh → da_giao') as status;

-- ============================================================================
-- BƯỚC 5: VERIFY SAU UPDATE
-- ============================================================================
SELECT 
    trangthai, 
    COUNT(*) as count 
FROM giao_nhan_thietbi_iso 
GROUP BY trangthai;

-- Expected: Chỉ có 'da_nhan' và/hoặc 'da_giao'
-- Không còn 'cho_nhan' và 'hoan_thanh'

-- ============================================================================
-- BƯỚC 6: SHRINK ENUM - Chỉ giữ giá trị mới
-- ============================================================================
ALTER TABLE giao_nhan_thietbi_iso
MODIFY COLUMN trangthai ENUM('da_nhan', 'dang_kiem_dinh', 'da_giao') 
NOT NULL DEFAULT 'da_nhan'
COMMENT 'da_nhan: Đã nhận từ đội | dang_kiem_dinh: Đang gửi kiểm định | da_giao: Đã giao lại cho đội';

SELECT 'ENUM shrunk to final values!' as status;

-- ============================================================================
-- BƯỚC 7: XÓA CỘT CŨ (nếu có)
-- ============================================================================
-- Kiểm tra xem cột có tồn tại không
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso'
  AND COLUMN_NAME IN ('loai_giao_nhan', 'phieu_giao_id');

-- Nếu tồn tại, xóa đi (chạy riêng từng câu nếu lỗi)
ALTER TABLE giao_nhan_thietbi_iso DROP COLUMN IF EXISTS loai_giao_nhan;
ALTER TABLE giao_nhan_thietbi_iso DROP COLUMN IF EXISTS phieu_giao_id;

-- ============================================================================
-- BƯỚC 8: THÊM CỘT MỚI
-- ============================================================================
-- Kiểm tra xem cột mới đã tồn tại chưa
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso'
  AND COLUMN_NAME IN ('nguoi_gui_kiemdinh', 'donvi_gui_kiemdinh', 'ngay_gui_kiemdinh');

-- Nếu chưa có, thêm vào (chạy riêng từng câu nếu lỗi)
ALTER TABLE giao_nhan_thietbi_iso
ADD COLUMN IF NOT EXISTS nguoi_gui_kiemdinh VARCHAR(255) DEFAULT NULL 
    COMMENT 'Người gửi đi kiểm định' AFTER donvi_giao,
ADD COLUMN IF NOT EXISTS donvi_gui_kiemdinh VARCHAR(255) DEFAULT NULL 
    COMMENT 'Đơn vị gửi kiểm định' AFTER nguoi_gui_kiemdinh,
ADD COLUMN IF NOT EXISTS ngay_gui_kiemdinh DATE DEFAULT NULL 
    COMMENT 'Ngày gửi kiểm định' AFTER donvi_gui_kiemdinh;

-- ============================================================================
-- BƯỚC 9: FINAL VERIFY
-- ============================================================================
-- Kiểm tra ENUM mới
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso' 
  AND COLUMN_NAME = 'trangthai';

-- Expected: enum('da_nhan','dang_kiem_dinh','da_giao')

-- Kiểm tra dữ liệu
SELECT 
    trangthai, 
    COUNT(*) as count 
FROM giao_nhan_thietbi_iso 
GROUP BY trangthai
ORDER BY trangthai;

-- Expected: Chỉ có các giá trị: da_nhan, dang_kiem_dinh, da_giao

-- Kiểm tra structure
DESCRIBE giao_nhan_thietbi_iso;

-- ============================================================================
-- ROLLBACK (chỉ chạy nếu có lỗi)
-- ============================================================================
/*
DROP TABLE IF EXISTS giao_nhan_thietbi_iso;
CREATE TABLE giao_nhan_thietbi_iso AS 
SELECT * FROM giao_nhan_thietbi_iso_backup_20260320;
ALTER TABLE giao_nhan_thietbi_iso MODIFY id INT AUTO_INCREMENT PRIMARY KEY;
*/

-- ============================================================================
-- HOÀN TẤT! ✅
-- ============================================================================
SELECT '✅ MIGRATION COMPLETED SUCCESSFULLY!' as status;
