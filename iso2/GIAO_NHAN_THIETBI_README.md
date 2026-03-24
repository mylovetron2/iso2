# Module Giao Nhận Thiết Bị Kiểm Định

## Tổng quan

Module **Giao Nhận Thiết Bị** được thiết kế để quản lý quy trình giao nhận thiết bị khi gửi đi kiểm định và nhận về sau kiểm định. Hệ thống theo dõi hai luồng chính:

1. **Giao Đi Kiểm Định (Team → Xưởng SCTBĐVL)**: Team giao thiết bị cho xưởng để gửi đi kiểm định
2. **Nhận Về Kiểm Định (Xưởng SCTBĐVL → Team)**: Xưởng trả lại thiết bị cho Team sau khi kiểm định xong

## Cấu trúc Files

### 1. Database Schema

**File:** `create_table_giao_nhan_thietbi.sql`
- Tạo bảng `giao_nhan_thietbi_iso` với 15 trường
- Foreign keys liên kết với `thietbi_iso`
- Hỗ trợ tự liên kết thông qua `phieu_giao_id` (phiếu nhận về liên kết với phiếu giao đi)

**Cấu trúc chính:**
```sql
- id (PK)
- thietbi_id (FK → thietbi_iso)
- ten_thietbi, ky_ma_hieu
- loai_giao_nhan: ENUM('giao_di_kd', 'nhan_ve_kd')
- nguoi_giao, donvi_giao, ngay_giao
- nguoi_nhan, donvi_nhan, ngay_nhan
- noidung_kiemdinh (kết quả kiểm định)
- trangthai: ENUM('cho_nhan', 'da_nhan', 'hoan_thanh')
- phieu_giao_id (liên kết phiếu nhận về với phiếu giao đi)
- created_by, created_at, updated_at
```

### 2. Permissions

**File:** `add_giaonhanthietbi_permissions.sql`

6 permissions được định nghĩa:
- `giaonhanthietbi.view` - Xem danh sách
- `giaonhanthietbi.create_giao` - Tạo phiếu giao đi
- `giaonhanthietbi.create_nhan` - Tạo phiếu nhận về
- `giaonhanthietbi.edit` - Sửa phiếu
- `giaonhanthietbi.delete` - Xóa phiếu
- `giaonhanthietbi.export` - Xuất báo cáo

### 3. Controller

**File:** `controllers/GiaoNhanThietBiController.php`

Chứa 7 phương thức chính:

#### `index()`
- Hiển thị danh sách phiếu với filters
- Hỗ trợ tìm kiếm theo: tên thiết bị, loại, trạng thái, đơn vị, khoảng thời gian
- JOIN với `thietbi_iso` và `donvi_iso` để lấy thông tin đầy đủ

#### `createGiaoDi()` & `storeGiaoDi()`
- Form tạo phiếu giao đi kiểm định
- Auto-fill tên thiết bị và ký mã hiệu từ dropdown
- `donvi_nhan` cố định là "SCTBDVL" (không thể thay đổi)
- `trangthai` tự động set thành 'da_nhan'
- Validation thiết bị tồn tại
- Transaction-safe insert

#### `createNhanVe()` & `storeNhanVe()`
- Form tạo phiếu nhận về sau kiểm định
- Chỉ hiển thị các phiếu giao đi chưa có phiếu nhận về tương ứng
- Auto-fill thông tin từ phiếu giao đi đã chọn
- Lưu kết quả kiểm định vào `noidung_kiemdinh`
- Link với phiếu giao đi qua `phieu_giao_id`
- `trangthai` tự động set thành 'hoan_thanh'

#### `view($id)`
- Hiển thị chi tiết phiếu
- Nếu là phiếu nhận về, hiển thị thông tin phiếu giao đi liên quan
- Show kết quả kiểm định (nếu có)

#### `delete($id)`
- Xóa phiếu với kiểm tra cascade
- Không cho xóa phiếu giao đi nếu đã có phiếu nhận về liên kết

### 4. Router

**File:** `giaonhanthietbi.php`

Route handler chính với actions:
- `index` - Danh sách (mặc định)
- `create_giao_di` - Form giao đi
- `store_giao_di` - Lưu giao đi
- `create_nhan_ve` - Form nhận về
- `store_nhan_ve` - Lưu nhận về
- `view` - Chi tiết
- `delete` - Xóa

Mỗi action có permission check riêng.

### 5. Views

