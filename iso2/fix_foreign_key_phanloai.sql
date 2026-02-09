-- Sửa foreign key constraint để trỏ đúng bảng phanloai_vattu_thanh_ly_iso

-- Xóa constraint cũ
ALTER TABLE vattu_thanh_ly_iso
DROP FOREIGN KEY fk_vattu_phanloai;

-- Tạo lại constraint mới trỏ đúng bảng
ALTER TABLE vattu_thanh_ly_iso
ADD CONSTRAINT fk_vattu_phanloai 
    FOREIGN KEY (phanloai_id) 
    REFERENCES phanloai_vattu_thanh_ly_iso(id);
