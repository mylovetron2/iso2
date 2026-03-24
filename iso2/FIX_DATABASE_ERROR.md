# ⚠️ ĐÃ FIX LỖI DATABASE CONNECTION

## 🔍 Vấn đề gì đã xảy ra:

Lỗi: **"Không kết nối được database"**

**Nguyên nhân:** Test script sử dụng mysqli, nhưng project này dùng **PDO**.

## ✅ Đã khắc phục:

1. ✅ **Tạo test_database.php** - Test database chi tiết
2. ✅ **Sửa test_giohang.php** - Chuyển từ mysqli sang PDO

---

## 🚀 CHẠY TEST NGAY:

### Bước 1: Test database connection
```
https://diavatly.cloud/iso2/test_database.php
```

Script sẽ kiểm tra:
- ✅ Config file
- ✅ Database settings (host, user, name...)
- ✅ PDO connection
- ✅ MySQL version
- ✅ Các bảng cần thiết
- ✅ Permissions trong roles

**Kết quả mong đợi:** "✅ DATABASE KẾT NỐI THÀNH CÔNG!"

### Bước 2: Test giỏ hàng
```
https://diavatly.cloud/iso2/test_giohang.php
```

Nếu Bước 1 OK, test này sẽ không còn lỗi database.

---

## 📋 Nếu test_database.php báo lỗi:

### Lỗi 1: "Access denied" (Sai user/pass)
```
→ Kiểm tra config/database.php
→ Đảm bảo DB_USER và DB_PASS đúng
```

### Lỗi 2: "Unknown database" (DB không tồn tại)
```sql
-- Tạo database:
CREATE DATABASE diavatly_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Lỗi 3: "Connection refused" (MySQL không chạy)
```
→ Kiểm tra MySQL service đang chạy
→ Kiểm tra DB_HOST và DB_PORT
```

### Lỗi 4: "Thiếu bảng cart_vattu_thanh_ly"
```
→ Cần chạy SQL: setup_giohang_phieudathang.sql
→ Xem hướng dẫn bên dưới
```

---

## 📦 Nếu thiếu bảng (chưa chạy SQL):

### Chạy SQL setup:

**phpMyAdmin:**
1. Chọn database: `diavatly_db`
2. Tab "SQL"
3. Copy toàn bộ file: `setup_giohang_phieudathang.sql`
4. Paste vào SQL editor
5. Click "Go"

**Kết quả:** Tạo 4 bảng mới:
- `cart_vattu_thanh_ly` - Giỏ hàng
- `phieu_dat_hang` - Phiếu đặt hàng
- `phieu_dat_hang_chi_tiet` - Chi tiết phiếu
- `lich_su_nhap_kho` - Lịch sử nhập kho

---

## 🔐 Sau khi chạy SQL:

### Grant permissions:
```
https://diavatly.cloud/iso2/grant_giohang_phieudathang_permissions.php
```

Script sẽ thêm 13 permissions vào `roles.permissions`:
- giohang: view, add, edit, delete
- phieudathang: view, create, edit, delete, approve, receive, stock, cancel, export

---

## ✅ CHECKLIST HOÀN CHỈNH:

- [ ] 1. Chạy `test_database.php` → Phải thấy "✅ KẾT NỐI THÀNH CÔNG"
- [ ] 2. Nếu thiếu bảng → Chạy SQL `setup_giohang_phieudathang.sql`
- [ ] 3. Chạy `grant_giohang_phieudathang_permissions.php`
- [ ] 4. Chạy `test_giohang.php` → Tất cả phải xanh ✅
- [ ] 5. Đăng nhập vào hệ thống
- [ ] 6. Test chức năng giỏ hàng

---

## 🔗 Quick Links:

- 🔍 **Test Database:** https://diavatly.cloud/iso2/test_database.php
- 🧪 **Test Giỏ Hàng:** https://diavatly.cloud/iso2/test_giohang.php
- ⚙️ **Grant Permissions:** https://diavatly.cloud/iso2/grant_giohang_phieudathang_permissions.php
- 🔐 **Login:** https://diavatly.cloud/iso2/views/auth/login.php

---

**Hãy chạy test_database.php và báo kết quả!** 🔧
