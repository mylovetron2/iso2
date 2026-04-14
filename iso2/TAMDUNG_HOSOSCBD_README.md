# Tính năng Tạm dừng Hồ sơ SCBĐ - Tài liệu

## Tổng quan

Tính năng cho phép **tạm dừng** và **tiếp tục** hồ sơ sửa chữa bảo dưỡng (SCBĐ) với đầy đủ lịch sử theo dõi.

## Các tính năng đã triển khai ✅

### 1. Tạm dừng hồ sơ với lý do bắt buộc ✅
- Người dùng có quyền `hososcbd.edit` có thể tạm dừng hồ sơ
- Lý do tạm dừng là **bắt buộc** khi thực hiện
- Lưu thông tin: người thực hiện, ngày giờ, lý do

### 2. Tiếp tục hồ sơ đã tạm dừng ✅
- Cho phép tiếp tục hồ sơ đã tạm dừng
- Ghi chú khi tiếp tục (tùy chọn)
- Tự động khôi phục trạng thái hoạt động

### 3. Lưu trữ đầy đủ lịch sử ✅
- Mỗi lần tạm dừng/tiếp tục đều được ghi lại
- Xem lịch sử theo từng hồ sơ
- Báo cáo tổng hợp tất cả thao tác

### 4. Hiển thị cảnh báo khi hồ sơ tạm dừng ✅
- Badge **"TẠM DỪNG"** màu cam hiển thị trong danh sách
- Nút "Tiếp tục" thay thế nút "Tạm dừng" khi hồ sơ đang tạm dừng
- Background màu cam nhạt cho các row đang tạm dừng

### 5. Tích hợp báo cáo ✅
- Hồ sơ tạm dừng được đánh dấu trong database
- Dễ dàng loại trừ khỏi báo cáo SCBĐ bằng cách filter `WHERE is_tamdung = 0`
- Có thể include lại nếu cần thiết

### 6. Báo cáo lịch sử tạm dừng ✅
- Trang `baocao_hososcbd_tamdung.php`: báo cáo lịch sử đầy đủ
  - Filter `?trangthai=dang_tam_dung`: Danh sách hồ sơ đang tạm dừng
  - Filter `?trangthai=all`: Toàn bộ lịch sử
- Bộ lọc: trạng thái, khoảng thời gian, đơn vị
- **Note:** Trang `hososcbd_tamdung_list.php` đã được gộp vào báo cáo (thống kê card màu cam)

## Cấu trúc Database

### Bảng: `hososcbd_tamdung`
```sql
CREATE TABLE hososcbd_tamdung (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hoso VARCHAR(50) NOT NULL,
    trangthai ENUM('tamdung', 'tieptuc') NOT NULL,
    nguoi_thuchien VARCHAR(100) NOT NULL,
    ngay_thuchien DATETIME NOT NULL,
    lydo_tamdung TEXT,
    ghichu_tieptuc TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_hoso (hoso),
    INDEX idx_trangthai (trangthai),
    INDEX idx_ngay (ngay_thuchien),
    
    FOREIGN KEY (hoso) REFERENCES hososcbd_iso(hoso) ON DELETE CASCADE
)
```

### Cột mới trong `hososcbd_iso`:
```sql
ALTER TABLE hososcbd_iso 
ADD COLUMN is_tamdung TINYINT(1) DEFAULT 0 COMMENT 'Trạng thái tạm dừng: 0=hoạt động, 1=tạm dừng',
ADD INDEX idx_is_tamdung (is_tamdung);
```

## Files đã tạo

### 1. Migration & Database
- `migrations/create_hososcbd_tamdung_table.sql` - Migration SQL
- `run_migration_tamdung.php` - Script chạy migration

### 2. Model
- `models/HoSoScBdTamDung.php` - Model quản lý pause/resume

### 3. API
- `api/hososcbd_tamdung.php` - REST API endpoints
  - `POST ?action=tam_dung` - Tạm dừng hồ sơ
  - `POST ?action=tiep_tuc` - Tiếp tục hồ sơ
  - `GET ?action=check_status&hoso=X` - Kiểm tra trạng thái
  - `GET ?action=lich_su&hoso=X` - Lấy lịch sử

### 4. Views & UI
- `views/hososcbd/partials/tamdung_modals.php` - 3 modals:
  - Modal tạm dừng
  - Modal tiếp tục
  - Modal lịch sử
- `views/hososcbd/index.php` - Đã tích hợp nút & cảnh báo

### 5. Reports
- `baocao_hososcbd_tamdung.php` - Báo cáo lịch sử tạm dừng/tiếp tục
  - Thống kê card màu cam → filter danh sách đang tạm dừng
  - Thống kê card màu xanh → xem toàn bộ lịch sử

## Hướng dẫn triển khai

### Bước 1: Chạy Migration
```
Truy cập: http://your-domain/iso2/run_migration_tamdung.php
```
Script sẽ tự động:
1. Tạo bảng `hososcbd_tamdung`
2. Thêm cột `is_tamdung` vào `hososcbd_iso`

### Bước 2: Kiểm tra quyền
Đảm bảo user có quyền `hososcbd.edit` để sử dụng tính năng tạm dừng/tiếp tục.

