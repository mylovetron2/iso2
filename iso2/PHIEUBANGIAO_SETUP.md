# Hướng Dẫn Cài Đặt Module Phiếu Bàn Giao

## 1. Chạy Migration SQL

### Cách 1: Trực tiếp trên server (qua SSH)
```bash
cd /path/to/iso2
mysql -u diavatly_master -p diavatly_db < migrations/20251122_create_phieubangiao_tables.sql
```

### Cách 2: Qua phpMyAdmin
1. Đăng nhập phpMyAdmin
2. Chọn database `diavatly_db`
3. Vào tab "SQL"
4. Copy toàn bộ nội dung file `migrations/20251122_create_phieubangiao_tables.sql`
5. Paste và Execute

### Cách 3: Dùng PHP script (upload lên server)
```bash
php migrations/run_migration.php
```

## 2. Kiểm Tra Tables Đã Tạo

```sql
-- Kiểm tra bảng phieubangiao_iso
SHOW CREATE TABLE phieubangiao_iso;
SELECT COUNT(*) FROM phieubangiao_iso;

-- Kiểm tra bảng phieubangiao_thietbi_iso
SHOW CREATE TABLE phieubangiao_thietbi_iso;
SELECT COUNT(*) FROM phieubangiao_thietbi_iso;
```

## 3. Test Workflow

### Bước 1: Truy cập trang chọn thiết bị
```
http://your-domain.com/phieubangiao.php?action=select
```

**Test cases:**
- [ ] Trang load thành công
- [ ] API endpoint trả về danh sách thiết bị chưa bàn giao
- [ ] Filter hoạt động (search, đơn vị, phiếu YC)
- [ ] Checkbox selection hoạt động
- [ ] Summary hiển thị đúng số thiết bị & số phiếu
- [ ] Submit chuyển sang bước 2

### Bước 2: Xác nhận và tạo phiếu
```
http://your-domain.com/phieubangiao.php?action=confirm
```

**Test cases:**
- [ ] Hiển thị đúng devices đã chọn, grouped by phiếu YC
- [ ] Form fields hiển thị đầy đủ
- [ ] Dropdown đơn vị nhận load đúng
- [ ] Validation hoạt động (required fields)
- [ ] Submit tạo phiếu thành công
- [ ] Redirect về danh sách với thông báo thành công
- [ ] hososcbd.bg cập nhật = 1

### Bước 3: Xem danh sách phiếu
```
http://your-domain.com/phieubangiao.php
```

**Test cases:**
- [ ] Danh sách phiếu hiển thị
- [ ] Stats cards hiển thị đúng (tổng, nháp, đã duyệt)
- [ ] Filter hoạt động (search, trạng thái, đơn vị)
- [ ] Pagination hoạt động
- [ ] Link "Xem" hoạt động
- [ ] Nút "Xóa" hiện với phiếu nháp

### Bước 4: Xem chi tiết phiếu
```
http://your-domain.com/phieubangiao.php?action=view&id=XXX
```

**Test cases:**
- [ ] Thông tin phiếu hiển thị đầy đủ
- [ ] Danh sách thiết bị hiển thị đúng
- [ ] Status badge hiển thị đúng màu
- [ ] Nút "In phiếu" hoạt động
- [ ] @media print CSS hoạt động

### Bước 5: Xóa phiếu nháp
**Test cases:**
- [ ] Chỉ xóa được phiếu nháp (trangthai=0)
- [ ] Confirm dialog hiện
- [ ] Xóa thành công
- [ ] hososcbd.bg trả về = 0
- [ ] Redirect về danh sách với thông báo

## 4. Kiểm Tra Permissions

```php
// File: includes/permissions.php hoặc tương tự
// Cần có các quyền:
- hososcbd.view   // Xem danh sách
- hososcbd.create // Tạo phiếu mới
- hososcbd.delete // Xóa phiếu nháp
```

## 5. Database Checks

