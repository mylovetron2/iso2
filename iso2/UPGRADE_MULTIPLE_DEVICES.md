# HƯỚNG DẪN NÂNG CẤP: 1 PHIẾU GIAO NHẬN NHIỀU THIẾT BỊ

## 📋 TỔNG QUAN

**Thay đổi:** Nâng cấp module giao nhận thiết bị từ **1 phiếu = 1 thiết bị** sang **1 phiếu = N thiết bị**

**Database Schema:**
- Bảng chính: `giao_nhan_thietbi_iso` - Thông tin phiếu (người giao/nhận, ngày, etc.)
- Bảng chi tiết: `giao_nhan_thietbi_chitiet` - Danh sách thiết bị trong phiếu

**Files đã sửa:**
1. ✅ `migrate_giaonhan_multiple_devices.sql` - Migration script
2. ✅ `controllers/GiaoNhanThietBiController.php` - 3 methods updated
3. ✅ `views/giaonhanthietbi/giao_di_multiple.php` - Form mới với add/remove rows
4. ✅ `views/giaonhanthietbi/index.php` - Hiển thị số thiết bị thay vì tên

---

## 🚀 BƯỚC TRIỂN KHAI

### 1️⃣ Backup Database (BẮT BUỘC)

```sql
-- Backup bảng chính (nếu có data)
CREATE TABLE giao_nhan_thietbi_iso_backup AS SELECT * FROM giao_nhan_thietbi_iso;

-- Verify backup
SELECT COUNT(*) FROM giao_nhan_thietbi_iso_backup;
```

### 2️⃣ Chạy Migration SQL

**Trên phpMyAdmin production:**

```
Database: diavatly_db
Tab: SQL
File: migrate_giaonhan_multiple_devices.sql
```

**Script này sẽ:**
- ✅ Tạo bảng `giao_nhan_thietbi_chitiet`
- ✅ Migrate dữ liệu cũ sang bảng chi tiết
- ✅ Thêm cột `tong_thietbi` vào bảng chính
- ✅ Chạy verification queries

**Kiểm tra sau khi chạy:**

```sql
-- 1. Bảng chi tiết đã tồn tại
SHOW TABLES LIKE 'giao_nhan_thietbi_chitiet';

-- 2. Cột mới đã có
DESCRIBE giao_nhan_thietbi_iso;
-- Phải thấy cột: tong_thietbi INT

-- 3. Dữ liệu cũ đã migrate
SELECT 
    gn.id,
    gn.tong_thietbi,
    COUNT(ct.id) as count_detail
FROM giao_nhan_thietbi_iso gn
LEFT JOIN giao_nhan_thietbi_chitiet ct ON gn.id = ct.phieu_id
GROUP BY gn.id;
-- tong_thietbi phải = count_detail
```

### 3️⃣ Upload Files Mới

**Via FTP/SFTP:**

```
✓ controllers/GiaoNhanThietBiController.php (replaced)
✓ views/giaonhanthietbi/giao_di_multiple.php (new)
✓ views/giaonhanthietbi/index.php (updated)
```

**Verify upload:**
```
http://diavatly.cloud/iso2/controllers/GiaoNhanThietBiController.php
http://diavatly.cloud/iso2/views/giaonhanthietbi/giao_di_multiple.php
```
(Nếu upload thành công, access sẽ bị 403 Forbidden - là đúng)

### 4️⃣ Test Chức Năng

#### Test 1: Danh sách phiếu
```
URL: http://diavatly.cloud/iso2/giaonhanthietbi.php
Kỳ vọng:
- Trang load không lỗi
- Cột "Số TB" hiển thị số thiết bị (ví dụ: "2 thiết bị")
- Các phiếu cũ (nếu có) hiển thị "1 thiết bị"
```

