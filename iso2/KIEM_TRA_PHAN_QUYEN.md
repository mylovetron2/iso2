# HƯỚNG DẪN KIỂM TRA CẤU TRÚC PHÂN QUYỀN

## 🔍 Cách 1: Qua trình duyệt (KHUYẾN NGHỊ)

Mở trình duyệt và vào:
```
http://localhost/iso2/check_permission_structure.php
```

Script sẽ hiển thị:
1. ✅ hoặc ❌ cho mỗi bảng: users, roles, role_user, permissions, role_permissions, user_permissions
2. Cấu trúc bảng roles (có cột permissions không?)
3. Cấu trúc bảng permissions (nếu tồn tại)
4. Ví dụ permissions có sẵn
5. **KẾT LUẬN**: Hệ thống dùng cách nào

---

## 🔍 Cách 2: Qua MySQL command

```sql
USE diavatly_db;

-- Kiểm tra bảng permissions
SHOW TABLES LIKE 'permissions';

-- Nếu có, xem cấu trúc
DESCRIBE permissions;

-- Xem dữ liệu
SELECT * FROM permissions LIMIT 10;

-- Kiểm tra bảng roles
DESCRIBE roles;

-- Xem quyền trong roles
SELECT id, name, LEFT(permissions, 100) FROM roles;
```

---

## 📋 KẾT LUẬN DỰ ĐOÁN

Dựa trên code trong `models/User.php`, hệ thống có:

### ✅ Bảng chắc chắn có:
- `users` - Bảng user
- `roles` - Bảng vai trò (có cột `permissions` dạng JSON string)
- `role_user` - Bảng liên kết user với role

### ❓ Bảng có thể có hoặc không:
- `permissions` - Bảng permissions riêng (cần xác nhận)
- `role_permissions` - Bảng liên kết role với permission
- `user_permissions` - Bảng liên kết user với permission  

### 🎯 HỆ THỐNG SỬ DỤNG 2 CÁCH:

**Cách 1 (Legacy):** Quyền lưu trong `roles.permissions` (JSON string)
```php
// Check permission
SELECT COUNT(*) FROM role_user ru 
INNER JOIN roles r ON ru.role_id = r.id 
WHERE ru.user_id = ? 
AND CONCAT(',', r.permissions, ',') LIKE '%,giohang.view,%'
```

**Cách 2 (Modern):** Quyền lưu trong bảng `permissions`
```php
// Check permission
SELECT COUNT(*) FROM user_permissions up 
INNER JOIN permissions p ON up.permission_id = p.id 
WHERE up.user_id = ? AND p.name = 'giohang.view'
```

---

## 💡 GIẢI PHÁP CHO GIỎ HÀNG

### Nếu bảng `permissions` KHÔNG TỒN TẠI:

**Option 1: Tạo bảng permissions (KHUYẾN NGHỊ)**
```sql
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL UNIQUE,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_permission` (`role_id`, `permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_permission` (`user_id`, `permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Sau đó chạy: `setup_giohang_phieudathang.sql`

**Option 2: Dùng roles.permissions (JSON)**
Sửa file SQL để thêm quyền vào `roles.permissions`:
```sql
-- Lấy permissions hiện tại của admin
SELECT permissions FROM roles WHERE id = 1;

-- Update thêm quyền mới
UPDATE roles 
SET permissions = JSON_ARRAY_APPEND(
    permissions, 
    '$', 
    'giohang.view', 
    'giohang.add', 
    ...
)
WHERE id = 1;
```

---

## ⚡ HÀNH ĐỘNG NGAY

1. **Mở trình duyệt**: http://localhost/iso2/check_permission_structure.php
2. **Xem kết quả**: Bảng permissions có hay không?
3. **Báo lại cho tôi** kết quả để tôi điều chỉnh SQL cho phù hợp!

---

**File check đã tạo:**
- ✅ `check_permission_structure.php` - Kiểm tra cấu trúc database
- ✅ `FIX_PERMISSIONS_README.md` - Hướng dẫn fix lỗi
- ✅ File này - Hướng dẫn kiểm tra và giải pháp
