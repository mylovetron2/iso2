-- ============================================================================
-- REFACTOR: Giao Nhận Thiết Bị - SIMPLE VERSION (Manual Execution)
-- ============================================================================
-- Date: 2026-03-20
-- 
-- HƯỚNG DẪN:
-- 1. Chạy từng khối SQL riêng biệt (copy-paste từng phần)
-- 2. Nếu gặp lỗi "column doesn't exist", bỏ qua và chạy tiếp
-- ============================================================================

USE diavatly_db;

-- ============================================================================
-- BƯỚC 1: BACKUP DỮ LIỆU (QUAN TRỌNG!)
-- ============================================================================
CREATE TABLE IF NOT EXISTS giao_nhan_thietbi_iso_backup_20260320 
AS SELECT * FROM giao_nhan_thietbi_iso;

CREATE TABLE IF NOT EXISTS giao_nhan_thietbi_chitiet_backup_20260320 
AS SELECT * FROM giao_nhan_thietbi_chitiet;

-- ============================================================================
-- BƯỚC 2: KIỂM TRA DỮ LIỆU HIỆN TẠI
-- ============================================================================
-- Xem các giá trị trangthai hiện tại
SELECT trangthai, COUNT(*) as count 
FROM giao_nhan_thietbi_iso 
GROUP BY trangthai;

-- Xem structure hiện tại
DESCRIBE giao_nhan_thietbi_iso;

-- ============================================================================
-- BƯỚC 3: UPDATE DỮ LIỆU CŨ (nếu có)
-- ============================================================================
-- Map giá trị cũ sang giá trị mới:
-- 'cho_nhan' → 'da_nhan'
-- 'hoan_thanh' → 'da_giao'

UPDATE giao_nhan_thietbi_iso 
SET trangthai = 'da_nhan' 
WHERE trangthai = 'cho_nhan';

UPDATE giao_nhan_thietbi_iso 
SET trangthai = 'da_giao' 
WHERE trangthai = 'hoan_thanh';

-- Verify sau khi update
SELECT trangthai, COUNT(*) as count 
FROM giao_nhan_thietbi_iso 
GROUP BY trangthai;
-- Expected: Chỉ còn 'da_nhan', 'da_giao', hoặc giá trị khác trong ENUM mới

-- ============================================================================
-- BƯỚC 4: XÓA CỘT CŨ
-- ============================================================================
-- Nếu lỗi "Unknown column", bỏ qua và chạy tiếp

-- Xóa cột loai_giao_nhan
ALTER TABLE giao_nhan_thietbi_iso
DROP COLUMN loai_giao_nhan;

-- Xóa cột phieu_giao_id
ALTER TABLE giao_nhan_thietbi_iso
DROP COLUMN phieu_giao_id;

-- ============================================================================
-- BƯỚC 5: ĐỔI ENUM TRANGTHAI
-- ============================================================================
ALTER TABLE giao_nhan_thietbi_iso
MODIFY COLUMN trangthai ENUM('da_nhan', 'dang_kiem_dinh', 'da_giao') 
NOT NULL DEFAULT 'da_nhan'
COMMENT 'da_nhan: Đã nhận từ đội | dang_kiem_dinh: Đang gửi kiểm định | da_giao: Đã giao lại cho đội';

-- ============================================================================
-- BƯỚC 6: THÊM CỘT MỚI
-- ============================================================================
-- Nếu lỗi "Duplicate column", bỏ qua và chạy tiếp

ALTER TABLE giao_nhan_thietbi_iso
ADD COLUMN nguoi_gui_kiemdinh VARCHAR(255) DEFAULT NULL 
    COMMENT 'Người gửi đi kiểm định' AFTER donvi_giao;

ALTER TABLE giao_nhan_thietbi_iso
ADD COLUMN donvi_gui_kiemdinh VARCHAR(255) DEFAULT NULL 
    COMMENT 'Đơn vị gửi kiểm định' AFTER nguoi_gui_kiemdinh;

ALTER TABLE giao_nhan_thietbi_iso
ADD COLUMN ngay_gui_kiemdinh DATE DEFAULT NULL 
    COMMENT 'Ngày gửi kiểm định' AFTER donvi_gui_kiemdinh;

-- ============================================================================
-- BƯỚC 7: UPDATE COMMENTS (optional)
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

-- ============================================================================
-- BƯỚC 8: VERIFY STRUCTURE
-- ============================================================================
-- Kiểm tra structure sau khi migrate
DESCRIBE giao_nhan_thietbi_iso;

-- Kiểm tra các cột mới đã được tạo
SELECT 
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
SELECT COUNT(*) as should_be_zero
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso'
  AND COLUMN_NAME IN ('loai_giao_nhan', 'phieu_giao_id');
-- Expected: 0

-- Kiểm tra cột mới đã tồn tại
SELECT COUNT(*) as should_be_3
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso'
  AND COLUMN_NAME IN ('nguoi_gui_kiemdinh', 'donvi_gui_kiemdinh', 'ngay_gui_kiemdinh');
-- Expected: 3

-- ============================================================================
-- BƯỚC 9: TEST DỮ LIỆU
-- ============================================================================
-- Kiểm tra dữ liệu sau khi migrate
SELECT 
    id,
    trangthai,
    nguoi_giao,
    donvi_giao,
    ngay_giao,
    nguoi_gui_kiemdinh,
    donvi_gui_kiemdinh,
    ngay_gui_kiemdinh,
    nguoi_nhan,
    donvi_nhan,
    ngay_nhan
FROM giao_nhan_thietbi_iso
ORDER BY id DESC
LIMIT 10;

-- Kiểm tra số lượng thiết bị
SELECT 
    gn.id,
    gn.trangthai,
    COUNT(ct.id) as so_thietbi
FROM giao_nhan_thietbi_iso gn
LEFT JOIN giao_nhan_thietbi_chitiet ct ON gn.id = ct.phieu_id
GROUP BY gn.id
ORDER BY gn.id DESC
LIMIT 10;

-- ============================================================================
-- ROLLBACK (nếu có lỗi)
-- ============================================================================
-- Chỉ chạy nếu cần rollback

/*
DROP TABLE IF EXISTS giao_nhan_thietbi_iso;
DROP TABLE IF EXISTS giao_nhan_thietbi_chitiet;

CREATE TABLE giao_nhan_thietbi_iso AS 
SELECT * FROM giao_nhan_thietbi_iso_backup_20260320;

CREATE TABLE giao_nhan_thietbi_chitiet AS 
SELECT * FROM giao_nhan_thietbi_chitiet_backup_20260320;

-- Restore AUTO_INCREMENT
ALTER TABLE giao_nhan_thietbi_iso MODIFY id INT AUTO_INCREMENT PRIMARY KEY;
ALTER TABLE giao_nhan_thietbi_chitiet MODIFY id INT AUTO_INCREMENT PRIMARY KEY;
*/

-- ============================================================================
-- END MIGRATION
-- ============================================================================
