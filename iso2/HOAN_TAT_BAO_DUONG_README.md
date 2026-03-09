# Chức năng Check Thiết bị Đã Bảo dưỡng

## Tổng quan
Đã thêm chức năng đánh dấu và theo dõi trạng thái hoàn thành bảo dưỡng cho từng quý của mỗi thiết bị.

## Cài đặt

### 1. Chạy Migration SQL
Chạy lệnh sau để thêm cột trạng thái hoàn thành vào database:

**Cách 1: Sử dụng PHP Script**
```bash
php execute_add_hoantat_columns.php
```

**Cách 2: Import trực tiếp SQL**
- Đăng nhập phpMyAdmin hoặc MySQL client
- Chọn database `iso2`
- Import file `add_hoantat_columns.sql`

### 2. Cấu trúc Database Mới
Đã thêm 4 cột mới vào bảng `ke_hoach_bao_duong_dinh_ky_iso`:

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `qui_1_hoantat` | TINYINT(1) | Quý 1 đã hoàn thành (1=đã, 0=chưa) |
| `qui_2_hoantat` | TINYINT(1) | Quý 2 đã hoàn thành (1=đã, 0=chưa) |
| `qui_3_hoantat` | TINYINT(1) | Quý 3 đã hoàn thành (1=đã, 0=chưa) |
| `qui_4_hoantat` | TINYINT(1) | Quý 4 đã hoàn thành (1=đã, 0=chưa) |

## Tính năng

### 1. Đánh dấu hoàn thành
- Mỗi quý có checkbox "Hoàn thành" riêng
- Chỉ user có quyền `kehoachbaoduong.edit` mới thấy checkbox
- Click checkbox để đánh dấu/bỏ đánh dấu hoàn thành
- Cập nhật realtime qua AJAX

### 2. Hiển thị trạng thái
**Chưa hoàn thành:**
- Badge màu nhạt (green-100, yellow-100, orange-100, red-100)
- Text màu đậm tương ứng

**Đã hoàn thành:**
- Badge màu đậm (green-600, yellow-600, orange-600, red-600)
- Text màu trắng
- Icon check (✓) bên cạnh giá trị

### 3. Bộ lọc mới
Thêm dropdown **Trạng thái** với 3 tùy chọn:
- **Tất cả**: Hiển thị tất cả thiết bị
- **Đã hoàn thành**: Thiết bị có ít nhất 1 quý đã hoàn thành
- **Chưa hoàn thành**: Thiết bị chưa hoàn thành quý nào

### 4. Xuất Excel
File Excel xuất ra có 12 cột:
1. STT
2. Tên thiết bị
3. Số S/N
4. Quí 1
5. **Q1 Hoàn thành** (mới)
6. Quí 2
7. **Q2 Hoàn thành** (mới)
8. Quí 3
9. **Q3 Hoàn thành** (mới)
10. Quí 4
11. **Q4 Hoàn thành** (mới)
12. Ghi chú

Cột "Hoàn thành" hiển thị:
- "Đã hoàn thành" nếu đã check
- Rỗng nếu chưa check

## Files đã thay đổi

### Database
- `add_hoantat_columns.sql` - Migration script
- `execute_add_hoantat_columns.php` - PHP script thực thi migration

### Controller
- `controllers/KeHoachBaoDuongDinhKyController.php`
  - Thêm method `updateHoanTat()` - Xử lý AJAX cập nhật trạng thái
  - Cập nhật `index()` - Nhận parameter `trangthai`
  - Cập nhật `getAll()` - Lọc theo trạng thái hoàn thành
  - Cập nhật `exportExcel()` - Xuất file với cột trạng thái

### View
- `views/kehoachbaoduongdinhky/index.php`
  - Thêm dropdown filter "Trạng thái"
  - Cập nhật 4 cột Quý hiển thị checkbox hoàn thành
  - Thêm JavaScript xử lý sự kiện checkbox
  - Badge thay đổi màu khi đã hoàn thành

### Entry Point
- `kehoachbaoduongdinhky.php`
  - Thêm case `updateHoanTat` trong switch
  - Kiểm tra quyền `kehoachbaoduong.edit`

## Quyền truy cập
Để đánh dấu hoàn thành, user cần có quyền:
```
kehoachbaoduong.edit
```

## Workflow sử dụng

### Đánh dấu hoàn thành
1. Truy cập trang Kế hoạch Bảo dưỡng
2. Tìm thiết bị cần đánh dấu
3. Tại cột quý tương ứng, tick checkbox "Hoàn thành"
4. Trang tự động reload, badge chuyển màu đậm với icon ✓

### Xem thiết bị đã hoàn thành
1. Chọn năm muốn xem
2. Dropdown "Trạng thái" → chọn "Đã hoàn thành"
3. Danh sách chỉ hiển thị thiết bị có ít nhất 1 quý đã hoàn thành

### Xuất báo cáo
1. Áp dụng các filter (năm, quý, trạng thái, tìm kiếm)
2. Click "Xuất Excel"
3. File Excel chứa cột trạng thái hoàn thành cho từng quý

## API Endpoint

### POST kehoachbaoduongdinhky.php?action=updateHoanTat
**Request Body (JSON):**
```json
{
  "id": 123,
  "qui": 1,
  "hoantat": 1
}
```

**Response (JSON):**
```json
{
  "success": true,
  "message": "Đã đánh dấu hoàn thành"
}
```

**Parameters:**
- `id` (int): ID thiết bị
- `qui` (int): Số quý (1-4)
- `hoantat` (int): 1=hoàn thành, 0=chưa hoàn thành

## Lưu ý
- Checkbox chỉ hiển thị khi quý có kế hoạch (giá trị khác rỗng)
- Cần quyền `kehoachbaoduong.edit` để thấy và sử dụng checkbox
- Trạng thái được lưu riêng cho từng quý của mỗi thiết bị
- Import Excel mới sẽ reset trạng thái hoàn thành về 0

## Troubleshooting

### Checkbox không hiển thị
- Kiểm tra quyền user: `SELECT permissions FROM roles WHERE id = [user_role_id]`
- Đảm bảo có quyền `kehoachbaoduong.edit`

### Lỗi khi click checkbox
- Kiểm tra console browser (F12) xem lỗi JavaScript
- Kiểm tra response từ server
- Đảm bảo database đã thêm 4 cột mới

### Migration lỗi
Nếu cột đã tồn tại, chạy:
```sql
SHOW COLUMNS FROM ke_hoach_bao_duong_dinh_ky_iso LIKE 'qui%hoantat';
```

Nếu chưa có cột nào, import lại `add_hoantat_columns.sql`.
