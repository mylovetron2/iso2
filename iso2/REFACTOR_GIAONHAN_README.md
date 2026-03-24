# REFACTOR: Giao Nhận Thiết Bị - Workflow 1 Phiếu

**Ngày:** 19/03/2026  
**Trạng thái:** Sẵn sàng để deploy

---

## 📋 TÓM TẮT THAY ĐỔI

### ❌ Logic CŨ (Sai):
- **2 loại phiếu riêng biệt**: `giao_di_kd` và `nhan_ve_kd`
- Tạo phiếu giao trước → Sau đó tạo phiếu nhận riêng (link qua `phieu_giao_id`)
- Phức tạp, không đúng quy trình thực tế

### ✅ Logic MỚI (Đúng):
- **1 phiếu duy nhất** với 3 trạng thái
- **Workflow:**
  1. **Đội gửi cho mình** → Tạo phiếu (trạng thái: `da_nhan`)
  2. **Mình gửi đi kiểm định** → Cập nhật (trạng thái: `dang_kiem_dinh`)
  3. **Kiểm định xong, trả lại** → Cập nhật (trạng thái: `da_giao`)

---

## 🗂️ CẤU TRÚC FILE

### 1. **Migration SQL**
```
refactor_giaonhan_workflow.sql (72 dòng)
```
- DROP cột `loai_giao_nhan`, `phieu_giao_id`
- MODIFY cột `trangthai` → ENUM('da_nhan', 'dang_kiem_dinh', 'da_giao')
- ADD cột `nguoi_gui_kiemdinh`, `donvi_gui_kiemdinh`, `ngay_gui_kiemdinh`
- UPDATE comments cho các cột

### 2. **Controller**
```
GiaoNhanThietBiController.php → GiaoNhanThietBiController_refactored.php (550+ dòng)
```

**Methods mới:**
- `index()` - Danh sách phiếu (filter theo trạng thái)
- `view()` - Chi tiết phiếu (hiển thị 3 bước)
- **BƯỚC 1**: `create()` + `store()` → Tạo phiếu nhận từ đội
- **BƯỚC 2**: `editGuiKiemDinh()` + `updateGuiKiemDinh()` → Gửi kiểm định
- **BƯỚC 3**: `editGiaoLai()` + `updateGiaoLai()` → Giao lại cho đội
- `delete()` - Xóa phiếu (cascade)

**Methods bị XÓA:**
- ❌ `createGiaoDi()` / `storeGiaoDi()`
- ❌ `createNhanVe()` / `storeNhanVe()`

### 3. **Router**
```
giaonhanthietbi.php → giaonhanthietbi_refactored.php
```

**Actions mới:**
- `index` - Danh sách
- `view` - Chi tiết
- `create` / `store` - Tạo phiếu nhận
- `editGuiKiemDinh` / `updateGuiKiemDinh` - Gửi kiểm định
- `editGiaoLai` / `updateGiaoLai` - Giao lại
- `delete` - Xóa

**Actions bị XÓA:**
- ❌ `create_giao_di` / `store_giao_di`
- ❌ `create_nhan_ve` / `store_nhan_ve`

### 4. **Views**
```
views/giaonhanthietbi/
├── create.php                     (280 dòng) - Form nhận từ đội + dynamic add/remove rows
├── edit_gui_kiemdinh.php          (180 dòng) - Form gửi kiểm định (readonly device list)
├── edit_giao_lai.php              (220 dòng) - Form giao lại + kết quả kiểm định
├── index_refactored.php           (240 dòng) - List với filter trạng thái + action buttons
└── view_refactored.php            (270 dòng) - Chi tiết với progress bar 3 bước
```

**Views bị XÓA:**
- ❌ `giao_di_multiple.php`
- ❌ `nhan_ve.php`

---

## 🛠️ HƯỚNG DẪN DEPLOY

### **Bước 1: Backup Database**
```sql
-- Backup bảng giao_nhan_thietbi_iso
CREATE TABLE giao_nhan_thietbi_iso_backup AS SELECT * FROM giao_nhan_thietbi_iso;

-- Backup bảng chitiet
CREATE TABLE giao_nhan_thietbi_chitiet_backup AS SELECT * FROM giao_nhan_thietbi_chitiet;
```

