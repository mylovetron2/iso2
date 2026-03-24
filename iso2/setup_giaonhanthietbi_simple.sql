-- =====================================================
-- THIẾT LẬP MODULE GIAO NHẬN THIẾT BỊ - SIMPLIFIED VERSION
-- Không dùng foreign keys để tránh lỗi constraint
-- =====================================================

-- Bước 1: Xóa bảng cũ nếu tồn tại
DROP TABLE IF EXISTS giao_nhan_thietbi_iso;

-- Bước 2: Tạo bảng mới
CREATE TABLE giao_nhan_thietbi_iso (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Thông tin thiết bị
    thietbi_id INT DEFAULT NULL COMMENT 'ID thiết bị (tham chiếu thietbi_iso.id)',
    ten_thietbi VARCHAR(255) DEFAULT NULL,
    ky_ma_hieu VARCHAR(100) DEFAULT NULL,
    
    -- Loại giao nhận
    loai_giao_nhan ENUM('giao_di_kd', 'nhan_ve_kd') NOT NULL COMMENT 'giao_di_kd: Team giao cho Us | nhan_ve_kd: Us giao lại cho Team',
    
    -- Thông tin bên giao
    nguoi_giao VARCHAR(100) NOT NULL COMMENT 'Tên người giao',
    donvi_giao VARCHAR(50) DEFAULT NULL COMMENT 'Mã đơn vị giao',
    ngay_giao DATE NOT NULL,
    
    -- Thông tin bên nhận  
    nguoi_nhan VARCHAR(100) NOT NULL COMMENT 'Tên người nhận',
    donvi_nhan VARCHAR(50) DEFAULT NULL COMMENT 'Mã đơn vị nhận',
    ngay_nhan DATE DEFAULT NULL,
    
    -- Thông tin kiểm định
    noidung_kiemdinh TEXT DEFAULT NULL COMMENT 'Kết quả kiểm định',
    ghichu TEXT DEFAULT NULL COMMENT 'Ghi chú',
    
    -- Trạng thái
    trangthai ENUM('cho_nhan', 'da_nhan', 'hoan_thanh') DEFAULT 'cho_nhan',
    
    -- Liên kết phiếu giao đi
    phieu_giao_id INT DEFAULT NULL COMMENT 'ID phiếu giao đi (tham chiếu giao_nhan_thietbi_iso.id)',
    
    -- Audit
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    KEY idx_thietbi (thietbi_id),
    KEY idx_loai (loai_giao_nhan),
    KEY idx_trangthai (trangthai),
    KEY idx_ngay_giao (ngay_giao),
    KEY idx_donvi_giao (donvi_giao),
    KEY idx_donvi_nhan (donvi_nhan),
    KEY idx_phieu_giao (phieu_giao_id)
    
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Giao nhận thiết bị kiểm định';

-- Bước 3: Thêm permissions vào roles (CSV format)
-- Cập nhật permissions cho Admin role
UPDATE roles 
SET permissions = CASE 
    WHEN permissions IS NULL OR permissions = '' THEN 'giaonhanthietbi.view,giaonhanthietbi.create_giao,giaonhanthietbi.create_nhan,giaonhanthietbi.edit,giaonhanthietbi.delete,giaonhanthietbi.export'
    WHEN permissions NOT LIKE '%giaonhanthietbi%' THEN CONCAT(permissions, ',giaonhanthietbi.view,giaonhanthietbi.create_giao,giaonhanthietbi.create_nhan,giaonhanthietbi.edit,giaonhanthietbi.delete,giaonhanthietbi.export')
    ELSE permissions
END
WHERE (name = 'Admin' OR name = 'admin' OR name = 'Manager' OR id = 1)
AND (permissions IS NULL OR permissions NOT LIKE '%giaonhanthietbi%');

-- Hoàn tất
SELECT 'Setup completed successfully!' as status;
SELECT id, name, 
       CASE 
         WHEN permissions LIKE '%giaonhanthietbi%' THEN 'Đã có quyền ✓'
         ELSE 'Chưa có quyền'
       END as status_giaonhanthietbi
FROM roles
WHERE name IN ('Admin', 'admin', 'Manager') OR id = 1;
