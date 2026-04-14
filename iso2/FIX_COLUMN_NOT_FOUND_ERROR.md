# Fix Lỗi: Column not found 'nguoi_thuchien'

## 🔍 Nguyên nhân

Lỗi **"Unknown column 'nguoi_thuchien' in 'field list'"** xảy ra vì:

1. **Bảng đã tồn tại với schema cũ** - Có thể bảng `hososcbd_tamdung` đã được tạo trước đó với cấu trúc thiếu cột
2. **CREATE TABLE IF NOT EXISTS không tạo lại** - Nếu bảng đã tồn tại, lệnh này bỏ qua việc tạo lại
3. **Charset mismatch** - Migration dùng utf8mb4 nhưng database dùng latin1

## ✅ Giải pháp (Chọn 1 trong 3)

### Giải pháp 1: Tự động sửa schema (Khuyến nghị)

**Bước 1:** Upload file fix:
```
fix_hososcbd_tamdung_schema.php
```

**Bước 2:** Truy cập:
```
https://diavatly.cloud/iso2/fix_hososcbd_tamdung_schema.php
```

**Kết quả:** Script sẽ tự động:
- Kiểm tra cột nào thiếu
- Thêm cột thiếu vào bảng
- Thêm indexes thiếu
- Hiển thị kết quả chi tiết

✅ **Ưu điểm:** An toàn, không mất dữ liệu, tự động
❌ **Nhược điểm:** Cần upload thêm 1 file

---

### Giải pháp 2: Chạy SQL sửa thủ công

**Truy cập phpMyAdmin hoặc MySQL console, chạy:**

```sql
-- Kiểm tra cột hiện có
SHOW COLUMNS FROM hososcbd_tamdung;

-- Thêm cột thiếu (nếu có)
ALTER TABLE hososcbd_tamdung 
ADD COLUMN nguoi_thuchien VARCHAR(100) NOT NULL COMMENT 'Người thực hiện' AFTER trangthai;

ALTER TABLE hososcbd_tamdung 
ADD COLUMN ngay_thuchien DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày thực hiện' AFTER nguoi_thuchien;

ALTER TABLE hososcbd_tamdung 
ADD COLUMN lydo_tamdung TEXT COMMENT 'Lý do tạm dừng' AFTER ngay_thuchien;

ALTER TABLE hososcbd_tamdung 
ADD COLUMN ghichu_tieptuc TEXT COMMENT 'Ghi chú tiếp tục' AFTER lydo_tamdung;

ALTER TABLE hososcbd_tamdung 
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp tạo' AFTER ghichu_tieptuc;

-- Thêm indexes
ALTER TABLE hososcbd_tamdung ADD INDEX idx_hoso (hoso);
ALTER TABLE hososcbd_tamdung ADD INDEX idx_trangthai (trangthai);
ALTER TABLE hososcbd_tamdung ADD INDEX idx_ngay (ngay_thuchien);
ALTER TABLE hososcbd_tamdung ADD INDEX idx_hoso_trangthai (hoso, trangthai);
```

✅ **Ưu điểm:** Kiểm soát hoàn toàn, thấy rõ từng bước
❌ **Nhược điểm:** Cần access trực tiếp database

---

### Giải pháp 3: Xóa và tạo lại bảng (Chỉ khi chưa có dữ liệu)

**⚠️ CẢNH BÁO: Sẽ XÓA toàn bộ dữ liệu trong bảng hososcbd_tamdung**

**Bước 1:** Uncomment dòng DROP TABLE trong file migration:

```sql
-- Xóa bảng cũ nếu tồn tại (CẢNH BÁO: Sẽ mất dữ liệu nếu có)
DROP TABLE IF EXISTS hososcbd_tamdung;  -- BỎ COMMENT dòng này
```

**Bước 2:** Chạy lại migration:
```
https://diavatly.cloud/iso2/run_migration_tamdung.php
```

✅ **Ưu điểm:** Đảm bảo schema đúng 100%
❌ **Nhược điểm:** Mất dữ liệu (nếu có)

---

## 📋 Files cần upload

### Tất cả giải pháp cần:
- ✅ `models/HoSoScBdTamDung.php` (đã có error handling)
- ✅ `api/hososcbd_tamdung.php` (đã có error handling)
- ✅ `models/HoSoSCBD.php` (đã có backward compatibility)

### Giải pháp 1 cần thêm:
- 📄 `fix_hososcbd_tamdung_schema.php` **(MỚI - file fix tự động)**

### Giải pháp 3 cần:
- 📄 `migrations/create_hososcbd_tamdung_table.sql` (đã sửa charset = latin1)

---

## 🔬 Kiểm tra sau khi fix

1. **Truy cập:**
   ```
   https://diavatly.cloud/iso2/check_tamdung_migration.php
   ```

2. **Xem kết quả:**
   - ✅ Bảng hososcbd_tamdung đã tồn tại
   - ✅ Số lượng record: 0 (hoặc số lượng có sẵn)

3. **Test tính năng:**
   - Vào trang Hồ sơ SCBĐ
   - Click nút "Tạm dừng"
   - Nhập lý do
   - Submit
   - **Mong đợi:** "Tạm dừng hồ sơ thành công" ✅

---

## 🎯 Khuyến nghị

**Dùng Giải pháp 1** vì:
- ✅ Tự động kiểm tra và sửa
- ✅ Không mất dữ liệu
- ✅ Hiển thị log chi tiết
- ✅ An toàn nhất

---

## 📞 Nếu vẫn gặp lỗi

Sau khi chạy fix, nếu vẫn lỗi, kiểm tra:

1. **Cấu trúc bảng thực tế:**
   ```sql
   SHOW CREATE TABLE hososcbd_tamdung;
   ```

2. **Danh sách cột:**
   ```sql
   SHOW COLUMNS FROM hososcbd_tamdung;
   ```

3. **Logs PHP:**
   - Xem file error log của Apache/Nginx
   - Hoặc xem browser console để thấy error message chi tiết

---

**Thời gian fix:** ~2-5 phút  
**Risk level:** LOW (Giải pháp 1, 2) hoặc MEDIUM (Giải pháp 3)
