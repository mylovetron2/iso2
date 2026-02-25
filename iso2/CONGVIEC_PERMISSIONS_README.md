# Hướng dẫn Permissions - Module Công Việc Sửa Chữa

## 📋 Tổng quan

Module Công Việc Sửa Chữa sử dụng hệ thống phân quyền để kiểm soát quyền truy cập và thao tác của người dùng.

## 🔐 Danh sách Permissions

Module có **4 permissions** chính:

| Permission Name          | Mô tả                        | Scope                          |
|--------------------------|------------------------------|--------------------------------|
| `congviec_suachua.view`   | Xem công việc sửa chữa       | Xem danh sách, thống kê        |
| `congviec_suachua.create` | Tạo công việc sửa chữa       | Thêm công việc mới             |
| `congviec_suachua.edit`   | Sửa công việc sửa chữa       | Cập nhật thông tin công việc   |
| `congviec_suachua.delete` | Xóa công việc sửa chữa       | Xóa công việc khỏi hệ thống    |

---

## 🚀 Cài đặt Permissions

### Bước 1: Chạy Migration

Thực thi migration SQL để thêm permissions vào database:

```bash
# URL:
http://your-domain.com/iso2/execute_add_congviec_permissions.php
```

**Kết quả:**
- ✅ Thêm 4 permissions vào bảng `permissions`
- ✅ Tự động cấp tất cả permissions cho admin role (role_id = 1)
- ✅ Tự động cấp permissions cho user stt = 5 (nếu tồn tại)

### Bước 2: Cấp quyền cho Users

Có 3 cách cấp quyền:

#### **Cách 1: Sử dụng Grant Script** (Khuyến nghị)

```bash
# URL:
http://your-domain.com/iso2/grant_congviec_permission.php
```

Giao diện web cho phép:
- Chọn user từ dropdown
- Chọn các permissions cần cấp
- Xem danh sách quyền hiện tại

#### **Cách 2: Trực tiếp qua SQL**

```sql
-- Cấp tất cả quyền cho user ID 10
INSERT INTO user_permissions (user_id, permission_id)
SELECT 10, id FROM permissions WHERE name LIKE 'congviec_suachua.%'
ON DUPLICATE KEY UPDATE user_id = user_id;

-- Cấp quyền xem và tạo cho user ID 15
INSERT INTO user_permissions (user_id, permission_id)
SELECT 15, id FROM permissions WHERE name IN ('congviec_suachua.view', 'congviec_suachua.create')
ON DUPLICATE KEY UPDATE user_id = user_id;
```

#### **Cách 3: Cấp quyền cho Role**

```sql
-- Cấp tất cả quyền cho role ID 2 (user thường)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE name LIKE 'congviec_suachua.%'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Cấp chỉ quyền xem cho role viewer (role_id = 3)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE name = 'congviec_suachua.view'
ON DUPLICATE KEY UPDATE role_id = role_id;
```

---

## 🔍 Kiểm tra quyền trong Code

### 1. Trong PHP Files

```php
// Kiểm tra xem user có quyền view không
if (hasPermission('congviec_suachua.view')) {
    // Hiển thị danh sách công việc
}

// Kiểm tra quyền tạo
if (hasPermission('congviec_suachua.create')) {
    // Hiển thị nút "Thêm công việc"
}

// Kiểm tra quyền sửa
if (hasPermission('congviec_suachua.edit')) {
    // Hiển thị form sửa
}

// Kiểm tra quyền xóa
if (hasPermission('congviec_suachua.delete')) {
    // Hiển thị nút xóa
}

// Yêu cầu bắt buộc phải có quyền (redirect nếu không có)
requirePermission('congviec_suachua.view');
```

### 2. Trong Views (Widget)

```php
<!-- Chỉ hiện nút "Thêm công việc" nếu có quyền create -->
<?php if (hasPermission('congviec_suachua.create')): ?>
<button onclick="openAddCongViecModal()">
    <i class="fas fa-plus-circle"></i> Thêm công việc
</button>
<?php endif; ?>

<!-- Chỉ hiện nút xóa nếu có quyền delete -->
<?php if (hasPermission('congviec_suachua.delete')): ?>
<button onclick="deleteCongViec(<?= $cv['stt'] ?>)">
    <i class="fas fa-trash"></i>
</button>
<?php endif; ?>
```

### 3. Trong Controller / API Endpoints

```php
// congviec_suachua.php
switch ($action) {
    case 'create':
        if (!hasPermission('congviec_suachua.create')) {
            echo json_encode(['success' => false, 'message' => 'Không có quyền tạo công việc']);
            exit;
        }
        $result = $controller->create();
        break;

    case 'delete':
        if (!hasPermission('congviec_suachua.delete')) {
            echo json_encode(['success' => false, 'message' => 'Không có quyền xóa công việc']);
            exit;
        }
        $result = $controller->delete();
        break;
}
```

