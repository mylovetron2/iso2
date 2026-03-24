-- ============================================================
-- SETUP CHỨC NĂNG GIỎ HÀNG & PHIẾU ĐẶT HÀNG - ALL IN ONE
-- Chạy file này để tạo tất cả bảng và permissions
-- ============================================================

USE `diavatly_db`;

-- 1. Tạo bảng Giỏ hàng
DROP TABLE IF EXISTS `cart_vattu_thanh_ly`;
CREATE TABLE IF NOT EXISTS `cart_vattu_thanh_ly` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `vattu_stt` int(11) NOT NULL COMMENT 'STT vật tư',
  `so_luong` int(11) DEFAULT 1 COMMENT 'Số lượng muốn đặt',
  `ghi_chu` text DEFAULT NULL COMMENT 'Ghi chú của user',
  `ngay_them` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Ngày thêm vào giỏ',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_vattu` (`user_id`, `vattu_stt`),
  KEY `idx_user` (`user_id`),
  KEY `idx_vattu` (`vattu_stt`),
  KEY `idx_ngay_them` (`ngay_them`),
  KEY `idx_user_ngay` (`user_id`, `ngay_them`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Giỏ hàng vật tư thanh lý';

-- 2. Tạo bảng Phiếu đặt hàng
DROP TABLE IF EXISTS `phieu_dat_hang`;
CREATE TABLE IF NOT EXISTS `phieu_dat_hang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_phieu` varchar(50) NOT NULL COMMENT 'Mã phiếu: PDH-YYYYMMDD-XXX',
  `nguoi_lap` int(11) NOT NULL COMMENT 'User ID người lập phiếu',
  `ngay_lap` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Ngày lập phiếu',
  `trang_thai` enum('draft','ordered','partial_received','received','stocked','cancelled') 
    NOT NULL DEFAULT 'draft' 
    COMMENT 'Trạng thái: draft=nháp, ordered=đã đặt, partial_received=nhận một phần, received=đã nhận đủ, stocked=đã nhập kho, cancelled=đã hủy',
  `ghi_chu` text DEFAULT NULL COMMENT 'Ghi chú chung của phiếu',
  `nguoi_duyet` int(11) DEFAULT NULL COMMENT 'User ID người duyệt',
  `ngay_duyet` datetime DEFAULT NULL COMMENT 'Ngày duyệt phiếu',
  `nguoi_nhan_hang` int(11) DEFAULT NULL COMMENT 'User ID người nhận hàng',
  `ngay_nhan_hang` datetime DEFAULT NULL COMMENT 'Ngày nhận hàng',
  `nguoi_nhap_kho` int(11) DEFAULT NULL COMMENT 'User ID người nhập kho',
  `ngay_nhap_kho` datetime DEFAULT NULL COMMENT 'Ngày nhập kho',
  `nha_cung_cap` varchar(255) DEFAULT NULL COMMENT 'Tên nhà cung cấp',
  `so_hd_ncc` varchar(100) DEFAULT NULL COMMENT 'Số hợp đồng NCC',
  `ngay_du_kien_nhan` date DEFAULT NULL COMMENT 'Ngày dự kiến nhận hàng',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ma_phieu` (`ma_phieu`),
  KEY `idx_nguoi_lap` (`nguoi_lap`),
  KEY `idx_trang_thai` (`trang_thai`),
  KEY `idx_ngay_lap` (`ngay_lap`),
  KEY `idx_ma_phieu` (`ma_phieu`),
  KEY `idx_trang_thai_ngay` (`trang_thai`, `ngay_lap`),
  KEY `idx_nguoi_lap_trang_thai` (`nguoi_lap`, `trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Phiếu đặt hàng vật tư';

-- 3. Tạo bảng Chi tiết phiếu
DROP TABLE IF EXISTS `phieu_dat_hang_chi_tiet`;
CREATE TABLE IF NOT EXISTS `phieu_dat_hang_chi_tiet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phieu_id` int(11) NOT NULL COMMENT 'ID phiếu đặt hàng',
  `vattu_stt` int(11) NOT NULL COMMENT 'STT vật tư (liên kết vattu_thanh_ly_iso)',
  `ten_tieng_anh` text DEFAULT NULL COMMENT 'Tên tiếng Anh (snapshot)',
  `ten_tieng_nga` text DEFAULT NULL COMMENT 'Tên tiếng Nga (snapshot)',
  `ten_tieng_viet` text DEFAULT NULL COMMENT 'Tên tiếng Việt (snapshot)',
  `dac_tinh_ky_thuat` text DEFAULT NULL COMMENT 'Đặc tính kỹ thuật',
  `don_vi` varchar(50) DEFAULT NULL COMMENT 'Đơn vị tính',
  `so_luong_dat` int(11) NOT NULL COMMENT 'Số lượng đặt hàng',
  `so_luong_nhan` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lượng đã nhận',
  `don_gia` decimal(15,2) DEFAULT NULL COMMENT 'Đơn giá dự kiến',
  `thanh_tien` decimal(15,2) DEFAULT NULL COMMENT 'Thành tiền = số lượng x đơn giá',
  `ghi_chu` text DEFAULT NULL COMMENT 'Ghi chú cho item này',
  `trang_thai` enum('pending','partial','completed','cancelled') 
    NOT NULL DEFAULT 'pending' 
    COMMENT 'Trạng thái: pending=chờ, partial=nhận một phần, completed=hoàn thành, cancelled=hủy',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_phieu` (`phieu_id`),
  KEY `idx_vattu` (`vattu_stt`),
  KEY `idx_trang_thai` (`trang_thai`),
  KEY `idx_phieu_vattu` (`phieu_id`, `vattu_stt`),
  KEY `idx_phieu_trang_thai` (`phieu_id`, `trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chi tiết phiếu đặt hàng';

-- 4. Tạo bảng Lịch sử nhập kho
DROP TABLE IF EXISTS `lich_su_nhap_kho`;
CREATE TABLE IF NOT EXISTS `lich_su_nhap_kho` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phieu_dat_hang_id` int(11) NOT NULL COMMENT 'ID phiếu đặt hàng',
  `phieu_chi_tiet_id` int(11) NOT NULL COMMENT 'ID chi tiết phiếu',
  `vattu_stt` int(11) NOT NULL COMMENT 'STT vật tư',
  `so_luong` int(11) NOT NULL COMMENT 'Số lượng nhập lần này',
  `so_luong_truoc` decimal(10,2) DEFAULT 0 COMMENT 'Số lượng tồn trước khi nhập',
  `so_luong_sau` decimal(10,2) DEFAULT 0 COMMENT 'Số lượng tồn sau khi nhập',
  `nguoi_nhap` int(11) NOT NULL COMMENT 'User ID người thực hiện nhập kho',
  `ngay_nhap` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Ngày giờ nhập kho',
  `vi_tri_kho` varchar(255) DEFAULT NULL COMMENT 'Vị trí lưu trữ trong kho',
  `tinh_trang` varchar(100) DEFAULT 'tot' COMMENT 'Tình trạng hàng nhận được',
  `ghi_chu` text DEFAULT NULL COMMENT 'Ghi chú về lần nhập này',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_phieu` (`phieu_dat_hang_id`),
  KEY `idx_chi_tiet` (`phieu_chi_tiet_id`),
  KEY `idx_vattu` (`vattu_stt`),
  KEY `idx_nguoi_nhap` (`nguoi_nhap`),
  KEY `idx_ngay_nhap` (`ngay_nhap`),
  KEY `idx_ngay_nhap_vattu` (`ngay_nhap`, `vattu_stt`),
  KEY `idx_nguoi_nhap_ngay` (`nguoi_nhap`, `ngay_nhap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử nhập kho vật tư';

-- 5. Thêm Triggers cho tính thành tiền tự động
DELIMITER $$

DROP TRIGGER IF EXISTS before_insert_chi_tiet_thanhtien$$
CREATE TRIGGER before_insert_chi_tiet_thanhtien 
BEFORE INSERT ON phieu_dat_hang_chi_tiet
FOR EACH ROW
BEGIN
    IF NEW.don_gia IS NOT NULL AND NEW.so_luong_dat IS NOT NULL THEN
        SET NEW.thanh_tien = NEW.so_luong_dat * NEW.don_gia;
    END IF;
END$$

DROP TRIGGER IF EXISTS before_update_chi_tiet_thanhtien$$
CREATE TRIGGER before_update_chi_tiet_thanhtien 
BEFORE UPDATE ON phieu_dat_hang_chi_tiet
FOR EACH ROW
BEGIN
    IF NEW.don_gia IS NOT NULL AND NEW.so_luong_dat IS NOT NULL THEN
        SET NEW.thanh_tien = NEW.so_luong_dat * NEW.don_gia;
    END IF;
END$$

DELIMITER ;

-- 6. Thêm Permissions vào Roles
-- ============================================================
-- LƯU Ý: Database KHÔNG có bảng permissions riêng
-- Permissions được lưu trong roles.permissions (JSON array)
-- ============================================================
-- 
-- HƯỚNG DẪN: Sau khi chạy SQL này, vào trình duyệt chạy:
-- http://localhost/iso2/grant_giohang_phieudathang_permissions.php
-- 
-- Script PHP sẽ tự động thêm 13 permissions vào roles.permissions:
--   - giohang.view, add, edit, delete
--   - phieudathang.view, create, edit, delete, approve, receive, stock, cancel, export
-- ============================================================

-- 7. Hiển thị kết quả
SELECT '✅ HOÀN TẤT! Đã tạo 4 bảng' as 'Status';
SELECT 'cart_vattu_thanh_ly' as 'Table', COUNT(*) as 'Rows' FROM cart_vattu_thanh_ly
UNION ALL
SELECT 'phieu_dat_hang', COUNT(*) FROM phieu_dat_hang
UNION ALL
SELECT 'phieu_dat_hang_chi_tiet', COUNT(*) FROM phieu_dat_hang_chi_tiet
UNION ALL
SELECT 'lich_su_nhap_kho', COUNT(*) FROM lich_su_nhap_kho;
