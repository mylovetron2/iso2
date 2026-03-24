# HƯỚNG DẪN KÍCH HOẠT MODULE GIAO NHẬN THIẾT BỊ

## ⚠️ Module chưa được kích hoạt

Module **Giao Nhận Thiết Bị** đã được tạo đầy đủ nhưng **chưa được setup database**.

### Lý do tạm thời disable:
- Database connection hiện tại không khả dụng (diavatly.com unreachable)
- Menu item đã được comment để tránh lỗi khi check permissions chưa tồn tại

## 📋 Checklist kích hoạt

Khi database connection khả dụng, thực hiện các bước sau:

### ✅ Bước 1: Chạy setup script
```bash
cd d:\projectISO2\iso2
php setup_giaonhanthietbi.php
```

**Script sẽ:**
- Tạo bảng `giao_nhan_thietbi_iso` với 15 trường
- Thêm 6 permissions vào `permissions_iso`
- Gán tất cả permissions cho admin (role_id = 1)
- Hiển thị cấu trúc bảng để xác nhận

**Kết quả mong đợi:**
```
✓ Đã tạo bảng thành công!
✓ Permissions: Thêm mới 6, Đã tồn tại 0
✓ Gán permissions cho admin: 6
=== HOÀN TẤT THIẾT LẬP ===
```

### ✅ Bước 2: Kích hoạt menu

Mở file: `views/layouts/header.php`

**Tìm dòng ~180-192** (phần comment):
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

**Thay thế bằng:**
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

### ✅ Bước 3: Kiểm tra hoạt động

1. **Đăng nhập** với tài khoản admin
2. **Kiểm tra menu** - Item "Giao Nhận Thiết Bị" xuất hiện trong sidebar
3. **Click vào menu** - Trang index hiển thị
4. **Test tạo phiếu giao đi:**
   - Click "Giao Đi Kiểm Định"
   - Chọn thiết bị (dropdown tự động fill tên + ký mã hiệu)
   - Nhập thông tin người giao (Team), người nhận (Us)
   - Lưu → Kiểm tra danh sách
5. **Test tạo phiếu nhận về:**
   - Click "Nhận Về Kiểm Định"
   - Chọn phiếu giao đi từ dropdown
   - Nhập kết quả kiểm định
   - Lưu → Kiểm tra liên kết với phiếu giao đi

## 📁 Files đã tạo

### Database
- `create_table_giao_nhan_thietbi.sql` - SQL tạo bảng
- `add_giaonhanthietbi_permissions.sql` - SQL permissions
- `setup_giaonhanthietbi.php` - **Script all-in-one (khuyên dùng)**
- `execute_create_giao_nhan_thietbi.php` - Script riêng tạo bảng

### Backend
- `giaonhanthietbi.php` - Router (7 actions)
- `controllers/GiaoNhanThietBiController.php` - Controller (425 dòng, 7 methods)

### Frontend
- `views/giaonhanthietbi/index.php` - Danh sách + filters
- `views/giaonhanthietbi/giao_di.php` - Form giao đi
- `views/giaonhanthietbi/nhan_ve.php` - Form nhận về
- `views/giaonhanthietbi/view.php` - Chi tiết phiếu

### Documentation
- `GIAO_NHAN_THIETBI_README.md` - Tài liệu đầy đủ (workflow, API, troubleshooting)
- `GIAO_NHAN_THIETBI_SUMMARY.md` - Tóm tắt nhanh
- `SETUP_INSTRUCTION.md` - *File này*

### Modified
- `views/layouts/header.php` - Menu item (đang comment)

## 🔧 Sửa lỗi đã thực hiện

**Vấn đề:** Module được thêm vào menu nhưng database chưa setup → Lỗi khi check permissions

**Giải pháp:**
- ✅ Comment menu item cho đến khi database ready
- ✅ Sửa tên cột: `madv`/`tendv` → alias `ma_don_vi`/`ten_don_vi`
- ✅ Sửa tên cột: `ten_thiet_bi` (có gạch dưới)
- ✅ Thống nhất tên biến: `$records`, `$thietbiList`, `$donviList`, `$phieuGiaoList`

## 🚀 Khi nào có thể sử dụng?

**NGAY SAU KHI:**
1. Database connection khả dụng (diavatly.com hoặc localhost)
2. Chạy `php setup_giaonhanthietbi.php` thành công
3. Uncomment menu trong `header.php`

**Refresh browser** → Menu xuất hiện → Bắt đầu sử dụng!

## 📞 Liên hệ hỗ trợ

Nếu gặp lỗi sau khi setup, kiểm tra:

1. **Lỗi "Permission denied"**
   ```sql
   SELECT * FROM role_permissions_iso 
   WHERE role_id = 1 AND permission_id IN (
       SELECT id FROM permissions_iso WHERE permission LIKE 'giaonhanthietbi.%'
   );
   ```
   → Phải có 6 records

2. **Dropdown thiết bị rỗng**
   ```sql
   SELECT COUNT(*) FROM thietbi_iso;
   ```
   → Phải > 0

3. **Form không auto-fill**
   - Mở DevTools Console (F12)
   - Check JavaScript errors

4. **Any other error**
   - Check `logs/error.log`
   - Hoặc xem PHP error output

---

**Status:** ⏳ CHỜ DATABASE SETUP  
**Last updated:** March 19, 2026
