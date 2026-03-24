-- Thêm cột thietbi_id vào bảng ke_hoach_bao_duong_dinh_ky_iso
ALTER TABLE `ke_hoach_bao_duong_dinh_ky_iso`
ADD COLUMN `thietbi_id` int(11) DEFAULT NULL COMMENT 'ID thiết bị (tham chiếu thietbi_iso.stt)' AFTER `id`;

-- Tạo index cho cột thietbi_id
CREATE INDEX idx_thietbi_id ON ke_hoach_bao_duong_dinh_ky_iso(`thietbi_id`);
