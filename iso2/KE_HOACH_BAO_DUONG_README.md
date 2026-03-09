# Kế hoạch Bảo dưỡng Thiết bị Định kỳ

Module quản lý kế hoạch bảo dưỡng thiết bị định kỳ theo quý, hỗ trợ import dữ liệu từ file Excel.

## Tính năng

- ✅ Quản lý kế hoạch bảo dưỡng định kỳ theo năm và quý
- ✅ Import dữ liệu từ file Excel với format chuẩn
- ✅ Tải mẫu Excel để điền thông tin
- ✅ Xuất Excel kế hoạch bảo dưỡng
- ✅ Tìm kiếm theo tên thiết bị hoặc số S/N
- ✅ Lọc theo năm
- ✅ Thống kê số lượng thiết bị theo từng quý
- ✅ Xóa toàn bộ kế hoạch theo năm

## Format dữ liệu Excel

File Excel import có các cột:

| STT | Tên thiết bị | Số S/N   | Quí 1 | Quí 2 | Quí 3 | Quí 4 | Ghi chú                    |
|-----|--------------|----------|-------|-------|-------|-------|----------------------------|
| 1   | GTET         | 11533904 | TO    |       |       |       |                            |
| 2   | IDT          | 11680456 | TO    |       |       |       | Máy đo nhiệt độ cao 180°C  |
| 3   | DSNT         | 11534471 |       | TO    |       |       |                            |

**Chú thích:**
- **TO** = Có kế hoạch bảo dưỡng (Technical Overhaul)
- Để trống nếu không có kế hoạch trong quý đó

## Database Schema

### Bảng `ke_hoach_bao_duong_dinh_ky_iso`

```sql
- id: ID tự tăng
- nam: Năm kế hoạch (YEAR)
- ten_thietbi: Tên thiết bị
- so_serial: Số serial
- qui_1: Kế hoạch quí 1
- qui_2: Kế hoạch quí 2
- qui_3: Kế hoạch quí 3
- qui_4: Kế hoạch quí 4
- ghi_chu: Ghi chú
- created_at: Ngày tạo
- updated_at: Ngày cập nhật
- created_by: Người tạo
```

## Cài đặt

### 1. Tạo database table

```bash
php execute_create_ke_hoach_bao_duong_dinh_ky.php
```

Hoặc chạy trực tiếp SQL:
```bash
mysql -u username -p database_name < create_table_ke_hoach_bao_duong_dinh_ky.sql
```

### 2. Cấp quyền cho roles

```bash
php grant_kehoachbaoduong_permission.php
```

Hoặc chạy SQL:
```bash
mysql -u username -p database_name < add_kehoachbaoduong_permissions.sql
```

## Quyền hạn

- `kehoachbaoduong.view`: Xem danh sách kế hoạch bảo dưỡng
- `kehoachbaoduong.create`: Import kế hoạch từ Excel
- `kehoachbaoduong.edit`: Sửa kế hoạch bảo dưỡng
- `kehoachbaoduong.delete`: Xóa kế hoạch bảo dưỡng

## Sử dụng

### 1. Truy cập module

```
http://localhost/iso2/kehoachbaoduongdinhky.php
```

### 2. Import dữ liệu từ Excel

1. Click nút **"Import Excel"**
2. Chọn năm kế hoạch
3. Tải file mẫu Excel bằng nút **"Tải file mẫu Excel"**
4. Điền thông tin thiết bị vào file Excel:
   - Tên thiết bị
   - Số serial (S/N)
   - Đánh dấu "TO" vào quý nào có kế hoạch bảo dưỡng
   - Ghi chú (nếu cần)
5. Upload file Excel
6. Chọn **"Xóa dữ liệu cũ"** nếu muốn thay thế hoàn toàn kế hoạch năm đó
7. Click **"Import dữ liệu"**

### 3. Xem kế hoạch bảo dưỡng

- Chọn năm cần xem trong dropdown "Năm"
- Tìm kiếm thiết bị theo tên hoặc số S/N
- Xem thống kê số lượng thiết bị theo từng quý
- Các quý có màu sắc khác nhau để dễ phân biệt:
  - Quí 1: Xanh lá
  - Quí 2: Vàng
  - Quí 3: Cam
  - Quí 4: Đỏ

### 4. Xuất Excel

