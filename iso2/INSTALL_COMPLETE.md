# 🎯 HƯỚNG DẪN CÀI ĐẶT GIỎ HÀNG - ĐẦY ĐỦ

## 📋 CHECKLIST HOÀN CHỈNH

### ✅ Phase 1: Đã hoàn thành (DONE)
- [x] Tạo 4 SQL files (tạo bảng)
- [x] Tạo 2 PHP controllers (GioHangController, PhieuDatHangController) 
- [x] Tạo 2 PHP routers (giohang.php, phieudathang.php)
- [x] Tạo 5 view files (giohang, phieudathang)
- [x] Tích hợp vào header.php (menu "Giỏ hàng")
- [x] Tích hợp vào vattuthanhly.php (nút + icon giỏ)
- [x] Tạo JavaScript AJAX cho giỏ hàng
- [x] Tạo grant_giohang_phieudathang_permissions.php
- [x] Tạo includes/auth_check.php (FIX lỗi 500)
- [x] Tạo includes/permission_check.php (FIX lỗi 500)

### ⏳ Phase 2: Cần người dùng làm (TODO)
- [ ] **Chạy SQL** tạo 4 bảng (phpMyAdmin)
- [ ] **Grant permissions** cho roles (chạy PHP script)
- [ ] **Test** chức năng giỏ hàng

---

## 🚀 CÀI ĐẶT (3 BƯỚC)

### BƯỚC 1: Kiểm tra permissions hiện tại

**Mục đích:** Xem user có quyền `giohang.view` chưa

**Chạy:**
```
https://diavatly.cloud/iso2/check_user_permissions.php
```

**Kết quả:**
- ✅ Nếu thấy "ĐÃ CÓ TẤT CẢ PERMISSIONS" → Bỏ qua Bước 2, nhảy sang Bước 3
- ❌ Nếu thấy "THIẾU X PERMISSIONS" → Làm Bước 2

---

### BƯỚC 2A: Chạy SQL (nếu chưa có bảng)

**Mục đích:** Tạo 4 bảng mới trong database

**Cách làm:**
1. Mở **phpMyAdmin**
2. Chọn database: **`diavatly_db`**
3. Click tab **"SQL"**
4. Mở file: **`setup_giohang_phieudathang.sql`**
5. **Copy toàn bộ** nội dung (161 dòng)
6. **Paste** vào SQL editor
7. Click **"Go"**

**Kết quả mong đợi:**
```
✅ HOÀN TẤT! Đã tạo 4 bảng

Table                      | Rows
---------------------------|-----
cart_vattu_thanh_ly        | 0
phieu_dat_hang             | 0
phieu_dat_hang_chi_tiet    | 0
lich_su_nhap_kho           | 0
```

**Nếu lỗi:**
- "Table already exists" → OK, bỏ qua
- "Access denied" → Kiểm tra quyền user database
- "Unknown database" → Tạo database `diavatly_db` trước

---

### BƯỚC 2B: Grant permissions

**Mục đích:** Thêm 13 permissions vào roles.permissions

**Chạy:**
```
https://diavatly.cloud/iso2/grant_giohang_phieudathang_permissions.php
```

**Script sẽ làm:**
1. Đọc bảng `roles`
2. Với mỗi role, đọc cột `permissions` (JSON array)
3. Thêm 13 permissions mới:
   - giohang: **view, add, edit, delete**
   - phieudathang: **view, create, edit, delete, approve, receive, stock, cancel, export**
4. Lưu lại JSON đã update
5. Hiển thị kết quả

**Kết quả mong đợi:**
```
✅ HOÀN TẤT!
Đã cập nhật 2 role(s)
13 permissions đã được thêm vào hệ thống phân quyền

Role: Admin
- Đã thêm: 13 permissions
- Tổng sau khi thêm: 45 permissions

Role: User  
- Đã thêm: 6 permissions (basic)
- Tổng sau khi thêm: 18 permissions
```

**Nếu lỗi:**
- "Already has permission" → OK, đã có rồi
- "Database connection failed" → Kiểm tra config/database.php
- "Table roles not found" → Database có vấn đề

---

### BƯỚC 3: Verify và Test

**3A. Check lại permissions:**
```
https://diavatly.cloud/iso2/check_user_permissions.php
```

