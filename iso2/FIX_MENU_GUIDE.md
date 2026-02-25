# 🔧 HƯỚNG DẪN KHẮC PHỤC LỖI MENU

## ⚠️ Vấn đề
Menu bị lỗi vì permissions `congviec_suachua.*` chưa tồn tại trong database.

## ✅ GIẢI PHÁP (3 BƯỚC)

### Bước 1: Chạy Migration
Truy cập URL sau để tạo permissions:
```
http://your-domain.com/iso2/execute_add_congviec_permissions.php
```

### Bước 2: Verify
Kiểm tra trong database:
```sql
SELECT * FROM permissions WHERE name LIKE 'congviec_suachua.%';
```
Phải có 4 records: view, create, edit, delete

### Bước 3: Bật lại menu
Trong file `views/layouts/header.php` (dòng ~86-95):

**TRƯỚC (hiện tại - tạm tắt):**
```php
<?php if (false): // Tạm thời tắt - Chạy migration trước ?>
```

**SAU (sau khi chạy migration):**
```php
<?php if (isLoggedIn() && hasPermission('congviec_suachua.view')): ?>
```

## 🎯 Hoàn tất
1. Đăng xuất
2. Đăng nhập lại
3. Menu "Công việc sửa chữa" sẽ hiện (nếu có quyền)

## 📝 Files liên quan
- `execute_add_congviec_permissions.php` - Script chạy migration
- `views/layouts/header.php` - File menu (dòng 86-95)
- `migrations/20260225_add_congviec_permissions.sql` - SQL migration

## 🐛 Nếu còn lỗi
1. Clear browser cache (Ctrl+Shift+Del)
2. Check PHP error log
3. Verify permissions table có đủ 4 records