### **Bước 2: Chạy Migration SQL**
1. Mở phpMyAdmin → Database `diavatly_db`
2. Tab **SQL** → Paste nội dung file `refactor_giaonhan_workflow.sql`
3. Click **Go**
4. Verify:
```sql
-- Kiểm tra cột loai_giao_nhan, phieu_giao_id đã bị xóa
SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'giao_nhan_thietbi_iso' 
  AND COLUMN_NAME IN ('loai_giao_nhan', 'phieu_giao_id');
-- Result: 0

-- Kiểm tra cột mới
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'giao_nhan_thietbi_iso' 
  AND COLUMN_NAME LIKE '%gui_kiemdinh%';
-- Result: nguoi_gui_kiemdinh, donvi_gui_kiemdinh, ngay_gui_kiemdinh
```

### **Bước 3: Replace Files**

**Trên server (FTP/SFTP):**
```bash
# 1. Backup files cũ
cp controllers/GiaoNhanThietBiController.php controllers/GiaoNhanThietBiController_OLD.php
cp giaonhanthietbi.php giaonhanthietbi_OLD.php

# 2. Upload files mới
# Rename _refactored thành tên chính:
mv GiaoNhanThietBiController_refactored.php → GiaoNhanThietBiController.php
mv giaonhanthietbi_refactored.php → giaonhanthietbi.php

# 3. Upload views mới
views/giaonhanthietbi/
├── create.php                     (NEW)
├── edit_gui_kiemdinh.php          (NEW)
├── edit_giao_lai.php              (NEW)
├── index.php                      (REPLACE với index_refactored.php)
└── view.php                       (REPLACE với view_refactored.php)

# 4. Xóa views cũ (optional backup trước)
rm views/giaonhanthietbi/giao_di_multiple.php
rm views/giaonhanthietbi/nhan_ve.php
```

### **Bước 4: Update Permissions**

**Permissions mới (simplified):**
```php
giaonhanthietbi.view     - Xem danh sách & chi tiết
giaonhanthietbi.create   - Tạo phiếu nhận từ đội
giaonhanthietbi.edit     - Gửi kiểm định + Giao lại
giaonhanthietbi.delete   - Xóa phiếu
```

**Permissions bị XÓA:**
- ❌ `giaonhanthietbi.create_giao`
- ❌ `giaonhanthietbi.create_nhan`

**SQL Update:**
```sql
-- Xóa permissions cũ
UPDATE roles 
SET permissions = REPLACE(permissions, ',giaonhanthietbi.create_giao,', ',')
WHERE permissions LIKE '%giaonhanthietbi.create_giao%';

UPDATE roles 
SET permissions = REPLACE(permissions, ',giaonhanthietbi.create_nhan,', ',')
WHERE permissions LIKE '%giaonhanthietbi.create_nhan%';

-- Thêm permissions mới (nếu chưa có)
UPDATE roles 
SET permissions = CONCAT(
    permissions,
    ',giaonhanthietbi.view',
    ',giaonhanthietbi.create',
    ',giaonhanthietbi.edit',
    ',giaonhanthietbi.delete'
)
WHERE name IN ('Admin', 'admin', 'Manager')
  AND permissions NOT LIKE '%giaonhanthietbi.view%';
```

### **Bước 5: Test Workflow**

#### **Test Case 1: Tạo phiếu nhận từ đội**
1. Login → Menu "Giao Nhận Thiết Bị"
2. Click "Tạo Phiếu Mới"
3. Nhập:
   - Người giao: "Nguyễn Văn A"
   - Đơn vị: Chọn đơn vị
   - Ngày giao: Hôm nay
4. Thêm 3 thiết bị (click "Thêm thiết bị")
5. Nhập tình trạng cho mỗi thiết bị
6. Submit → **Kiểm tra**:
   - ✅ Message: "Tạo phiếu nhận thành công! (3 thiết bị)"
   - ✅ Redirect đến view.php
   - ✅ Trạng thái: "Đã Nhận Từ Đội" (màu xanh dương)
   - ✅ Hiển thị 3 thiết bị trong table
   - ✅ Có nút "Gửi Kiểm Định"

#### **Test Case 2: Gửi kiểm định**
1. Từ trang chi tiết phiếu (trạng thái "Đã Nhận")
2. Click nút "Gửi Kiểm Định"
3. Form hiển thị:
   - ✅ Thông tin nhận từ đội (readonly, màu xanh)
   - ✅ Danh sách thiết bị (readonly, table)
   - ✅ Form nhập thông tin gửi (editable, màu cam)
