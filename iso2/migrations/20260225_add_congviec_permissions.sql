-- Add congviec_suachua permissions
-- File: migrations/20260225_add_congviec_permissions.sql
-- Description: Thêm quyền quản lý công việc sửa chữa hàng ngày

-- Insert permissions for congviec_suachua module
INSERT INTO permissions (name, description, created_at) VALUES
('congviec_suachua.view', 'Xem công việc sửa chữa', NOW()),
('congviec_suachua.create', 'Tạo công việc sửa chữa', NOW()),
('congviec_suachua.edit', 'Sửa công việc sửa chữa', NOW()),
('congviec_suachua.delete', 'Xóa công việc sửa chữa', NOW())
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Grant all congviec_suachua permissions to admin role (role_id = 1)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE name LIKE 'congviec_suachua.%'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Grant all congviec_suachua permissions to user with stt = 5 (if exists)
INSERT INTO user_permissions (user_id, permission_id)
SELECT 5, id FROM permissions WHERE name LIKE 'congviec_suachua.%'
WHERE EXISTS (SELECT 1 FROM users WHERE stt = 5)
ON DUPLICATE KEY UPDATE user_id = user_id;

-- Optional: Grant view and create permissions to regular users (role_id = 2)
-- Uncomment if needed:
-- INSERT INTO role_permissions (role_id, permission_id)
-- SELECT 2, id FROM permissions WHERE name IN ('congviec_suachua.view', 'congviec_suachua.create')
-- ON DUPLICATE KEY UPDATE role_id = role_id;

-- Verify
SELECT 'Permissions đã thêm:' AS info;
SELECT id, name, description, created_at 
FROM permissions 
WHERE name LIKE 'congviec_suachua.%'
ORDER BY name;

SELECT 'Role permissions đã cấp:' AS info;
SELECT rp.role_id, p.name, p.description
FROM role_permissions rp
JOIN permissions p ON rp.permission_id = p.id
WHERE p.name LIKE 'congviec_suachua.%'
ORDER BY rp.role_id, p.name;

SELECT 'User permissions đã cấp:' AS info;
SELECT up.user_id, u.username, p.name, p.description
FROM user_permissions up
JOIN permissions p ON up.permission_id = p.id
LEFT JOIN users u ON up.user_id = u.stt
WHERE p.name LIKE 'congviec_suachua.%'
ORDER BY up.user_id, p.name;
