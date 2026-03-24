# HƯỚNG DẪN NHANH - CHỨC NĂNG GIỎ HÀNG & PHIẾU ĐẶT HÀNG

## BƯỚC 1: CHẠY SQL (2 PHÚT)

### Cách 1: Chạy file master (KHUYẾN NGHỊ)
```bash
mysql -u root -p iso2 < setup_giohang_phieudathang.sql
```

### Cách 2: Chạy từng file
```bash
mysql -u root -p iso2 < create_table_cart_giohang.sql
mysql -u root -p iso2 < create_table_phieu_dat_hang.sql
mysql -u root -p iso2 < create_table_phieu_dat_hang_chi_tiet.sql
mysql -u root -p iso2 < create_table_lich_su_nhap_kho.sql
mysql -u root -p iso2 < add_giohang_phieudathang_permissions.sql
```

### Kiểm tra kết quả
```sql
USE iso2;
SHOW TABLES LIKE '%cart%';
SELECT COUNT(*) FROM permissions WHERE module IN ('giohang', 'phieudathang');
```
Kết quả: Có 4 bảng mới + 13 permissions mới

---

## BƯỚC 2: KIỂM TRA GIAO DIỆN (5 PHÚT)

### 1. Vào trang Vật tư thanh lý
- Tìm 2 nút mới:
  - **"Giỏ hàng"** (màu tím) - có badge số lượng
  - **"Quản lý phiếu ĐH"** (màu xanh indigo)

- Tìm icon 🛒 ở mỗi dòng vật tư

### 2. Thêm vào giỏ hàng
- Click icon 🛒 ở vài dòng vật tư
- Kiểm tra:
  - Badge số lượng tăng lên
  - Thông báo "Đã thêm vào giỏ hàng" xuất hiện

### 3. Xem giỏ hàng
- Click nút **"Giỏ hàng"**
- Kiểm tra:
  - Danh sách vật tư đã chọn
  - Sửa số lượng (auto-save)
  - Xóa từng item hoặc **"Xóa tất cả"**

---

## BƯỚC 3: TEST WORKFLOW ĐẶT HÀNG (10 PHÚT)

### A. Tạo phiếu từ giỏ hàng
1. Trong giỏ hàng, click **"Tạo Phiếu Đặt Hàng"**
2. Điền thông tin:
   - Nhà cung cấp: "Công ty ABC"
   - Số HĐ NCC: "HD-2024-001"
   - Ngày dự kiến: chọn ngày
   - Số lượng, đơn giá cho từng item
3. Click **"Lưu Phiếu"**
4. ✅ Phiếu tạo thành công với trạng thái NHÁP

### B. Quy trình duyệt → nhận → nhập kho

```
NHÁP → Duyệt → ĐÃ ĐẶT → Nhận hàng → ĐÃ NHẬN → Nhập kho → ĐÃ NHẬP KHO
```

#### B1. Duyệt phiếu (nếu có quyền approve)
- Click **"Duyệt Phiếu"**
- Trạng thái: NHÁP → **ĐÃ ĐẶT**

#### B2. Nhận hàng (nếu có quyền receive)
- Click **"Nhận Hàng"**
- Nhập số lượng thực nhận (có thể < số đặt)
- Click **"Xác nhận"**
- Trạng thái: 
  - Nếu nhận ít hơn → **NHẬN MỘT PHẦN**
  - Nếu nhận đủ → **ĐÃ NHẬN**

#### B3. Nhập kho (nếu có quyền stock)
- Click **"Nhập Kho"**
- Điền:
  - Vị trí kho: "Kệ A-01"
  - Tình trạng: "Tốt"
- Click **"Xác nhận nhập kho"**
- Trạng thái: ĐÃ NHẬN → **ĐÃ NHẬP KHO**

#### B4. Kiểm tra tồn kho
```sql
SELECT stt, ten_tieng_viet, soluong_conlai 
FROM vattu_thanh_ly_iso 
WHERE stt IN (1, 2, 3); -- Thay số STT của vật tư vừa nhập
```
✅ Số lượng tồn kho đã được cộng thêm!

#### B5. Kiểm tra lịch sử
```sql
SELECT * FROM lich_su_nhap_kho ORDER BY ngay_nhap DESC LIMIT 5;
```
✅ Có log ghi nhận: số lượng trước/sau, người nhập, ngày giờ

---

## BƯỚC 4: TEST TÍNH NĂNG NÂNG CAO (5 PHÚT)

