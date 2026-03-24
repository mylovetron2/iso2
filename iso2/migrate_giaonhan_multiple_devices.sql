-- Migration: Cho phép 1 phiếu giao nhận có nhiều thiết bị
-- Tạo bảng chi tiết thiết bị

-- 1. Tạo bảng chi tiết thiết bị
CREATE TABLE IF NOT EXISTS giao_nhan_thietbi_chitiet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phieu_id INT NOT NULL COMMENT 'ID phiếu giao nhận',
    thietbi_id INT DEFAULT NULL COMMENT 'ID thiết bị từ thietbi_iso',
    ten_thietbi VARCHAR(255) DEFAULT NULL COMMENT 'Tên thiết bị (copy từ thietbi_iso)',
    ky_ma_hieu VARCHAR(100) DEFAULT NULL COMMENT 'Ký mã hiệu (copy từ thietbi_iso)',
    soluong INT DEFAULT 1 COMMENT 'Số lượng thiết bị',
    tinhtrang TEXT DEFAULT NULL COMMENT 'Tình trạng thiết bị khi giao/nhận',
    ghichu TEXT DEFAULT NULL COMMENT 'Ghi chú riêng cho thiết bị này',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phieu_id (phieu_id),
    INDEX idx_thietbi_id (thietbi_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Chi tiết thiết bị trong phiếu giao nhận';

-- 2. Migrate dữ liệu cũ từ giao_nhan_thietbi_iso sang chi tiết (nếu có)
INSERT INTO giao_nhan_thietbi_chitiet (phieu_id, thietbi_id, ten_thietbi, ky_ma_hieu, soluong, created_at)
SELECT 
    id as phieu_id,
    thietbi_id,
    ten_thietbi,
    ky_ma_hieu,
    1 as soluong,
    created_at
FROM giao_nhan_thietbi_iso
WHERE thietbi_id IS NOT NULL;

-- 3. Xóa các cột không cần thiết từ bảng chính (optional - có thể giữ lại để tương thích)
-- Không xóa ngay, để backup:
-- ALTER TABLE giao_nhan_thietbi_iso 
--     DROP COLUMN thietbi_id,
--     DROP COLUMN ten_thietbi,
--     DROP COLUMN ky_ma_hieu;

-- 4. Thêm cột tổng số thiết bị vào bảng chính (optional)
ALTER TABLE giao_nhan_thietbi_iso 
ADD COLUMN tong_thietbi INT DEFAULT 0 COMMENT 'Tổng số thiết bị trong phiếu' AFTER trangthai;

-- 5. Update tổng số thiết bị
UPDATE giao_nhan_thietbi_iso gn
SET tong_thietbi = (
    SELECT COUNT(*) 
    FROM giao_nhan_thietbi_chitiet ct 
    WHERE ct.phieu_id = gn.id
);

-- Verification queries
SELECT '=== Kiểm tra bảng chi tiết ===' as step;
SELECT COUNT(*) as total_chitiet FROM giao_nhan_thietbi_chitiet;

SELECT '=== Kiểm tra tổng thiết bị ===' as step;
SELECT id, loai_giao_nhan, tong_thietbi, created_at 
FROM giao_nhan_thietbi_iso 
ORDER BY created_at DESC 
LIMIT 5;

SELECT '=== Chi tiết theo phiếu ===' as step;
SELECT 
    gn.id as phieu_id,
    gn.loai_giao_nhan,
    gn.tong_thietbi,
    COUNT(ct.id) as count_chitiet
FROM giao_nhan_thietbi_iso gn
LEFT JOIN giao_nhan_thietbi_chitiet ct ON gn.id = ct.phieu_id
GROUP BY gn.id
HAVING count_chitiet > 0
LIMIT 5;