Should see: **"✅ ĐÃ CÓ TẤT CẢ PERMISSIONS GIỎ HÀNG!"**

**3B. Logout và Login lại:**
```
Logout → Login lại (để refresh session)
```

**3C. Test giỏ hàng:**

**Option 1: Vào trang vật tư**
```
https://diavatly.cloud/iso2/vattuthanhly.php
```
- Kiểm tra có 2 nút: "Giỏ hàng" (tím) + "Quản lý phiếu ĐH" (indigo)
- Kiểm tra mỗi dòng có icon 🛒
- Click 🛒 → Thêm vào giỏ → Badge tăng

**Option 2: Vào giỏ hàng trực tiếp**
```
https://diavatly.cloud/iso2/giohang.php?action=index
```
- Nếu giỏ trống → Thấy message "Giỏ hàng trống"
- Nếu có items → Thấy danh sách

**Option 3: Test getCount (đơn giản nhất)**
```
https://diavatly.cloud/iso2/giohang.php?action=getCount
```
Should return JSON: `{"success": true, "count": 0}`

---

## 🧪 TEST WORKFLOW ĐẦY ĐỦ

### 1. Thêm vào giỏ
1. Vào `vattuthanhly.php`
2. Click icon 🛒 ở 3-5 items khác nhau
3. Xem badge tăng (góc phải nút "xiohàng")

### 2. Xem giỏ hàng
1. Click nút "Giỏ hàng"
2. Thấy danh sách items đã chọn
3. Có thể sửa số lượng, ghi chú
4. Auto-save khi sửa

### 3. Tạo phiếu đặt hàng
1. Trong giỏ hàng, click "Tạo Phiếu Đặt Hàng"
2. Điền form: NCC, số HĐ, ngày dự kiến...
3. Xem danh sách items từ giỏ
4. Click "Lưu phiếu"

### 4. Workflow phiếu
1. **Draft** (Nháp) → Click "Duyệt phiếu"
2. **Ordered** (Đã đặt) → Đợi hàng về
3. Click "Nhận hàng" → Nhập số lượng nhận
4. **Received** (Đã nhận) → Click "Nhập kho"
5. **Stocked** (Đã nhập kho) → Tự động cập nhật tồn kho

### 5. Kiểm tra kết quả
```sql
-- Check giỏ hàng
SELECT * FROM cart_vattu_thanh_ly ORDER BY ngay_them DESC LIMIT 5;

-- Check phiếu đặt hàng
SELECT * FROM phieu_dat_hang ORDER BY ngay_lap DESC LIMIT 5;

-- Check lịch sử nhập kho
SELECT * FROM lich_su_nhap_kho ORDER BY ngay_nhap DESC LIMIT 5;

-- Check số lượng tồn đã tăng
SELECT stt, ten_tieng_viet, so_luong 
FROM vattu_thanh_ly_iso 
WHERE stt IN (SELECT DISTINCT vattu_stt FROM lich_su_nhap_kho);
```

---

## 🐛 TROUBLESHOOTING

### Lỗi 1: "Không kết nối được database"
**Giải pháp:**
```
https://diavatly.cloud/iso2/test_database.php
```
Script sẽ chỉ rõ lỗi gì và cách fix

### Lỗi 2: "Bị redirect đến hososcbd.php"
**Nguyên nhân:** Chưa có permission `giohang.view`
**Giải pháp:** Chạy Bước 2B (grant permissions)

### Lỗi 3: "Table cart_vattu_thanh_ly doesn't exist"
**Nguyên nhân:** Chưa chạy SQL
**Giải pháp:** Chạy Bước 2A (SQL tạo bảng)

### Lỗi 4: "Call to undefined function checkPermission()"
**Nguyên nhân:** File permission_check.php chưa load
**Giải pháp:** Đã fix, file đã tạo

### Lỗi 5: "500 Internal Server Error"
**Nguyên nhân:** Thiếu auth_check.php hoặc permission_check.php
**Giải pháp:** Đã fix, 2 file đã tạo

### Lỗi 6: Badge không cập nhật
**Giải pháp:** 
- Clear cache: Ctrl + Shift + R
- Check JavaScript console (F12)
- Check AJAX endpoint: `giohang.php?action=getCount`

