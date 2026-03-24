# CHỨC NĂNG GIỎ HÀNG VÀ PHIẾU ĐẶT HÀNG VẬT TƯ

## 📋 TỔNG QUAN

Hệ thống quản lý giỏ hàng và phiếu đặt hàng cho vật tư thanh lý, cho phép:
- ✅ Lưu tạm vật tư đã chọn vào giỏ hàng (persistent qua nhiều phiên)
- ✅ Tạo phiếu đặt hàng từ giỏ hàng hoặc chọn trực tiếp
- ✅ Workflow quản lý: Tạo → Duyệt → Nhận hàng → Nhập kho
- ✅ Tự động cập nhật tồn kho khi nhập kho
- ✅ Lịch sử nhập kho đầy đủ

## 🗄️ CẤU TRÚC DATABASE

### 1. Bảng `cart_vattu_thanh_ly` (Giỏ hàng)
```sql
- id: Primary key
- user_id: ID user (FK -> users)
- vattu_stt: STT vật tư (FK -> vattu_thanh_ly_iso)
- so_luong: Số lượng muốn đặt
- ghi_chu: Ghi chú
- ngay_them: Timestamp
```

### 2. Bảng `phieu_dat_hang` (Header phiếu)
```sql
- id: Primary key
- ma_phieu: Mã phiếu duy nhất (PDH-YYYYMMDD-XXX)
- nguoi_lap, nguoi_duyet, nguoi_nhan_hang, nguoi_nhap_kho: User IDs
- trang_thai: draft | ordered | partial_received | received | stocked | cancelled
- nha_cung_cap, so_hd_ncc: Thông tin NCC
- ngay_du_kien_nhan: Ngày dự kiến
```

### 3. Bảng `phieu_dat_hang_chi_tiet` (Chi tiết vật tư)
```sql
- id: Primary key
- phieu_id: FK -> phieu_dat_hang
- vattu_stt: FK -> vattu_thanh_ly_iso
- ten_tieng_anh, ten_tieng_nga, ten_tieng_viet: Snapshot tên
- so_luong_dat, so_luong_nhan: Số lượng
- don_gia, thanh_tien: Giá trị
```

### 4. Bảng `lich_su_nhap_kho` (Log nhập kho)
```sql
- id: Primary key
- phieu_dat_hang_id, phieu_chi_tiet_id: FKs
- vattu_stt: FK -> vattu_thanh_ly_iso
- so_luong: Số lượng nhập lần này
- so_luong_truoc, so_luong_sau: Tồn kho trước/sau
- nguoi_nhap: User ID
- vi_tri_kho, tinh_trang: Thông tin kho
```

## 📂 CẤU TRÚC FILES

### Backend
```
controllers/
├── GioHangController.php          # CRUD giỏ hàng
└── PhieuDatHangController.php      # Workflow phiếu đặt hàng

routers/
├── giohang.php                     # Router giỏ hàng
└── phieudathang.php                # Router phiếu đặt hàng
```

### Frontend
```
views/
├── giohang/
│   └── index.php                   # Xem/quản lý giỏ hàng
└── phieudathang/
    ├── index.php                   # Danh sách phiếu
    ├── create.php                  # Tạo phiếu mới
    ├── view.php                    # Chi tiết phiếu
    └── receive.php                 # Form nhận hàng
```

### SQL Files
```
create_table_cart_giohang.sql
create_table_phieu_dat_hang.sql
create_table_phieu_dat_hang_chi_tiet.sql
create_table_lich_su_nhap_kho.sql
add_giohang_phieudathang_permissions.sql
```

## 🔐 PERMISSIONS

### Giỏ hàng
- `giohang.view` - Xem giỏ hàng
- `giohang.add` - Thêm vào giỏ
- `giohang.edit` - Sửa số lượng
- `giohang.delete` - Xóa khỏi giỏ

### Phiếu đặt hàng
- `phieudathang.view` - Xem phiếu
- `phieudathang.create` - Tạo phiếu
- `phieudathang.approve` - Duyệt phiếu
- `phieudathang.receive` - Nhận hàng
- `phieudathang.stock` - Nhập kho
- `phieudathang.cancel` - Hủy phiếu
- `phieudathang.export` - Xuất Excel

## 🚀 HƯỚNG DẪN CÀI ĐẶT

