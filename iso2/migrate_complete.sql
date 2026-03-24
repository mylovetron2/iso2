-- ============================================================================
-- MIGRATION HOÀN CHỈNH - Giao Nhận Thiết Bị
-- ============================================================================
-- Date: 2026-03-20
-- 
-- MỤC TIÊU:
-- 1. Tạo bảng chitiet (nếu chưa có)
-- 2. Migrate dữ liệu từ master → chitiet
-- 3. Xóa cột device khỏi master
-- 4. Thêm 3 cột mới cho workflow kiểm định
-- ============================================================================

USE diavatly_db;

-- ============================================================================
-- BƯỚC 1: BACKUP (BẮT BUỘC!)
-- ============================================================================
CREATE TABLE IF NOT EXISTS giao_nhan_thietbi_iso_backup_complete 
AS SELECT * FROM giao_nhan_thietbi_iso;

SELECT 'Backup master table created!' as status;

-- ============================================================================
-- BƯỚC 2: TẠO BẢNG CHI TIẾT (nếu chưa có)
-- ============================================================================
CREATE TABLE IF NOT EXISTS giao_nhan_thietbi_chitiet (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    phieu_id INT(11) NOT NULL COMMENT 'ID phiếu giao nhận (FK)',
    thietbi_id INT(11) DEFAULT NULL COMMENT 'ID thiết bị trong hệ thống (nếu có)',
    ten_thietbi VARCHAR(255) DEFAULT NULL COMMENT 'Tên thiết bị',
    ky_ma_hieu VARCHAR(100) DEFAULT NULL COMMENT 'Ký mã hiệu',
    soluong INT(11) DEFAULT 1 COMMENT 'Số lượng',
    tinhtrang VARCHAR(255) DEFAULT NULL COMMENT 'Tình trạng thiết bị',
    ghichu TEXT DEFAULT NULL COMMENT 'Ghi chú chi tiết thiết bị',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_phieu_id (phieu_id),
    INDEX idx_thietbi_id (thietbi_id),
    
    CONSTRAINT fk_chitiet_phieu 
        FOREIGN KEY (phieu_id) 
        REFERENCES giao_nhan_thietbi_iso(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Chi tiết thiết bị trong phiếu giao nhận';

SELECT 'Chi tiết table created (or already exists)!' as status;

-- ============================================================================
-- BƯỚC 3: KIỂM TRA CẤU TRÚC MASTER TABLE
-- ============================================================================
-- Kiểm tra xem master table có cột device không
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso'
  AND COLUMN_NAME IN ('thietbi_id', 'ten_thietbi', 'ky_ma_hieu');

-- ============================================================================
-- BƯỚC 4: MIGRATE DỮ LIỆU TỪ MASTER → CHITIET
-- ============================================================================
-- CHỈ CHẠY NẾU master table CÓ cột device!
-- Kiểm tra xem đã migrate chưa (chitiet table có data chưa)

SET @chitiet_count = (SELECT COUNT(*) FROM giao_nhan_thietbi_chitiet);

-- Nếu chitiet table rỗng VÀ master table có cột device → Migrate
INSERT INTO giao_nhan_thietbi_chitiet 
    (phieu_id, thietbi_id, ten_thietbi, ky_ma_hieu, soluong, created_at)
SELECT 
    id as phieu_id,
    thietbi_id,
    ten_thietbi,
    ky_ma_hieu,
    1 as soluong, -- Mỗi phiếu cũ = 1 thiết bị
    created_at
FROM giao_nhan_thietbi_iso
WHERE @chitiet_count = 0 -- Chỉ migrate nếu chitiet table rỗng
  AND (thietbi_id IS NOT NULL OR ten_thietbi IS NOT NULL OR ky_ma_hieu IS NOT NULL);

SELECT CONCAT('Migrated ', ROW_COUNT(), ' device records to chitiet table') as status;

-- ============================================================================
-- BƯỚC 5: CẬP NHẬT TỔNG SỐ THIẾT BỊ TRONG MASTER
-- ============================================================================
-- Đảm bảo cột tong_thietbi phản ánh đúng số thiết bị trong chitiet
UPDATE giao_nhan_thietbi_iso gn
SET tong_thietbi = (
    SELECT COUNT(*) 
    FROM giao_nhan_thietbi_chitiet ct 
    WHERE ct.phieu_id = gn.id
)
WHERE id IN (SELECT DISTINCT phieu_id FROM giao_nhan_thietbi_chitiet);

SELECT 'Updated tong_thietbi count in master table' as status;

-- ============================================================================
-- BƯỚC 6: XÓA CỘT DEVICE KHỎI MASTER TABLE
-- ============================================================================
-- CHỈ CHẠY SAU KHI ĐÃ MIGRATE XONG VÀ VERIFY DATA!
-- Dùng prepared statements cho tương thích MySQL cũ

-- Drop thietbi_id
SET @col_exists = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'diavatly_db' 
      AND TABLE_NAME = 'giao_nhan_thietbi_iso' 
      AND COLUMN_NAME = 'thietbi_id');

SET @sql_drop = IF(@col_exists > 0, 
    'ALTER TABLE giao_nhan_thietbi_iso DROP COLUMN thietbi_id', 
    'SELECT "Column thietbi_id does not exist"');
PREPARE stmt FROM @sql_drop;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop ten_thietbi
SET @col_exists = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'diavatly_db' 
      AND TABLE_NAME = 'giao_nhan_thietbi_iso' 
      AND COLUMN_NAME = 'ten_thietbi');

