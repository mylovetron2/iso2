# ✅ ĐÃ THÊM PERMISSIONS VÀO GIAO DIỆN QUẢN LÝ QUYỀN

## 🎯 Đã làm gì:

**File:** `views/admin/permissions_manager.php`

### Thêm 13 permissions mới:

**Giỏ hàng (4):**
- ✅ giohang.view - Xem giỏ hàng
- ✅ giohang.add - Thêm vào giỏ hàng
- ✅ giohang.edit - Sửa giỏ hàng
- ✅ giohang.delete - Xóa khỏi giỏ hàng

**Phiếu đặt hàng (9):**
- ✅ phieudathang.view - Xem phiếu đặt hàng
- ✅ phieudathang.create - Tạo phiếu đặt hàng
- ✅ phieudathang.edit - Sửa phiếu đặt hàng
- ✅ phieudathang.delete - Xóa phiếu đặt hàng
- ✅ phieudathang.approve - Duyệt phiếu đặt hàng
- ✅ phieudathang.receive - Nhận hàng
- ✅ phieudathang.stock - Nhập kho
- ✅ phieudathang.cancel - Hủy phiếu
- ✅ phieudathang.export - Xuất Excel phiếu ĐH

---

## 🚀 CÁCH SỬ DỤNG

### Bước 1: Vào giao diện Quản lý quyền

```
https://diavatly.cloud/iso2/views/admin/permissions_manager.php
```

**Lưu ý:** Chỉ Admin mới vào được trang này!

### Bước 2: Tìm 2 nhóm mới

Scroll xuống, bạn sẽ thấy 2 nhóm mới:

📦 **Giỏ hàng**
- ☐ Xem giỏ hàng
- ☐ Thêm vào giỏ hàng
- ☐ Sửa giỏ hàng
- ☐ Xóa khỏi giỏ hàng

📋 **Phiếu đặt hàng**
- ☐ Xem phiếu đặt hàng
- ☐ Tạo phiếu đặt hàng
- ☐ Sửa phiếu đặt hàng
- ☐ Xóa phiếu đặt hàng
- ☐ Duyệt phiếu đặt hàng
- ☐ Nhận hàng
- ☐ Nhập kho
- ☐ Hủy phiếu
- ☐ Xuất Excel phiếu ĐH

### Bước 3: Chọn permissions cho từng Role

**Ví dụ cho Role "User":**
1. Tìm khung của Role "User"
2. Kéo xuống tìm nhóm "Giỏ hàng"
3. **Tick/Chọn** 4 checkbox:
   - ✅ Xem giỏ hàng
   - ✅ Thêm vào giỏ hàng
   - ✅ Sửa giỏ hàng
   - ✅ Xóa khỏi giỏ hàng

4. Tìm nhóm "Phiếu đặt hàng"
5. **Tick/Chọn** permissions cơ bản:
   - ✅ Xem phiếu đặt hàng
   - ✅ Tạo phiếu đặt hàng
   - ✅ Sửa phiếu đặt hàng

6. Click nút **"Lưu quyền"** ở góc phải trên

**Ví dụ cho Role "Admin":**
- Tick **TẤT CẢ** 13 checkboxes (giohang + phieudathang)
- Click "Lưu quyền"

### Bước 4: Verify đã lưu

Sau khi click "Lưu quyền", trang sẽ reload và hiện:
```
✅ Cập nhật quyền thành công!
```

Kiểm tra lại: Các checkbox đã chọn vẫn còn tích ✅

---

## 🧪 TEST SAU KHI CẤP QUYỀN

### Bước 1: User logout và login lại

**LƯU Ý QUAN TRỌNG:** Phải logout và login lại để session load permissions mới!

```
1. Logout
2. Login lại
3. Tiếp tục test
```

### Bước 2: Check permissions

```
https://diavatly.cloud/iso2/check_user_permissions.php
```

Should see: **"✅ ĐÃ CÓ TẤT CẢ PERMISSIONS GIỎ HÀNG!"**

### Bước 3: Test giỏ hàng

**Option 1: Từ menu**
- Vào menu sidebar
- Click "Giỏ hàng"
- Không còn bị redirect đến hososcbd.php!

**Option 2: Từ trang vật tư**
```
https://diavatly.cloud/iso2/vattuthanhly.php
```
- Thấy nút "Giỏ hàng" (màu tím)
- Thấy icon 🛒 trong mỗi dòng
- Click 🛒 → Badge tăng
- Click "Giỏ hàng" → Xem giỏ

### Bước 4: Test workflow đầy đủ