### Bước 1: Chạy SQL Scripts
```bash
# Tạo 4 bảng
mysql -u root -p iso2 < create_table_cart_giohang.sql
mysql -u root -p iso2 < create_table_phieu_dat_hang.sql
mysql -u root -p iso2 < create_table_phieu_dat_hang_chi_tiet.sql
mysql -u root -p iso2 < create_table_lich_su_nhap_kho.sql

# Thêm permissions
mysql -u root -p iso2 < add_giohang_phieudathang_permissions.sql
```

### Bước 2: Kiểm tra Files
Đảm bảo các files sau đã tồn tại:
- ✅ controllers/GioHangController.php
- ✅ controllers/PhieuDatHangController.php
- ✅ giohang.php
- ✅ phieudathang.php
- ✅ views/giohang/index.php
- ✅ views/phieudathang/index.php
- ✅ views/phieudathang/create.php
- ✅ views/phieudathang/view.php
- ✅ views/phieudathang/receive.php

### Bước 3: Kiểm tra Integration
Files đã sửa:
- ✅ views/vattuthanhly/index.php (thêm nút giỏ hàng, JS)
- ✅ views/layouts/header.php (thêm menu giỏ hàng, phiếu ĐH)

## 📱 HƯỚNG DẪN SỬ DỤNG

### Workflow 1: Từ Giỏ Hàng

1. **Thêm vào giỏ hàng**
   - Vào trang "Vật tư thanh lý"
   - Click icon 🛒 ở mỗi dòng vật tư
   - Hoặc chọn nhiều vật tư → Click "Tạo phiếu đặt hàng"

2. **Quản lý giỏ hàng**
   - Click nút "Giỏ hàng" (màu tím) hoặc menu sidebar
   - Xem badge số lượng items
   - Chỉnh sửa số lượng, ghi chú
   - Xóa items không cần

3. **Tạo phiếu đặt hàng**
   - Trong giỏ hàng, click "Tạo Phiếu Đặt Hàng"
   - Điền thông tin: NCC, số HĐ, ngày dự kiến
   - Kiểm tra danh sách vật tư, số lượng
   - Click "Lưu Phiếu"

### Workflow 2: Tạo Trực Tiếp

1. Từ trang "Vật tư thanh lý"
2. Click "Tạo phiếu đặt hàng" (màu cam)
3. Chọn vật tư trong danh sách
4. Click "Tạo Phiếu"
5. Điền form và lưu

### Workflow 3: Quản Lý Phiếu

**Xem danh sách phiếu:**
- Menu → "Phiếu đặt hàng"
- Hoặc click "Quản lý phiếu ĐH" trên trang vật tư
- Lọc theo trạng thái, tìm kiếm

**Chi tiết phiếu:**
- Click vào mã phiếu
- Xem thông tin đầy đủ
- Buttons action theo trạng thái

### Workflow 4: Duyệt & Nhận Hàng

**Duyệt phiếu (Admin):**
```
Trạng thái: draft → ordered
- Vào chi tiết phiếu
- Click "Duyệt Phiếu"
- Phiếu chuyển sang "Đã đặt hàng"
```

**Nhận hàng (Thủ kho):**
```
Trạng thái: ordered → received
- Click "Nhận Hàng"
- Nhập số lượng thực nhận cho từng item
- Có thể nhận từng phần (partial_received)
- Click "Xác Nhận Nhận Hàng"
```

**Nhập kho:**
```
Trạng thái: received → stocked
- Click "Nhập Kho"
- Hệ thống tự động:
  + Cộng số lượng vào tồn kho (soluong_conlai)
  + Ghi log lịch sử nhập kho
  + Cập nhật trạng thái phiếu
```

## 🔄 WORKFLOW STATES

```
draft          → Nháp (vừa tạo)
   ↓ Duyệt
ordered        → Đã đặt hàng (chờ NCC giao)
   ↓ Nhận hàng
partial_received → Nhận một phần
   ↓ Nhận tiếp
received       → Đã nhận đủ hàng
   ↓ Nhập kho
stocked        → Đã nhập kho (hoàn thành)

cancelled      → Đã hủy (từ draft/ordered)
```

## 🎯 FEATURES

### Giỏ Hàng
- ✅ Thêm/xóa/sửa items
- ✅ Lưu tạm persistent (không mất khi logout)
- ✅ Badge hiển thị số lượng realtime
- ✅ Tạo phiếu đặt hàng từ giỏ
- ✅ Xóa giỏ sau khi tạo phiếu (option)

### Phiếu Đặt Hàng
- ✅ Tạo từ giỏ hàng hoặc chọn trực tiếp
- ✅ Lưu snapshot thông tin vật tư
- ✅ Quản lý trạng thái theo workflow
- ✅ Nhận hàng từng phần (partial)
- ✅ Tự động tính thành tiền
- ✅ Phê duyệt đa cấp (người lập, duyệt, nhận, nhập kho)