#### `views/giaonhanthietbi/index.php`
- Danh sách phiếu với bảng responsive
- Filters: tìm kiếm, loại, trạng thái, đơn vị, từ ngày, đến ngày
- 2 nút chính: "Giao Đi Kiểm Định" (blue) và "Nhận Về Kiểm Định" (green)
- Badges màu hiển thị loại và trạng thái
- Thao tác: Xem, Xóa (có confirmation)

#### `views/giaonhanthietbi/giao_di.php`
- Form 3 phần:
  1. **Thông tin thiết bị**: Dropdown chọn thiết bị (auto-fill tên + ký mã hiệu)
  2. **Bên giao (Team)**: Người giao, Đơn vị giao, Ngày giao
  3. **Bên nhận (Us)**: Người nhận, Đơn vị nhận (SCTBDVL - readonly), Ngày nhận
- JavaScript auto-fill từ data attributes

#### `views/giaonhanthietbi/nhan_ve.php`
- Form 4 phần:
  1. **Chọn phiếu giao đi**: Dropdown các phiếu đang chờ nhận về
  2. **Thông tin thiết bị** (readonly, auto-fill từ phiếu đã chọn)
  3. **Thông tin trả lại (Us → Team)**: Người giao, Ngày giao, Người nhận, Đơn vị nhận
  4. **Kết quả kiểm định**: Textarea nhập nội dung kiểm định
- JavaScript auto-fill khi chọn phiếu giao đi

#### `views/giaonhanthietbi/view.php`
- Layout 2 cột:
  - **Cột chính**: Thông tin chung, Thiết bị, Bên giao, Bên nhận, Kết quả kiểm định, Ghi chú
  - **Sidebar**: Thông tin hệ thống (người tạo, ngày tạo, cập nhật), Thao tác nhanh
- Hiển thị phiếu giao đi liên quan (nếu là phiếu nhận về)
- Link sang chi tiết thiết bị

### 6. Menu Integration

**File:** `views/layouts/header.php`

Thêm menu item mới:
```php
<!-- 3.6. Giao Nhận Thiết Bị -->
<?php if (isLoggedIn() && hasPermission('giaonhanthietbi.view')): ?>
<li>
    <a href="/iso2/giaonhanthietbi.php">
        <i class="fas fa-exchange-alt mr-2"></i> Giao Nhận Thiết Bị
    </a>
</li>
<?php endif; ?>
```

Menu xuất hiện sau "Vật tư thanh lý" trong sidebar.

## Cài đặt

### Bước 1: Chạy Setup Script

```bash
php setup_giaonhanthietbi.php
```

Script này sẽ:
1. Tạo bảng `giao_nhan_thietbi_iso`
2. Thêm 6 permissions vào `permissions_iso`
3. Gán tất cả permissions cho admin (role_id = 1)
4. Hiển thị cấu trúc bảng để xác nhận

### Bước 2: Kiểm tra Menu

- Đăng nhập với tài khoản admin
- Menu "Giao Nhận Thiết Bị" sẽ xuất hiện trong sidebar
- Click vào để truy cập module

### Bước 3: Test Workflow

1. **Tạo phiếu giao đi:**
   - Click "Giao Đi Kiểm Định"
   - Chọn thiết bị (tên tự động điền)
   - Nhập người giao (Team), đơn vị giao, ngày giao
   - Nhập người nhận (Us), ngày nhận
   - Lưu → Trạng thái = "Đã nhận"

2. **Tạo phiếu nhận về:**
   - Click "Nhận Về Kiểm Định"
   - Chọn phiếu giao đi từ dropdown
   - Thông tin thiết bị tự động điền
   - Nhập người giao (Us), người nhận (Team)
   - Nhập kết quả kiểm định
   - Lưu → Trạng thái = "Hoàn thành"

## Quy trình làm việc

### Luồng 1: Team → Xưởng (Giao đi)
```
Team có thiết bị cần kiểm định
    ↓
Team giao thiết bị cho Xưởng SCTBĐVL
    ↓
Xưởng nhận thiết bị (tạo phiếu giao_di_kd)
    ↓
Trạng thái: da_nhan (Xưởng đã nhận)
```

### Luồng 2: Xưởng → Team (Nhận về)
```
Xưởng gửi thiết bị đi kiểm định tại đơn vị bên ngoài
    ↓
Nhận kết quả kiểm định
    ↓
Xưởng trả lại thiết bị cho Team (tạo phiếu nhan_ve_kd)
    ↓ 
Link với phiếu giao đi ban đầu (phieu_giao_id)
    ↓
Ghi lại kết quả kiểm định (noidung_kiemdinh)
    ↓
Trạng thái: hoan_thanh
```

