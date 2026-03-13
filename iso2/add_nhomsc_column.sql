-- Thêm cột nhomsc vào bảng ke_hoach_bao_duong_dinh_ky_iso
-- Nhóm sửa chữa - có thể để phân loại hoặc group thiết bị

ALTER TABLE ke_hoach_bao_duong_dinh_ky_iso 
ADD COLUMN nhomsc VARCHAR(100) DEFAULT NULL COMMENT 'Nhóm sửa chữa';