1. **Thêm vào giỏ:** Click 🛒 ở 3-5 items
2. **Xem giỏ:** Click nút "Giỏ hàng"
3. **Sửa số lượng:** Thay đổi số lượng → Auto-save
4. **Tạo phiếu:** Click "Tạo Phiếu Đặt Hàng"
5. **Điền form:** NCC, số HĐ, ngày...
6. **Lưu phiếu:** Click "Lưu"
7. **Workflow:** Draft → Duyệt → Nhận hàng → Nhập kho
8. **Kiểm tra:** Số lượng tồn kho tăng

---

## 📊 GỢI Ý CẤP QUYỀN CHO TỪNG ROLE

### Role: Admin (Tất cả)
```
✅ Tất cả 13 permissions
```

### Role: Manager (Hầu hết)
```
✅ giohang: view, add, edit, delete (4)
✅ phieudathang: view, create, edit, approve, receive, stock, export (7)
❌ phieudathang.delete, cancel (2) - Chỉ Admin
```

### Role: User (Cơ bản)
```
✅ giohang: view, add, edit, delete (4)
✅ phieudathang: view, create, edit (3)
❌ Approve, receive, stock, delete, cancel, export (6) - Manager/Admin only
```

### Role: Viewer (Chỉ xem)
```
✅ giohang: view (1)
✅ phieudathang: view (1)
❌ Tất cả các quyền khác
```

---

## 🔧 LƯU Ý QUAN TRỌNG

### 1. Phải logout/login sau khi cấp quyền
Session cache permissions, không tự động reload!

### 2. Phải chạy SQL trước
Nếu chưa chạy `setup_giohang_phieudathang.sql`:
- Cấp quyền vẫn OK
- Nhưng tính năng không hoạt động (thiếu bảng)

### 3. Kiểm tra trong database
```sql
SELECT id, name, permissions 
FROM roles 
ORDER BY id;
```

Should see permissions có chứa:
```
...,giohang.view,giohang.add,...,phieudathang.view,...
```

### 4. Format permissions
Permissions trong database lưu dạng:
```
project.view,project.create,giohang.view,giohang.add,...
```

Separated by comma (,) - KHÔNG có space!

---

## ❓ TROUBLESHOOTING

### Lỗi 1: "Không thấy nhóm Giỏ hàng"
**Nguyên nhân:** File chưa update
**Giải pháp:** 
- Clear browser cache (Ctrl + Shift + R)
- Reload trang permissions_manager.php

### Lỗi 2: "Đã tick nhưng sau khi Lưu bị mất"
**Nguyên nhân:** Form submit lỗi hoặc database permission không đúng format
**Giải pháp:**
- Check database: `SELECT permissions FROM roles WHERE id = ?`
- Đảm bảo format: `perm1,perm2,perm3` (comma-separated, no spaces)

### Lỗi 3: "Đã cấp quyền nhưng vẫn bị redirect"
**Nguyên nhân:** Chưa logout/login lại
**Giải pháp:**
1. Logout
2. Login lại
3. Test lại check_user_permissions.php

### Lỗi 4: "Admin không thấy trang Quản lý quyền"
**Nguyên nhân:** User không có role Admin
**Giải pháp:**
```sql
-- Grant Admin role
INSERT INTO role_user (user_id, role_id) 
VALUES (?, (SELECT id FROM roles WHERE name = 'Admin' LIMIT 1));
```

---

## 🔗 QUICK LINKS

- 🔑 **Quản lý quyền:** https://diavatly.cloud/iso2/views/admin/permissions_manager.php
- 🔍 **Check permissions:** https://diavatly.cloud/iso2/check_user_permissions.php
- 🧪 **Test giỏ hàng:** https://diavatly.cloud/iso2/test_giohang.php
- 🛒 **Giỏ hàng:** https://diavatly.cloud/iso2/giohang.php
- 📦 **Vật tư:** https://diavatly.cloud/iso2/vattuthanhly.php

---

## ✅ CHECKLIST

- [ ] 1. Vào trang Quản lý quyền
- [ ] 2. Tìm nhóm "Giỏ hàng" và "Phiếu đặt hàng"
- [ ] 3. Tick permissions cho từng Role
- [ ] 4. Click "Lưu quyền" cho mỗi Role
- [ ] 5. Thấy message "✅ Cập nhật quyền thành công!"
- [ ] 6. Logout và Login lại
- [ ] 7. Check: check_user_permissions.php → Thấy "✅ ĐÃ CÓ..."
- [ ] 8. Test: Click "Giỏ hàng" → Không lỗi
- [ ] 9. Test: Click 🛒 thêm item → Badge tăng
- [ ] 10. Test: Tạo phiếu đặt hàng

---

**Vào ngay trang Quản lý quyền và cấp permissions!** 🚀

```
https://diavatly.cloud/iso2/views/admin/permissions_manager.php
```
