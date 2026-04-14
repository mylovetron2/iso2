# Schema Thực Tế Bảng hososcbd_tamdung (Production)

## Tổng quan
Bảng `hososcbd_tamdung` trong production có schema **RẤT KHÁC** với migration ban đầu. Code đã được cải tiến để tự động phát hiện và xử lý các cột động.

## QUAN TRỌNG: ENUM Values

**Production schema dùng:**
- ✅ `'dang_tam_dung'` (13 ký tự) - Đang tạm dừng
- ✅ `'da_tiep_tuc'` (11 ký tự) - Đã tiếp tục

**KHÔNG dùng:**
- ❌ `'tamdung'` - SAI!
- ❌ `'tieptuc'` - SAI!

## Các Cột Trong Schema Production

### Cột Bắt Buộc (NOT NULL)
1. **id** - INT AUTO_INCREMENT PRIMARY KEY
2. **hoso** - VARCHAR(50) NOT NULL - Mã hồ sơ
3. **mavt** - VARCHAR(50) NOT NULL - Mã vật tư (từ hososcbd_iso)
4. **ngay_tamdung** - DATETIME NOT NULL - Ngày tạm dừng
5. **trangthai** - ENUM('dang_tam_dung','da_tiep_tuc') - Trạng thái
6. **nguoi_thuchien** - VARCHAR(100) NOT NULL - Người thực hiện
7. **ngay_thuchien** - DATETIME NOT NULL - Ngày giờ thực hiện (DEFAULT CURRENT_TIMESTAMP)
8. **created_at** - TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
9. **updated_at** - TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### Cột Tùy Chọn (NULL - Nullable)
10. **somay** - VARCHAR(50) NULL - Số máy (từ hososcbd_iso)
11. **model** - VARCHAR(100) NULL - Model thiết bị
12. **maql** - VARCHAR(100) NULL - Mã quản lý
13. **nguoi_tamdung** - VARCHAR(100) NULL - Người tạm dừng
14. **lydo_tamdung** - TEXT NULL - Lý do tạm dừng
15. **ngay_tieptuc** - DATETIME NULL - Ngày tiếp tục
16. **nguoi_tieptuc** - VARCHAR(100) NULL - Người tiếp tục
17. **ghichu_tieptuc** - TEXT NULL - Ghi chú khi tiếp tục
18. **thoigian_tamdung_gio** - INT(11) NULL - Thời gian tạm dừng (giờ)
19. **thoigian_tamdung_ngay** - DECIMAL(10,2) NULL - Thời gian tạm dừng (ngày)

## Cách Code Xử Lý

### Phát Hiện Cột Động
```php
$columns = $this->db->query("SHOW COLUMNS FROM hososcbd_tamdung")->fetchAll(PDO::FETCH_COLUMN);
$hasMavt = in_array('mavt', $columns);
$hasSomay = in_array('somay', $columns);
$hasPhieu = in_array('phieu', $columns);
$hasNgayTamdung = in_array('ngay_tamdung', $columns);
$hasNgayTieptuc = in_array('ngay_tieptuc', $columns);
```

### Xử Lý Trong tamDungHoSo()
- **hoso**: Tham số đầu vào ✓
- **trangthai**: `'dang_tam_dung'` ✓
- **nguoi_thuchien**: Tham số đầu vào ✓
- **ngay_thuchien**: NOW() ✓
- **lydo_tamdung**: Tham số đầu vào ✓
- **mavt, somay, phieu**: Query từ `hososcbd_iso` ✓
- **ngay_tamdung**: NOW() ✓

```php
// Lấy thông tin từ bảng gốc
$hosoInfo = $db->query("SELECT mavt, somay, phieu FROM hososcbd_iso WHERE hoso = ?")->fetch();

// Thêm vào INSERT nếu cột tồn tại
if ($hasMavt && $hosoInfo['mavt']) {
    $insertCols[] = 'mavt';
    $params[] = $hosoInfo['mavt'];
}

if ($hasNgayTamdung) {
    $insertCols[] = 'ngay_tamdung';
    $insertPlaceholders[] = 'NOW()';
}
```