---

## 📂 Files đã cập nhật

| File                                         | Thay đổi                                      |
|----------------------------------------------|-----------------------------------------------|
| `migrations/20260225_add_congviec_permissions.sql` | ✅ Migration SQL thêm permissions             |
| `execute_add_congviec_permissions.php`       | ✅ Script web để execute migration            |
| `grant_congviec_permission.php`              | ✅ Script web cấp quyền cho users             |
| `congviec_suachua.php`                       | ✅ Thêm permission checks cho actions         |
| `views/hososcbd/components/congviec_widget.php` | ✅ Ẩn/hiện buttons theo permissions          |

---

## 🔄 Luồng phân quyền hoạt động

```
┌─────────────────────┐
│   User Login        │
│   $_SESSION loaded  │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────────────────┐
│  hasPermission() được gọi       │
│  Check: Role + Direct User Perm │
└──────────┬──────────────────────┘
           │
           ▼
    ┌──────────┴──────────┐
    │                     │
   YES                   NO
    │                     │
    ▼                     ▼
┌────────────┐      ┌──────────────┐
│ Show UI    │      │ Hide UI      │
│ Allow API  │      │ Deny API     │
└────────────┘      └──────────────┘
```

### Chi tiết kiểm tra:

1. **User có permission trực tiếp?**
   - Kiểm tra bảng `user_permissions` → `permission_id` match
   - Nếu có → ✅ Allowed

2. **User có role với permission?**
   - Kiểm tra `users.role` → `roles.id`
   - Kiểm tra `role_permissions` → `permission_id` match
   - Nếu có → ✅ Allowed

3. **Không có quyền nào?**
   - ❌ Denied
   - Redirect hoặc hiển thị thông báo lỗi

---

## 💡 Use Cases

### UC1: Admin toàn quyền

```sql
-- Admin role (role_id = 1) đã được cấp ALL permissions sau khi chạy migration
-- Không cần làm gì thêm
```

**Result:** Admin có thể:
- ✅ Xem tất cả công việc
- ✅ Thêm công việc mới
- ✅ Sửa công việc
- ✅ Xóa công việc

### UC2: User chỉ xem

```sql
INSERT INTO user_permissions (user_id, permission_id)
SELECT 20, id FROM permissions WHERE name = 'congviec_suachua.view';
```

**Result:** User 20 có thể:
- ✅ Xem danh sách công việc
- ✅ Xem thống kê, KPI
- ❌ Không thấy nút "Thêm công việc"
- ❌ Không thấy nút "Xóa"

### UC3: Nhân viên tạo và xem (không sửa/xóa)

```sql
INSERT INTO user_permissions (user_id, permission_id)
SELECT 25, id FROM permissions WHERE name IN ('congviec_suachua.view', 'congviec_suachua.create');
```

**Result:** User 25 có thể:
- ✅ Xem công việc
- ✅ Thêm công việc mới (trong ngày <= 8h)
- ❌ Không thể xóa
- ❌ Widget không hiện nút "Xóa"

### UC4: Role "Supervisor" - xem + sửa

```sql
-- Tạo role supervisor
INSERT INTO roles (name, description) VALUES ('supervisor', 'Giám sát sửa chữa');

-- Cấp quyền view + edit
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id 
FROM roles r, permissions p
WHERE r.name = 'supervisor' 
AND p.name IN ('congviec_suachua.view', 'congviec_suachua.edit');

-- Gán role cho user
UPDATE users SET role = 'supervisor' WHERE stt = 30;
```

**Result:** User 30 có thể:
- ✅ Xem công việc
- ✅ Sửa công việc (cập nhật trạng thái, thời gian...)
- ❌ Không thể tạo mới
- ❌ Không thể xóa

---

## ⚙️ Testing

### 1. Test permission checks

```bash
# Login với user khác nhau và kiểm tra:

1. User không có quyền view → Redirect /iso2/index.php?error=no_permission
2. User có view, không có create → Không thấy nút "Thêm công việc"
3. User có create → Click "Thêm công việc" → Form hiện ra → Save → Thành công
4. User không có delete → Không thấy icon trash trong table
5. API delete với user không có quyền → {"success": false, "message": "Không có quyền xóa"}
```

### 2. Test migration scripts