### 1. Nhận hàng từng phần
- Tạo phiếu với 10 items
- Lần 1: Nhận 3 items → Trạng thái: **NHẬN MỘT PHẦN**
- Lần 2: Nhận 7 items còn lại → Trạng thái: **ĐÃ NHẬN**

### 2. Tìm kiếm & lọc
- Vào "Quản lý phiếu ĐH"
- Test:
  - Tìm theo mã phiếu
  - Lọc theo trạng thái
  - Sắp xếp theo ngày

### 3. Hủy phiếu (nếu có quyền)
- Chọn phiếu trạng thái NHÁP hoặc ĐÃ ĐẶT
- Click **"Hủy"**
- Trạng thái → **ĐÃ HỦY**

---

## CỐT LỖI THƯỜNG GẶP

### ❌ Badge không hiển thị
**Nguyên nhân**: JavaScript chưa load
**Giải pháp**:
```
1. Hard refresh: Ctrl + Shift + R
2. Xóa cache trình duyệt
3. Kiểm tra F12 Console có lỗi không
```

### ❌ Thêm vào giỏ hàng không được
**Nguyên nhân**: Thiếu quyền
**Giải pháp**:
```sql
-- Gán quyền cho user hiện tại
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE module = 'giohang';
```

### ❌ Không thấy nút "Duyệt" / "Nhận hàng"
**Nguyên nhân**: Thiếu quyền phieudathang.approve / phieudathang.receive
**Giải pháp**:
```sql
-- Gán quyền approve
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT YOUR_ROLE_ID, id FROM permissions 
WHERE name IN ('phieudathang.approve', 'phieudathang.receive', 'phieudathang.stock');
```

### ❌ Nhập kho không cộng số lượng
**Kiểm tra**:
```sql
-- Kiểm tra cột soluong_conlai có tồn tại không
SHOW COLUMNS FROM vattu_thanh_ly_iso LIKE 'soluong_conlai';
```
Nếu không có → Chạy:
```sql
ALTER TABLE vattu_thanh_ly_iso 
ADD COLUMN soluong_conlai DECIMAL(10,2) DEFAULT 0 AFTER soluong;
```

---

## TÍNH NĂNG BỔ SUNG (TÙY CHỌN)

### Thêm Foreign Keys (nếu cần referential integrity)
```sql
-- Chỉ chạy sau khi đã verify table users tồn tại
ALTER TABLE cart_vattu_thanh_ly 
  ADD CONSTRAINT fk_cart_user 
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE cart_vattu_thanh_ly 
  ADD CONSTRAINT fk_cart_vattu 
  FOREIGN KEY (vattu_stt) REFERENCES vattu_thanh_ly_iso(stt) ON DELETE CASCADE;
```
❗ **Lưu ý**: Hệ thống hoạt động bình thường không cần FKs

---

## CHECKLIST HOÀN THÀNH

### ✅ Database Setup
- [ ] Chạy SQL thành công (4 bảng + permissions)
- [ ] Kiểm tra bảng: `SHOW TABLES LIKE '%cart%'`
- [ ] Kiểm tra permissions: `SELECT * FROM permissions WHERE module IN ('giohang', 'phieudathang')`

### ✅ Giao diện
- [ ] Thấy nút "Giỏ hàng" và "Quản lý phiếu ĐH"
- [ ] Thấy icon 🛒 ở mỗi dòng vật tư
- [ ] Badge số lượng cập nhật real-time

### ✅ Workflow cơ bản
- [ ] Thêm vào giỏ hàng thành công
- [ ] Tạo phiếu đặt hàng từ giỏ hàng
- [ ] Duyệt phiếu (draft → ordered)
- [ ] Nhận hàng (ordered → received)
- [ ] Nhập kho (received → stocked)
- [ ] Số lượng tồn kho được cộng thêm

### ✅ Tính năng nâng cao
- [ ] Nhận hàng từng phần (partial receipt)
- [ ] Tìm kiếm và lọc phiếu
- [ ] Hủy phiếu
- [ ] Xem lịch sử nhập kho

---

## HỖ TRỢ

Nếu gặp lỗi, kiểm tra:
1. **PHP Error Log**: `tail -f /var/log/apache2/error.log`
2. **Browser Console**: F12 → Console tab
3. **Network Tab**: F12 → Network → Xem request/response

Hoặc mở file **GIOHANG_PHIEUDATHANG_README.md** để xem hướng dẫn chi tiết.

---

**🎉 HOÀN TẤT**
Hệ thống giỏ hàng & phiếu đặt hàng đã sẵn sàng sử dụng!
