-- Migration: Create phieubangiao tables
-- Date: 2025-11-22
-- Description: Tables for managing delivery notes (phiếu bàn giao)

-- Table: phieubangiao_iso
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

-- Table: phieubangiao_thietbi_iso (Chi tiết thiết bị trong phiếu BG)
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

-- Grant permissions (if needed)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON phieubangiao_iso TO 'your_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON phieubangiao_thietbi_iso TO 'your_user'@'localhost';
