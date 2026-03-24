# HƯỚNG DẪN CÀI ĐẶT - GIỎ HÀNG & PHIẾU ĐẶT HÀNG

## ⚠️ LƯU Ý QUAN TRỌNG

**Database KHÔNG có bảng `permissions` riêng!**

Hệ thống lưu permissions trong `roles.permissions` (JSON array), không dùng bảng `permissions`, `role_permissions`, `user_permissions`.

---

## 🚀 BƯỚC 1: TẠO 4 BẢNG DATABASE (2 phút)

### Cách 1: Qua phpMyAdmin (DỄ NHẤT - KHUYẾN NGHỊ)

1. Mở phpMyAdmin
2. Chọn database **`diavatly_db`**
3. Click tab **"SQL"**
4. Copy toàn bộ nội dung file: **`setup_giohang_phieudathang.sql`**
5. Paste vào và click **"Go"**

### Cách 2: Command line MySQL

```bash
mysql -u root -p diavatly_db < setup_giohang_phieudathang.sql
```

### ✅ Kết quả mong đợi:

```
✅ HOÀN TẤT! Đã tạo 4 bảng
```

**4 bảng được tạo:**
- `cart_vattu_thanh_ly` - Giỏ hàng
- `phieu_dat_hang` - Phiếu đặt hàng (header)
- `phieu_dat_hang_chi_tiet` - Chi tiết phiếu
- `lich_su_nhap_kho` - Lịch sử nhập kho

### Kiểm tra:

```sql
USE diavatly_db;
SHOW TABLES LIKE '%cart%';
SHOW TABLES LIKE '%phieu_dat_hang%';
SHOW TABLES LIKE '%lich_su_nhap_kho%';
```

---

## 🚀 BƯỚC 2: THÊM PERMISSIONS VÀO ROLES (30 giây)

**Mở trình duyệt và vào URL:**

```
http://localhost/iso2/grant_giohang_phieudathang_permissions.php
```

### Script sẽ làm gì?

1. ✅ Đọc `roles.permissions` hiện tại (JSON array)
2. ✅ Thêm 13 permissions mới vào array
3. ✅ Gán đầy đủ cho role **Admin**
4. ✅ Gán quyền cơ bản cho role **User**
5. ✅ Hiển thị kết quả chi tiết

### 13 Permissions được thêm:

**Giỏ hàng (4):**
- `giohang.view` - Xem giỏ hàng
- `giohang.add` - Thêm vào giỏ hàng
- `giohang.edit` - Sửa số lượng
- `giohang.delete` - Xóa khỏi giỏ hàng

**Phiếu đặt hàng (9):**
- `phieudathang.view` - Xem phiếu
- `phieudathang.create` - Tạo phiếu mới
- `phieudathang.edit` - Sửa phiếu
- `phieudathang.delete` - Xóa phiếu
- `phieudathang.approve` - Duyệt phiếu
- `phieudathang.receive` - Nhận hàng
- `phieudathang.stock` - Nhập kho
- `phieudathang.cancel` - Hủy phiếu
- `phieudathang.export` - Xuất Excel

### ✅ Kết quả mong đợi:

Trang web hiển thị:
```
✅ HOÀN TẤT!
Đã cập nhật X role(s)
13 permissions đã được thêm vào hệ thống phân quyền
```

### Kiểm tra:

```sql
SELECT id, name, permissions FROM roles;
```

Bạn sẽ thấy permissions chứa chuỗi JSON như:
```json
["project.view","project.create",...,"giohang.view","giohang.add",...]
```

---

## 🚀 BƯỚC 3: TEST GIAO DIỆN (5 phút)

### 1. Clear cache trình duyệt
- **Windows**: Ctrl + Shift + R
- **Mac**: Cmd + Shift + R

### 2. Vào trang Vật tư thanh lý

```
http://localhost/iso2/vattuthanhly.php
```

### 3. Tìm các thành phần mới:

