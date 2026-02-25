-- ================================================================
-- DROP ALL KPI TABLES - XÓA TẤT CẢ BẢNG KPI
-- ================================================================
-- Mục đích: Xóa toàn bộ hệ thống KPI để tạo lại từ đầu
-- Sử dụng khi: Gặp lỗi FK constraint, muốn làm sạch database
-- Tác giả: GitHub Copilot
-- Ngày: 2026-02-25
-- ================================================================

-- Tắt kiểm tra FK để có thể xóa theo thứ tự bất kỳ
SET FOREIGN_KEY_CHECKS = 0;

-- ================================================================
-- BƯỚC 1: XÓA CÁC VIEW (không có dependency)
-- ================================================================

DROP VIEW IF EXISTS view_congviec_full_info;
DROP VIEW IF EXISTS view_congviec_nhanvien_thongke;
DROP VIEW IF EXISTS view_kpi_thietbi_thongke;
DROP VIEW IF EXISTS view_thongke_theo_capdo;

SELECT '✓ Đã xóa 4 VIEWs' AS status;

-- ================================================================
-- BƯỚC 2: XÓA CÁC TRIGGER
-- ================================================================

DROP TRIGGER IF EXISTS before_insert_congviec_check_8h;
DROP TRIGGER IF EXISTS before_update_congviec_check_8h;

SELECT '✓ Đã xóa 2 TRIGGERs' AS status;

-- ================================================================
-- BƯỚC 3: XÓA CÁC BẢNG CON (có FK đến bảng khác)
-- ================================================================

-- Xóa bảng công việc sửa chữa
DROP TABLE IF EXISTS congviec_suachua_iso;
SELECT '✓ Đã xóa congviec_suachua_iso' AS status;

-- Xóa bảng thiết bị - cấp độ bảo cưỡng
DROP TABLE IF EXISTS thietbi_capdo_kpi_iso;
SELECT '✓ Đã xóa thietbi_capdo_kpi_iso' AS status;

-- ================================================================
-- BƯỚC 4: XÓA CÁC BẢNG CHA (parent tables)
-- ================================================================

-- Xóa bảng cấp độ bảo cưỡng
DROP TABLE IF EXISTS capdo_baocuong_iso;
SELECT '✓ Đã xóa capdo_baocuong_iso' AS status;

-- Xóa bảng hồ sơ sửa chữa bảo dưỡng
DROP TABLE IF EXISTS hososcbd_iso;
SELECT '✓ Đã xóa hososcbd_iso' AS status;

-- Xóa bảng đơn vị (nếu có)
DROP TABLE IF EXISTS donvi_iso;
SELECT '✓ Đã xóa donvi_iso' AS status;

-- Xóa bảng thiết bị (nếu có)
DROP TABLE IF EXISTS thietbi_iso;
SELECT '✓ Đã xóa thietbi_iso' AS status;

-- ================================================================
-- BƯỚC 5: XÓA CÁC BẢNG BACKUP (nếu có)
-- ================================================================

DROP TABLE IF EXISTS congviec_suachua_iso_backup_20260225;
DROP TABLE IF EXISTS congviec_suachua_iso_backup;
DROP TABLE IF EXISTS thietbi_capdo_kpi_backup;

SELECT '✓ Đã xóa các bảng backup' AS status;

-- Bật lại kiểm tra FK
SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================
-- BƯỚC 6: KIỂM TRA KẾT QUẢ
-- ================================================================

SELECT 
    '✅ ĐÃ XÓA TẤT CẢ BẢNG THÀNH CÔNG!' AS final_status,
    'Bây giờ có thể chạy scripts tạo bảng từ đầu' AS next_step;

-- Hiển thị danh sách bảng còn lại (không nên thấy các bảng KPI)
SELECT '📋 Danh sách bảng còn lại trong database:' AS info;
SHOW TABLES LIKE '%_iso%';

-- ================================================================
-- HƯỚNG DẪN SAU KHI XÓA
-- ================================================================

/*
✅ BƯỚC TIẾP THEO - TẠO LẠI TỪ ĐẦU:

1️⃣ Tạo bảng cơ sở (hososcbd_iso, donvi_iso, thietbi_iso):
   mysql -u user -p db < migrations/20251121_create_hososcbd_tables.sql
   
   HOẶC trong phpMyAdmin: 
   Import file: migrations/20251121_create_hososcbd_tables.sql

2️⃣ Tạo hệ thống KPI (capdo_baocuong_iso, congviec_suachua_iso, thietbi_capdo_kpi_iso):
   mysql -u user -p db < migrations/20260224_create_kpi_suachua_system_FIXED.sql
   
   HOẶC trong phpMyAdmin:
   Import file: migrations/20260224_create_kpi_suachua_system_FIXED.sql

3️⃣ (Optional) Chuẩn hóa FK nếu cần:
   mysql -u user -p db < migrations/ALTER_congviec_hososcbd_FK.sql

🎯 SAU KHI HOÀN TẤT, KIỂM TRA:

SELECT COUNT(*) FROM hososcbd_iso;           -- Phải thấy 0 hoặc số lượng records
SELECT COUNT(*) FROM capdo_baocuong_iso;     -- Phải thấy 3 (CAP1, CAP2, CAP3)
SELECT COUNT(*) FROM congviec_suachua_iso;   -- Phải thấy 0 (bảng mới)
SELECT COUNT(*) FROM thietbi_capdo_kpi_iso;  -- Phải thấy 0 (bảng mới)

SHOW CREATE TABLE congviec_suachua_iso;      -- Kiểm tra FK constraints
*/