### Nhập Kho
- ✅ Tự động cập nhật tồn kho
- ✅ Ghi log đầy đủ (số lượng trước/sau)
- ✅ Lưu vị trí kho, tình trạng hàng
- ✅ Không thể nhập kho nếu chưa nhận hàng

## 🐛 TROUBLESHOOTING

### Không thấy menu Giỏ hàng/Phiếu ĐH
- Kiểm tra permissions: `giohang.view`, `phieudathang.view`
- Chạy lại file `add_giohang_phieudathang_permissions.sql`

### Lỗi khi thêm vào giỏ hàng
- Kiểm tra bảng `cart_vattu_thanh_ly` đã tạo chưa
- Check foreign keys: `users.id`, `vattu_thanh_ly_iso.stt`

### Badge giỏ hàng không hiện
- Mở DevTools Console kiểm tra lỗi JavaScript
- Kiểm tra file `giohang.php?action=getCount` trả về đúng JSON

### Nhập kho không cập nhật tồn
- Kiểm tra cột `soluong_conlai` trong bảng `vattu_thanh_ly_iso`
- Xem log `lich_su_nhap_kho` có ghi không

## 📊 TESTING

### Test Giỏ Hàng
```
1. Vào /iso2/vattuthanhly.php
2. Click icon 🛒 ở vài vật tư
3. Kiểm tra badge tăng lên
4. Vào giỏ hàng, thay đổi số lượng
5. Xóa 1 item, kiểm tra badge giảm
```

### Test Phiếu Đặt Hàng
```
1. Tạo phiếu từ giỏ hàng (có 3-5 items)
2. Kiểm tra phiếu ở trạng thái "draft"
3. Duyệt phiếu → "ordered"
4. Nhận hàng (nhập SL < SL đặt) → "partial_received"
5. Nhận tiếp cho đủ → "received"
6. Nhập kho → "stocked"
7. Kiểm tra tồn kho đã tăng chưa
8. Xem lịch sử nhập kho
```

### Test Permissions
```
1. Login với user thường (không phải admin)
2. Kiểm tra không thấy nút "Duyệt", "Nhập kho"
3. Login admin, kiểm tra thấy đầy đủ buttons
```

## 📝 NOTES

- Mã phiếu tự động: `PDH-YYYYMMDD-XXX` (XXX là số thứ tự trong ngày)
- Snapshot: Tên vật tư lưu trong chi tiết phiếu để tránh mất data khi xóa master
- Trigger: Tự động tính thành tiền khi insert/update chi tiết
- Cascade delete: Xóa phiếu → xóa luôn chi tiết
- Restrict delete: Không cho xóa vật tư nếu đang có trong phiếu

## 🔧 API ENDPOINTS

### Giỏ Hàng (AJAX)
```
POST /iso2/giohang.php?action=add
POST /iso2/giohang.php?action=update
POST /iso2/giohang.php?action=delete
POST /iso2/giohang.php?action=clear
GET  /iso2/giohang.php?action=getCount
```

### Phiếu Đặt Hàng
```
GET  /iso2/phieudathang.php?action=index
GET  /iso2/phieudathang.php?action=create&from_cart=1
POST /iso2/phieudathang.php?action=create
GET  /iso2/phieudathang.php?action=view&id=123
POST /iso2/phieudathang.php?action=approve
GET  /iso2/phieudathang.php?action=receive&id=123
POST /iso2/phieudathang.php?action=receive
POST /iso2/phieudathang.php?action=stock
POST /iso2/phieudathang.php?action=cancel
```

## ✅ CHECKLIST HOÀN THÀNH

- [x] 4 bảng database
- [x] SQL permissions
- [x] 2 Controllers (GioHang, PhieuDatHang)
- [x] 2 Routers
- [x] 6 Views
- [x] Integration vào vattuthanhly/index.php
- [x] Integration vào header.php
- [x] JavaScript cart badge
- [x] AJAX add to cart
- [x] Documentation

## 📞 HỖ TRỢ

Nếu gặp vấn đề, kiểm tra:
1. Error log PHP: `/var/log/apache2/error.log`
2. Browser Console: F12 → Console
3. Network tab: Kiểm tra AJAX requests
4. Database: Kiểm tra data có insert đúng không

---
**Version:** 1.0  
**Last Updated:** 2026-03-23  
**Author:** GitHub Copilot
