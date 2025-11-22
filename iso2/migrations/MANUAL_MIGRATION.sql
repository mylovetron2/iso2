-- =====================================================
-- MIGRATION: Phiếu Bàn Giao Module
-- Date: 2025-11-22
-- Description: Tạo bảng quản lý phiếu bàn giao thiết bị
-- Instructions: Copy và paste từng khối SQL vào phpMyAdmin
-- =====================================================

-- =====================================================
-- BƯỚC 1: Tạo bảng phieubangiao_iso (Bảng chính)
-- =====================================================

CREATE TABLE IF NOT EXISTS phieubangiao_iso (
    stt INT AUTO_INCREMENT PRIMARY KEY,
    sophieu VARCHAR(50) NOT NULL UNIQUE COMMENT 'Số phiếu bàn giao (PBG-001)',
    phieuyc VARCHAR(50) NOT NULL COMMENT 'Phiếu yêu cầu dịch vụ',
    ngaybg DATE NOT NULL COMMENT 'Ngày bàn giao',
    nguoigiao VARCHAR(100) COMMENT 'Người giao thiết bị',
    nguoinhan VARCHAR(100) COMMENT 'Người nhận thiết bị',
    donvigiao VARCHAR(50) COMMENT 'Đơn vị giao',
    donvinhan VARCHAR(50) COMMENT 'Đơn vị nhận',
    ghichu TEXT COMMENT 'Ghi chú chung',
    trangthai TINYINT DEFAULT 0 COMMENT '0: Nháp, 1: Đã duyệt',
    nguoitao VARCHAR(50) COMMENT 'Username người tạo',
    ngaytao DATETIME DEFAULT CURRENT_TIMESTAMP,
    nguoisua VARCHAR(50) COMMENT 'Username người sửa cuối',
    ngaysua DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sophieu (sophieu),
    INDEX idx_phieuyc (phieuyc),
    INDEX idx_ngaybg (ngaybg),
    INDEX idx_trangthai (trangthai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Phiếu bàn giao thiết bị';

-- =====================================================
-- BƯỚC 2: Tạo bảng phieubangiao_thietbi_iso (Bảng chi tiết)
-- =====================================================

CREATE TABLE IF NOT EXISTS phieubangiao_thietbi_iso (
    stt INT AUTO_INCREMENT PRIMARY KEY,
    sophieu VARCHAR(50) NOT NULL COMMENT 'Số phiếu bàn giao',
    hososcbd_stt INT NOT NULL COMMENT 'Link đến hososcbd_iso.stt',
    tinhtrang TEXT COMMENT 'Tình trạng thiết bị khi bàn giao',
    ghichu TEXT COMMENT 'Ghi chú riêng cho thiết bị',
    ngaytao DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sophieu (sophieu),
    INDEX idx_hososcbd (hososcbd_stt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Chi tiết thiết bị trong phiếu bàn giao';

-- =====================================================
-- BƯỚC 3: Kiểm tra bảng đã tạo thành công
-- =====================================================

SHOW TABLES LIKE 'phieubangiao%';

-- =====================================================
-- BƯỚC 4: Xem cấu trúc bảng
-- =====================================================

DESCRIBE phieubangiao_iso;
DESCRIBE phieubangiao_thietbi_iso;

-- =====================================================
-- BƯỚC 5: Kiểm tra Foreign Keys
-- =====================================================

SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('phieubangiao_iso', 'phieubangiao_thietbi_iso')
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- =====================================================
-- BƯỚC 6: Test data (Optional - để test)
-- =====================================================

-- Kiểm tra thiết bị có thể bàn giao (ngaykt đã có, bg = 0)
SELECT 
    h.stt,
    h.sophieu as phieuyc,
    h.mavt,
    h.tenvt,
    h.somay,
    h.madv,
    h.ngaykt,
    h.bg,
    d.tendv
FROM hososcbd_iso h
LEFT JOIN donvi_iso d ON h.madv = d.madv
WHERE h.ngaykt IS NOT NULL 
AND h.ngaykt != '0000-00-00'
AND h.bg = 0
LIMIT 10;

-- =====================================================
-- BƯỚC 7: Cấp quyền cho module (QUAN TRỌNG!)
-- =====================================================

-- Thêm permissions cho phieubangiao
INSERT INTO permissions (name, description, created_at) VALUES
('phieubangiao.view', 'Xem phiếu bàn giao', NOW()),
('phieubangiao.create', 'Tạo phiếu bàn giao', NOW()),
('phieubangiao.edit', 'Sửa phiếu bàn giao', NOW()),
('phieubangiao.delete', 'Xóa phiếu bàn giao', NOW()),
('phieubangiao.approve', 'Duyệt phiếu bàn giao', NOW())
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Cấp tất cả quyền phieubangiao cho role admin (role_id = 1)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE name LIKE 'phieubangiao.%'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Cấp tất cả quyền cho user stt = 5 (nếu có)
INSERT INTO user_permissions (user_id, permission_id)
SELECT 5, id FROM permissions WHERE name LIKE 'phieubangiao.%'
WHERE EXISTS (SELECT 1 FROM users WHERE stt = 5)
ON DUPLICATE KEY UPDATE user_id = user_id;

-- Optional: Cấp quyền xem và tạo cho user thường (role_id = 2)
-- Bỏ comment nếu cần:
-- INSERT INTO role_permissions (role_id, permission_id)
-- SELECT 2, id FROM permissions WHERE name IN ('phieubangiao.view', 'phieubangiao.create')
-- ON DUPLICATE KEY UPDATE role_id = role_id;

-- Kiểm tra permissions đã thêm
SELECT * FROM permissions WHERE name LIKE 'phieubangiao.%';

-- =====================================================
-- HOÀN TẤT!
-- Sau khi chạy xong các lệnh trên:
-- 1. Truy cập: http://your-domain.com/phieubangiao.php
-- 2. Click "Tạo phiếu bàn giao"
-- 3. Chọn thiết bị và test workflow
-- =====================================================