#### Test 2: Tạo phiếu giao đi mới
```
URL: http://diavatly.cloud/iso2/giaonhanthietbi.php?action=create_giao_di
Kỳ vọng:
- Form hiển thị với 1 row thiết bị ban đầu
- Nút "Thêm thiết bị" hoạt động (thêm row mới)
- Nút X màu đỏ (xóa row, nhưng phải giữ ít nhất 1)
- Submit form → Success message với số thiết bị
```

#### Test 3: Xem chi tiết phiếu
```
URL: http://diavatly.cloud/iso2/giaonhanthietbi.php?action=view&id=1
Kỳ vọng:
- Hiển thị danh sách thiết bị trong phiếu (bảng)
- Mỗi thiết bị 1 dòng với tình trạng, ghi chú riêng
```

---

## 🔍 CÁC THAY ĐỔI CHI TIẾT

### Database Schema

**Bảng `giao_nhan_thietbi_iso` (11 -> 10 columns data):**
```sql
-- REMOVED: thietbi_id, ten_thietbi, ky_ma_hieu
-- ADDED: tong_thietbi INT
-- Giữ lại columns cũ để backward compatible, nhưng không dùng nữa
```

**Bảng `giao_nhan_thietbi_chitiet` (NEW):**
```sql
id INT PK
phieu_id INT                -- FK to giao_nhan_thietbi_iso.id
thietbi_id INT              -- FK to thietbi_iso.stt
ten_thietbi VARCHAR(255)    -- Copy từ thietbi_iso.tenvt
ky_ma_hieu VARCHAR(100)     -- Copy từ thietbi_iso.somay
soluong INT DEFAULT 1       -- Số lượng (mặc định 1)
tinhtrang TEXT              -- Tình trạng thiết bị khi giao/nhận
ghichu TEXT                 -- Ghi chú riêng cho từng thiết bị
created_at TIMESTAMP
updated_at TIMESTAMP
```

### Controller Changes

**Method `index()` - Lấy danh sách:**
```php
// BEFORE: JOIN thietbi_iso tb
// AFTER:  LEFT JOIN giao_nhan_thietbi_chitiet ct + COUNT(ct.id)
// Result: $record['so_thietbi'] = số thiết bị trong phiếu
```

**Method `storeGiaoDi()` - Tạo phiếu:**
```php
// BEFORE: Single device (thietbi_id, ten_thietbi, ky_ma_hieu)
// AFTER:  Array of devices (thietbi_id[], tinhtrang[], ghichu_thietbi[])
// Logic:
//   1. INSERT vào giao_nhan_thietbi_iso (phiếu chính, tong_thietbi=count)
//   2. Lấy lastInsertId()
//   3. FOREACH thiết bị → INSERT vào giao_nhan_thietbi_chitiet
```

**Method `view()` - Chi tiết:**
```php
// BEFORE: Single record with device info
// AFTER:  
//   $record = phiếu chính
//   $thietbiList = array chi tiết thiết bị (SELECT * FROM chitiet WHERE phieu_id=?)
```

### View Changes

**File `giao_di_multiple.php`:**
- Dynamic rows với JavaScript
- Button "Thêm thiết bị" → `addThietBiRow()`
- Button X (xóa) → `removeThietBiRow()` nhưng phải giữ ít nhất 1
- Validate: Không trùng thiết bị
- Submit: Arrays `thietbi_id[]`, `tinhtrang[]`, `ghichu_thietbi[]`

**File `index.php`:**
- Column "Tên Thiết Bị" + "Ký Mã Hiệu" → "Số TB"
- Display: Badge màu tím `"2 thiết bị"`
- Colspan: 11 → 10

---

## ❌ ROLLBACK (NẾU CÓ VẤN ĐỀ)

### Option 1: Rollback Migration

```sql
-- 1. Xóa bảng chi tiết
DROP TABLE IF EXISTS giao_nhan_thietbi_chitiet;

-- 2. Xóa cột tong_thietbi
ALTER TABLE giao_nhan_thietbi_iso DROP COLUMN tong_thietbi;

-- 3. Restore từ backup (nếu có data cũ)
-- (Manual hoặc từ backup file)
```

