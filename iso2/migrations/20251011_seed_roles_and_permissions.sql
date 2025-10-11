-- Thêm role admin với đầy đủ quyền
INSERT INTO roles (name, permissions) VALUES ('admin', 'project.view,project.create,project.edit,project.delete,project.manage');

-- Thêm role user cơ bản
INSERT INTO roles (name, permissions) VALUES ('user', 'project.view');

-- Gán role admin cho user đầu tiên (id=1)
INSERT INTO role_user (user_id, role_id)
SELECT 1, id FROM roles WHERE name = 'admin';

-- Gán role user cho user thứ hai (id=2) nếu có
INSERT INTO role_user (user_id, role_id)
SELECT 2, id FROM roles WHERE name = 'user';
