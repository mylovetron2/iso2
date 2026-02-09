-- Tạo bảng phân loại vật tư thanh lý
CREATE TABLE IF NOT EXISTS `phanloai_vattu_thanh_ly_iso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_phanloai` varchar(50) NOT NULL COMMENT 'Mã phân loại (unique)',
  `ten_phanloai` varchar(100) NOT NULL COMMENT 'Tên phân loại',
  `mau_sac` varchar(50) DEFAULT NULL COMMENT 'Mã màu để hiển thị (bg-blue-100, text-blue-800)',
  `thu_tu` int(11) DEFAULT 0 COMMENT 'Thứ tự sắp xếp',
  `mo_ta` text DEFAULT NULL COMMENT 'Mô tả chi tiết',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ma_phanloai` (`ma_phanloai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Phân loại vật tư thanh lý';

-- Insert dữ liệu mẫu cho bảng phân loại
INSERT INTO `phanloai_vattu_thanh_ly_iso` (`ma_phanloai`, `ten_phanloai`, `mau_sac`, `thu_tu`, `mo_ta`) VALUES
('VATTU', 'Vật tư', 'bg-blue-100 text-blue-800', 1, 'Vật tư chung'),
('CONGCU_DUNGCU', 'Công cụ dụng cụ', 'bg-purple-100 text-purple-800', 2, 'Công cụ dụng cụ sản xuất'),
('TAISAN', 'Tài sản', 'bg-green-100 text-green-800', 3, 'Tài sản cố định'),
('PHELIEU', 'Phế liệu', 'bg-gray-100 text-gray-800', 4, 'Phế liệu không sử dụng');

-- Thêm cột phanloai_id vào bảng vattu_thanh_ly_iso
ALTER TABLE `vattu_thanh_ly_iso` 
ADD COLUMN `phanloai_id` int(11) DEFAULT 1 COMMENT 'ID phân loại (foreign key)'
AFTER `mavattu`,
ADD CONSTRAINT `fk_vattu_phanloai` FOREIGN KEY (`phanloai_id`) REFERENCES `phanloai_vattu_thanh_ly_iso` (`id`);

-- Tạo index cho cột phanloai_id
CREATE INDEX `idx_phanloai_id` ON `vattu_thanh_ly_iso` (`phanloai_id`);
