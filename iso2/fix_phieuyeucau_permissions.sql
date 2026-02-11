-- Cấp quyền phieuyeucau cho admin role
-- Chạy script này nếu gặp lỗi không đăng nhập được sau khi thêm quyền mới

-- Bước 1: Kiểm tra role admin hiện tại
SELECT * FROM roles WHERE name = 'admin';

-- Bước 2: Cập nhật thêm quyền phieuyeucau cho admin
-- Lấy permissions hiện tại của admin
SELECT id, name, permissions FROM roles WHERE name = 'admin';

-- Nếu admin chưa có quyền phieuyeucau, thêm vào:
UPDATE roles 
SET permissions = CONCAT(permissions, ',phieuyeucau.view,phieuyeucau.create,phieuyeucau.edit,phieuyeucau.delete')
WHERE name = 'admin' 
AND permissions NOT LIKE '%phieuyeucau.view%';

-- Bước 3: Kiểm tra lại
SELECT id, name, permissions FROM roles WHERE name = 'admin';

-- Bước 4: Nếu muốn cấp cho user cụ thể (giả sử role_id = 1)
UPDATE roles 
SET permissions = CONCAT(
    IFNULL(permissions, ''),
    CASE WHEN permissions IS NULL OR permissions = '' THEN '' ELSE ',' END,
    'phieuyeucau.view,phieuyeucau.create,phieuyeucau.edit,phieuyeucau.delete'
)
WHERE id = 1 
AND (permissions IS NULL OR permissions NOT LIKE '%phieuyeucau.view%');

-- Bước 5: Xóa cache session nếu cần
-- Đăng xuất và đăng nhập lại để load quyền mới
