-- Cập nhật nhomsc='RDNGA' cho các bản ghi id từ 2233 đến 2369
-- Bảng: ke_hoach_bao_duong_dinh_ky_iso

UPDATE ke_hoach_bao_duong_dinh_ky_iso 
SET nhomsc = 'RDNGA' 
WHERE id >= 2233 AND id <= 2369;

-- Kiểm tra kết quả
-- SELECT id, ten_thietbi, so_serial, nhomsc FROM ke_hoach_bao_duong_dinh_ky_iso WHERE id >= 2233 AND id <= 2369;
