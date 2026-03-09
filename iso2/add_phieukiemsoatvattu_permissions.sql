-- Script SQL để cấp quyền Phiếu Kiểm Soát Vật Tư cho các role
-- Chạy script này nếu PHP script bị timeout

-- Thêm quyền cho role Admin (giả sử role có name 'Admin' hoặc 'admin')
UPDATE roles 
SET permissions = CONCAT(permissions, ',phieukiemsoatvattu.view,phieukiemsoatvattu.create,phieukiemsoatvattu.edit,phieukiemsoatvattu.delete')
WHERE (name = 'Admin' OR name = 'admin' OR name = 'Manager')
AND permissions NOT LIKE '%phieukiemsoatvattu%';

-- Thêm quyền cho role User (view, create, edit)
UPDATE roles 
SET permissions = CONCAT(permissions, ',phieukiemsoatvattu.view,phieukiemsoatvattu.create,phieukiemsoatvattu.edit')
WHERE (name = 'User' OR name = 'user' OR name = 'Editor')
AND permissions NOT LIKE '%phieukiemsoatvattu%';

-- Thêm quyền cho role Viewer (chỉ view)
UPDATE roles 
SET permissions = CONCAT(permissions, ',phieukiemsoatvattu.view')
WHERE (name = 'Viewer' OR name = 'viewer')
AND permissions NOT LIKE '%phieukiemsoatvattu%';

-- Hiển thị kết quả
SELECT id, name, 
       CASE 
         WHEN permissions LIKE '%phieukiemsoatvattu%' THEN 'Đã có quyền ✓'
         ELSE 'Chưa có quyền'
       END as status_phieukiemsoatvattu
FROM roles
ORDER BY id;
