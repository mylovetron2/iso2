-- Đổi tên cột stt thành thietbi_id trong bảng ke_hoach_bao_duong_dinh_ky_iso
-- Migration này dành cho cơ sở dữ liệu đã có cột 'stt'

-- Kiểm tra và xóa index cũ nếu tồn tại
DROP INDEX IF EXISTS idx_stt ON ke_hoach_bao_duong_dinh_ky_iso;

-- Đổi tên cột stt thành thietbi_id
ALTER TABLE `ke_hoach_bao_duong_dinh_ky_iso`
CHANGE COLUMN `stt` `thietbi_id` int(11) DEFAULT NULL COMMENT 'ID thiết bị (tham chiếu thietbi_iso.stt)';

-- Tạo index mới cho cột thietbi_id
CREATE INDEX idx_thietbi_id ON ke_hoach_bao_duong_dinh_ky_iso(`thietbi_id`);
