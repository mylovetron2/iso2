## Fix: Thiết bị bị lặp lại trong trang chi tiết phiếu

### Vấn đề
Trong trang `phieuyeucau.php?action=view&phieu=...`, danh sách thiết bị bị hiển thị lặp lại 6 lần.

### Nguyên nhân
Query trong `PhieuYeuCau::getPhieuDetail()` có LEFT JOIN không chính xác:

```php
// SAI: Chỉ JOIN theo mavt
LEFT JOIN thietbi_iso t ON h.mavt = t.mavt
```

Nếu bảng `thietbi_iso` có nhiều bản ghi với cùng `mavt` (nhưng khác `somay`), mỗi thiết bị trong `hososcbd_iso` sẽ match với nhiều records, gây ra lặp.

### Giải pháp
JOIN chính xác theo CẢ `mavt` VÀ `somay`:

```php
// ĐÚNG: JOIN theo mavt và somay
LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
```

### File đã sửa
- **models/PhieuYeuCau.php** (dòng ~206)
  - Method: `getPhieuDetail()`
  - Sửa điều kiện JOIN trong query lấy danh sách thiết bị

### Kiểm tra
1. Truy cập trang chi tiết phiếu: `/iso2/phieuyeucau.php?action=view&phieu=0001`
2. Kiểm tra danh sách thiết bị không còn bị lặp
3. Mỗi thiết bị chỉ hiển thị 1 lần

### Kết quả mong đợi
- Trước: 6 thiết bị → hiển thị 36 dòng (mỗi thiết bị lặp 6 lần)
- Sau: 6 thiết bị → hiển thị 6 dòng (đúng)

---
**Phiên bản:** 1.0.1  
**Ngày fix:** 11/02/2026
