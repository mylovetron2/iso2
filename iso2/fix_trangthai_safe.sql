-- ============================================================================
-- FIX ENUM TRANGTHAI - SAFE METHOD
-- ============================================================================
-- Date: 2026-03-20
-- Phương pháp an toàn: Đổi sang VARCHAR → UPDATE → Đổi lại ENUM
-- ============================================================================

USE diavatly_db;

-- BƯỚC 1: Kiểm tra ENUM hiện tại
-- ============================================================================
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso' 
  AND COLUMN_NAME = 'trangthai';

-- Xem các giá trị đang có
SELECT DISTINCT trangthai 
FROM giao_nhan_thietbi_iso 
ORDER BY trangthai;

-- BƯỚC 2: BACKUP (QUAN TRỌNG!)
-- ============================================================================
CREATE TABLE IF NOT EXISTS giao_nhan_thietbi_iso_backup_20260320 
AS SELECT * FROM giao_nhan_thietbi_iso;

-- BƯỚC 3: Đổi sang VARCHAR tạm thời
-- ============================================================================
ALTER TABLE giao_nhan_thietbi_iso 
MODIFY COLUMN trangthai VARCHAR(50) NOT NULL DEFAULT 'da_nhan';

-- BƯỚC 4: UPDATE dữ liệu (GIỜ SẼ THÀNH CÔNG!)
-- ============================================================================
-- Kiểm tra trước khi update
SELECT trangthai, COUNT(*) as count 
FROM giao_nhan_thietbi_iso 
GROUP BY trangthai;

-- Update tất cả các mapping
UPDATE giao_nhan_thietbi_iso 
SET trangthai = 'da_nhan' 
WHERE trangthai IN ('cho_nhan', 'Đang chờ', 'pending', 'received');

UPDATE giao_nhan_thietbi_iso 
SET trangthai = 'da_giao' 
WHERE trangthai IN ('hoan_thanh', 'completed', 'done', 'finished', 'Hoàn thành');

UPDATE giao_nhan_thietbi_iso 
SET trangthai = 'dang_kiem_dinh' 
WHERE trangthai IN ('processing', 'in_progress', 'Đang xử lý');

-- Verify sau update
SELECT trangthai, COUNT(*) as count 
FROM giao_nhan_thietbi_iso 
GROUP BY trangthai;

-- BƯỚC 5: Đổi lại thành ENUM mới (GIỜ SẼ THÀNH CÔNG!)
-- ============================================================================
ALTER TABLE giao_nhan_thietbi_iso
MODIFY COLUMN trangthai ENUM('da_nhan', 'dang_kiem_dinh', 'da_giao') 
NOT NULL DEFAULT 'da_nhan'
COMMENT 'da_nhan: Đã nhận từ đội | dang_kiem_dinh: Đang gửi kiểm định | da_giao: Đã giao lại cho đội';

-- BƯỚC 6: Verify kết quả
-- ============================================================================
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso' 
  AND COLUMN_NAME = 'trangthai';

-- Expected: enum('da_nhan','dang_kiem_dinh','da_giao')

SELECT trangthai, COUNT(*) as count 
FROM giao_nhan_thietbi_iso 
GROUP BY trangthai;

-- Expected: Chỉ có 3 giá trị: da_nhan, dang_kiem_dinh, da_giao

-- ============================================================================
-- HOÀN TẤT!
-- ============================================================================
