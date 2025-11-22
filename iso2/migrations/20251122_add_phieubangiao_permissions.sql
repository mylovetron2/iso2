-- Add phieubangiao permissions
-- File: migrations/20251122_add_phieubangiao_permissions.sql
-- Description: Thêm quyền quản lý phiếu bàn giao cho roles và users

-- Insert permissions for phieubangiao module
INSERT INTO permissions (name, description, created_at) VALUES
('phieubangiao.view', 'Xem phiếu bàn giao', NOW()),
('phieubangiao.create', 'Tạo phiếu bàn giao', NOW()),
('phieubangiao.edit', 'Sửa phiếu bàn giao', NOW()),
('phieubangiao.delete', 'Xóa phiếu bàn giao', NOW()),
('phieubangiao.approve', 'Duyệt phiếu bàn giao', NOW())
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Grant all phieubangiao permissions to admin role (role_id = 1)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE name LIKE 'phieubangiao.%'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Grant all phieubangiao permissions to user with stt = 5 (if exists)
INSERT INTO user_permissions (user_id, permission_id)
SELECT 5, id FROM permissions WHERE name LIKE 'phieubangiao.%'
WHERE EXISTS (SELECT 1 FROM users WHERE stt = 5)
ON DUPLICATE KEY UPDATE user_id = user_id;

-- Optional: Grant view and create permissions to regular users (role_id = 2)
-- Uncomment if needed:
-- INSERT INTO role_permissions (role_id, permission_id)
-- SELECT 2, id FROM permissions WHERE name IN ('phieubangiao.view', 'phieubangiao.create')
-- ON DUPLICATE KEY UPDATE role_id = role_id;