4. Nhập:
   - Người gửi kiểm định
   - Đơn vị gửi
   - Ngày gửi
5. Submit → **Kiểm tra**:
   - ✅ Message: "Cập nhật thông tin gửi kiểm định thành công!"
   - ✅ Trạng thái đổi: "Đang Kiểm Định" (màu cam)
   - ✅ Hiển thị thông tin gửi kiểm định
   - ✅ Có nút "Giao Lại Cho Đội"

#### **Test Case 3: Giao lại cho đội**
1. Từ trang chi tiết phiếu (trạng thái "Đang Kiểm Định")
2. Click nút "Giao Lại Cho Đội"
3. Form hiển thị:
   - ✅ Thông tin nhận từ đội (readonly)
   - ✅ Thông tin gửi kiểm định (readonly)
   - ✅ Danh sách thiết bị (readonly)
   - ✅ Form nhập thông tin giao lại (editable, màu tím)
4. Nhập:
   - Người nhận (auto-fill từ người giao ban đầu)
   - Đơn vị nhận (auto-select đơn vị ban đầu)
   - Ngày giao lại
   - **Kết quả kiểm định** (required)
5. Submit → **Kiểm tra**:
   - ✅ Message: "Hoàn tất giao lại thiết bị cho đội thành công!"
   - ✅ Trạng thái đổi: "Đã Giao" (màu xanh lá)
   - ✅ Hiển thị đầy đủ 3 bước
   - ✅ Nút "Gửi Kiểm Định" và "Giao Lại" BIẾN MẤT
   - ✅ Nút "Xóa" BIẾN MẤT (không xóa được phiếu hoàn tất)

#### **Test Case 4: Filter & Search**
1. Tại trang index:
2. Test filter trạng thái:
   - ✅ "Đã Nhận" → Chỉ hiển thị phiếu màu xanh dương
   - ✅ "Đang Kiểm Định" → Chỉ phiếu màu cam
   - ✅ "Đã Giao" → Chỉ phiếu màu xanh lá
3. Test tìm kiếm:
   - ✅ Tên thiết bị → Tìm đúng
   - ✅ Người giao → Tìm đúng
4. **Kiểm tra action buttons:**
   - ✅ Phiếu "Đã Nhận" → Có icon 📦 (shipping-fast) - link đến editGuiKiemDinh
   - ✅ Phiếu "Đang Kiểm Định" → Có icon ✅ (check-circle) - link đến editGiaoLai
   - ✅ Phiếu "Đã Giao" → KHÔNG có action button
   - ✅ Nút xóa chỉ xuất hiện nếu trạng thái != "Đã Giao"

#### **Test Case 5: Progress Bar**
1. View chi tiết phiếu:
2. **Trạng thái "Đã Nhận":**
   - ✅ Step 1 (Nhận từ đội): Màu xanh dương ✅
   - ✅ Step 2 (Gửi kiểm định): Màu xám ⚪
   - ✅ Step 3 (Giao lại): Màu xám ⚪
   - ✅ Connector 1-2: Màu xám
   - ✅ Connector 2-3: Màu xám
3. **Trạng thái "Đang Kiểm Định":**
   - ✅ Step 1: Xanh dương ✅
   - ✅ Step 2: Màu cam ✅
   - ✅ Step 3: Màu xám ⚪
   - ✅ Connector 1-2: Màu cam
   - ✅ Connector 2-3: Màu xám
4. **Trạng thái "Đã Giao":**
   - ✅ Cả 3 steps màu xanh/cam/xanh lá ✅✅✅
   - ✅ Cả 2 connectors màu xanh/cam

#### **Test Case 6: Statistics**
1. Tại trang index dưới cùng:
2. **Kiểm tra 4 boxes:**
   - ✅ "Tổng phiếu": Đếm đúng
   - ✅ "Đã nhận": Đếm đúng phiếu màu xanh
   - ✅ "Đang kiểm định": Đếm đúng phiếu màu cam
   - ✅ "Đã giao": Đếm đúng phiếu màu xanh lá

