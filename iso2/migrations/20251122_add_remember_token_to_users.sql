-- Migration: Thêm cột remember_token vào bảng users
-- Date: 2025-11-22
-- Description: Hỗ trợ chức năng "Ghi nhớ đăng nhập"

ALTER TABLE users 
ADD COLUMN remember_token VARCHAR(64) NULL UNIQUE 
COMMENT 'Token để ghi nhớ đăng nhập (30 ngày)';

-- Tạo index cho performance
CREATE INDEX idx_remember_token ON users(remember_token);
