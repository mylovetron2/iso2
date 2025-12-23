-- View lịch sử bảo dưỡng - Fix encoding issue
-- Không convert trong view, để PHP xử lý encoding

CREATE OR REPLACE VIEW view_lich_su_bao_duong_iso AS 
SELECT 
  tb.stt,
  tb.mavt,
  tb.tenvt AS ten_vat_tu,
  tb.somay,
  tb.mamay,
  CONCAT(tb.mavt, '-', tb.somay) AS thiet_bi_id,
  tb.madv,
  hs.ngaykt,
  hs.honghoc,
  hs.khacphuc,
  hs.noidung
FROM thietbi_iso tb
LEFT JOIN hososcbd_iso hs ON tb.mavt = hs.mavt AND tb.somay = hs.somay
ORDER BY tb.mavt, tb.somay, hs.ngaykt DESC;