#### **Test Case 7: Delete with Cascade**
1. Tạo phiếu test với 3 thiết bị
2. Kiểm tra DB:
```sql
SELECT COUNT(*) FROM giao_nhan_thietbi_chitiet WHERE phieu_id = <TEST_ID>;
-- Result: 3
```
3. Click nút "Xóa" (chỉ có nếu trạng thái != "Đã Giao")
4. Confirm dialog
5. **Kiểm tra**:
   - ✅ Message: "Xóa phiếu thành công!"
   - ✅ Redirect về index
   - ✅ Phiếu biến mất khỏi danh sách
6. Kiểm tra DB:
```sql
SELECT COUNT(*) FROM giao_nhan_thietbi_chitiet WHERE phieu_id = <TEST_ID>;
-- Result: 0 (cascade delete worked)
```

---

## 🔍 TROUBLESHOOTING

### Lỗi: "Unknown column 'loai_giao_nhan'"
**Nguyên nhân:** Migration SQL chưa chạy  
**Giải pháp:** Chạy lại `refactor_giaonhan_workflow.sql`

### Lỗi: "Call to undefined method createGiaoDi()"
**Nguyên nhân:** Router cũ đang gọi method đã xóa  
**Giải pháp:** Replace `giaonhanthietbi.php` bằng version mới

### Lỗi: View bị lỗi layout
**Nguyên nhân:** View cũ và mới có cấu trúc khác nhau  
**Giải pháp:** 
1. Xóa view cũ: `giao_di_multiple.php`, `nhan_ve.php`
2. Upload view mới: `create.php`, `edit_gui_kiemdinh.php`, `edit_giao_lai.php`
3. Replace: `index.php`, `view.php`

### Permissions không hoạt động
**Nguyên nhân:** Permissions cũ còn trong database  
**Giải pháp:** Chạy lại SQL Update permissions (Bước 4)

---

## 📊 SO SÁNH TRƯỚC/SAU

| Tiêu chí | TRƯỚC (Sai) | SAU (Đúng) |
|----------|-------------|------------|
| **Số loại phiếu** | 2 (giao_di + nhan_ve) | 1 (với 3 trạng thái) |
| **Số bảng** | 2 master + 1 chitiet | 1 master + 1 chitiet |
| **Controller methods** | 8 methods | 9 methods (rõ ràng hơn) |
| **Views** | 4 views | 5 views (tách biệt 3 bước) |
| **Workflow** | Giao → Nhận (ngược) | Nhận → Gửi KĐ → Giao (đúng) |
| **Trạng thái** | cho_nhan, da_nhan, hoan_thanh | da_nhan, dang_kiem_dinh, da_giao |
| **Link phiếu** | phieu_giao_id (FK) | Không cần (cùng 1 phiếu) |
| **Permissions** | 4 permissions | 4 permissions (đơn giản hơn) |

---

## 📝 NOTES

1. **Không cần migrate dữ liệu cũ** nếu chưa có dữ liệu production
2. **Nếu có dữ liệu cũ**, cần viết script migrate riêng (liên hệ dev)
3. **Backup trước khi chạy migration** (bắt buộc)
4. **Test trên local trước**, sau đó mới deploy production
5. **Uncomment menu** trong header.php sau khi test xong

---

## ✅ CHECKLIST DEPLOY

- [ ] Backup database (giao_nhan_thietbi_iso + chitiet)
- [ ] Chạy migration SQL
- [ ] Verify schema (cột mới, cột cũ đã xóa)
- [ ] Backup files cũ
- [ ] Upload controller mới
- [ ] Upload router mới
- [ ] Upload 5 views mới
- [ ] Xóa 2 views cũ
- [ ] Update permissions trong database
- [ ] Test workflow đầy đầy (7 test cases)
- [ ] Verify không có lỗi PHP
- [ ] Uncomment menu
- [ ] Thông báo user về workflow mới

---

## 🎯 KẾT QUẢ MỚI THỰC

✅ **1 phiếu duy nhất** từ đầu đến cuối  
✅ **3 trạng thái** rõ ràng: da_nhan → dang_kiem_dinh → da_giao  
✅ **Progress bar** trực quan  
✅ **Action buttons** hiển thị đúng theo trạng thái  
✅ **Cascade delete** an toàn  
✅ **Filter & search** theo trạng thái  
✅ **Statistics boxes** theo dõi tổng quan  

**Workflow đúng với quy trình thực tế! 🚀**

---

**Liên hệ:** GitHub Copilot  
**Ngày:** 19/03/2026
