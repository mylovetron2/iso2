-- Thêm cột trạng thái hoàn thành bảo dưỡng cho từng quý
-- Mỗi quý có cột riêng: qui_1_hoantat, qui_2_hoantat, qui_3_hoantat, qui_4_hoantat
-- Giá trị: 1 = đã hoàn thành, 0 hoặc NULL = chưa hoàn thành

ALTER TABLE `ke_hoach_bao_duong_dinh_ky_iso`
ADD COLUMN `qui_1_hoantat` TINYINT(1) DEFAULT 0 COMMENT 'Quý 1 đã hoàn thành: 1=đã, 0=chưa',
ADD COLUMN `qui_2_hoantat` TINYINT(1) DEFAULT 0 COMMENT 'Quý 2 đã hoàn thành: 1=đã, 0=chưa',
ADD COLUMN `qui_3_hoantat` TINYINT(1) DEFAULT 0 COMMENT 'Quý 3 đã hoàn thành: 1=đã, 0=chưa',
ADD COLUMN `qui_4_hoantat` TINYINT(1) DEFAULT 0 COMMENT 'Quý 4 đã hoàn thành: 1=đã, 0=chưa';

-- Thêm index để tối ưu truy vấn theo trạng thái hoàn thành
CREATE INDEX idx_qui_1_hoantat ON ke_hoach_bao_duong_dinh_ky_iso(qui_1_hoantat);
CREATE INDEX idx_qui_2_hoantat ON ke_hoach_bao_duong_dinh_ky_iso(qui_2_hoantat);
CREATE INDEX idx_qui_3_hoantat ON ke_hoach_bao_duong_dinh_ky_iso(qui_3_hoantat);
CREATE INDEX idx_qui_4_hoantat ON ke_hoach_bao_duong_dinh_ky_iso(qui_4_hoantat);