## Trạng thái phiếu

| Trạng thái | Ý nghĩa | Màu hiển thị |
|------------|---------|--------------|
| `cho_nhan` | Chờ nhận (chưa sử dụng trong workflow hiện tại) | Vàng |
| `da_nhan` | Đã nhận (phiếu giao đi đã được xưởng nhận) | Xanh dương |
| `hoan_thanh` | Hoàn thành (phiếu nhận về, đã trả lại Team) | Xanh lá |

## Query Examples

### Lấy tất cả phiếu giao đi chưa có phiếu nhận về
```sql
SELECT * FROM giao_nhan_thietbi_iso 
WHERE loai_giao_nhan = 'giao_di_kd'
AND id NOT IN (
    SELECT phieu_giao_id FROM giao_nhan_thietbi_iso 
    WHERE phieu_giao_id IS NOT NULL
);
```

### Lấy phiếu với thông tin thiết bị và đơn vị
```sql
SELECT 
    g.*,
    t.ten_thiet_bi,
    t.ky_ma_hieu,
    dg.ten_don_vi as ten_donvi_giao,
    dn.ten_don_vi as ten_donvi_nhan
FROM giao_nhan_thietbi_iso g
LEFT JOIN thietbi_iso t ON g.thietbi_id = t.id
LEFT JOIN donvi_iso dg ON g.donvi_giao = dg.ma_don_vi
LEFT JOIN donvi_iso dn ON g.donvi_nhan = dn.ma_don_vi
ORDER BY g.created_at DESC;
```

## Lưu ý Kỹ thuật

### 1. Transaction Safety
- Controller sử dụng `PDO::beginTransaction()` cho các operations insert/update
- Rollback tự động khi có exception
- Error logging vào `logs/error.log`

### 2. Data Integrity
- Foreign key constraint với `thietbi_iso`
- Self-referencing foreign key (`phieu_giao_id`)
- CASCADE DELETE không được bật để tránh xóa nhầm

### 3. Auto-fill JavaScript
- Views sử dụng `data-*` attributes để lưu metadata
- Event listener `change` để update form fields
- Format date từ YYYY-MM-DD sang DD/MM/YYYY

### 4. Permission Checks
- Router kiểm tra permission cho mỗi action
- View kiểm tra permission để hiển thị buttons/links
- Separate permissions cho create_giao và create_nhan

### 5. Responsive Design
- Tailwind CSS grid cho filters (1 col mobile, 3 col tablet, 6 col desktop)
- Overflow-x-auto cho bảng
- Mobile-friendly forms

## API Response Format

### Success Response (Session-based)
```php
$_SESSION['success'] = 'Đã lưu phiếu thành công';
header('Location: giaonhanthietbi.php');
```

### Error Response
```php
$_SESSION['error'] = 'Thiết bị không tồn tại';
header('Location: giaonhanthietbi.php?action=create_giao_di');
```

## Future Enhancements

### Có thể mở rộng:
1. **Export Excel/PDF**: Thêm method `exportExcel()` trong controller
2. **Signature Upload**: Thêm trường `chu_ky_giao`, `chu_ky_nhan` để upload chữ ký số
3. **Email Notification**: Gửi email tự động khi phiếu được tạo
4. **Barcode/QR**: Generate mã vạch cho mỗi phiếu
5. **Attachment**: Upload file đính kèm (hình ảnh thiết bị, kết quả kiểm định)
6. **History Log**: Bảng riêng để log tất cả thay đổi trạng thái
7. **Dashboard**: Thống kê số phiếu theo trạng thái, đơn vị, tháng

## Troubleshooting

### Lỗi "Permission denied"
→ Kiểm tra `role_permissions_iso` đã có permissions cho role của user chưa

### Dropdown thiết bị rỗng
→ Kiểm tra bảng `thietbi_iso` có dữ liệu chưa

### Form không auto-fill
→ Mở DevTools Console để check JavaScript errors

### Database connection failed
→ Kiểm tra file `config/database.php` và kết nối server

## Liên hệ & Hỗ trợ

Module được phát triển theo yêu cầu dựa trên form giấy hiện có. Mọi câu hỏi hoặc đề xuất cải tiến xin liên hệ team phát triển.

---

**Phiên bản:** 1.0  
**Ngày tạo:** 2024  
**Tác giả:** GitHub Copilot