### Xử Lý Trong tiepTucHoSo()
- **hoso**: Tham số đầu vào ✓
- **trangthai**: `'da_tiep_tuc'` ✓
- **nguoi_thuchien**: Tham số đầu vào ✓
- **ngay_thuchien**: NOW() ✓
- **ghichu_tieptuc**: Tham số đầu vào (optional) ✓
- **mavt, somay, phieu**: Query từ `hososcbd_iso` ✓
- **ngay_tamdung**: Copy từ record pause gần nhất ✓
- **ngay_tieptuc**: NOW() ✓

```php
// Lấy ngay_tamdung từ record pause gần nhất
$lastPauseRecord = $db->query("
    SELECT ngay_tamdung 
    FROM hososcbd_tamdung 
    WHERE hoso = ? AND trangthai = 'dang_tam_dung' 
    ORDER BY id DESC LIMIT 1
")->fetch();
$ngayTamdungValue = $lastPauseRecord ? $lastPauseRecord['ngay_tamdung'] : date('Y-m-d H:i:s');

if ($hasNgayTamdung) {
    $insertCols[] = 'ngay_tamdung';
    $params[] = $ngayTamdungValue; // Copy từ pause record
}

if ($hasNgayTieptuc) {
    $insertCols[] = 'ngay_tieptuc';
    $insertPlaceholders[] = 'NOW()';
}
```

## Lỗi Đã Fix

### Lỗi 1: Field 'mavt' doesn't have a default value
- **Nguyên nhân**: INSERT không chỉ định cột mavt nhưng cột NOT NULL
- **Giải pháp**: Query mavt từ hososcbd_iso và thêm vào INSERT ✓

### Lỗi 2: Field 'ngay_tamdung' doesn't have a default value
- **Nguyên nhân**: INSERT không chỉ định cột ngay_tamdung nhưng cột NOT NULL
- **Giải pháp**: 
  - Với record 'dang_tam_dung': Set ngay_tamdung = NOW() ✓
  - Với record 'da_tiep_tuc': Copy từ record pause gần nhất ✓

### Lỗi 3: Data truncated for column 'trangthai'
- **Nguyên nhân**: Code dùng `'tamdung'`, `'tieptuc'` nhưng ENUM chỉ chấp nhận `'dang_tam_dung'`, `'da_tiep_tuc'`
- **Giải pháp**: Sửa toàn bộ code dùng đúng ENUM values ✓

### Lỗi 4: Column 'ngay_tamdung' cannot be null
- **Nguyên nhân**: Cố set NULL cho cột NOT NULL trong tiepTucHoSo()
- **Giải pháp**: Copy ngay_tamdung từ record pause thay vì set NULL ✓

## Lợi Ích Của Giải Pháp

1. **Tương Thích Ngược**: Code hoạt động với schema tối thiểu hoặc mở rộng
2. **Không Cần Sửa Migration**: Chấp nhận mọi schema variation
3. **Zero Downtime**: Không cần ALTER TABLE production
4. **Future-Proof**: Tự động adapt với cột mới
5. **Backward Compatible**: Hỗ trợ cả legacy values ('tamdung' → 'dang_tam_dung')

## Best Practices

**KHÔNG BAO GIỜ** giả định schema cố định khi:
- Deploy code trước migration
- Nhiều môi trường khác nhau (dev/staging/production)
- Có lịch sử manual ALTER TABLE

**LUÔN LUÔN** kiểm tra:
```php
$columns = $this->db->query("SHOW COLUMNS FROM table")->fetchAll(PDO::FETCH_COLUMN);
if (in_array('column_name', $columns)) {
    // Safe to use column
}
```

## Cập Nhật Cuối
- **Ngày**: 2026-04-10
- **Phiên bản**: 1.3 (Fixed ngay_tamdung NOT NULL constraint)
- **File**: models/HoSoScBdTamDung.php
