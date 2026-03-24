-- Tạo bảng giao nhận thiết bị kiểm định
DROP TABLE IF EXISTS giao_nhan_thietbi_iso;

CREATE TABLE giao_nhan_thietbi_iso (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Thông tin thiết bị
    thietbi_id INT DEFAULT NULL,
    ten_thietbi VARCHAR(255),
    ky_ma_hieu VARCHAR(100),
    
    -- Loại giao nhận
    loai_giao_nhan ENUM('giao_di_kd', 'nhan_ve_kd') NOT NULL COMMENT 'giao_di_kd: Đội giao cho mình | nhan_ve_kd: Mình giao lại cho đội',
    
    -- Thông tin bên giao
    nguoi_giao VARCHAR(100) NOT NULL COMMENT 'Tên người giao',
    donvi_giao VARCHAR(50) COMMENT 'Mã đơn vị giao (từ donvi_iso)',
    ngay_giao DATE NOT NULL,
    
    -- Thông tin bên nhận  
    nguoi_nhan VARCHAR(100) NOT NULL COMMENT 'Tên người nhận',
    donvi_nhan VARCHAR(50) COMMENT 'Mã đơn vị nhận',
    ngay_nhan DATE,
    
    -- Thông tin kiểm định (chỉ có khi nhận về)
    noidung_kiemdinh TEXT COMMENT 'Kết quả/Nội dung kiểm định',
    ghichu TEXT COMMENT 'Ghi chú chung',
    
    -- Trạng thái
    trangthai ENUM('cho_nhan', 'da_nhan', 'hoan_thanh') DEFAULT 'cho_nhan' COMMENT 'cho_nhan: Chờ nhận | da_nhan: Đã nhận | hoan_thanh: Hoàn thành',
    
    -- Liên kết với phiếu giao đi (nếu là phiếu nhận về)
    phieu_giao_id INT DEFAULT NULL COMMENT 'ID phiếu giao đi tương ứng',
    
    -- Audit fields
    created_by INT COMMENT 'User ID người tạo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_thietbi (thietbi_id),
    INDEX idx_loai (loai_giao_nhan),
    INDEX idx_trangthai (trangthai),
    INDEX idx_ngay_giao (ngay_giao),
    INDEX idx_donvi_giao (donvi_giao),
    INDEX idx_donvi_nhan (donvi_nhan),
    INDEX idx_phieu_giao (phieu_giao_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Quản lý giao nhận thiết bị kiểm định';

-- Add foreign keys after table creation (avoid constraint errors)
-- Uncomment these if thietbi_iso table exists and has matching structure
-- ALTER TABLE giao_nhan_thietbi_iso 
--     ADD CONSTRAINT fk_giaonhan_thietbi 
--     FOREIGN KEY (thietbi_id) REFERENCES thietbi_iso(id) ON DELETE SET NULL;
-- 
-- ALTER TABLE giao_nhan_thietbi_iso 
--     ADD CONSTRAINT fk_giaonhan_phieugiao 
--     FOREIGN KEY (phieu_giao_id) REFERENCES giao_nhan_thietbi_iso(id) ON DELETE SET NULL;