### Option 2: Rollback Files

```
Restore from Git:
- controllers/GiaoNhanThietBiController.php (commit trước)
- views/giaonhanthietbi/giao_di.php (file cũ)
- views/giaonhanthietbi/index.php (commit trước)
```

### Option 3: Keep Migration, Use Old UI Temporarily

Sửa `controllers/GiaoNhanThietBiController.php`:

```php
// Line ~107 (createGiaoDi method)
require_once __DIR__ . '/../views/giaonhanthietbi/giao_di.php'; 
// Thay vì: giao_di_multiple.php
```

---

## 📊 TESTING CHECKLIST

- [ ] Migration chạy thành công không lỗi
- [ ] Bảng `giao_nhan_thietbi_chitiet` tồn tại
- [ ] Cột `tong_thietbi` đã có trong bảng chính
- [ ] Dữ liệu cũ migrate thành công (nếu có)
- [ ] Trang danh sách load không lỗi
- [ ] Column "Số TB" hiển thị đúng
- [ ] Form tạo mới load không lỗi
- [ ] Nút "Thêm thiết bị" hoạt động
- [ ] Nút "Xóa" hoạt động (giữ ít nhất 1 row)
- [ ] Submit form với 1 thiết bị → Success
- [ ] Submit form với nhiều thiết bị → Success
- [ ] Chi tiết phiếu hiển thị danh sách thiết bị
- [ ] Tìm kiếm theo tên thiết bị vẫn hoạt động
- [ ] Filters (loại, trạng thái, đơn vị) hoạt động
- [ ] Permissions checks vẫn hoạt động

---

## 🔧 TROUBLESHOOTING

### Lỗi: "Unknown column 'so_thietbi'"

**Nguyên nhân:** View cũ đang cache

**Fix:**
```sql
-- Clear query cache
RESET QUERY CACHE;

-- Hoặc verify column
SELECT tong_thietbi FROM giao_nhan_thietbi_iso LIMIT 1;
```

### Lỗi: "Table 'giao_nhan_thietbi_chitiet' doesn't exist"

**Nguyên nhân:** Migration chưa chạy

**Fix:** Chạy lại `migrate_giaonhan_multiple_devices.sql`

### Lỗi: "Cannot add or update a child row"

**Nguyên nhân:** Foreign key constraint (nếu có)

**Fix:** Migration đã không tạo FK, check lại:
```sql
SHOW CREATE TABLE giao_nhan_thietbi_chitiet;
-- Không nên thấy FOREIGN KEY constraints
```

### Form không hiển thị nút "Thêm thiết bị"

**Nguyên nhân:** JavaScript error hoặc FontAwesome chưa load

**Fix:** 
- Check browser console (F12)
- Verify `<script>` block có trong file
- Verify FontAwesome CDN trong header.php

### Phiếu cũ hiển thị "0 thiết bị"

**Nguyên nhân:** Migration chưa chạy phần UPDATE tong_thietbi

**Fix:**
```sql
UPDATE giao_nhan_thietbi_iso gn
SET tong_thietbi = (
    SELECT COUNT(*) 
    FROM giao_nhan_thietbi_chitiet ct 
    WHERE ct.phieu_id = gn.id
);
```

---

## 📞 SUPPORT

**Nếu gặp vấn đề:**
1. Check PHP error log: `tail -f /path/to/php_error.log`
2. Check MySQL error log: `SHOW VARIABLES LIKE 'log_error';`
3. Run debug script: `http://diavatly.cloud/iso2/debug_giaonhanthietbi.php`
4. Verify uploads: Check file modification time trên server

**Rollback nếu cần:**
- Có backup database → Restore
- Có Git history → Revert commits
- Keep migration + use old UI → Change require path
