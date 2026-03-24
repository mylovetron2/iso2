# HƯỚNG DẪN DEPLOY LÊN SERVER PRODUCTION

## ⚠️ Lỗi Foreign Key Constraint đã được sửa

**Lỗi gặp phải:**
```
SQLSTATE[HY000]: General error: 1215 Cannot add foreign key constraint
```

**Nguyên nhân:**
- Cột `thietbi_id` trong bảng `thietbi_iso` có thể có kiểu dữ liệu khác với INT
- Hoặc charset/collation không khớp giữa 2 bảng
- Hoặc engine không phải InnoDB

**Giải pháp:**
- ✅ Đã tạo bảng **KHÔNG có foreign key constraints**
- ✅ Dùng charset `latin1` (giống các bảng khác trong hệ thống)
- ✅ Thay đổi `thietbi_id` thành `DEFAULT NULL` (không bắt buộc)

---

## 🚀 CÁCH DEPLOY

### ⚠️ QUAN TRỌNG: Database đã được sửa

File SQL đã được cập nhật với cơ chế permissions đúng:
- ✅ **Không dùng** bảng `permissions` và `role_permissions` (chưa tồn tại)
- ✅ **Thêm permissions vào cột CSV** trong bảng `roles.permissions`
- ✅ Hệ thống check permissions từ string field `roles.permissions`

### Phương án 1: Chạy SQL trực tiếp trên phpMyAdmin (KHUYÊN DÙNG)

1. **Login phpMyAdmin** tại `diavatly.cloud/phpmyadmin`

2. **Chọn database:** `diavatly_db` (hoặc database ISO2 của bạn)

3. **Click tab "SQL"**

4. **Copy toàn bộ nội dung file:** `setup_giaonhanthietbi_simple.sql`

5. **Paste vào** ô SQL query

6. **Click "Go"** để thực thi

7. **Kiểm tra kết quả:**
   - Bảng `giao_nhan_thietbi_iso` xuất hiện trong danh sách tables
   - Query `SELECT * FROM roles WHERE permissions LIKE '%giaonhanthietbi%';` phải trả về Admin role
   - Field `permissions` của role Admin chứa các string: `giaonhanthietbi.view`, `giaonhanthietbi.create_giao`, etc.

### Phương án 2: Upload và chạy qua terminal

```bash
# SSH vào server
ssh username@diavatly.cloud

# Di chuyển vào thư mục
cd /home/mapselli676e/domains/diavatly.cloud/public_html/iso2

# Upload file setup_giaonhanthietbi_simple.sql lên server (dùng FTP/SFTP)

# Chạy SQL
mysql -u diavatly_master -p diavatly_db < setup_giaonhanthietbi_simple.sql

# Nhập password: 12345678
```

### Phương án 3: Chạy PHP script (nếu connection OK)

```bash
cd /home/mapselli676e/domains/diavatly.cloud/public_html/iso2
php setup_giaonhanthietbi.php
```

---

## ✅ SAU KHI DEPLOY XONG

### 1. Kích hoạt menu

Mở file: `views/layouts/header.php`

**Tìm dòng ~180-192:**
```php
<!-- 3.6. Giao Nhận Thiết Bị - DISABLED until database setup complete -->
<?php /* UNCOMMENT after running: php setup_giaonhanthietbi.php
if (isLoggedIn() && hasPermission('giaonhanthietbi.view')): ?>
<li>
    <a href="/iso2/giaonhanthietbi.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
        <i class="fas fa-exchange-alt mr-2"></i> Giao Nhận Thiết Bị
    </a>
</li>
<?php endif; */ ?>
```

**Uncomment thành:**
```php
<!-- 3.6. Giao Nhận Thiết Bị -->
<?php if (isLoggedIn() && hasPermission('giaonhanthietbi.view')): ?>
<li>
    <a href="/iso2/giaonhanthietbi.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
        <i class="fas fa-exchange-alt mr-2"></i> Giao Nhận Thiết Bị
    </a>
</li>
<?php endif; ?>
```

