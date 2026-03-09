-- Script SQL để cấp quyền Kế hoạch Bảo dưỡng định kỳ cho các role
-- Chạy script này nếu PHP script bị timeout

-- Thêm quyền cho role Admin (giả sử role có name 'Admin' hoặc 'admin')
UPDATE roles 
SET permissions = CONCAT(permissions, ',kehoachbaoduong.view,kehoachbaoduong.create,kehoachbaoduong.edit,kehoachbaoduong.delete')
WHERE (name = 'Admin' OR name = 'admin' OR name = 'Manager')
AND permissions NOT LIKE '%kehoachbaoduong%';

-- Thêm quyền cho role User (view, create, edit)
UPDATE roles 
SET permissions = CONCAT(permissions, ',kehoachbaoduong.view,kehoachbaoduong.create,kehoachbaoduong.edit')
WHERE (name = 'User' OR name = 'user' OR name = 'Editor')
AND permissions NOT LIKE '%kehoachbaoduong%';

-- Thêm quyền cho role Viewer (chỉ view)
UPDATE roles 
SET permissions = CONCAT(permissions, ',kehoachbaoduong.view')
WHERE (name = 'Viewer' OR name = 'viewer')
AND permissions NOT LIKE '%kehoachbaoduong%';

-- Hiển thị kết quả
SELECT id, name, 
       CASE 
         WHEN permissions LIKE '%kehoachbaoduong%' THEN 'Đã có quyền ✓'
         ELSE 'Chưa có quyền'
       END as status_kehoachbaoduong
FROM roles
ORDER BY id;