- Click nút **"Xuất Excel"** để tải kế hoạch bảo dưỡng của năm hiện tại
- File Excel sẽ có format chuẩn với đầy đủ thông tin

### 5. Xóa kế hoạch

- Click nút **"Xóa toàn bộ kế hoạch năm XXXX"** ở cuối trang
- Xác nhận xóa
- **Lưu ý**: Hành động này không thể hoàn tác!

## API Methods

### KeHoachBaoDuongDinhKyController

- `index()`: Hiển thị danh sách kế hoạch với lọc và tìm kiếm
- `import()`: Hiển thị form import Excel
- `processImport()`: Xử lý file Excel và lưu vào database
- `exportExcel()`: Xuất file Excel kế hoạch bảo dưỡng
- `delete()`: Xóa toàn bộ kế hoạch theo năm

## File Structure

```
iso2/
├── kehoachbaoduongdinhky.php                          # Entry point
├── download_template_bao_duong.php                    # Download mẫu Excel
├── controllers/
│   └── KeHoachBaoDuongDinhKyController.php           # Controller
├── views/
│   └── kehoachbaoduongdinhky/
│       ├── index.php                                  # Danh sách kế hoạch
│       └── import.php                                 # Form import Excel
├── create_table_ke_hoach_bao_duong_dinh_ky.sql      # SQL schema
├── execute_create_ke_hoach_bao_duong_dinh_ky.php    # Script tạo table
├── grant_kehoachbaoduong_permission.php              # Script cấp quyền
└── add_kehoachbaoduong_permissions.sql               # SQL cấp quyền
```

## Ví dụ sử dụng

### Import kế hoạch năm 2026

1. Truy cập: `http://localhost/iso2/kehoachbaoduongdinhky.php?action=import`
2. Chọn năm: **2026**
3. Tải mẫu Excel
4. Điền dữ liệu:
   ```
   STT | Tên TB | Số S/N   | Q1 | Q2 | Q3 | Q4 | Ghi chú
   1   | GTET   | 11533904 | TO |    |    |    |
   2   | IDT    | 11680456 | TO |    |    |    | Máy đo nhiệt độ cao 180°C
   3   | DSNT   | 11534471 |    | TO |    |    |
   ```
5. Upload và import

### Tìm kiếm thiết bị

- Tìm theo tên: `GTET`
- Tìm theo serial: `11533904`
- Kết quả sẽ hiển thị tất cả thiết bị khớp với từ khóa

## Troubleshooting

### Lỗi "Không tìm thấy bảng"

- Chạy lại script: `php execute_create_ke_hoach_bao_duong_dinh_ky.php`
- Hoặc import SQL thủ công: `create_table_ke_hoach_bao_duong_dinh_ky.sql`

### Lỗi "Không có quyền"

- Chạy script cấp quyền: `php grant_kehoachbaoduong_permission.php`
- Hoặc import SQL: `add_kehoachbaoduong_permissions.sql`
- Kiểm tra role của user trong quản lý phân quyền

### Lỗi import Excel

- Kiểm tra file Excel có đúng format không
- Đảm bảo cột đầu tiên là header (STT, Tên thiết bị,...)
- File phải là .xlsx hoặc .xls
- Kiểm tra kích thước file (max 5MB)

### Dữ liệu import bị trùng

- Chọn checkbox **"Xóa dữ liệu cũ của năm này"** khi import
- Hoặc xóa thủ công kế hoạch năm đó trước khi import lại

## Lưu ý

- Mỗi năm có một bộ kế hoạch riêng
- Có thể import nhiều lần, dữ liệu sẽ được thêm vào (nếu không chọn xóa dữ liệu cũ)
- "TO" (Technical Overhaul) là giá trị chuẩn, có thể dùng giá trị khác
- Có thể để trống các quý không có kế hoạch
- Ghi chú không bắt buộc
- Khi xóa kế hoạch năm, tất cả dữ liệu của năm đó sẽ bị xóa hoàn toàn

## Tính năng mở rộng (tương lai)

- [ ] Sửa từng dòng kế hoạch trực tiếp trên giao diện
- [ ] Thêm trạng thái thực hiện (Đã thực hiện / Chưa thực hiện)
- [ ] Nhắc nhở khi đến quý cần bảo dưỡng
- [ ] Liên kết với danh sách thiết bị trong hệ thống
- [ ] Export PDF kế hoạch bảo dưỡng
- [ ] Lịch sử thay đổi kế hoạch