```sql
-- Sau khi tạo phiếu thành công, kiểm tra:

-- Phiếu bàn giao đã tạo
SELECT * FROM phieubangiao_iso ORDER BY ngaytao DESC LIMIT 5;

-- Chi tiết thiết bị
SELECT pb.sophieu, pb.phieuyc, pbt.hososcbd_stt, pbt.tinhtrang
FROM phieubangiao_iso pb
JOIN phieubangiao_thietbi_iso pbt ON pb.sophieu = pbt.sophieu
ORDER BY pb.ngaytao DESC LIMIT 10;

-- Thiết bị đã được đánh dấu bg=1
SELECT h.stt, h.mavt, h.tenvt, h.bg
FROM hososcbd_iso h
WHERE h.bg = 1
ORDER BY h.stt DESC LIMIT 10;

-- Kiểm tra foreign key constraints
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'diavatly_db'
AND TABLE_NAME IN ('phieubangiao_iso', 'phieubangiao_thietbi_iso');
```

## 6. Troubleshooting

### Lỗi: "Table not found"
- Chạy lại migration SQL
- Kiểm tra database connection trong config/database.php

### Lỗi: "Foreign key constraint fails"
- Kiểm tra bảng hososcbd_iso tồn tại
- Kiểm tra column hososcbd_iso.stt tồn tại

### Lỗi: API không trả về dữ liệu
- Kiểm tra file api/phieubangiao_available_devices.php
- Kiểm tra dữ liệu: `SELECT * FROM hososcbd_iso WHERE ngaykt IS NOT NULL AND bg = 0`

### Lỗi: Session lost giữa step 1 và 2
- Kiểm tra session_start() trong includes/auth.php
- Kiểm tra $_SESSION['pbg_temp_devices'] có được set

### Lỗi: "Permission denied"
- Kiểm tra user đã login
- Kiểm tra hasPermission() function
- Kiểm tra roles & permissions trong database

## 7. Production Checklist

- [ ] Migration SQL đã chạy thành công
- [ ] Tables tạo với đúng charset (utf8mb4)
- [ ] Foreign keys hoạt động
- [ ] Indexes đã được tạo
- [ ] Permissions được cấp đúng cho user
- [ ] Test toàn bộ workflow end-to-end
- [ ] Test với nhiều phiếu YC khác nhau
- [ ] Test delete và verify bg status restore
- [ ] Test print functionality
- [ ] Backup database trước khi deploy

## 8. Files Checklist

### Phase 1 (Commit c42df41):
- [x] migrations/20251122_create_phieubangiao_tables.sql
- [x] models/PhieuBanGiao.php
- [x] models/PhieuBanGiaoThietBi.php
- [x] controllers/PhieuBanGiaoController.php
- [x] api/phieubangiao_available_devices.php
- [x] phieubangiao.php (router)
- [x] views/phieubangiao/index.php

### Phase 2 (Commit 622bd4a):
- [x] views/phieubangiao/select_devices.php
- [x] views/phieubangiao/confirm_create.php
- [x] views/phieubangiao/view.php

### Helper:
- [x] migrations/run_migration.php

## 9. Business Logic Summary

**Workflow:**
1. User chọn nhiều thiết bị từ các phiếu YC khác nhau
2. System tự động group theo phiếu YC
3. System tạo 1 phiếu bàn giao cho mỗi phiếu YC
4. Mỗi phiếu có thể lưu nháp (trangthai=0) hoặc duyệt luôn (trangthai=1)
5. Thiết bị được đánh dấu bg=1 sau khi tạo phiếu
6. Xóa phiếu nháp sẽ restore bg=0

**Constraints:**
- 1 phiếu bàn giao = 1 phiếu YC (business rule)
- Chỉ thiết bị có ngaykt (ngày kết thúc sửa chữa) mới được chọn
- Chỉ thiết bị chưa bàn giao (bg=0) mới hiện trong selection
- Chỉ xóa được phiếu nháp (trangthai=0)

**Auto-numbering:**
- Format: PBG-0001, PBG-0002, ...
- Tăng tự động từ số cuối cùng trong database
