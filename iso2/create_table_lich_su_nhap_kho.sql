-- Bảng lịch sử nhập kho (Stock In History)
-- Log mỗi lần nhập kho từ phiếu đặt hàng

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

-- Foreign keys (thêm sau khi các bảng liên quan đã tồn tại):
-- ALTER TABLE `lich_su_nhap_kho` ADD CONSTRAINT `fk_nhapkho_phieu` FOREIGN KEY (`phieu_dat_hang_id`) REFERENCES `phieu_dat_hang` (`id`);
-- ALTER TABLE `lich_su_nhap_kho` ADD CONSTRAINT `fk_nhapkho_chitiet` FOREIGN KEY (`phieu_chi_tiet_id`) REFERENCES `phieu_dat_hang_chi_tiet` (`id`);
-- ALTER TABLE `lich_su_nhap_kho` ADD CONSTRAINT `fk_nhapkho_vattu` FOREIGN KEY (`vattu_stt`) REFERENCES `vattu_thanh_ly_iso` (`stt`);
-- ALTER TABLE `lich_su_nhap_kho` ADD CONSTRAINT `fk_nhapkho_nguoi_nhap` FOREIGN KEY (`nguoi_nhap`) REFERENCES `users` (`id`);
