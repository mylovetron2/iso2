-- Tìm các bản ghi có nhomsc NULL trong bảng ke_hoach_bao_duong_dinh_ky_iso

SELECT id, ten_thietbi, so_serial, nhomsc, nam 
FROM ke_hoach_bao_duong_dinh_ky_iso 
WHERE nhomsc IS NULL
ORDER BY id ASC;

-- Đếm số lượng bản ghi có nhomsc NULL
-- SELECT COUNT(*) as total FROM ke_hoach_bao_duong_dinh_ky_iso WHERE nhomsc IS NULL;