**✅ 2 nút mới** (phía trên danh sách):
- **"Giỏ hàng"** (màu tím) - có badge số lượng
- **"Quản lý phiếu ĐH"** (màu xanh indigo)

**✅ Icon 🛒 mới** (mỗi dòng vật tư):
- Click vào để thêm vào giỏ hàng
- Badge cập nhật real-time

### 4. Test thêm vào giỏ hàng

1. Click icon 🛒 ở 3-5 dòng vật tư
2. Xem badge số lượng tăng lên
3. Thông báo "Đã thêm vào giỏ hàng" xuất hiện

### 5. Xem giỏ hàng

1. Click nút **"Giỏ hàng"**
2. Kiểm tra:
   - ✅ Danh sách vật tư đã chọn
   - ✅ Sửa số lượng (auto-save)
   - ✅ Thêm ghi chú
   - ✅ Xóa từng item
   - ✅ Nút "Xóa tất cả"
   - ✅ Nút "Tạo Phiếu Đặt Hàng"

---

## 🚀 BƯỚC 4: TEST WORKFLOW ĐẶT HÀNG (10 phút)

### A. Tạo phiếu từ giỏ hàng

1. Trong giỏ hàng, click **"Tạo Phiếu Đặt Hàng"**
2. Điền thông tin:
   - Nhà cung cấp: "Công ty ABC"
   - Số HĐ NCC: "HD-2024-001"
   - Ngày dự kiến nhận: chọn ngày
   - Số lượng, đơn giá cho từng item
3. Click **"Lưu Phiếu"**
4. ✅ Phiếu tạo thành công với mã: **PDH-20260323-001**

### B. Quy trình workflow

```
NHÁP → Duyệt → ĐÃ ĐẶT → Nhận hàng → ĐÃ NHẬN → Nhập kho → ĐÃ NHẬP KHO
```

#### B1. Duyệt phiếu (cần quyền `phieudathang.approve`)

- Click **"Duyệt Phiếu"**
- Trạng thái: NHÁP → **ĐÃ ĐẶT**

#### B2. Nhận hàng (cần quyền `phieudathang.receive`)

- Click **"Nhận Hàng"**
- Nhập số lượng thực nhận (có thể < số đặt)
- Click **"Xác nhận"**
- Trạng thái:
  - Nhận ít hơn → **NHẬN MỘT PHẦN**
  - Nhận đủ → **ĐÃ NHẬN**

#### B3. Nhập kho (cần quyền `phieudathang.stock`)

- Click **"Nhập Kho"**
- Điền vị trí kho: "Kệ A-01"
- Tình trạng: "Tốt"
- Click **"Xác nhận nhập kho"**
- Trạng thái: ĐÃ NHẬN → **ĐÃ NHẬP KHO**

#### B4. Kiểm tra tồn kho đã cộng

```sql
SELECT stt, ten_tieng_viet, soluong_conlai 
FROM vattu_thanh_ly_iso 
WHERE stt IN (1, 2, 3); -- STT của vật tư vừa nhập
```

✅ Số lượng tồn kho đã được cộng thêm!

#### B5. Kiểm tra lịch sử

```sql
SELECT * FROM lich_su_nhap_kho 
ORDER BY ngay_nhap DESC LIMIT 5;
```

✅ Có log: số lượng trước/sau, người nhập, ngày giờ

---

## ❌ XỬ LÝ LỖI THƯỜNG GẶP

### 1. Badge không hiển thị

**Nguyên nhân**: JavaScript chưa load hoặc lỗi AJAX

**Giải pháp**:
```
1. Hard refresh: Ctrl + Shift + R
2. F12 → Console → kiểm tra lỗi
3. F12 → Network → xem request giohang.php?action=getCount
```

### 2. Không thấy nút "Giỏ hàng" / "Phiếu ĐH"

**Nguyên nhân**: Thiếu quyền

