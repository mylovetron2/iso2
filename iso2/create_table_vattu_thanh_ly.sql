-- Bảng quản lý vật tư/thiết bị thanh lý
CREATE TABLE IF NOT EXISTS `vattu_thanh_ly_iso` (
  `stt` int(11) NOT NULL AUTO_INCREMENT,
  `mavattu` varchar(50) DEFAULT NULL COMMENT 'Mã vật tư',
  `ten_tienganh` text DEFAULT NULL COMMENT 'Tên tiếng Anh',
  `ten_tiengnga` text DEFAULT NULL COMMENT 'Tên tiếng Nga',
  `ten_tiengviet` text DEFAULT NULL COMMENT 'Tên tiếng Việt',
  `dactinhkt_tiengnga` text DEFAULT NULL COMMENT 'Đặc tính kỹ thuật tiếng Nga',
  `dactinhkt_tiengviet` text DEFAULT NULL COMMENT 'Đặc tính kỹ thuật tiếng Việt',
  `dvt_tiengnga` varchar(50) DEFAULT NULL COMMENT 'Đơn vị tính tiếng Nga',
  `dvt_tiengviet` varchar(50) DEFAULT NULL COMMENT 'Đơn vị tính tiếng Việt',
  `soluong_conlai` decimal(10,2) DEFAULT NULL COMMENT 'Số lượng còn lại',
  `dongia` decimal(15,2) DEFAULT NULL COMMENT 'Đơn giá',
  `ngaynhan` date DEFAULT NULL COMMENT 'Ngày nhận',
  `sohd` varchar(50) DEFAULT NULL COMMENT 'Số hợp đồng',
  `ngaykyhd` date DEFAULT NULL COMMENT 'Ngày ký hợp đồng',
  `nguoiquanly` varchar(100) DEFAULT NULL COMMENT 'Người quản lý',
  `vitribaoquan` varchar(200) DEFAULT NULL COMMENT 'Vị trí bảo quản',
  `ghichu` text DEFAULT NULL COMMENT 'Ghi chú',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`stt`),
  KEY `idx_mavattu` (`mavattu`),
  KEY `idx_ngaynhan` (`ngaynhan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quản lý vật tư thanh lý';

-- Bảng thông tin thanh lý/sử dụng (chi tiết lịch sử sử dụng)
CREATE TABLE IF NOT EXISTS `vattu_thanh_ly_sudung_iso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vattu_stt` int(11) NOT NULL COMMENT 'Liên kết với bảng vattu_thanh_ly_iso',
  `nguoisudung` varchar(100) DEFAULT NULL COMMENT 'Người sử dụng',
  `ngaysd_nhan` date DEFAULT NULL COMMENT 'Ngày sử dụng/nhận',
  `soluong` decimal(10,2) DEFAULT NULL COMMENT 'Số lượng',
  `bophan` varchar(100) DEFAULT NULL COMMENT 'Bộ phận',
  `mucdich_sudung` text DEFAULT NULL COMMENT 'Mục đích sử dụng',
  `trangthai` varchar(20) DEFAULT 'dangdung' COMMENT 'Trạng thái: dangdung, dahoan, thanh_ly',
  `ngayhoanthanh` date DEFAULT NULL COMMENT 'Ngày hoàn thành/trả lại',
  `ghichu` text DEFAULT NULL COMMENT 'Ghi chú',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_vattu_sudung` (`vattu_stt`),
  KEY `idx_nguoisudung` (`nguoisudung`),
  KEY `idx_ngaysd` (`ngaysd_nhan`),
  KEY `idx_trangthai` (`trangthai`),
  CONSTRAINT `fk_vattu_sudung` FOREIGN KEY (`vattu_stt`) REFERENCES `vattu_thanh_ly_iso` (`stt`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chi tiết thanh lý/sử dụng vật tư';

-- Bảng lịch sử thay đổi số lượng
CREATE TABLE IF NOT EXISTS `vattu_thanh_ly_lichsu_iso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vattu_stt` int(11) NOT NULL COMMENT 'Liên kết với bảng vattu_thanh_ly_iso',
  `loai_thaydoi` enum('nhap','xuat','dieu_chinh','thanh_ly') NOT NULL COMMENT 'Loại thay đổi',
  `soluong_truoc` decimal(10,2) DEFAULT NULL COMMENT 'Số lượng trước thay đổi',
  `soluong_thaydoi` decimal(10,2) NOT NULL COMMENT 'Số lượng thay đổi (+/-)',
  `soluong_sau` decimal(10,2) DEFAULT NULL COMMENT 'Số lượng sau thay đổi',
  `nguoi_thuchien` varchar(100) DEFAULT NULL COMMENT 'Người thực hiện',
  `ngay_thuchien` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Ngày thực hiện',
  `lydothaydo` text DEFAULT NULL COMMENT 'Lý do thay đổi',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_vattu_lichsu` (`vattu_stt`),
  KEY `idx_ngay_thuchien` (`ngay_thuchien`),
  KEY `idx_loai_thaydoi` (`loai_thaydoi`),
  CONSTRAINT `fk_vattu_lichsu` FOREIGN KEY (`vattu_stt`) REFERENCES `vattu_thanh_ly_iso` (`stt`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử thay đổi số lượng vật tư';

-- Tạo view để xem tổng hợp
CREATE OR REPLACE VIEW `view_vattu_thanh_ly_tonghop` AS
SELECT 
    v.stt,
    v.mavattu,
    v.ten_tienganh,
    v.ten_tiengnga,
    v.ten_tiengviet,
    v.dactinhkt_tiengnga,
    v.dactinhkt_tiengviet,
    v.dvt_tiengnga,
    v.dvt_tiengviet,
    v.soluong_conlai,
    v.dongia,
    v.ngaynhan,
    v.sohd,
    v.ngaykyhd,
    v.nguoiquanly,
    v.vitribaoquan,
    COUNT(DISTINCT s.id) as so_lan_sudung,
    SUM(CASE WHEN s.trangthai = 'dangdung' THEN s.soluong ELSE 0 END) as soluong_dangdung,
    v.soluong_conlai * v.dongia as tong_tien
FROM vattu_thanh_ly_iso v
LEFT JOIN vattu_thanh_ly_sudung_iso s ON v.stt = s.vattu_stt
GROUP BY v.stt;

-- Insert dữ liệu mẫu (ví dụ từ hình)
INSERT INTO `vattu_thanh_ly_iso` 
(`mavattu`, `ten_tienganh`, `ten_tiengnga`, `ten_tiengviet`, `dvt_tiengnga`, `dvt_tiengviet`, `soluong_conlai`, `ngaynhan`, `sohd`) 
VALUES
('011.004.00521', 'Tụ điện 10uF 25V X8L, RADIAL', 'Конденсатор /', 'Tụ điện 10uF 25V X8L, RADIAL', NULL, 'Cái', 80.00, '2026-01-16', '0044/25/DVL-STE');
