# FIX LỖI #1265 - Data truncated for column 'trangthai'

**Ngày:** 20/03/2026  
**Lỗi:** `#1265 - Data truncated for column 'trangthai' at row 3`

---

## 🔍 NGUYÊN NHÂN

Trong bảng `giao_nhan_thietbi_iso` có dữ liệu với giá trị `trangthai` không khớp với ENUM mới.

**ENUM cũ (có thể):**
- `'cho_nhan'` (chờ nhận)
- `'da_nhan'` (đã nhận)
- `'hoan_thanh'` (hoàn thành)

**ENUM mới:**
- `'da_nhan'` 
- `'dang_kiem_dinh'` 
- `'da_giao'`

→ Giá trị `'cho_nhan'` và `'hoan_thanh'` không có trong ENUM mới → **Lỗi!**

---

## ✅ GIẢI PHÁP

### **Option 1: Dùng file Simple SQL (RECOMMENDED)**

Sử dụng file `refactor_giaonhan_simple.sql` - đã tách từng bước rõ ràng.

**Cách thực hiện:**

1. **Mở phpMyAdmin** → Database `diavatly_db`

2. **Chạy từng khối SQL riêng biệt:**

#### **Bước 1: Backup**
```sql
CREATE TABLE giao_nhan_thietbi_iso_backup_20260320 
AS SELECT * FROM giao_nhan_thietbi_iso;
```

#### **Bước 2: Kiểm tra dữ liệu**
```sql
SELECT trangthai, COUNT(*) as count 
FROM giao_nhan_thietbi_iso 
GROUP BY trangthai;
```
**Output mẫu:**
```
trangthai      | count
---------------|------
cho_nhan       | 2
da_nhan        | 1
hoan_thanh     | 1
```

#### **Bước 3: UPDATE dữ liệu cũ**
```sql
-- Map 'cho_nhan' → 'da_nhan'
UPDATE giao_nhan_thietbi_iso 
SET trangthai = 'da_nhan' 
WHERE trangthai = 'cho_nhan';

-- Map 'hoan_thanh' → 'da_giao'
UPDATE giao_nhan_thietbi_iso 
SET trangthai = 'da_giao' 
WHERE trangthai = 'hoan_thanh';
```

#### **Bước 4: Verify sau update**
```sql
SELECT trangthai, COUNT(*) as count 
FROM giao_nhan_thietbi_iso 
GROUP BY trangthai;
```
**Expected output:**
```
trangthai           | count
--------------------|------
da_nhan             | 3
da_giao             | 1
```
✅ Chỉ còn giá trị trong ENUM mới!

#### **Bước 5: Xóa cột cũ**
```sql
ALTER TABLE giao_nhan_thietbi_iso
DROP COLUMN loai_giao_nhan;

ALTER TABLE giao_nhan_thietbi_iso
DROP COLUMN phieu_giao_id;
```
⚠️ Nếu lỗi "Unknown column", bỏ qua → Chạy tiếp.

#### **Bước 6: Đổi ENUM (SẼ THÀNH CÔNG!)**
```sql
ALTER TABLE giao_nhan_thietbi_iso
MODIFY COLUMN trangthai ENUM('da_nhan', 'dang_kiem_dinh', 'da_giao') 
NOT NULL DEFAULT 'da_nhan'
COMMENT 'da_nhan: Đã nhận từ đội | dang_kiem_dinh: Đang gửi kiểm định | da_giao: Đã giao lại cho đội';
```
✅ Thành công vì đã UPDATE dữ liệu trước!

#### **Bước 7: Thêm cột mới**
```sql
ALTER TABLE giao_nhan_thietbi_iso
ADD COLUMN nguoi_gui_kiemdinh VARCHAR(255) DEFAULT NULL 
    COMMENT 'Người gửi đi kiểm định' AFTER donvi_giao;

ALTER TABLE giao_nhan_thietbi_iso
ADD COLUMN donvi_gui_kiemdinh VARCHAR(255) DEFAULT NULL 
    COMMENT 'Đơn vị gửi kiểm định' AFTER nguoi_gui_kiemdinh;

ALTER TABLE giao_nhan_thietbi_iso
ADD COLUMN ngay_gui_kiemdinh DATE DEFAULT NULL 
    COMMENT 'Ngày gửi kiểm định' AFTER donvi_gui_kiemdinh;
```

#### **Bước 8: Verify**
```sql
DESCRIBE giao_nhan_thietbi_iso;
```
✅ Kiểm tra có các cột: `nguoi_gui_kiemdinh`, `donvi_gui_kiemdinh`, `ngay_gui_kiemdinh`

---

### **Option 2: Dùng Script One-Time**

Nếu muốn chạy 1 lần, copy toàn bộ đoạn này:

