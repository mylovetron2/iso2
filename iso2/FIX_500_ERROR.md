# ✅ ĐÃ FIX LỖI 500 - GIOHANG.PHP

## 🔍 Nguyên nhân lỗi

File `giohang.php` và `phieudathang.php` đang cố gắng load 2 file KHÔNG TỒN TẠI:
- ❌ `includes/auth_check.php` 
- ❌ `includes/permission_check.php`

Điều này gây ra lỗi 500 Internal Server Error.

## ✅ Đã khắc phục

Đã tạo 2 file còn thiếu:

### 1. `includes/auth_check.php` (66 dòng)
**Chức năng:**
- `requireLogin()` - Bắt buộc đăng nhập
- `getCurrentUserId()` - Lấy user ID hiện tại
- `getCurrentUsername()` - Lấy username hiện tại
- `hasRole()` - Kiểm tra role của user

### 2. `includes/permission_check.php` (108 dòng)
**Chức năng:**
- `checkPermission()` - Kiểm tra permission, tự động redirect nếu không có quyền
- `hasAnyPermission()` - Có ít nhất 1 permission trong danh sách
- `hasAllPermissions()` - Có tất cả permissions trong danh sách
- `requireAnyPermission()` - Bắt buộc có ít nhất 1 permission
- `requireAllPermissions()` - Bắt buộc có tất cả permissions

## 🎯 Các bước tiếp theo

### Bước 1: Chạy debug để kiểm tra
```
https://diavatly.cloud/iso2/debug_giohang_500.php
```

Script sẽ kiểm tra:
- ✅ File tồn tại
- ✅ Controller syntax
- ✅ Database connection
- ✅ Các bảng cần thiết
- ✅ PHP error log

### Bước 2: Nếu báo "Chưa tạo bảng cart_vattu_thanh_ly"

**Chạy SQL:**
1. Mở phpMyAdmin
2. Chọn database: `diavatly_db`
3. Tab "SQL"
4. Copy toàn bộ file: `setup_giohang_phieudathang.sql`
5. Paste và click "Go"

Sẽ tạo 4 bảng:
- `cart_vattu_thanh_ly` - Giỏ hàng
- `phieu_dat_hang` - Phiếu đặt hàng
- `phieu_dat_hang_chi_tiet` - Chi tiết phiếu
- `lich_su_nhap_kho` - Lịch sử nhập kho

### Bước 3: Grant permissions

**Chạy PHP script:**
```
https://diavatly.cloud/iso2/grant_giohang_phieudathang_permissions.php
```

Sẽ thêm 13 permissions vào `roles.permissions`:
- giohang: view, add, edit, delete
- phieudathang: view, create, edit, delete, approve, receive, stock, cancel, export

### Bước 4: Test chức năng

1. **Clear cache trình duyệt:** Ctrl + Shift + R
2. **Truy cập trang vật tư thanh lý:**
   ```
   https://diavatly.cloud/iso2/vattuthanhly.php
   ```
3. **Kiểm tra UI:**
   - Nút "Giỏ hàng" (màu tím) + badge số lượng
   - Nút "Quản lý phiếu ĐH" (màu indigo)
   - Icon 🛒 trong mỗi dòng vật tư

4. **Test workflow:**
   - Click 🛒 thêm vào giỏ → Badge tăng
   - Click "Giỏ hàng" → Xem giỏ
   - Sửa số lượng → Auto-save
   - "Tạo Phiếu Đặt Hàng" → Điền form
   - Submit → Tạo phiếu (trạng thái: draft)
   - Duyệt → Đặt hàng → Nhận hàng → Nhập kho

## 🐛 Debug thêm nếu vẫn lỗi

### Xem error log chi tiết:
```php
// Thêm vào đầu giohang.php tạm thời:
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Hoặc xem PHP error log:
1. `error.log` trong thư mục gốc
2. `logs/error.log`
3. hoặc check `php.ini` → `error_log` setting

### Check database:
```sql
USE diavatly_db;

-- Kiểm tra bảng
SHOW TABLES LIKE 'cart_vattu_thanh_ly';
SHOW TABLES LIKE 'phieu_dat_hang%';

-- Kiểm tra permissions
SELECT id, name, permissions 
FROM roles 
ORDER BY id;
```

## 📋 Files đã tạo/sửa

### Mới tạo (2 files):
1. ✅ `includes/auth_check.php` (66 dòng)
2. ✅ `includes/permission_check.php` (108 dòng)

### Debug tools (2 files):
1. `debug_giohang_500.php` - Debug lỗi 500
2. `debug_login_v2.php` - Debug login issue

### Đã có sẵn (không đổi):
- `giohang.php` - Router giỏ hàng
- `phieudathang.php` - Router phiếu đặt hàng
- `controllers/GioHangController.php` - Controller giỏ hàng
- `controllers/PhieuDatHangController.php` - Controller phiếu ĐH
- Tất cả views (5 files)

## 🎉 Kết quả mong đợi

Sau khi hoàn thành tất cả các bước:

✅ Giỏ hàng hoạt động  
✅ Thêm vật tư vào giỏ  
✅ Tạo phiếu đặt hàng  
✅ Workflow: Draft → Ordered → Received → Stocked  
✅ Tự động nhập kho khi complete  
✅ Lưu lịch sử nhập kho  

---

**Lưu ý:** Nếu vẫn gặp lỗi, hãy chạy `debug_giohang_500.php` và báo kết quả chi tiết!
