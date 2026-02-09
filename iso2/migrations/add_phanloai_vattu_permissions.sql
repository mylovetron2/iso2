-- Thêm quyền quản lý phân loại vật tư thanh lý
-- File: migrations/add_phanloai_vattu_permissions.sql

-- Insert permissions cho module phân loại vật tư
INSERT INTO permissions (name, description, created_at) VALUES
('phanloai_vattu.view', 'Xem danh sách phân loại vật tư thanh lý', NOW()),
('phanloai_vattu.create', 'Tạo mới phân loại vật tư thanh lý', NOW()),
('phanloai_vattu.edit', 'Sửa thông tin phân loại vật tư thanh lý', NOW()),
('phanloai_vattu.delete', 'Xóa phân loại vật tư thanh lý', NOW())
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Cấp quyền cho admin role (role_id = 1)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE name LIKE 'phanloai_vattu.%'
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

-- Cấp quyền trực tiếp cho admin users (user_id = 1 hoặc role = 'admin')
INSERT INTO user_permissions (user_id, permission_id)
SELECT u.stt, p.id 
FROM users u
CROSS JOIN permissions p
WHERE (u.role = 'admin' OR u.stt = 1)
  AND p.name LIKE 'phanloai_vattu.%'
ON DUPLICATE KEY UPDATE user_id = VALUES(user_id);