### Bước 3: Sử dụng
1. Vào trang **Hồ sơ SCBĐ** (`hososcbd.php`)
2. Tại cột "Chi tiết", click nút **"Tạm dừng"** màu vàng
3. Nhập lý do tạm dừng (bắt buộc) và xác nhận
4. Hồ sơ sẽ hiển thị badge **"TẠM DỪNG"** màu cam
5. Click **"Tiếp tục"** để khôi phục hồ sơ
6. Click **"Lịch sử"** để xem chi tiết thay đổi

## API Usage Examples

### Tạm dừng hồ sơ
```javascript
const formData = new FormData();
formData.append('action', 'tam_dung');
formData.append('hoso', '1997-1');
formData.append('lydo_tamdung', 'Thiếu linh kiện chờ đặt hàng');

const response = await fetch('/iso2/api/hososcbd_tamdung.php', {
    method: 'POST',
    body: formData
});

const data = await response.json();
// {success: true, message: "Tạm dừng hồ sơ thành công", id: 123}
```

### Tiếp tục hồ sơ
```javascript
const formData = new FormData();
formData.append('action', 'tiep_tuc');
formData.append('hoso', '1997-1');
formData.append('ghichu_tieptuc', 'Đã nhận được linh kiện');

const response = await fetch('/iso2/api/hososcbd_tamdung.php', {
    method: 'POST',
    body: formData
});
```

### Kiểm tra trạng thái
```javascript
const response = await fetch('/iso2/api/hososcbd_tamdung.php?action=check_status&hoso=1997-1');
const data = await response.json();
// {success: true, is_tamdung: true, info: {...}}
```

## Tích hợp với Báo cáo

Để loại trừ hồ sơ tạm dừng khỏi báo cáo SCBĐ, thêm điều kiện:

```sql
SELECT * FROM hososcbd_iso 
WHERE is_tamdung = 0  -- Chỉ lấy hồ sơ đang hoạt động
```

Hoặc để bao gồm cả tạm dừng (nếu cần):
```sql
SELECT * FROM hososcbd_iso 
-- Không filter is_tamdung, lấy tất cả
```

## UI Components

### Nút Tạm dừng (màu vàng)
```html
<button onclick="openTamDungModal('1997-1', 'MAY123', 'ABC-001')" 
        class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded">
    <i class="fas fa-pause-circle"></i> Tạm dừng
</button>
```

### Nút Tiếp tục (màu xanh)
```html
<button onclick="openTiepTucModal('1997-1', 'MAY123', 'ABC-001')" 
        class="bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded">
    <i class="fas fa-play-circle"></i> Tiếp tục
</button>
```

### Nút Lịch sử (màu xám)
```html
<button onclick="openLichSuModal('1997-1')" 
        class="bg-gray-500 hover:bg-gray-600 text-white px-2 py-1 rounded">
    <i class="fas fa-history"></i> Lịch sử
</button>
```

### Badge Tạm dừng
```html
<span class="bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded">
    <i class="fas fa-pause-circle"></i> TẠM DỪNG
</span>
```

## Best Practices

1. **Lý do tạm dừng bắt buộc**: Giúp theo dõi và quản lý tốt hơn
2. **Lịch sử đầy đủ**: Mọi thay đổi đều được ghi lại
3. **Quyền phân quyền**: Chỉ user có `hososcbd.edit` mới được thao tác
4. **Cascade delete**: Khi xóa hồ sơ, lịch sử tạm dừng cũng tự động xóa
5. **Performance**: Index trên `is_tamdung`, `hoso`, `ngay_thuchien` để query nhanh

## Báo lỗi & Hỗ trợ

Nếu gặp lỗi:
1. Kiểm tra database đã có bảng `hososcbd_tamdung` chưa
2. Kiểm tra cột `is_tamdung` trong `hososcbd_iso`
3. Kiểm tra quyền user: `hososcbd.edit`
4. Xem console log trên browser (F12)
5. Kiểm tra error log PHP

## Changelog

### v1.0 - 2026-04-10
- ✅ Tạo bảng hososcbd_tamdung
- ✅ Thêm cột is_tamdung vào hososcbd_iso
- ✅ Model: HoSoScBdTamDung
- ✅ API: tam_dung, tiep_tuc, check_status, lich_su
- ✅ UI: Modal quản lý tạm dừng (consolidated)
- ✅ Tích hợp buttons vào hososcbd/index.php
- ✅ Badge cảnh báo cho hồ sơ tạm dừng
- ✅ Báo cáo lịch sử: baocao_hososcbd_tamdung.php (gộp cả danh sách tạm dừng)
- ✅ Migration script: run_migration_tamdung.php

## Screenshots & Demo

### Danh sách Hồ sơ SCBĐ
- Badge **"TẠM DỪNG"** hiển thị bên cạnh số hồ sơ
- Nút **"Tạm dừng"** / **"Tiếp tục"** trong cột Chi tiết
- Nút **"Lịch sử"** để xem thay đổi

### Modal Tạm dừng
- Input lý do tạm dừng (required)
- Warning: hồ sơ sẽ được loại khỏi báo cáo
- Xác nhận hoặc hủy

### Modal Tiếp tục
- Hiển thị thông tin tạm dừng trước đó
- Ghi chú khi tiếp tục (optional)
- Xác nhận hoặc hủy

### Modal Lịch sử
- Danh sách đầy đủ thao tác tạm dừng/tiếp tục
- Timeline với icon + màu sắc phân biệt
- Người thực hiện, ngày giờ, lý do/ghi chú

---

**Phát triển bởi**: Team ABC  
**Ngày**: 10/04/2026  
**Version**: 1.0