**Giải pháp**:
```sql
-- Kiểm tra permissions của user hiện tại
SELECT r.name, r.permissions 
FROM role_user ru 
JOIN roles r ON ru.role_id = r.id 
WHERE ru.user_id = YOUR_USER_ID;
```

Nếu không có quyền, chạy lại:
```
http://localhost/iso2/grant_giohang_phieudathang_permissions.php
```

### 3. Thêm vào giỏ hàng bị lỗi

**Kiểm tra**:
```sql
-- Bảng cart có tồn tại không?
SHOW TABLES LIKE 'cart_vattu_thanh_ly';

-- Dữ liệu có insert được không?
SELECT * FROM cart_vattu_thanh_ly LIMIT 5;
```

**Xem PHP error log**:
```php
// Thêm vào đầu giohang.php để debug
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### 4. Không thấy nút "Duyệt" / "Nhận hàng"

**Nguyên nhân**: User thiếu quyền `phieudathang.approve` hoặc `phieudathang.receive`

**Giải pháp**:

Nếu bạn là admin, quyền đã được gán tự động. Nếu không thấy:

1. Logout và login lại
2. Kiểm tra session có đúng không
3. Chạy lại script grant permissions

### 5. Nhập kho không cộng số lượng

**Kiểm tra cột `soluong_conlai`**:
```sql
SHOW COLUMNS FROM vattu_thanh_ly_iso LIKE 'soluong_conlai';
```

Nếu không có, thêm:
```sql
ALTER TABLE vattu_thanh_ly_iso 
ADD COLUMN soluong_conlai DECIMAL(10,2) DEFAULT 0 
AFTER soluong;
```

---

## ✅ CHECKLIST HOÀN THÀNH

### Database Setup
- [ ] 4 bảng đã tạo thành công
- [ ] Permissions đã thêm vào roles
- [ ] Kiểm tra `SELECT * FROM roles` có chứa giohang.* và phieudathang.*

### Giao diện
- [ ] Thấy nút "Giỏ hàng" và "Quản lý phiếu ĐH"
- [ ] Thấy icon 🛒 ở mỗi dòng vật tư
- [ ] Badge số lượng cập nhật real-time

### Workflow
- [ ] Thêm vào giỏ hàng thành công
- [ ] Tạo phiếu từ giỏ hàng
- [ ] Duyệt phiếu (draft → ordered)
- [ ] Nhận hàng (ordered → received)
- [ ] Nhập kho (received → stocked)
- [ ] Số lượng tồn kho được cộng thêm

---

## 📚 FILES QUAN TRỌNG

**SQL Scripts:**
- `setup_giohang_phieudathang.sql` - Tạo 4 bảng (chạy đầu tiên)

**PHP Scripts:**
- `grant_giohang_phieudathang_permissions.php` - Thêm permissions vào roles
- `giohang.php` - Router giỏ hàng
- `phieudathang.php` - Router phiếu đặt hàng
- `controllers/GioHangController.php` - Logic giỏ hàng
- `controllers/PhieuDatHangController.php` - Logic phiếu đặt hàng

**Views:**
- `views/giohang/index.php` - Giao diện giỏ hàng
- `views/phieudathang/index.php` - Danh sách phiếu
- `views/phieudathang/create.php` - Form tạo phiếu
- `views/phieudathang/view.php` - Chi tiết phiếu
- `views/phieudathang/receive.php` - Form nhận hàng

**Documentation:**
- `GIOHANG_PHIEUDATHANG_README.md` - Hướng dẫn kỹ thuật đầy đủ
- `SETUP_GIOHANG_PERMISSIONS.md` - File này

---

## 🎉 HOÀN TẤT!

Hệ thống giỏ hàng & phiếu đặt hàng đã sẵn sàng sử dụng!

**2 bước còn lại:**
1. ✅ Chạy SQL tạo bảng
2. ✅ Chạy PHP script thêm permissions

**→ Xong! Vào web và test thôi!** 🚀
