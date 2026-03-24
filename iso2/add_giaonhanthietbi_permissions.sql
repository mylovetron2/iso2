-- Thêm permissions cho module giao nhận thiết bị vào roles
-- Permissions được lưu dạng CSV trong cột permissions của bảng roles

-- Cập nhật cho Admin/Manager roles
UPDATE roles 
SET permissions = CASE 
    WHEN permissions IS NULL OR permissions = '' THEN 'giaonhanthietbi.view,giaonhanthietbi.create_giao,giaonhanthietbi.create_nhan,giaonhanthietbi.edit,giaonhanthietbi.delete,giaonhanthietbi.export'
    WHEN permissions NOT LIKE '%giaonhanthietbi%' THEN CONCAT(permissions, ',giaonhanthietbi.view,giaonhanthietbi.create_giao,giaonhanthietbi.create_nhan,giaonhanthietbi.edit,giaonhanthietbi.delete,giaonhanthietbi.export')
    ELSE permissions
END
WHERE (name IN ('Admin', 'admin', 'Manager') OR id = 1)
AND (permissions IS NULL OR permissions NOT LIKE '%giaonhanthietbi%');

-- Kiểm tra kết quả
SELECT id, name, 
       CASE 
         WHEN permissions LIKE '%giaonhanthietbi%' THEN 'Có quyền ✓'
         ELSE 'Chưa có quyền'
       END as status
FROM roles
ORDER BY id;