SET @sql_drop = IF(@col_exists > 0, 
    'ALTER TABLE giao_nhan_thietbi_iso DROP COLUMN ten_thietbi', 
    'SELECT "Column ten_thietbi does not exist"');
PREPARE stmt FROM @sql_drop;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop ky_ma_hieu
SET @col_exists = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'diavatly_db' 
      AND TABLE_NAME = 'giao_nhan_thietbi_iso' 
      AND COLUMN_NAME = 'ky_ma_hieu');

SET @sql_drop = IF(@col_exists > 0, 
    'ALTER TABLE giao_nhan_thietbi_iso DROP COLUMN ky_ma_hieu', 
    'SELECT "Column ky_ma_hieu does not exist"');
PREPARE stmt FROM @sql_drop;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Dropped device columns from master table' as status;

-- ============================================================================
-- BƯỚC 7: THÊM 3 CỘT MỚI CHO WORKFLOW KIỂM ĐỊNH
-- ============================================================================
-- Dùng prepared statements cho tương thích MySQL cũ

-- Add nguoi_gui_kiemdinh
SET @col_exists = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'diavatly_db' 
      AND TABLE_NAME = 'giao_nhan_thietbi_iso' 
      AND COLUMN_NAME = 'nguoi_gui_kiemdinh');

SET @sql_add = IF(@col_exists = 0, 
    'ALTER TABLE giao_nhan_thietbi_iso ADD COLUMN nguoi_gui_kiemdinh VARCHAR(255) DEFAULT NULL COMMENT "Người gửi đi kiểm định" AFTER donvi_giao', 
    'SELECT "Column nguoi_gui_kiemdinh already exists"');
PREPARE stmt FROM @sql_add;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add donvi_gui_kiemdinh
SET @col_exists = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'diavatly_db' 
      AND TABLE_NAME = 'giao_nhan_thietbi_iso' 
      AND COLUMN_NAME = 'donvi_gui_kiemdinh');

SET @sql_add = IF(@col_exists = 0, 
    'ALTER TABLE giao_nhan_thietbi_iso ADD COLUMN donvi_gui_kiemdinh VARCHAR(255) DEFAULT NULL COMMENT "Đơn vị gửi kiểm định" AFTER nguoi_gui_kiemdinh', 
    'SELECT "Column donvi_gui_kiemdinh already exists"');
PREPARE stmt FROM @sql_add;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add ngay_gui_kiemdinh
SET @col_exists = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'diavatly_db' 
      AND TABLE_NAME = 'giao_nhan_thietbi_iso' 
      AND COLUMN_NAME = 'ngay_gui_kiemdinh');

SET @sql_add = IF(@col_exists = 0, 
    'ALTER TABLE giao_nhan_thietbi_iso ADD COLUMN ngay_gui_kiemdinh DATE DEFAULT NULL COMMENT "Ngày gửi kiểm định" AFTER donvi_gui_kiemdinh', 
    'SELECT "Column ngay_gui_kiemdinh already exists"');
PREPARE stmt FROM @sql_add;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Added new workflow columns!' as status;

-- ============================================================================
-- BƯỚC 8: VERIFY FINAL STRUCTURE
-- ============================================================================

