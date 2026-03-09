# Phiếu Kiểm Soát Vật Tư

Module quản lý phiếu kiểm soát vật tư thanh lý, theo dõi vật tư nhận và tiêu hao trong các công việc bảo dưỡng, sửa chữa thiết bị.

## Tính năng

- ✅ Tạo phiếu kiểm soát vật tư với tự động tạo số phiếu
- ✅ Quản lý danh sách vật tư nhận và tiêu hao
- ✅ Tìm kiếm và lọc phiếu theo trạng thái
- ✅ In phiếu với định dạng chuẩn
- ✅ Hủy phiếu khi cần thiết
- ✅ Phân quyền theo role

## Database Schema

### Bảng `phieu_kiem_soat_vattu_iso`
Lưu thông tin header của phiếu:
- `id`: ID tự tăng
- `so_phieu`: Số phiếu tự động (format: PKSV-YYYYMM-XXX)
- `loai_congviec`: Loại công việc (BD theo kế hoạch / KT, BD, SC, gia công đột xuất)
- `bophan_dathang`: Bộ phận đặt hàng
- `ten_thietbi`: Tên thiết bị
- `ky_mahieu`: Ký mã hiệu
- `nguoi_lap_phieu`: Người lập phiếu
- `bophan_nguoilap`: Bộ phận người lập
- `phieu_xuat_kho_so`: Số phiếu xuất kho
- `ngay_xuat_kho`: Ngày xuất kho
- `trangthai`: Trạng thái (dang_thuc_hien/hoan_thanh/huy)
- `ghi_chu`: Ghi chú
- `created_at`: Ngày tạo
- `updated_at`: Ngày cập nhật
- `created_by`: Người tạo

### Bảng `phieu_kiem_soat_vattu_chitiet_iso`
Lưu chi tiết vật tư trong phiếu:
- `id`: ID tự tăng
- `phieu_id`: ID phiếu (foreign key)
- `vattu_stt`: STT vật tư từ bảng `vattu_thanh_ly_iso` (foreign key)
- `soluong_nhan`: Số lượng nhận
- `soluong_tieuhao`: Số lượng tiêu hao
- `ghichu`: Ghi chú
- `created_at`: Ngày tạo

## Cài đặt

### 1. Tạo database tables
```bash
php execute_create_phieu_kiem_soat_vattu.php
```

Hoặc chạy trực tiếp SQL:
```bash
mysql -u username -p database_name < create_table_phieu_kiem_soat_vattu.sql
```

### 2. Cấp quyền cho roles
```bash
php grant_phieukiemsoatvattu_permission.php
```

## Quyền hạn

- `phieukiemsoatvattu.view`: Xem danh sách và chi tiết phiếu
- `phieukiemsoatvattu.create`: Tạo phiếu mới
- `phieukiemsoatvattu.edit`: Sửa và hủy phiếu
- `phieukiemsoatvattu.delete`: Xóa phiếu (chưa implement)

## Sử dụng

### 1. Truy cập module
```
http://localhost/iso2/phieukiemsoatvattu.php
```

### 2. Tạo phiếu mới
- Click nút "Tạo phiếu mới"
- Điền thông tin cơ bản:
  - Loại công việc
  - Bộ phận đặt hàng
  - Tên thiết bị
  - Người lập phiếu
  - Phiếu xuất kho số
  - Ngày xuất kho
- Thêm vật tư:
  - Click "Thêm vật tư"
  - Nhập mã vật tư (có autocomplete)
  - Nhập số lượng nhận và tiêu hao
  - Thêm ghi chú nếu cần
- Click "Lưu phiếu"

### 3. Xem chi tiết phiếu
- Click vào biểu tượng con mắt trong danh sách
- Xem đầy đủ thông tin phiếu và danh sách vật tư
- In phiếu nếu cần

### 4. Hủy phiếu
- Vào chi tiết phiếu
- Click nút "Hủy phiếu"
- Xác nhận hủy

## API Methods

### PhieuKiemSoatVatTuController

- `index()`: Hiển thị danh sách phiếu với tìm kiếm và lọc
- `create()`: Hiển thị form tạo phiếu mới
- `store()`: Lưu phiếu mới vào database
- `view()`: Xem chi tiết phiếu
- `cancel()`: Hủy phiếu

## File Structure

```
iso2/
├── phieukiemsoatvattu.php                          # Entry point
├── controllers/
│   └── PhieuKiemSoatVatTuController.php           # Controller
├── views/
│   └── phieukiemsoatvattu/
│       ├── index.php                               # Danh sách phiếu
│       ├── create.php                              # Form tạo phiếu
│       └── view.php                                # Chi tiết phiếu
├── create_table_phieu_kiem_soat_vattu.sql         # SQL schema
├── execute_create_phieu_kiem_soat_vattu.php       # Script tạo tables
└── grant_phieukiemsoatvattu_permission.php        # Script cấp quyền
```

## Mẫu số phiếu

Format: `PKSV-YYYYMM-XXX`

Ví dụ:
- `PKSV-202503-001`: Phiếu đầu tiên tháng 3/2025
- `PKSV-202503-002`: Phiếu thứ hai tháng 3/2025
- `PKSV-202504-001`: Phiếu đầu tiên tháng 4/2025

## Trạng thái phiếu

- **Đang thực hiện** (`dang_thuc_hien`): Phiếu mới tạo, đang được xử lý
- **Đã hoàn thành** (`hoan_thanh`): Phiếu đã hoàn thành
- **Đã hủy** (`huy`): Phiếu bị hủy, không còn hiệu lực

## Lưu ý

- Phiếu đã hủy không thể khôi phục lại
- Số phiếu được tạo tự động theo tháng
- Vật tư trong phiếu liên kết với bảng `vattu_thanh_ly_iso`
- Khi xóa vật tư từ bảng vật tư thanh lý, các chi tiết phiếu sẽ bị restrict (không cho xóa)
- Khi xóa phiếu, các chi tiết sẽ tự động xóa theo (cascade)

## Troubleshooting

### Lỗi "Không tìm thấy vật tư"
- Kiểm tra xem bảng `vattu_thanh_ly_iso` có dữ liệu chưa
- Kiểm tra foreign key constraint đã được tạo chưa

### Lỗi "Không có quyền"
- Chạy lại script `grant_phieukiemsoatvattu_permission.php`
- Kiểm tra role của user trong bảng `roles`

### Số phiếu không tự động
- Kiểm tra format số phiếu cũ trong database
- Kiểm tra hàm `generateSoPhieu()` trong controller