```sql
USE diavatly_db;

-- Backup
CREATE TABLE IF NOT EXISTS giao_nhan_thietbi_iso_backup_20260320 
AS SELECT * FROM giao_nhan_thietbi_iso;

-- Update data
UPDATE giao_nhan_thietbi_iso SET trangthai = 'da_nhan' WHERE trangthai = 'cho_nhan';
UPDATE giao_nhan_thietbi_iso SET trangthai = 'da_giao' WHERE trangthai = 'hoan_thanh';

-- Drop old columns (nếu có)
ALTER TABLE giao_nhan_thietbi_iso DROP COLUMN IF EXISTS loai_giao_nhan;
ALTER TABLE giao_nhan_thietbi_iso DROP COLUMN IF EXISTS phieu_giao_id;

-- Modify ENUM
ALTER TABLE giao_nhan_thietbi_iso
MODIFY COLUMN trangthai ENUM('da_nhan', 'dang_kiem_dinh', 'da_giao') 
NOT NULL DEFAULT 'da_nhan'
COMMENT 'da_nhan: Đã nhận từ đội | dang_kiem_dinh: Đang gửi kiểm định | da_giao: Đã giao lại cho đội';

-- Add new columns (sử dụng ADD IF NOT EXISTS nếu MySQL 8.0+)
ALTER TABLE giao_nhan_thietbi_iso
ADD COLUMN nguoi_gui_kiemdinh VARCHAR(255) DEFAULT NULL COMMENT 'Người gửi đi kiểm định' AFTER donvi_giao,
ADD COLUMN donvi_gui_kiemdinh VARCHAR(255) DEFAULT NULL COMMENT 'Đơn vị gửi kiểm định' AFTER nguoi_gui_kiemdinh,
ADD COLUMN ngay_gui_kiemdinh DATE DEFAULT NULL COMMENT 'Ngày gửi kiểm định' AFTER donvi_gui_kiemdinh;

-- Verify
SELECT trangthai, COUNT(*) FROM giao_nhan_thietbi_iso GROUP BY trangthai;
DESCRIBE giao_nhan_thietbi_iso;
```

⚠️ **Lưu ý:** MySQL phiên bản cũ không hỗ trợ `DROP COLUMN IF EXISTS`. Nếu lỗi, chạy từng bước riêng.

---

## 🔄 ROLLBACK (nếu có lỗi)

```sql
-- Xóa bảng hiện tại
DROP TABLE giao_nhan_thietbi_iso;

-- Restore từ backup
CREATE TABLE giao_nhan_thietbi_iso AS 
SELECT * FROM giao_nhan_thietbi_iso_backup_20260320;

-- Restore AUTO_INCREMENT
ALTER TABLE giao_nhan_thietbi_iso 
MODIFY id INT AUTO_INCREMENT PRIMARY KEY;

-- Verify
SELECT * FROM giao_nhan_thietbi_iso;
```

---

## 📊 MAPPING DỮ LIỆU

| Giá Trị Cũ | Giá Trị Mới | Ý Nghĩa |
|-------------|-------------|---------|
| `cho_nhan` | `da_nhan` | Đội đã gửi cho mình, đang ở trạng thái "đã nhận" |
| `da_nhan` | `da_nhan` | Giữ nguyên - đã nhận từ đội |
| `hoan_thanh` | `da_giao` | Hoàn tất = đã giao lại cho đội |

**Logic mới:**
1. `da_nhan` - Đội gửi cho mình, mình đã nhận
2. `dang_kiem_dinh` - Mình đã gửi đi kiểm định
3. `da_giao` - Kiểm định xong, đã giao lại cho đội (hoàn tất)

---

## ✅ CHECKLIST

- [ ] Backup bảng `giao_nhan_thietbi_iso`
- [ ] Kiểm tra giá trị `trangthai` hiện tại (SELECT ... GROUP BY)
- [ ] UPDATE dữ liệu cũ: `cho_nhan` → `da_nhan`, `hoan_thanh` → `da_giao`
- [ ] Verify sau UPDATE (không còn giá trị ngoài ENUM mới)
- [ ] DROP cột `loai_giao_nhan`, `phieu_giao_id`
- [ ] MODIFY COLUMN `trangthai` (giờ sẽ thành công)
- [ ] ADD cột mới: `nguoi_gui_kiemdinh`, `donvi_gui_kiemdinh`, `ngay_gui_kiemdinh`
- [ ] DESCRIBE table để verify
- [ ] Test ứng dụng

---

## 📝 NOTES

1. **Luôn BACKUP trước khi chạy migration**
2. **Chạy trên LOCAL trước**, test đầy đủ, sau đó mới chạy PRODUCTION
3. **Chạy từng bước** trong phpMyAdmin để dễ debug
4. Nếu gặp lỗi `DROP COLUMN`, bỏ qua (cột không tồn tại)
5. Nếu gặp lỗi `ADD COLUMN Duplicate`, bỏ qua (cột đã tồn tại)

---

**File liên quan:**
- `refactor_giaonhan_simple.sql` - Migration từng bước chi tiết
- `refactor_giaonhan_workflow.sql` - Migration với prepared statements
- `REFACTOR_GIAONHAN_README.md` - Tài liệu đầy đủ

**Hoàn thành!** 🎉
