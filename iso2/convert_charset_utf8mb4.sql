-- ============================================================================
-- CONVERT CHARSET TO UTF8MB4 - Support tiếng Việt
-- ============================================================================
-- Date: 2026-03-20
-- 
-- Vấn đề: Tables dùng latin1_swedish_ci → không lưu được tiếng Việt
-- Giải pháp: Convert sang utf8mb4_unicode_ci
-- ============================================================================

USE diavatly_db;

-- ============================================================================
-- BƯỚC 1: CONVERT MASTER TABLE
-- ============================================================================

-- Convert table collation
ALTER TABLE giao_nhan_thietbi_iso 
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SELECT 'Master table converted to utf8mb4!' as status;

-- ============================================================================
-- BƯỚC 2: CONVERT CHITIET TABLE
-- ============================================================================

-- Convert table collation
ALTER TABLE giao_nhan_thietbi_chitiet 
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SELECT 'Chitiet table converted to utf8mb4!' as status;

-- ============================================================================
-- BƯỚC 3: VERIFY CONVERSION
-- ============================================================================

-- Check master table
SELECT 
    'giao_nhan_thietbi_iso' as table_name,
    TABLE_COLLATION,
    IF(TABLE_COLLATION LIKE '%utf8mb4%', '✅ OK', '❌ FAILED') as status
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso';

-- Check chitiet table
SELECT 
    'giao_nhan_thietbi_chitiet' as table_name,
    TABLE_COLLATION,
    IF(TABLE_COLLATION LIKE '%utf8mb4%', '✅ OK', '❌ FAILED') as status
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_chitiet';

-- Check text columns in master
SELECT 
    COLUMN_NAME,
    CHARACTER_SET_NAME,
    COLLATION_NAME,
    IF(CHARACTER_SET_NAME = 'utf8mb4', '✅ OK', '❌ FAILED') as status
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso'
  AND DATA_TYPE IN ('varchar', 'text', 'char')
ORDER BY ORDINAL_POSITION;

-- ============================================================================
-- BƯỚC 4: TEST INSERT TIẾNG VIỆT
-- ============================================================================

-- Test insert với ký tự tiếng Việt
INSERT INTO giao_nhan_thietbi_iso (
    nguoi_giao, donvi_giao, ngay_giao,
    ghichu, trangthai, tong_thietbi,
    created_at, updated_at
) VALUES (
    'Nguyễn Văn A',
    'DVLTH', 
    CURDATE(),
    'Test phiếu với tiếng Việt: áàảãạ éèẻẽẹ íìỉĩị óòỏõọ úùủũụ ýỳỷỹỵ',
    'da_nhan',
    0,
    NOW(),
    NOW()
);

-- Get inserted record
SELECT 
    id,
    nguoi_giao,
    ghichu,
    'Should display Vietnamese correctly' as note
FROM giao_nhan_thietbi_iso 
ORDER BY id DESC 
LIMIT 1;

-- Delete test record
DELETE FROM giao_nhan_thietbi_iso 
WHERE nguoi_giao = 'Nguyễn Văn A' 
  AND ghichu LIKE 'Test phiếu với tiếng Việt%';

SELECT 'Test record deleted' as status;

-- ============================================================================
SELECT '✅ CHARSET CONVERSION COMPLETE!' as final_status;
-- ============================================================================
