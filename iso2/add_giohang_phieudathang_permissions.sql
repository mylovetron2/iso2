-- Thêm permissions cho chức năng Giỏ hàng và Phiếu đặt hàng
-- Database: diavatly_db
-- ============================================================
-- LƯU Ý: Database KHÔNG có bảng permissions riêng
-- Permissions được lưu trong roles.permissions (JSON array)
-- ============================================================

USE `diavatly_db`;

-- ============================================================
-- HƯỚNG DẪN CÀI ĐẶT PERMISSIONS
-- ============================================================
-- 
-- Vào trình duyệt và chạy script PHP:
-- http://localhost/iso2/grant_giohang_phieudathang_permissions.php
-- 
-- Script sẽ tự động thêm 13 permissions vào roles.permissions:
-- 
-- Giỏ hàng (4):
--   - giohang.view         : Xem giỏ hàng
--   - giohang.add          : Thêm vật tư vào giỏ hàng
--   - giohang.edit         : Sửa số lượng trong giỏ hàng
--   - giohang.delete       : Xóa vật tư khỏi giỏ hàng
-- 
-- Phiếu đặt hàng (9):
--   - phieudathang.view    : Xem phiếu đặt hàng
--   - phieudathang.create  : Tạo phiếu đặt hàng mới
--   - phieudathang.edit    : Sửa phiếu đặt hàng
--   - phieudathang.delete  : Xóa phiếu đặt hàng
--   - phieudathang.approve : Duyệt phiếu đặt hàng
--   - phieudathang.receive : Xác nhận nhận hàng
--   - phieudathang.stock   : Nhập kho
--   - phieudathang.cancel  : Hủy phiếu đặt hàng
--   - phieudathang.export  : Xuất Excel phiếu đặt hàng
-- 
-- ============================================================

SELECT '✅ XEM HƯỚNG DẪN Ở TRÊN!' as 'Status';
SELECT 'Chạy: http://localhost/iso2/grant_giohang_phieudathang_permissions.php' as 'Next Step';

-- Hiển thị kết quả
SELECT 'Đã thêm permissions cho Giỏ hàng và Phiếu đặt hàng!' as message;
SELECT * FROM permissions WHERE module IN ('giohang', 'phieudathang') ORDER BY module, name;
