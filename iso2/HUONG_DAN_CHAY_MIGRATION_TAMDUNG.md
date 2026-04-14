# Hướng dẫn chạy Migration Tạm dừng hồ sơ SCBĐ

## ⚠️ LƯU Ý QUAN TRỌNG

**Migration này KHÔNG sửa bảng `hososcbd_iso`**
- Chỉ tạo bảng mới `hososcbd_tamdung` để lưu lịch sử
- Không thêm cột mới vào bảng gốc
- An toàn 100% với dữ liệu hiện có

## Lỗi hiện tại

**Message:** "Không thể tạm dừng hồ sơ. Hồ sơ có thể đã tạm dừng rồi."

**Nguyên nhân:** Database chưa có bảng `hososcbd_tamdung`.

## Bước 1: Upload các file cần thiết

Upload 2 file sau lên server production:

1. **migrations/create_hososcbd_tamdung_table.sql**
   - Đường dẫn: `/iso2/migrations/create_hososcbd_tamdung_table.sql`
   - Tạo folder `migrations` nếu chưa có

2. **run_migration_tamdung.php**
   - Đường dẫn: `/iso2/run_migration_tamdung.php`
   - File này sẽ chạy migration SQL

## Bước 2: Chạy Migration

### Cách 1: Qua trình duyệt (Khuyến nghị)

1. Truy cập: `https://diavatly.cloud/iso2/run_migration_tamdung.php`
2. Xem kết quả trên màn hình:
   - ✅ Thành công: Hiển thị thông báo "Migration đã chạy thành công"
   - ❌ Thất bại: Hiển thị lỗi chi tiết

### Cách 2: Qua MySQL command line

```bash
mysql -u username -p iso2 < migrations/create_hososcbd_tamdung_table.sql
```

## Bước 3: Kiểm tra Migration

Sau khi chạy, kiểm tra bằng cách truy cập:
`https://diavatly.cloud/iso2/check_tamdung_migration.php`

**Kết quả mong đợi:**
- ✅ Bảng hososcbd_tamdung đã tồn tại
- Số hồ sơ đang tạm dừng: 0
- Tổng số lần tạm dừng: 0
- Tổng số lần tiếp tục: 0

## Bước 4: Kiểm tra tính năng

1. Truy cập trang Hồ sơ SCBĐ: `https://diavatly.cloud/iso2/hososcbd.php`
2. Click nút "Tạm dừng" trên một hồ sơ bất kỳ
3. Nhập lý do tạm dừng và Submit
4. Xem kết quả:
   - ✅ Thành công: Hồ sơ được đánh dấu cam "TẠM DỪNG"
   - ✅ Button chuyển thành "Tiếp tục"

## Cấu trúc Database sau Migration

### Bảng mới: hososcbd_tamdung

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
    FOREIGN KEY (hoso) REFERENCES hososcbd_iso(hoso) ON DELETE CASCADE,
    INDEX idx_hoso (hoso),
    INDEX idx_trangthai (trangthai),
    INDEX idx_ngay (ngay_thuchien)
);
```

**Cách hoạt động:**
- Mỗi lần tạm dừng: INSERT record với `trangthai='tamdung'`
- Mỗi lần tiếp tục: INSERT record với `trangthai='tieptuc'`
- **Xác định trạng thái hiện tại:** Query record mới nhất (MAX(id)) của hồ sơ
  - Nếu `trangthai='tamdung'` → Đang tạm dừng
  - Nếu `trangthai='tieptuc'` hoặc không có record → Đang hoạt động

### Bảng hososcbd_iso: KHÔNG THAY ĐỔI

Migration này **không sửa** bảng `hososcbd_iso` để đảm bảo an toàn dữ liệu.


## Troubleshooting

### Lỗi: Table already exists
**Nguyên nhân:** Migration đã chạy trước đó.
**Giải pháp:** Không cần làm gì, tính năng đã sẵn sàng.

### Lỗi: Foreign key constraint fails
**Nguyên nhân:** Bảng `hososcbd_iso` không có cột `hoso` hoặc không phải PRIMARY KEY/UNIQUE.
**Giải pháp:** Kiểm tra cấu trúc bảng `hososcbd_iso`, đảm bảo cột `hoso` là PRIMARY KEY hoặc UNIQUE.

### Lỗi: Access denied
**Nguyên nhân:** User MySQL không có quyền CREATE TABLE.
**Giải pháp:** Liên hệ admin database để cấp quyền hoặc chạy migration bằng user root.

### Lỗi: Bảng hososcbd_tamdung không tồn tại khi sử dụng tính năng
**Nguyên nhân:** Chưa chạy migration.
**Giải pháp:** Chạy migration qua `run_migration_tamdung.php`.

## Sau khi Migration thành công

Tất cả các tính năng sau sẽ hoạt động:

1. ✅ Nút "Tạm dừng" trên danh sách hồ sơ
2. ✅ Modal nhập lý do tạm dừng
3. ✅ Nút "Tiếp tục" cho hồ sơ đã tạm dừng
4. ✅ Modal xem lịch sử tạm dừng/tiếp tục
5. ✅ Badge "TẠM DỪNG" màu cam
6. ✅ Báo cáo lịch sử: `baocao_hososcbd_tamdung.php`
7. ✅ Danh sách hồ sơ tạm dừng: `baocao_hososcbd_tamdung.php?trangthai=dang_tam_dung` (click card thống kê màu cam)

## Xóa Migration (Rollback)

Nếu cần xóa bỏ tính năng:

```sql
-- Xóa bảng lịch sử (sẽ xóa toàn bộ lịch sử tạm dừng)
DROP TABLE IF EXISTS hososcbd_tamdung;
```

**LƯU Ý:** 
- Thao tác này sẽ xóa toàn bộ lịch sử tạm dừng/tiếp tục
- Backup database trước khi rollback
- **KHÔNG ảnh hưởng** đến bảng `hososcbd_iso` vì migration không sửa bảng này
