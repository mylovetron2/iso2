# Import Vật Tư - So Sánh Mã

## Mô tả
Tính năng import vật tư với logic so sánh mã vật tư:
- **Thêm mới** những vật tư có mã chưa tồn tại trong hệ thống
- **Cập nhật số lượng** những vật tư đã tồn tại (theo số lượng trong file Excel)
- **Set số lượng = 0** những vật tư có trong database nhưng không có trong file Excel

## Files liên quan
- `import_vattu_compare.php` - Trang import chính
- `download_vattu_compare_template.php` - Download template Excel mẫu

## Cách sử dụng

### 1. Tải file Excel mẫu
- Truy cập: `vattuthanhly.php` → Menu dropdown → "Import Excel (So sánh mã)"
- Hoặc trực tiếp: `import_vattu_compare.php`
- Click nút "Tải file Excel mẫu"

### 2. Chuẩn bị dữ liệu
Cấu trúc file Excel:
- **Cột A (STT)**: Số thứ tự (tùy chọn)
- **Cột B (Mã vật tư)**: BẮT BUỘC - Dùng để so sánh
- **Cột C (Tên vật tư)**: Có thể gồm tiếng Nga và tiếng Việt, ngăn cách bởi " - "
- **Cột D (Don gia(usd))**: Đơn giá bằng USD
- **Cột E (Tồn)**: Số lượng tồn kho
- **Cột F (Phân loại)**: VT, CCDC, TS, PL hoặc tên đầy đủ

### 3. Upload và Import
- Upload file Excel đã chuẩn bị
- Hệ thống sẽ:
  - So sánh mã vật tư trong Excel với database
  - **Thêm mới** những vật tư có mã chưa tồn tại
  - **Cập nhật số lượng còn lại** những vật tư đã có mã trong hệ thống
  - **Set số lượng = 0** những vật tư có trong DB nhưng không có trong file Excel

### 4. Xem kết quả
Sau khi import, hệ thống hiển thị:
- ✅ Danh sách vật tư đã thêm mới (với chi tiết đầy đủ)
- 🔄 Danh sách vật tư đã cập nhật số lượng (hiển thị số lượng mới)
- 🔻 Danh sách vật tư đã set số lượng = 0 (không có trong file)
- ❌ Danh sách lỗi (nếu có)

## Logic xử lý

```php
// Pseudo code
foreach ($excelRows as $row) {
    $mavattu = $row['B'];
    $soluong = $row['E'];
    
    if (empty($mavattu)) {
        continue; // Bỏ qua dòng trống
    }
    
    if (existsInDatabase($mavattu)) {
        updateQuantity($mavattu, $soluong); // Đã tồn tại → Cập nhật số lượng
    } else {
        insert($row); // Chưa có → Thêm mới
    }
    
    // Track mã vật tư đã xử lý
    $excelMaVatTu[$mavattu] = true;
}

// Xử lý vật tư có trong DB nhưng không có trong Excel
foreach ($databaseMaVatTu as $mavattu) {
    if (!isset($excelMaVatTu[$mavattu])) {
        updateQuantity($mavattu, 0); // Set số lượng = 0
    }
}
```

## Xử lý dữ liệu đặc biệt

### Tên vật tư song ngữ
```
Input:  "Аэрозоль для чистки контактов - Bình xịt công tắc"
Output: 
  - ten_tiengnga: "Аэрозоль для чистки контактов"
  - ten_tiengviet: "Bình xịt công tắc"
```

### Tên vật tư một ngữ
```
Input:  "Аэрозоль для чистки контактов"
Output: 
  - ten_tiengnga: "Аэрозоль для чистки контактов"
  - ten_tiengviet: null
```

### Đơn vị tính mặc định
```
dvt_tiengnga: "шт."
dvt_tiengviet: "Cái"
```

### Ghi chú tự động
```
ghichu: "Import từ file Excel"
```

## Khác biệt với import_vattu_excel.php

| Tính năng | import_vattu_excel.php | import_vattu_compare.php |
|-----------|------------------------|--------------------------|
| Cấu trúc file | 19 cột đầy đủ | 6 cột đơn giản |
| Logic | Import tất cả | Thêm mới + Cập nhật + Zeroing |
| Trùng mã | Có thể lỗi | Tự động cập nhật số lượng |
| Không có trong file | Không xử lý | Tự động set = 0 |
| Tên vật tư | Tách riêng 3 ngôn ngữ | Tự động tách Nga/Việt |
| Đơn giá | VNĐ + USD | Chỉ USD |
| Use case | Import ban đầu, đầy đủ | Import/Cập nhật/Đồng bộ nhanh |

## Lưu ý
- ⚠️ Mã vật tư là duy nhất, không được trùng
- ⚠️ Dòng đầu tiên (header) sẽ bị bỏ qua
- ⚠️ **File Excel được coi như snapshot hoàn chỉnh của tồn kho**
- ✅ Vật tư mới → THÊM MỚI
- ✅ Vật tư đã có → CẬP NHẬT số lượng còn lại
- ✅ Vật tư không có trong file → SET SỐ LƯỢNG = 0 (coi như đã hết hàng)
- 💡 Chỉ cập nhật số lượng, không cập nhật thông tin khác (tên, giá...)
- 💡 Nếu muốn cập nhật thông tin khác, dùng chức năng "Sửa" thủ công
- 🚨 **QUAN TRỌNG**: Đảm bảo file Excel có đầy đủ tất cả vật tư còn tồn kho, vì những vật tư không có trong file sẽ bị set số lượng = 0

## Migration cần thiết
```sql
-- Đảm bảo cột dongia và dongia_usd là DECIMAL
-- Chạy file: add_dongia_usd_field_vattu_thanh_ly.sql
```

## Ngày tạo
2026-04-29
