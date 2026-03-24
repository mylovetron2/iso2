-- ============================================================================
-- FIX NULLABLE COLUMNS - Cho phép NULL trong workflow 3 bước
-- ============================================================================
-- Date: 2026-03-20
-- 
-- WORKFLOW MỚI:
-- Bước 1 (da_nhan): Chỉ có nguoi_giao, donvi_giao, ngay_giao
-- Bước 2 (dang_kiem_dinh): Thêm nguoi_gui_kiemdinh, donvi_gui_kiemdinh, ngay_gui_kiemdinh
-- Bước 3 (da_giao): Thêm nguoi_nhan, donvi_nhan, ngay_nhan
--
-- → Các cột của bước 2 và 3 phải cho phép NULL!
-- ============================================================================

USE diavatly_db;

-- Cho phép NULL cho các cột được điền sau
ALTER TABLE giao_nhan_thietbi_iso
MODIFY COLUMN nguoi_nhan VARCHAR(100) DEFAULT NULL COMMENT 'Người nhận lại thiết bị (đội nhận từ mình) - Điền ở bước 3',
MODIFY COLUMN donvi_nhan VARCHAR(50) DEFAULT NULL COMMENT 'Đơn vị nhận lại thiết bị (đội nhận từ mình) - Điền ở bước 3',
MODIFY COLUMN ngay_nhan DATE DEFAULT NULL COMMENT 'Ngày giao lại cho đội - Điền ở bước 3';

-- Verify
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'diavatly_db' 
  AND TABLE_NAME = 'giao_nhan_thietbi_iso'
  AND COLUMN_NAME IN ('nguoi_giao', 'nguoi_nhan', 'nguoi_gui_kiemdinh', 
                       'donvi_giao', 'donvi_nhan', 'donvi_gui_kiemdinh',
                       'ngay_giao', 'ngay_nhan', 'ngay_gui_kiemdinh')
ORDER BY COLUMN_NAME;

SELECT '✅ Columns updated to allow NULL for 3-step workflow!' as status;
