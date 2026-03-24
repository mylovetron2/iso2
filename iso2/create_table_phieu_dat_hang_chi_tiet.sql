-- Bảng chi tiết phiếu đặt hàng (Purchase Order Details)
-- Lưu từng vật tư trong phiếu đặt hàng

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

-- Foreign keys (thêm sau khi đã tạo bảng phieu_dat_hang và vattu_thanh_ly_iso):
-- ALTER TABLE `phieu_dat_hang_chi_tiet` ADD CONSTRAINT `fk_chi_tiet_phieu` FOREIGN KEY (`phieu_id`) REFERENCES `phieu_dat_hang` (`id`) ON DELETE CASCADE;
-- ALTER TABLE `phieu_dat_hang_chi_tiet` ADD CONSTRAINT `fk_chi_tiet_vattu` FOREIGN KEY (`vattu_stt`) REFERENCES `vattu_thanh_ly_iso` (`stt`) ON DELETE RESTRICT;

-- Trigger tự động tính thành tiền
DELIMITER $$
CREATE TRIGGER before_insert_chi_tiet_thanhtien 
BEFORE INSERT ON phieu_dat_hang_chi_tiet
FOR EACH ROW
BEGIN
    IF NEW.don_gia IS NOT NULL AND NEW.so_luong_dat IS NOT NULL THEN
        SET NEW.thanh_tien = NEW.so_luong_dat * NEW.don_gia;
    END IF;
END$$

CREATE TRIGGER before_update_chi_tiet_thanhtien 
BEFORE UPDATE ON phieu_dat_hang_chi_tiet
FOR EACH ROW
BEGIN
    IF NEW.don_gia IS NOT NULL AND NEW.so_luong_dat IS NOT NULL THEN
        SET NEW.thanh_tien = NEW.so_luong_dat * NEW.don_gia;
    END IF;
END$$
DELIMITER ;