```bash
# Test execute_add_congviec_permissions.php
1. Chạy URL → Xem kết quả
2. Kiểm tra DB: SELECT * FROM permissions WHERE name LIKE 'congviec_suachua.%'
3. Verify 4 records tồn tại

# Test grant_congviec_permission.php
1. Chọn user → Chọn permissions → Submit
2. Kiểm tra DB: SELECT * FROM user_permissions WHERE user_id = X
3. Verify records được insert
```

### 3. Test UI visibility

```bash
# Login với users khác nhau:
1. Admin → Thấy tất cả buttons (Thêm, Xem, Xóa)
2. Viewer → Chỉ thấy icon mắt (Xem)
3. Creator → Thấy "Thêm công việc", không thấy "Xóa"
```

---

## 🐛 Troubleshooting

### Vấn đề 1: User không thấy quyền sau khi cấp

**Nguyên nhân:** Session cache

**Giải pháp:**
```bash
1. Đăng xuất
2. Xóa cache browser (Ctrl+Shift+Del)
3. Đăng nhập lại
```

### Vấn đề 2: Migration báo lỗi "Duplicate entry"

**Nguyên nhân:** Permissions đã tồn tại

**Giải pháp:** 
- Đây là **NORMAL** behavior (ON DUPLICATE KEY UPDATE)
- Migration vẫn thành công, bỏ qua lỗi này

### Vấn đề 3: Admin không thấy nút "Thêm công việc"

**Kiểm tra:**
```sql
-- Check admin có permissions không
SELECT 
    u.stt, u.username, u.role,
    p.name as permission_name
FROM users u
LEFT JOIN role_permissions rp ON u.role = rp.role_id
LEFT JOIN permissions p ON rp.permission_id = p.id
WHERE u.stt = 1 AND p.name LIKE 'congviec_suachua.%';

-- Nếu empty → Chạy lại migration hoặc grant script
```

### Vấn đề 4: API vẫn cho phép delete dù không có quyền

**Nguyên nhân:** Cache PHP hoặc file cũ

**Giải pháp:**
```bash
1. Clear PHP opcache (restart web server)
2. Hard refresh browser (Ctrl+F5)
3. Verify file congviec_suachua.php có permission checks chưa
```

---

## 📝 Best Practices

### 1. Nguyên tắc phân quyền

✅ **DO:**
- Cấp quyền theo role (không cấp trực tiếp cho từng user)
- Admin luôn có ALL permissions
- Viewer chỉ có `view` permission
- User thường có `view` + `create`
- Supervisor có `view` + `edit`
- Manager có ALL permissions

❌ **DON'T:**
- Cấp quyền `delete` cho user thường
- Cho phép user tự cấp quyền cho mình
- Hardcode user_id trong code

### 2. Security

- ✅ Luôn check permission ở **backend** (PHP)
- ✅ Frontend visibility chỉ để UX tốt hơn (không phải security)
- ✅ API endpoints phải validate permission trước khi xử lý
- ✅ Log activity khi có thao tác quan trọng (delete, edit)

### 3. Maintenance

- 📅 Review permissions **hàng quý**
- 🔍 Audit user_permissions table định kỳ
- 🗑️ Xóa permissions của users đã nghỉ việc
- 📊 Monitor permission usage (ai dùng gì)

---

## 🔗 Liên quan

### Files liên quan:

```
includes/permissions.php          → Hàm hasPermission(), requirePermission()
config/constants.php              → Định nghĩa permission constants (optional)
models/User.php                   → hasPermission() implementation
controllers/CongViecSuaChuaController.php → Business logic
congviec_suachua.php              → Entry point với permission checks
views/hososcbd/components/congviec_widget.php → UI với conditional rendering
```

### Database schema:

```sql
permissions (id, name, description, created_at)
role_permissions (role_id, permission_id)
user_permissions (user_id, permission_id)
roles (id, name, description)
users (stt, username, role)
```

---

## 📞 Support

**Vấn đề kỹ thuật:**
- Check file: `includes/permissions.php`
- Debug: `error_log()` trong `hasPermission()`
- Database: Verify bảng `permissions`, `role_permissions`, `user_permissions`

**Cần thêm permissions mới:**
1. Thêm vào migration SQL
2. Chạy lại execute script
3. Cập nhật code checks

**Thay đổi logic phân quyền:**
- Edit `models/User.php` → `hasPermission()`
- Clear cache
- Test lại toàn bộ workflows

---

## 📚 Changelog

| Ngày       | Phiên bản | Thay đổi                               |
|------------|-----------|----------------------------------------|
| 2026-02-25 | 1.0.0     | ✅ Initial release - 4 permissions     |

---

**✅ HOÀN TẤT!** Module Công Việc Sửa Chữa đã được tích hợp đầy đủ permissions system.
