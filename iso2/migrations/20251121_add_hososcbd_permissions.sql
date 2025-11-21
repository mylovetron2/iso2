-- Add hososcbd permissions
-- File: migrations/20251121_add_hososcbd_permissions.sql

-- Insert permissions for hososcbd module
INSERT INTO permissions (name, description, created_at) VALUES
('hososcbd.view', 'Xem hồ sơ SCBĐ', NOW()),
('hososcbd.create', 'Tạo hồ sơ SCBĐ', NOW()),
('hososcbd.edit', 'Sửa hồ sơ SCBĐ', NOW()),
('hososcbd.delete', 'Xóa hồ sơ SCBĐ', NOW())
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Grant all hososcbd permissions to admin role (role_id = 1)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE name LIKE 'hososcbd.%'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Grant all hososcbd permissions to user with stt = 5 (if exists)
INSERT INTO user_permissions (user_id, permission_id)
SELECT 5, id FROM permissions WHERE name LIKE 'hososcbd.%'
WHERE EXISTS (SELECT 1 FROM users WHERE stt = 5)
ON DUPLICATE KEY UPDATE user_id = user_id;