### Lỗi 7: Logo/CSS vỡ sau khi grant
**Giải pháp:**
- Clear browser cache
- Hard reload: Ctrl + Shift + R

---

## 📁 FILES ĐÃ TẠO

### SQL Files (5 files)
- ✅ setup_giohang_phieudathang.sql (161 dòng)
- ✅ create_table_cart_giohang.sql
- ✅ create_table_phieu_dat_hang.sql
- ✅ create_table_phieu_dat_hang_chi_tiet.sql
- ✅ create_table_lich_su_nhap_kho.sql

### PHP Controllers (2 files)
- ✅ controllers/GioHangController.php (320 dòng)
- ✅ controllers/PhieuDatHangController.php (580 dòng)

### PHP Routers (2 files)
- ✅ giohang.php (74 dòng)
- ✅ phieudathang.php (96 dòng)

### PHP Helpers (2 files) - MỚI TẠO ĐỂ FIX LỖI 500
- ✅ includes/auth_check.php (66 dòng)
- ✅ includes/permission_check.php (108 dòng)

### View Files (5 files)
- ✅ views/giohang/index.php (240 dòng)
- ✅ views/phieudathang/index.php (180 dòng)
- ✅ views/phieudathang/create.php (115 dòng)
- ✅ views/phieudathang/view.php (120 dòng)
- ✅ views/phieudathang/receive.php (80 dòng)

### Integration Files (2 modified)
- ✅ views/layouts/header.php (thêm menu giỏ hàng)
- ✅ views/vattuthanhly/index.php (thêm nút + icon + JS)

### Grant Script (1 file)
- ✅ grant_giohang_phieudathang_permissions.php (186 dòng)

### Test/Debug Scripts (5 files)
- ✅ test_database.php - Test database connection
- ✅ test_giohang.php - Test giỏ hàng setup
- ✅ check_user_permissions.php - Check permissions chi tiết
- ✅ debug_giohang_500.php - Debug lỗi 500
- ✅ debug_login_v2.php - Debug login issue

### Documentation (6+ files)
- ✅ GIOHANG_PHIEUDATHANG_README.md
- ✅ SETUP_GIOHANG_PERMISSIONS.md
- ✅ FIX_500_ERROR.md
- ✅ FIX_DATABASE_ERROR.md
- ✅ FIX_REDIRECT_ERROR.md
- ✅ TEST_GIOHANG_GUIDE.md
- ✅ INSTALL_COMPLETE.md (file này)

---

## 🔗 QUICK REFERENCE

### Check & Diagnostic
- Check permissions: `check_user_permissions.php`
- Test database: `test_database.php`
- Test giỏ hàng: `test_giohang.php`

### Installation
- Grant permissions: `grant_giohang_phieudathang_permissions.php`
- SQL file: `setup_giohang_phieudathang.sql`

### Usage
- Giỏ hàng: `giohang.php?action=index`
- Phiếu ĐH: `phieudathang.php?action=index`
- Vật tư: `vattuthanhly.php`

---

## ✅ SUCCESS CRITERIA

Khi nào coi như cài đặt thành công:

1. ✅ `check_user_permissions.php` → "ĐÃ CÓ TẤT CẢ PERMISSIONS"
2. ✅ `test_giohang.php` → Tất cả kiểm tra xanh ✅
3. ✅ `vattuthanhly.php` → Có nút "Giỏ hàng" + icon 🛒
4. ✅ Click 🛒 → Badge tăng
5. ✅ Click "Giỏ hàng" → Xem được giỏ
6. ✅ Tạo phiếu → Lưu thành công
7. ✅ Workflow: Draft → Ordered → Received → Stocked
8. ✅ Số lượng tồn kho tự động tăng sau khi nhập kho

---

## 🎯 BẮT ĐẦU NGAY

**3 bước đơn giản:**

1. **Check:** https://diavatly.cloud/iso2/check_user_permissions.php
2. **Grant:** https://diavatly.cloud/iso2/grant_giohang_phieudathang_permissions.php (nếu cần)
3. **Test:** https://diavatly.cloud/iso2/vattuthanhly.php

**Báo kết quả từng bước để được hỗ trợ tiếp!** 🚀
