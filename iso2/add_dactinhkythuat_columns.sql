-- Thêm cột đặc tính kỹ thuật vào bảng vattu_thanh_ly_iso

ALTER TABLE vattu_thanh_ly_iso
ADD COLUMN dactinhkt_tiengnga TEXT NULL AFTER ten_tiengviet,
ADD COLUMN dactinhkt_tiengviet TEXT NULL AFTER dactinhkt_tiengnga;
