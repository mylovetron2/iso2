-- ============================================================================
-- REFACTOR: Giao Nhận Thiết Bị - Single Receipt Workflow
-- ============================================================================
-- Date: 2026-03-19
-- 
-- LOGIC MỚI:
-- 1. Đội gửi cho mình 3 thiết bị → Tạo phiếu (trạng thái: da_nhan)
-- 2. Mình gửi đi kiểm định → Cập nhật (trạng thái: dang_kiem_dinh)
-- 3. Kiểm định xong, trả lại → Cập nhật (trạng thái: da_giao)
--
-- WORKFLOW: da_nhan → dang_kiem_dinh → da_giao
-- ============================================================================

USE diavatly_db;

-- Bước 1: Kiểm tra và migrate dữ liệu cũ (nếu có)
-- ============================================================================
-- Nếu có dữ liệu với trangthai cũ, cần map sang giá trị mới:
-- 'cho_nhan' → 'da_nhan'
-- 'da_nhan' → 'da_nhan' (giữ nguyên)
-- 'hoan_thanh' → 'da_giao'

UPDATE giao_nhan_thietbi_iso 
SET trangthai = 'da_nhan' 
WHERE trangthai = 'cho_nhan';

UPDATE giao_nhan_thietbi_iso 
SET trangthai = 'da_giao' 
WHERE trangthai = 'hoan_thanh';

-- Bước 2: Xóa loại phiếu cũ
-- ============================================================================
-- Check if column exists before dropping
SET @col_exists = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'diavatly_db' 
      AND TABLE_NAME = 'giao_nhan_thietbi_iso' 
      AND COLUMN_NAME = 'loai_giao_nhan');

SET @sql_drop_loai = IF(@col_exists > 0, 
    'ALTER TABLE giao_nhan_thietbi_iso DROP COLUMN loai_giao_nhan', 
    'SELECT "Column loai_giao_nhan does not exist"');
PREPARE stmt FROM @sql_drop_loai;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'diavatly_db' 
      AND TABLE_NAME = 'giao_nhan_thietbi_iso' 
      AND COLUMN_NAME = 'phieu_giao_id');

SET @sql_drop_phieu = IF(@col_exists > 0, 
    'ALTER TABLE giao_nhan_thietbi_iso DROP COLUMN phieu_giao_id', 
    'SELECT "Column phieu_giao_id does not exist"');
PREPARE stmt FROM @sql_drop_phieu;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Bước 3: Đổi ENUM trangthai
-- ============================================================================
ALTER TABLE giao_nhan_thietbi_iso
MODIFY COLUMN trangthai ENUM('da_nhan', 'dang_kiem_dinh', 'da_giao') NOT NULL DEFAULT 'da_nhan'
COMMENT 'da_nhan: Đã nhận từ đội | dang_kiem_dinh: Đang gửi kiểm định | da_giao: Đã giao lại cho đội';

-- Bước 4: Thêm cột cho thông tin gửi kiểm định
-- ============================================================================
-- Check if columns already exist before adding
SET @col_exists = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'diavatly_db' 
      AND TABLE_NAME = 'giao_nhan_thietbi_iso' 
      AND COLUMN_NAME = 'nguoi_gui_kiemdinh');

SET @sql_add_cols = IF(@col_exists = 0, 
    'ALTER TABLE giao_nhan_thietbi_iso
     ADD COLUMN nguoi_gui_kiemdinh VARCHAR(255) DEFAULT NULL COMMENT "Người gửi đi kiểm định" AFTER donvi_giao,
     ADD COLUMN donvi_gui_kiemdinh VARCHAR(255) DEFAULT NULL COMMENT "Đơn vị gửi kiểm định" AFTER nguoi_gui_kiemdinh,
     ADD COLUMN ngay_gui_kiemdinh DATE DEFAULT NULL COMMENT "Ngày gửi kiểm định" AFTER donvi_gui_kiemdinh', 
    'SELECT "Columns nguoi_gui_kiemdinh, donvi_gui_kiemdinh, ngay_gui_kiemdinh already exist"');
PREPARE stmt FROM @sql_add_cols;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Bước 5: Đổi tên cột cho rõ ràng (update comments only)
-- ============================================================================
ALTER TABLE giao_nhan_thietbi_iso
MODIFY COLUMN nguoi_giao VARCHAR(255) DEFAULT NULL 
    COMMENT 'Người giao thiết bị (từ đội gửi cho mình)',
MODIFY COLUMN donvi_giao VARCHAR(255) DEFAULT NULL 
    COMMENT 'Đơn vị giao thiết bị (đội gửi cho mình)',
MODIFY COLUMN ngay_giao DATE DEFAULT NULL 
    COMMENT 'Ngày nhận thiết bị từ đội',
MODIFY COLUMN nguoi_nhan VARCHAR(255) DEFAULT NULL 
    COMMENT 'Người nhận lại thiết bị (đội nhận từ mình)',
MODIFY COLUMN donvi_nhan VARCHAR(255) DEFAULT NULL 
    COMMENT 'Đơn vị nhận lại thiết bị (đội nhận từ mình)',
MODIFY COLUMN ngay_nhan DATE DEFAULT NULL 
    COMMENT 'Ngày giao lại cho đội';

-- Bước 6: Cấu trúc bảng sau khi refactor
-- ============================================================================
-- giao_nhan_thietbi_iso:
--   id (PK)
--   
--   -- Thông tin nhận từ đội (Bước 1: da_nhan)
--   nguoi_giao, donvi_giao, ngay_giao
--   
--   -- Thông tin gửi kiểm định (Bước 2: dang_kiem_dinh)
--   nguoi_gui_kiemdinh, donvi_gui_kiemdinh, ngay_gui_kiemdinh
--   
--   -- Thông tin giao lại cho đội (Bước 3: da_giao)
--   nguoi_nhan, donvi_nhan, ngay_nhan
--   noidung_kiemdinh (kết quả kiểm định)
--   
--   -- Metadata
--   ghichu, trangthai, tong_thietbi
--   created_by, created_at, updated_at
--
-- giao_nhan_thietbi_chitiet: (giữ nguyên)
--   id, phieu_id, thietbi_id, ten_thietbi, ky_ma_hieu
--   soluong, tinhtrang, ghichu

-- Bước 7: Xóa dữ liệu test cũ (nếu có)
-- ============================================================================
-- Nếu đã có dữ liệu test với loai_giao_nhan, cần xóa và tạo lại
-- DELETE FROM giao_nhan_thietbi_chitiet;
-- DELETE FROM giao_nhan_thietbi_iso;
-- ALTER TABLE giao_nhan_thietbi_iso AUTO_INCREMENT = 1;
-- ALTER TABLE giao_nhan_thietbi_chitiet AUTO_INCREMENT = 1;

-- Bước 8: Verify schema
-- ============================================================================
SELECT 
    'giao_nhan_thietbi_iso' as table_name,
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso'
ORDER BY ORDINAL_POSITION;

-- Kiểm tra không còn loai_giao_nhan và phieu_giao_id
-- ============================================================================
SELECT 
    COUNT(*) as should_be_zero
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso'
  AND COLUMN_NAME IN ('loai_giao_nhan', 'phieu_giao_id');

-- Expected: 0 (các cột đã bị xóa)

-- ============================================================================
-- END MIGRATION
-- ============================================================================