### 2. Upload các file mới lên server

**Upload bằng FTP/SFTP các files sau:**

```
/iso2/giaonhanthietbi.php
/iso2/controllers/GiaoNhanThietBiController.php
/iso2/views/giaonhanthietbi/index.php
/iso2/views/giaonhanthietbi/giao_di.php
/iso2/views/giaonhanthietbi/nhan_ve.php
/iso2/views/giaonhanthietbi/view.php
/iso2/views/layouts/header.php (đã sửa)
```

### 3. Kiểm tra permissions server

```bash
# Set permissions cho các file
chmod 644 /home/mapselli676e/domains/diavatly.cloud/public_html/iso2/giaonhanthietbi.php
chmod 644 /home/mapselli676e/domains/diavatly.cloud/public_html/iso2/controllers/GiaoNhanThietBiController.php
chmod 644 /home/mapselli676e/domains/diavatly.cloud/public_html/iso2/views/giaonhanthietbi/*.php
```

### 4. Test module

1. **Login** vào hệ thống với tài khoản admin
2. **Kiểm tra menu** - Item "Giao Nhận Thiết Bị" xuất hiện
3. **Click vào** → Trang index hiển thị
4. **Tạo phiếu giao đi** để test workflow

---

## 🔍 KIỂM TRA SAU KHI DEPLOY

### Query 1: Kiểm tra bảng đã tạo
```sql
SHOW TABLES LIKE 'giao_nhan_thietbi_iso';
```
**Kết quả:** 1 row

### Query 2: Kiểm tra cấu trúc bảng
```sql
DESCRIBE giao_nhan_thietbi_iso;
```
**Kết quả:** 18 columns

### Query 3: Kiểm tra permissions trong roles
```sql
SELECT id, name, 
       CASE 
         WHEN permissions LIKE '%giaonhanthietbi%' THEN 'Có quyền ✓'
         ELSE 'Chưa có quyền'
       END as status
FROM roles
WHERE name IN ('Admin', 'admin', 'Manager') OR id = 1;
```
**Kết quả:** Admin roles phải có status = "Có quyền ✓"

### Query 4: Xem chi tiết permissions
```sql
SELECT id, name, permissions 
FROM roles 
WHERE permissions LIKE '%giaonhanthietbi%';
```
**Kết quả:** Phải thấy các permissions: `giaonhanthietbi.view`, `giaonhanthietbi.create_giao`, v.v.

---

## ❗ NẾU VẪN GẶP LỖI

### Lỗi: Permission denied khi tạo bảng

**Nguyên nhân:** User database không có quyền CREATE TABLE

**Giải pháp:** Chạy bằng phpMyAdmin (có quyền cao hơn)

### Lỗi: Duplicate entry for key 'PRIMARY'

**Nguyên nhân:** Bảng đã tồn tại

**Giải pháp:**
```sql
DROP TABLE IF EXISTS giao_nhan_thietbi_iso;
-- Rồi chạy lại script
```

### Lỗi: Unknown column in field list

**Nguyên nhân:** Database version khác nhau

**Giải pháp:** Kiểm tra MySQL version:
```sql
SELECT VERSION();
```
Phải >= 5.6

---

## 📋 CHECKLIST HOÀN TẤT

- [ ] SQL script đã chạy thành công trên production
- [ ] Bảng `giao_nhan_thietbi_iso` đã được tạo
- [ ] 6 permissions đã được thêm vào `permissions_iso`
- [ ] Admin role đã được gán permissions
- [ ] Các file PHP đã upload lên server
- [ ] Menu đã được uncomment trong `header.php`
- [ ] Đã test login và truy cập module
- [ ] Đã test tạo phiếu giao đi
- [ ] Đã test tạo phiếu nhận về

---

**Sau khi hoàn tất checklist → Module sẵn sàng sử dụng!** ✅
