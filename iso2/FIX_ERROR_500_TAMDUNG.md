# FIX LỖI 500: Tính năng Tạm dừng Hồ sơ SCBĐ

## ❌ Lỗi gặp phải
```
GET https://diavatly.cloud/iso2/hososcbd.php 
net::ERR_HTTP_RESPONSE_CODE_FAILURE 500 (Internal Server Error)
```

## 🔍 Nguyên nhân
Code mới đã được deploy nhưng **migration database chưa chạy trên production**, dẫn đến:
- Bảng `hososcbd_tamdung` chưa tồn tại
- Cột `is_tamdung` chưa có trong bảng `hososcbd_iso`

## ✅ Giải pháp đã triển khai

### 1. Fix ngay (đã hoàn thành) ✓
Đã cập nhật code để **không bị lỗi** khi migration chưa chạy:

**File đã sửa:** `models/HoSoSCBD.php`
- Kiểm tra cột `is_tamdung` có tồn tại không trước khi query
- Nếu chưa có → dùng `0 as is_tamdung` để tránh lỗi
- Code giờ **backward compatible**, chạy được cả khi chưa migration

### 2. Chạy migration (cần làm tiếp)

**Bước 1: Kiểm tra trạng thái**
```
Truy cập: https://diavatly.cloud/iso2/check_tamdung_migration.php
```
Script sẽ hiển thị:
- ✓/✗ Bảng `hososcbd_tamdung` tồn tại chưa
- ✓/✗ Cột `is_tamdung` có trong `hososcbd_iso` chưa
- Danh sách files đã deploy
- Link nhanh để chạy migration

**Bước 2: Chạy migration**
```
Truy cập: https://diavatly.cloud/iso2/run_migration_tamdung.php
```
Script sẽ tự động:
1. Tạo bảng `hososcbd_tamdung`
2. Thêm cột `is_tamdung` vào `hososcbd_iso`
3. Tạo các index cần thiết

**Bước 3: Xác nhận**
- Reload trang `hososcbd.php`
- Kiểm tra các nút "Tạm dừng", "Tiếp tục", "Lịch sử" hiển thị đúng

## 📁 Files đã deploy

### Files mới (cần upload lên production)
✅ `migrations/create_hososcbd_tamdung_table.sql`  
✅ `models/HoSoScBdTamDung.php`  
✅ `api/hososcbd_tamdung.php`  
✅ `views/hososcbd/partials/tamdung_modals.php`  
✅ `baocao_hososcbd_tamdung.php`  
✅ `baocao_hososcbd_tamdung.php` (gộp danh sách tạm dừng)  
✅ `run_migration_tamdung.php`  
✅ `check_tamdung_migration.php` ← **FILE MỚI** để kiểm tra  

### Files đã cập nhật (cần upload lại)
✅ `models/HoSoSCBD.php` ← **ĐÃ FIX LỖI 500**  
✅ `views/hososcbd/index.php`  

### Files documentation (optional)
📄 `TAMDUNG_HOSOSCBD_README.md`  
📄 `TAMDUNG_HOSOSCBD_SUMMARY.md`  
📄 `FIX_ERROR_500_TAMDUNG.md` (file này)

## 🚀 Quy trình Deployment đúng

### Lần đầu triển khai tính năng mới:

1. **Upload files mới** lên production
2. **Chạy migration** trước
3. **Upload files cập nhật** (đã tích hợp tính năng)
4. **Test** tính năng

### Đã làm (không đúng thứ tự):
1. ✓ Upload files cập nhật (có query cột chưa tồn tại) → Lỗi 500
2. ✗ Chưa chạy migration → Cột chưa có

### Sửa ngay:
1. ✓ **Upload lại** `models/HoSoSCBD.php` (đã fix) → **Không còn lỗi 500**
2. → Chạy migration khi rảnh
3. → Tính năng hoạt động đầy đủ

## 📋 Checklist Fix Lỗi

### Ngay lập tức (để trang không bị lỗi 500):
- [x] Upload `models/HoSoSCBD.php` (version mới đã fix)
- [x] Upload `check_tamdung_migration.php` (để kiểm tra)
- [x] Reload trang hososcbd.php → Không còn lỗi 500

### Sau đó (để tính năng hoạt động):
- [ ] Truy cập `check_tamdung_migration.php`
- [ ] Click "Chạy migration" hoặc truy cập `run_migration_tamdung.php`
- [ ] Upload các files còn lại:
  - [ ] `run_migration_tamdung.php`
  - [ ] `migrations/create_hososcbd_tamdung_table.sql`
  - [ ] `models/HoSoScBdTamDung.php`
  - [ ] `api/hososcbd_tamdung.php`
  - [ ] `views/hososcbd/partials/tamdung_modals.php`
  - [ ] `baocao_hososcbd_tamdung.php`
  - [ ] `baocao_hososcbd_tamdung.php?trangthai=dang_tam_dung` (filter danh sách tạm dừng)
- [ ] Test tính năng tạm dừng/tiếp tục

## ⚠️ Lưu ý quan trọng

### Code hiện tại (sau khi fix):
- ✅ **An toàn**: Không bị lỗi 500 khi migration chưa chạy
- ✅ **Backward compatible**: Hoạt động cả khi chưa có cột `is_tamdung`
- ✅ **Progressive enhancement**: Tính năng tự động hoạt động sau khi migration

### Nếu không chạy migration:
- Trang hososcbd.php hoạt động bình thường
- Buttons "Tạm dừng" vẫn hiển thị (nhưng không hoạt động)
- Không có badge "TẠM DỪNG"
- API trả về lỗi khi gọi

### Sau khi chạy migration:
- ✓ Tất cả tính năng hoạt động đầy đủ
- ✓ Buttons functional
- ✓ Badge hiển thị
- ✓ API hoạt động
- ✓ Báo cáo có dữ liệu

## 🔧 Commands hữu ích

### Kiểm tra bảng có tồn tại:
```sql
SHOW TABLES LIKE 'hososcbd_tamdung';
```

### Kiểm tra cột có tồn tại:
```sql
SHOW COLUMNS FROM hososcbd_iso LIKE 'is_tamdung';
```

### Chạy migration thủ công (nếu cần):
```sql
-- Xem file: migrations/create_hososcbd_tamdung_table.sql
-- Copy nội dung và chạy trong phpMyAdmin hoặc MySQL client
```

## 📞 Hỗ trợ

Nếu vẫn gặp lỗi sau khi upload file `HoSoSCBD.php` mới:
1. Check PHP error log trên server
2. Xem Chrome DevTools Console (F12)
3. Truy cập `check_tamdung_migration.php` để xem chi tiết

---

**Status:** ✅ FIXED (code không còn lỗi 500)  
**Next step:** Chạy migration để enable tính năng  
**Updated:** 2026-04-10
