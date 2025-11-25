-- =====================================================
-- MIGRATION: Lô Module
-- Description: Quản lý lô thiết bị
-- Date: 2025-11-25
-- =====================================================

-- Tạo bảng lo_iso
CREATE TABLE IF NOT EXISTS lo_iso (
    stt INT AUTO_INCREMENT PRIMARY KEY,
    malo VARCHAR(50) NOT NULL UNIQUE COMMENT 'Mã lô',
    tenlo VARCHAR(255) NOT NULL COMMENT 'Tên lô',
    ghichu TEXT COMMENT 'Ghi chú',
    nguoitao VARCHAR(50) COMMENT 'Username người tạo',
    ngaytao DATETIME DEFAULT CURRENT_TIMESTAMP,
    nguoisua VARCHAR(50) COMMENT 'Username người sửa cuối',
    ngaysua DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_malo (malo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Quản lý lô thiết bị';

-- Insert dữ liệu mẫu
INSERT INTO lo_iso (malo, tenlo, ghichu) VALUES
('L001', 'Lô 1 - Thiết bị nhập kho tháng 1', 'Lô thiết bị đầu năm'),
('L002', 'Lô 2 - Thiết bị nhập kho tháng 2', NULL);
