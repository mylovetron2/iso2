-- Bảng phiếu đặt hàng (Purchase Order Header)
-- Lưu thông tin chính của phiếu đặt hàng

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

-- Foreign keys (thêm sau khi kiểm tra bảng users tồn tại):
-- ALTER TABLE `phieu_dat_hang` ADD CONSTRAINT `fk_phieu_nguoi_lap` FOREIGN KEY (`nguoi_lap`) REFERENCES `users` (`id`);
-- ALTER TABLE `phieu_dat_hang` ADD CONSTRAINT `fk_phieu_nguoi_duyet` FOREIGN KEY (`nguoi_duyet`) REFERENCES `users` (`id`);
-- ALTER TABLE `phieu_dat_hang` ADD CONSTRAINT `fk_phieu_nguoi_nhan` FOREIGN KEY (`nguoi_nhan_hang`) REFERENCES `users` (`id`);
-- ALTER TABLE `phieu_dat_hang` ADD CONSTRAINT `fk_phieu_nguoi_nhap_kho` FOREIGN KEY (`nguoi_nhap_kho`) REFERENCES `users` (`id`);
