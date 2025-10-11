-- Gán role admin cho user có stt = 5
INSERT INTO role_user (user_id, role_id)
SELECT 5, id FROM roles WHERE name = 'admin';
