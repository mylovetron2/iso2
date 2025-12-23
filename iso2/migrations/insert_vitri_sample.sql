-- Insert sample data into vitri_iso table
INSERT INTO vitri_iso (mavitri, tenvitri, mota) VALUES
('VT001', 'Khu vực A - Trạm 1', 'Trạm bơm khu A'),
('VT002', 'Khu vực A - Trạm 2', 'Trạm bơm khu A phụ'),
('VT003', 'Khu vực B - Nhà máy chính', 'Nhà máy sản xuất khu B'),
('VT004', 'Khu vực C - Giàn khoan', 'Vị trí giàn khoan ngoài khơi'),
('VT005', 'Xưởng sửa chữa', 'Xưởng bảo trì thiết bị'),
('VT006', 'Kho vật tư', 'Kho lưu trữ vật tư thiết bị'),
('VT007', 'Phòng điều khiển', 'Phòng điều khiển trung tâm'),
('VT008', 'Trạm nén khí', 'Trạm cung cấp khí nén'),
('VT009', 'Trạm xử lý nước', 'Hệ thống xử lý nước thải'),
('VT010', 'Khu vực D - Mỏ Nam', 'Vị trí khai thác mỏ Nam')
ON DUPLICATE KEY UPDATE tenvitri = VALUES(tenvitri), mota = VALUES(mota);
