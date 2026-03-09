-- Bảng phiếu kiểm soát vật tư thanh lý
CREATE TABLE IF NOT EXISTS `phieu_kiem_soat_vattu_iso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `so_phieu` varchar(50) DEFAULT NULL COMMENT 'Số phiếu tự động',
  `loai_congviec` varchar(100) DEFAULT NULL COMMENT 'BD theo kế hoạch / KT, BD, SC, gia công đột xuất',
  `bophan_dathang` varchar(200) DEFAULT NULL COMMENT 'Bộ phận đặt hàng',
  `ten_thietbi` varchar(500) DEFAULT NULL COMMENT 'Tên thiết bị',
  `ky_mahieu` varchar(100) DEFAULT NULL COMMENT 'Ký mã hiệu',
  `nguoi_lap_phieu` varchar(200) DEFAULT NULL COMMENT 'Người lập phiếu',
  `bophan_nguoilap` varchar(200) DEFAULT NULL COMMENT 'Bộ phận người lập',
  `phieu_xuat_kho_so` varchar(100) DEFAULT NULL COMMENT 'Phiếu xuất kho số',
  `ngay_xuat_kho` date DEFAULT NULL COMMENT 'Ngày xuất kho',
  `trangthai` varchar(50) DEFAULT 'dang_thuc_hien' COMMENT 'dang_thuc_hien, hoan_thanh, huy',
  `ghi_chu` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_so_phieu` (`so_phieu`),
  KEY `idx_ngay_xuat_kho` (`ngay_xuat_kho`),
  KEY `idx_trangthai` (`trangthai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng chi tiết vật tư trong phiếu
CREATE TABLE IF NOT EXISTS `phieu_kiem_soat_vattu_chitiet_iso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phieu_id` int(11) NOT NULL COMMENT 'ID phiếu',
  `vattu_stt` int(11) NOT NULL COMMENT 'STT vật tư từ bảng vattu_thanh_ly_iso',
  `soluong_nhan` decimal(10,2) DEFAULT 0.00 COMMENT 'Số lượng nhận',
  `soluong_tieuhao` decimal(10,2) DEFAULT 0.00 COMMENT 'Số lượng tiêu hao',
  `ghichu` varchar(500) DEFAULT NULL COMMENT 'Ghi chú',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_phieu_id` (`phieu_id`),
  KEY `idx_vattu_stt` (`vattu_stt`),
  CONSTRAINT `fk_phieu_ksvattu_phieu` FOREIGN KEY (`phieu_id`) REFERENCES `phieu_kiem_soat_vattu_iso` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_phieu_ksvattu_vattu` FOREIGN KEY (`vattu_stt`) REFERENCES `vattu_thanh_ly_iso` (`stt`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
