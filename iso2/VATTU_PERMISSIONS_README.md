# Hướng dẫn Cập nhật Quyền Vật Tư Thanh Lý

## Quyền đã thêm

Module **Vật Tư Thanh Lý** có 4 quyền:

- `vattu.view` - Xem vật tư thanh lý
- `vattu.create` - Tạo vật tư thanh lý  
- `vattu.edit` - Sửa vật tư thanh lý
- `vattu.delete` - Xóa vật tư thanh lý

## Cách cấp quyền

### Tự động (Khuyến nghị)

Chạy script để tự động cấp quyền cho tất cả role:

```bash
php grant_vattu_permission.php
```

Script sẽ:
- Admin/Manager: Full quyền (view, create, edit, delete)
- Viewer/User: Chỉ xem (view)
- Role khác: Xem và sửa (view, edit)

### Thủ công qua giao diện

1. Truy cập: `/iso2/admin_user_permissions.php`
2. Chọn role cần cấp quyền
3. Tích chọn quyền trong nhóm **Vật tư thanh lý**
4. Nhấn **Lưu quyền**

## Menu đã thêm

Menu **Vật tư thanh lý** xuất hiện trong:
- **Quản lý Thiết bị** → **Vật tư thanh lý**
- Icon: 📦 (fas fa-boxes)
- URL: `/iso2/vattuthanhly.php`
- Điều kiện: User phải có quyền `vattu.view`

## Kiểm tra quyền trong code

```php
// Kiểm tra xem user có quyền view không
if (hasPermission('vattu.view')) {
    // Hiển thị danh sách vật tư
}

// Kiểm tra quyền tạo
if (hasPermission('vattu.create')) {
    // Hiển thị nút "Thêm vật tư"
}

// Kiểm tra quyền sửa
if (hasPermission('vattu.edit')) {
    // Hiển thị nút "Sửa"
}

// Kiểm tra quyền xóa
if (hasPermission('vattu.delete')) {
    // Hiển thị nút "Xóa"
}
```

## Files đã cập nhật

1. ✅ `views/admin/permissions_manager.php` - Thêm định nghĩa quyền
2. ✅ `views/layouts/header.php` - Thêm menu item
3. ✅ `grant_vattu_permission.php` - Script tự động cấp quyền
4. ✅ `vattuthanhly.php` - Entry point có kiểm tra quyền
5. ✅ `controllers/VatTuThanhLyController.php` - Các action kiểm tra quyền
6. ✅ `views/vattuthanhly/index.php` - UI kiểm tra quyền cho button

## Lưu ý

- Chỉ role có quyền `vattu.view` mới thấy menu
- Các action create/edit/delete sẽ bị chặn nếu không có quyền tương ứng
- Quyền được lưu trong bảng `roles` ở cột `permissions` (dạng CSV)
