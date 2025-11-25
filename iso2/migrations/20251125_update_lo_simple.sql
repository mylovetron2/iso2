-- =====================================================
-- MIGRATION: Update lo_iso table - Simple version
-- Description: Thêm các cột thiếu vào table lo_iso
-- Date: 2025-11-25
-- =====================================================

-- Thêm cột ghichu
ALTER TABLE lo_iso ADD COLUMN IF NOT EXISTS ghichu TEXT COMMENT 'Ghi chú';

-- Thêm cột nguoitao
ALTER TABLE lo_iso ADD COLUMN IF NOT EXISTS nguoitao VARCHAR(50) COMMENT 'Username người tạo';

-- Thêm cột ngaytao
ALTER TABLE lo_iso ADD COLUMN IF NOT EXISTS ngaytao DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày tạo';

-- Thêm cột nguoisua
ALTER TABLE lo_iso ADD COLUMN IF NOT EXISTS nguoisua VARCHAR(50) COMMENT 'Username người sửa cuối';

-- Thêm cột ngaysua (tự động update khi có thay đổi)
ALTER TABLE lo_iso ADD COLUMN IF NOT EXISTS ngaysua DATETIME ON UPDATE CURRENT_TIMESTAMP COMMENT 'Ngày sửa cuối';

-- Tạo index
CREATE INDEX IF NOT EXISTS idx_malo ON lo_iso(malo);
