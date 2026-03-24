# 🔍 DEBUG: Lỗi "Có lỗi xảy ra khi thêm giỏ hàng"

## 📝 Vấn đề đã fix:

✅ **Sửa tên bảng trong GioHangController.php**
- Dòng 340: `phanloai_vattu_thanh_ly_iso` → `phanloai_vattu_thanh_ly`

---

## 🧪 Bước 1: Chạy các test scripts

### Test 1: Kiểm tra bảng database
```
URL: https://diavatly.cloud/iso2/check_table_name.php
```
**Kết quả mong đợi:**
- ✅ Bảng `cart_vattu_thanh_ly` tồn tại
- ✅ Bảng `phanloai_vattu_thanh_ly` tồn tại

**Nếu bảng KHÔNG tồn tại:**
- Chạy file SQL: `setup_giohang_phieudathang.sql` trong phpMyAdmin

---

### Test 2: Test cơ bản thêm giỏ hàng
```
URL: https://diavatly.cloud/iso2/test_add_giohang.php
```
**Kết quả mong đợi:**
- ✅ Database connected
- ✅ Bảng cart_vattu_thanh_ly đã tồn tại
- ✅ Test INSERT thành công

---

### Test 3: Test AJAX endpoint (giống frontend)
```
URL: https://diavatly.cloud/iso2/test_ajax_add_cart.php
```
**Kết quả mong đợi:**
- ✅ Response JSON: `{"success": true, "message": "...", "cart_count": 1}`

---

## 🐛 Các lỗi thường gặp và cách fix:

### Lỗi 1: Bảng `cart_vattu_thanh_ly` không tồn tại
**Triệu chứng:**
```
Table 'diavatly_db.cart_vattu_thanh_ly' doesn't exist
```

**Cách fix:**
1. Vào phpMyAdmin
2. Chọn database: `diavatly_db`
3. Tab "SQL"
4. Copy toàn bộ nội dung file `setup_giohang_phieudathang.sql`
5. Paste và chạy
6. Kiểm tra: 4 bảng mới được tạo:
   - cart_vattu_thanh_ly
   - phieu_dat_hang
   - phieu_dat_hang_chi_tiet
   - lich_su_nhap_kho

---

### Lỗi 2: Tên bảng phân loại sai
**Triệu chứng:**
```
Table 'diavatly_db.phanloai_vattu_thanh_ly_iso' doesn't exist
```

**Cách fix:**
✅ Đã fix trong commit này - thay đổi `GioHangController.php` dòng 340

---

### Lỗi 3: Permission denied
**Triệu chứng:**
- Redirect về trang login
- Hoặc hiện lỗi "Bạn không có quyền..."

**Cách fix:**
1. Vào: https://diavatly.cloud/iso2/views/admin/permissions_manager.php
2. Tìm role của bạn (Admin, User, etc.)
3. Cuộn xuống phần "Giỏ hàng"
4. Tick các checkboxes:
   - ☑ giohang.view
   - ☑ giohang.add
   - ☑ giohang.edit
   - ☑ giohang.delete
5. Nhấn "Lưu quyền"
6. **Logout và login lại** (quan trọng!)

---

### Lỗi 4: AJAX không hoạt động
**Triệu chứng:**
- Console log: `error`, `404`, `500`
- Badge không cập nhật

**Cách fix:**
1. Mở Developer Tools (F12)
2. Tab "Console" - xem lỗi JavaScript
3. Tab "Network" - xem request/response
4. Kiểm tra:
   - URL endpoint: `/iso2/giohang.php?action=add`
   - Method: POST
   - Data: `vattu_stt`, `so_luong`
   - Response: JSON với `success: true/false`

**Nếu 500 error:**
- Check PHP error log: `/iso2/check_php_error_log.php`
- Thường do:
  - Session không có user_id
  - Database connection failed
  - Permissions không đủ

---

## 🔬 Debug chi tiết bằng Browser Console:

### Bước 1: Mở trang tạo phiếu
```
URL: https://diavatly.cloud/iso2/phieudathang.php?action=create&step=1
```

### Bước 2: Mở DevTools (F12)

### Bước 3: Thử tick checkbox
Trong Console, chạy lệnh test:
```javascript
// Test add to cart
fetch('/iso2/giohang.php?action=add', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'vattu_stt=1&so_luong=1'
})
.then(response => response.json())
.then(data => {
    console.log('Success:', data);
})
.catch(error => {
    console.error('Error:', error);
});
```

**Kết quả mong đợi:**
```json
{
  "success": true,
  "message": "Đã thêm vào giỏ hàng",
  "cart_count": 1
}
```

**Nếu lỗi:**
```json
{
  "success": false,
  "message": "Lý do lỗi ở đây"
}
```

---

## 📊 Checklist đầy đủ:

### Database:
- [ ] Bảng `cart_vattu_thanh_ly` tồn tại
- [ ] Bảng `phieu_dat_hang` tồn tại
- [ ] Bảng `phieu_dat_hang_chi_tiet` tồn tại
- [ ] Bảng `lich_su_nhap_kho` tồn tại
- [ ] Bảng `phanloai_vattu_thanh_ly` tồn tại (không có _iso)

### Files:
- [ ] `controllers/GioHangController.php` - dòng 340 dùng đúng tên bảng
- [ ] `controllers/PhieuDatHangController.php` - có method `store()`
- [ ] `views/phieudathang/create.php` - có 2 steps
- [ ] `giohang.php` - có routes `removeByVattu`, `updateByVattu`
- [ ] `phieudathang.php` - có route `store`

### Permissions:
- [ ] User có permission `giohang.view`
- [ ] User có permission `giohang.add`
- [ ] User có permission `giohang.edit`
- [ ] User có permission `giohang.delete`
- [ ] User đã logout/login lại sau khi cấp permissions

### Frontend:
- [ ] jQuery đã load (check Console)
- [ ] Không có JavaScript error
- [ ] AJAX request đúng URL
- [ ] Badge element có ID `cart-badge`

---

## 🚨 Nếu vẫn lỗi sau khi check tất cả:

### 1. Xem PHP error log:
```
URL: https://diavatly.cloud/iso2/check_php_error_log.php
```

### 2. Test trực tiếp controller:
```
URL: https://diavatly.cloud/iso2/test_ajax_add_cart.php
```

### 3. Gửi thông tin lỗi:
Cần các thông tin sau để debug:
- Screenshot màn hình lỗi
- Browser Console log (F12 → Console)
- Network tab response (F12 → Network → Click request → Response)
- PHP error log (từ check_php_error_log.php)

---

## ✅ Sau khi FIX thành công:

Test lại luồng hoàn chỉnh:
1. Vào: `/iso2/phieudathang.php?action=create&step=1`
2. Tick 2-3 vật tư → Badge hiện (1) (2) (3)
3. Nhấn "Tiếp tục" → Hiện tóm tắt vật tư
4. Điền form NCC
5. Nhấn "Tạo phiếu"
6. ✅ Phiếu được tạo, giỏ hàng reset về (0)

---

**Tác giả:** GitHub Copilot  
**Version:** 1.0  
**Ngày:** 2026-03-24
