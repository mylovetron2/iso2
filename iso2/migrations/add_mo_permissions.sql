-- Add permissions for Mo (Mỏ) management
INSERT INTO permissions (name, description) VALUES
('mo.view', 'Xem danh sách mỏ'),
('mo.create', 'Tạo mới mỏ'),
('mo.edit', 'Sửa thông tin mỏ'),
('mo.delete', 'Xóa mỏ')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Grant permissions to admin role (assuming role_id = 1 for admin)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE name LIKE 'mo.%'
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);
