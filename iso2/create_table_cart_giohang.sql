-- Bảng giỏ hàng vật tư thanh lý (Cart)
-- Lưu tạm các vật tư user đã chọn, tồn tại qua nhiều phiên

CREATE TABLE IF NOT EXISTS `cart_vattu_thanh_ly` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `vattu_stt` int(11) NOT NULL COMMENT 'STT vật tư',
  `so_luong` int(11) DEFAULT 1 COMMENT 'Số lượng muốn đặt',
  `ghi_chu` text DEFAULT NULL COMMENT 'Ghi chú của user',
  `ngay_them` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Ngày thêm vào giỏ',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_vattu` (`user_id`, `vattu_stt`),
  KEY `idx_user` (`user_id`),
  KEY `idx_vattu` (`vattu_stt`),
  KEY `idx_ngay_them` (`ngay_them`),
  KEY `idx_user_ngay` (`user_id`, `ngay_them`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Giỏ hàng vật tư thanh lý';

-- Foreign keys sẽ được thêm sau khi kiểm tra bảng tham chiếu
-- Chạy các lệnh sau nếu cần (sau khi đảm bảo bảng users và vattu_thanh_ly_iso đã tồn tại):
-- ALTER TABLE `cart_vattu_thanh_ly` 
--   ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
-- ALTER TABLE `cart_vattu_thanh_ly` 
--   ADD CONSTRAINT `fk_cart_vattu` FOREIGN KEY (`vattu_stt`) REFERENCES `vattu_thanh_ly_iso` (`stt`) ON DELETE CASCADE;