-- Master table structure
SELECT '=== MASTER TABLE (giao_nhan_thietbi_iso) ===' as info;
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE, 
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso'
ORDER BY ORDINAL_POSITION;

-- Chi tiết table structure
SELECT '=== CHI TIẾT TABLE (giao_nhan_thietbi_chitiet) ===' as info;
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE, 
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_chitiet'
ORDER BY ORDINAL_POSITION;

-- Data count
SELECT 
    (SELECT COUNT(*) FROM giao_nhan_thietbi_iso) as master_records,
    (SELECT COUNT(*) FROM giao_nhan_thietbi_chitiet) as chitiet_records;

-- Sample JOIN query (like controller code)
SELECT 
    gn.id,
    gn.trangthai,
    gn.nguoi_giao,
    gn.nguoi_nhan,
    COUNT(ct.id) as so_thietbi,
    GROUP_CONCAT(ct.ten_thietbi SEPARATOR ', ') as danh_sach_thietbi
FROM giao_nhan_thietbi_iso gn
LEFT JOIN giao_nhan_thietbi_chitiet ct ON gn.id = ct.phieu_id
GROUP BY gn.id
ORDER BY gn.created_at DESC
LIMIT 5;

-- ============================================================================
-- KIỂM TRA CÁC YÊU CẦU
-- ============================================================================
SELECT '=== FINAL CHECKLIST ===' as info;

-- 1. Master table KHÔNG còn cột device
SELECT 
    IF(COUNT(*) = 0, 
       '✅ PASS: Master table no device columns', 
       '❌ FAIL: Master table still has device columns') as check_1
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso'
  AND COLUMN_NAME IN ('thietbi_id', 'ten_thietbi', 'ky_ma_hieu');

-- 2. Master table CÓ 3 cột mới
SELECT 
    IF(COUNT(*) = 3, 
       '✅ PASS: Master table has 3 new workflow columns', 
       '❌ FAIL: Master table missing workflow columns') as check_2
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso'
  AND COLUMN_NAME IN ('nguoi_gui_kiemdinh', 'donvi_gui_kiemdinh', 'ngay_gui_kiemdinh');

-- 3. Chi tiết table tồn tại
SELECT 
    IF(COUNT(*) > 0, 
       '✅ PASS: Chi tiết table exists', 
       '❌ FAIL: Chi tiết table not found') as check_3
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_chitiet';

-- 4. ENUM đúng
SELECT 
    IF(COLUMN_TYPE = "enum('da_nhan','dang_kiem_dinh','da_giao')", 
       '✅ PASS: ENUM trangthai correct', 
       '❌ FAIL: ENUM trangthai incorrect') as check_4
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso'
  AND COLUMN_NAME = 'trangthai';

-- 5. Foreign key constraint exists
SELECT 
    IF(COUNT(*) > 0, 
       '✅ PASS: Foreign key constraint exists', 
       '⚠️ WARNING: No foreign key constraint (optional)') as check_5
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'diavatly_db'
  AND TABLE_NAME = 'giao_nhan_thietbi_chitiet'
  AND REFERENCED_TABLE_NAME = 'giao_nhan_thietbi_iso';

-- ============================================================================
-- ROLLBACK (chỉ chạy nếu có lỗi nghiêm trọng)
-- ============================================================================
/*
-- Restore from backup
DROP TABLE IF EXISTS giao_nhan_thietbi_iso;
CREATE TABLE giao_nhan_thietbi_iso AS 
SELECT * FROM giao_nhan_thietbi_iso_backup_complete;

DROP TABLE IF EXISTS giao_nhan_thietbi_chitiet;

-- Hoặc chỉ khôi phục structure:
ALTER TABLE giao_nhan_thietbi_iso
ADD COLUMN thietbi_id INT(11) DEFAULT NULL,
ADD COLUMN ten_thietbi VARCHAR(255) DEFAULT NULL,
ADD COLUMN ky_ma_hieu VARCHAR(100) DEFAULT NULL;

UPDATE giao_nhan_thietbi_iso master
INNER JOIN giao_nhan_thietbi_iso_backup_complete backup ON master.id = backup.id
SET 
    master.thietbi_id = backup.thietbi_id,
    master.ten_thietbi = backup.ten_thietbi,
    master.ky_ma_hieu = backup.ky_ma_hieu;
*/

-- ============================================================================
SELECT '✅ MIGRATION COMPLETE!' as final_status;
-- ============================================================================
