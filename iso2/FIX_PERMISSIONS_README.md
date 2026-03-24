# FIX HOÀN TẤT - CHẠY SQL VÀ PHP

## ✅ Đã xác nhận

**Database KHÔNG có bảng `permissions`, `role_permissions`, `user_permissions`**

Hệ thống chỉ dùng:
- Bảng `roles` với cột `permissions` (JSON array)
- Bảng `role_user` (liên kết user với role)

Permissions được lưu dạng: `["project.view","project.create",...,"giohang.view",...]`

## 🚀 CÁCH CÀI ĐẶT MỚI (2 BƯỚC)

### BƯỚC 1: Tạo 4 bảng database

**Qua phpMyAdmin:**
1. Mở phpMyAdmin
2. Chọn database **diavatly_db**
3. Tab "SQL"
4. Copy nội dung file `setup_giohang_phieudathang.sql`
5. Click "Go"

**Hoặc command line:**
```bash
mysql -u root -pMATKHAU diavatly_db < setup_giohang_phieudathang.sql
```

### BƯỚC 2: Thêm permissions vào roles

**Mở trình duyệt:**
```
http://localhost/iso2/grant_giohang_phieudathang_permissions.php
```

Script sẽ tự động:
- Đọc `roles.permissions` hiện tại (JSON)
- Thêm 13 permissions mới
- Update lại vào database
- Hiển thị kết quả

## ✅ Kiểm tra kết quả

```sql
USE diavatly_db;

-- Kiểm tra bảng đã tạo
SHOW TABLES LIKE '%cart%';
SHOW TABLES LIKE '%phieu_dat_hang%';

-- Kiểm tra permissions trong roles
SELECT id, name, permissions FROM roles;
```

Bạn sẽ thấy trong cột `permissions` có chứa:
```json
[..., "giohang.view", "giohang.add", ..., "phieudathang.view", ...]
```

## 📋 13 Permissions đã thêm

**Giỏ hàng (4):**
- giohang.view
- giohang.add
- giohang.edit
- giohang.delete

**Phiếu đặt hàng (9):**
- phieudathang.view
- phieudathang.create
- phieudathang.edit
- phieudathang.delete
- phieudathang.approve
- phieudathang.receive
- phieudathang.stock
- phieudathang.cancel
- phieudathang.export

## ❗ LƯU Ý

Nếu bạn đã chạy SQL trước đó và gặp lỗi, **KHÔNG CẦN XÓA** gì cả. 
File SQL mới đã có:
- `IF NOT EXISTS` cho CREATE TABLE
- `ON DUPLICATE KEY UPDATE` cho INSERT permissions
- `INSERT IGNORE` cho role_permissions

→ Chạy lại an toàn, không bị trùng lặp!

## 🎯 Sau khi chạy SQL xong

1. **Clear cache trình duyệt** (Ctrl+Shift+R)
2. **Vào trang Vật tư thanh lý**
3. **Tìm 2 nút mới:**
   - "Giỏ hàng" (màu tím)
   - "Quản lý phiếu ĐH" (màu xanh indigo)
4. **Click icon 🛒** ở mỗi dòng để test

## 🆘 Nếu vẫn lỗi

Gửi cho tôi:
```sql
-- Chạy lệnh này và gửi kết quả
DESCRIBE permissions;
```

Để tôi xem cấu trúc bảng permissions chính xác.
