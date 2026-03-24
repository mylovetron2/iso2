-- Bảng kế hoạch bảo dưỡng thiết bị định kỳ
CREATE TABLE IF NOT EXISTS `ke_hoach_bao_duong_dinh_ky_iso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thietbi_id` int(11) DEFAULT NULL COMMENT 'ID thiết bị (tham chiếu thietbi_iso.stt)',
  `nam` int(4) NOT NULL COMMENT 'Năm kế hoạch',
  `ten_thietbi` varchar(500) DEFAULT NULL COMMENT 'Tên thiết bị',
  `so_serial` varchar(100) DEFAULT NULL COMMENT 'Số serial',
  `qui_1` varchar(50) DEFAULT NULL COMMENT 'Kế hoạch quí 1 (TO = có kế hoạch)',
  `qui_2` varchar(50) DEFAULT NULL COMMENT 'Kế hoạch quí 2',
  `qui_3` varchar(50) DEFAULT NULL COMMENT 'Kế hoạch quí 3',
  `qui_4` varchar(50) DEFAULT NULL COMMENT 'Kế hoạch quí 4',
  `ghi_chu` text DEFAULT NULL COMMENT 'Ghi chú',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_thietbi_id` (`thietbi_id`),
  KEY `idx_nam` (`nam`),
  KEY `idx_so_serial` (`so_serial`(50)),
  KEY `idx_ten_thietbi` (`ten_thietbi`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo index cho tìm kiếm nhanh
CREATE INDEX idx_nam_serial ON ke_hoach_bao_duong_dinh_ky_iso(nam, so_serial(50));
