# HƯỚNG DẪN TRIỂN KHAI - REFACTOR HỆ THỐNG CÔNG VIỆC KPI

## 📋 TỔNG QUAN

Refactor hệ thống quản lý công việc `congviec_suachua_iso` để:
- ✅ Thay thế nhập liệu thủ công mavt/somay → Chọn từ hồ sơ SCBD có sẵn
- ✅ Đảm bảo mỗi công việc liên kết với 1 hồ sơ SCBD (hososcbd_iso)
- ✅ Tự động lấy thông tin thiết bị từ hồ sơ SCBD
- ✅ Tăng tính chính xác dữ liệu, giảm lỗi nhập liệu

---

## ⚠️ YÊU CẦU TRƯỚC KHI TRIỂN KHAI

### 1. Kiểm tra dữ liệu hiện tại
```sql
-- Kiểm tra số lượng bản ghi trong congviec_suachua_iso
SELECT COUNT(*) as tong_bangi FROM congviec_suachua_iso;

-- Kiểm tra các bản ghi KHÔNG có hososcbd_stt
SELECT COUNT(*) as khong_co_hososcbd 
FROM congviec_suachua_iso 
WHERE hososcbd_stt IS NULL;

-- Kiểm tra số lượng hồ sơ SCBD hiện có
SELECT COUNT(*) as tong_hoso FROM hososcbd_iso;
```

### 2. Backup Database
```bash
# Trên Windows (PowerShell)
cd "C:\xampp\mysql\bin"
.\mysqldump.exe -u root -p diavatly_db > "D:\backup_congviec_$(Get-Date -Format 'yyyyMMdd_HHmmss').sql"

# Hoặc dùng phpMyAdmin: Export → SQL → Lưu file
```

### 3. Xác nhận môi trường
- PHP >= 7.4
- MySQL >= 5.7
- Quyền ALTER TABLE trên database
- Git branch: `sua-ko-co-nut-luu-phieu`

---

## 🚀 CÁC BƯỚC TRIỂN KHAI

### **BƯỚC 1: Cập nhật Database Schema**

Execute migration file:

```sql
-- File: migrations/20260224_ALTER_congviec_based_on_hososcbd.sql
SOURCE D:/projectISO2/iso2/migrations/20260224_ALTER_congviec_based_on_hososcbd.sql;
```

**Hoặc copy-paste từng phần trong migration vào phpMyAdmin:**

1. Tạo bảng mới `congviec_suachua_iso_new`
2. Copy dữ liệu từ bảng cũ (chỉ các bản ghi có `hososcbd_stt`)
3. Tạo VIEW `view_congviec_full`
4. Tạo lại TRIGGERs
5. RENAME bảng (sau khi test)

**⚠️ LƯU Ý:** Không chạy bước RENAME ngay! Test trước!

---

### **BƯỚC 2: Kiểm tra Migration**

```sql
-- 1. Kiểm tra cấu trúc bảng mới
DESCRIBE congviec_suachua_iso_new;

-- 2. So sánh số lượng bản ghi
SELECT 
    (SELECT COUNT(*) FROM congviec_suachua_iso) as cu,
    (SELECT COUNT(*) FROM congviec_suachua_iso_new) as moi;

-- 3. Test VIEW
SELECT * FROM view_congviec_full LIMIT 5;

-- 4. Kiểm tra TRIGGER
SHOW TRIGGERS LIKE 'congviec_suachua_iso_new';

-- 5. Test INSERT dữ liệu mẫu
INSERT INTO congviec_suachua_iso_new 
    (nhanvien_stt, ngay_lam_viec, hososcbd_stt, capdo_stt, so_gio_lam, noi_dung_congviec)
VALUES 
    (1, CURDATE(), 1, 1, 2.5, 'Test công việc');

-- Xóa test record
DELETE FROM congviec_suachua_iso_new WHERE noi_dung_congviec = 'Test công việc';
```

---

### **BƯỚC 3: Cập nhật Application Code**

✅ **ĐÃ HOÀN THÀNH** các file sau:

#### 1. Model: `models/CongViecSuaChua.php`
- ✅ `getByNhanVienNgay()` - JOIN với hososcbd_iso, thietbi_iso
- ✅ `getTongGioTrongNgay()` - Dùng field `ngay_lam_viec`
- ✅ `createWithValidation()` - Require `hososcbd_stt`
- ✅ `getLichSuThietBi()` - Query qua hososcbd_iso
- ✅ `getByHoSoScBd()` - Method mới

#### 2. Controller: `controllers/CongViecSuaChuaController.php`
- ✅ `create()` - Nhận `hososcbd_stt` thay vì `mavt/somay`
- ✅ `getHoSoList()` - API endpoint cho dropdown
- ✅ `getHoSoInfo()` - AJAX lấy thông tin thiết bị

#### 3. View: `views/congviec/index.php`
- ✅ Thay input `mavt/somay` → Dropdown `hososcbd_stt`
- ✅ Hiển thị thông tin thiết bị động khi chọn hồ sơ
- ✅ JavaScript load danh sách hồ sơ SCBD
- ✅ Đổi tên field: `ngay_lam` → `ngay_lam_viec`, `noi_dung` → `noi_dung_congviec`

---

### **BƯỚC 4: Cập nhật Model để sử dụng bảng mới (TẠM THỜI)**

**Để test trước khi RENAME bảng:**

```php
// File: models/CongViecSuaChua.php
// Thay đổi TẠM THỜI

class CongViecSuaChua extends BaseModel
{
    protected $table = 'congviec_suachua_iso_new'; // <-- Thêm _new
    // ... rest of code
}
```

**⚠️ CHỈ LÀM BƯỚC NÀY KHI TEST, SAU ĐÓ PHẢI ĐỔI LẠI!**

---

### **BƯỚC 5: Test Chức Năng**

#### Test Case 1: Load trang công việc
1. Truy cập: `http://localhost/iso2/congviec_suachua.php`
2. Chọn nhân viên, chọn ngày
3. Click "Thêm công việc mới"
4. **Kiểm tra:** Dropdown "Hồ sơ SCBD" có hiển thị danh sách không?

#### Test Case 2: Chọn hồ sơ SCBD
1. Chọn 1 hồ sơ từ dropdown
2. **Kiểm tra:** 
   - Thông tin thiết bị hiển thị đúng (Mã TB, Serial, Tên TB)?
   - Màu xanh dương, có icon info?

#### Test Case 3: Thêm công việc mới
1. Điền đầy đủ form:
   - Hồ sơ SCBD: Chọn 1 cái
   - Cấp độ bảo dưỡng: Chọn 1 cái
   - Số giờ làm: 2.5
   - Nội dung: "Test công việc refactor"
2. Click **Lưu**
3. **Kiểm tra:**
   - Thông báo thành công?
   - Công việc xuất hiện trong danh sách?
   - Thông tin thiết bị hiển thị đúng?

#### Test Case 4: Validation giới hạn 8 giờ
1. Thêm công việc 5 giờ → OK
2. Thêm công việc 4 giờ → **PHI FAIL** (vượt 8h)
3. **Kiểm tra:** Có thông báo lỗi?

#### Test Case 5: Xóa công việc
1. Click icon thùng rác
2. Confirm
3. **Kiểm tra:** Công việc bị xóa, tổng giờ cập nhật?

---

### **BƯỚC 6: Finalize Migration (SAU KHI TEST OK)**

```sql
-- 1. Backup bảng cũ (để rollback nếu cần)
RENAME TABLE congviec_suachua_iso TO congviec_suachua_iso_backup_20260224;

-- 2. Đổi tên bảng mới thành chính thức
RENAME TABLE congviec_suachua_iso_new TO congviec_suachua_iso;

-- 3. Cập nhật VIEW để trỏ đúng bảng
DROP VIEW IF EXISTS view_congviec_full;
CREATE VIEW view_congviec_full AS
SELECT 
    cv.*,
    cd.ma_capdo, cd.ten_capdo, cd.kpi_gio_chuan,
    hs.mavt, hs.somay, hs.hoso as ma_hoso, hs.phieu,
    tb.TENVT as ten_thietbi, tb.DONVI as don_vi_thietbi,
    nv.HOTEN as ten_nhanvien,
    dv.TEN as ten_donvi
FROM congviec_suachua_iso cv
LEFT JOIN capdo_baocuong_iso cd ON cv.capdo_stt = cd.stt
LEFT JOIN hososcbd_iso hs ON cv.hososcbd_stt = hs.stt
LEFT JOIN thietbi_iso tb ON hs.mavt = tb.MAVT AND hs.somay = tb.SOMAY
LEFT JOIN resume nv ON cv.nhanvien_stt = nv.STT
LEFT JOIN donvi_iso dv ON nv.DONVI = dv.STT;

-- 4. Xóa TRIGGERs cũ nếu còn
DROP TRIGGER IF EXISTS before_insert_congviec_suachua_check_gio;
DROP TRIGGER IF EXISTS before_update_congviec_suachua_check_gio;

-- 5. Tạo lại TRIGGERs cho bảng chính thức
DELIMITER //

CREATE TRIGGER before_insert_congviec_suachua_check_gio
BEFORE INSERT ON congviec_suachua_iso
FOR EACH ROW
BEGIN
    DECLARE tong_gio DECIMAL(5,2);
    
    SELECT COALESCE(SUM(so_gio_lam), 0) INTO tong_gio
    FROM congviec_suachua_iso
    WHERE nhanvien_stt = NEW.nhanvien_stt 
      AND ngay_lam_viec = NEW.ngay_lam_viec;
    
    IF (tong_gio + NEW.so_gio_lam) > 8 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Tổng số giờ trong ngày vượt quá 8 giờ';
    END IF;
END//

CREATE TRIGGER before_update_congviec_suachua_check_gio
BEFORE UPDATE ON congviec_suachua_iso
FOR EACH ROW
BEGIN
    DECLARE tong_gio DECIMAL(5,2);
    
    SELECT COALESCE(SUM(so_gio_lam), 0) INTO tong_gio
    FROM congviec_suachua_iso
    WHERE nhanvien_stt = NEW.nhanvien_stt 
      AND ngay_lam_viec = NEW.ngay_lam_viec
      AND stt != NEW.stt;
    
    IF (tong_gio + NEW.so_gio_lam) > 8 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Tổng số giờ trong ngày vượt quá 8 giờ';
    END IF;
END//

DELIMITER ;
```

---

### **BƯỚC 7: Đổi lại Model về tên bảng chính thức**

```php
// File: models/CongViecSuaChua.php

class CongViecSuaChua extends BaseModel
{
    protected $table = 'congviec_suachua_iso'; // <-- Xóa _new
    // ... rest of code
}
```

---

## 🔄 ROLLBACK (NẾU CẦN)

### Nếu gặp lỗi TRƯỚC khi RENAME:

```sql
-- Chỉ cần xóa bảng _new và VIEW
DROP TABLE IF EXISTS congviec_suachua_iso_new;
DROP VIEW IF EXISTS view_congviec_full;
```

### Nếu đã RENAME và muốn quay lại:

```sql
-- 1. Xóa bảng mới
DROP TABLE IF EXISTS congviec_suachua_iso;

-- 2. Khôi phục từ backup
RENAME TABLE congviec_suachua_iso_backup_20260224 TO congviec_suachua_iso;

-- 3. Restore code cũ từ Git
git checkout HEAD~1 -- models/CongViecSuaChua.php
git checkout HEAD~1 -- controllers/CongViecSuaChuaController.php
git checkout HEAD~1 -- views/congviec/index.php
```

---

## 📊 THAY ĐỔI CHI TIẾT

### Database Schema Changes

| Field Old | Field New | Thay đổi |
|-----------|-----------|----------|
| `ngay_lam` | `ngay_lam_viec` | Đổi tên cho rõ nghĩa |
| `mavt` | ❌ Xóa | Lấy từ hososcbd_iso |
| `somay` | ❌ Xóa | Lấy từ hososcbd_iso |
| `ten_thietbi` | ❌ Xóa | Lấy từ hososcbd_iso |
| `hososcbd_stt` | `hososcbd_stt NOT NULL` | Bắt buộc |
| - | `thietbi_stt INT NULL` | Cache FK cho performance |
| `noi_dung` | `noi_dung_congviec` | Đổi tên rõ nghĩa |

### API Changes

#### Endpoint: `congviec_suachua.php?action=create`

**Before:**
```javascript
formData.append('mavt', 'TB001');
formData.append('somay', 'SN12345');
formData.append('noi_dung', 'Bảo dưỡng định kỳ');
```

**After:**
```javascript
formData.append('hososcbd_stt', 123);
formData.append('noi_dung_congviec', 'Bảo dưỡng định kỳ');
// mavt, somay tự động lấy từ hososcbd_iso
```

#### Endpoint MỚI: `getHoSoList`

**Request:**
```
GET congviec_suachua.php?action=getHoSoList&keyword=TB001
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "stt": 123,
      "ma_hoso": "SCBD-2024-001",
      "mavt": "TB001",
      "somay": "SN12345",
      "ten_thietbi": "Máy nén khí",
      "display_text": "SCBD-2024-001 - TB001/SN12345 - Máy nén khí"
    }
  ]
}
```

#### Endpoint MỚI: `getHoSoInfo`

**Request:**
```
GET congviec_suachua.php?action=getHoSoInfo&stt=123
```

**Response:**
```json
{
  "success": true,
  "data": {
    "mavt": "TB001",
    "somay": "SN12345",
    "ten_thietbi": "Máy nén khí",
    "ma_hoso": "SCBD-2024-001"
  }
}
```

---

## ✅ CHECKLIST TRIỂN KHAI

- [ ] **Pre-deployment**
  - [ ] Backup database
  - [ ] Kiểm tra số bản ghi không có hososcbd_stt
  - [ ] Đọc kỹ hướng dẫn

- [ ] **Database Migration**
  - [ ] Execute migration SQL
  - [ ] Kiểm tra bảng `congviec_suachua_iso_new`
  - [ ] Test VIEW `view_congviec_full`
  - [ ] Test TRIGGERs

- [ ] **Code Updates**
  - [ ] Model: Đổi table → `congviec_suachua_iso_new` (tạm thời)
  - [ ] Verify Model methods
  - [ ] Verify Controller endpoints
  - [ ] Verify View form

- [ ] **Testing**
  - [ ] Test Case 1: Load trang
  - [ ] Test Case 2: Chọn hồ sơ
  - [ ] Test Case 3: Thêm công việc
  - [ ] Test Case 4: Validation 8h
  - [ ] Test Case 5: Xóa công việc
  - [ ] Test Case 6: Hiển thị lịch sử

- [ ] **Finalization**
  - [ ] RENAME tables (backup → chính thức)
  - [ ] Update VIEW
  - [ ] Recreate TRIGGERs
  - [ ] Model: Đổi table về `congviec_suachua_iso`
  - [ ] Test lại toàn bộ

- [ ] **Post-deployment**
  - [ ] Kiểm tra logs
  - [ ] Monitor performance
  - [ ] Xóa bảng backup sau 1 tuần (nếu ổn định)
  - [ ] Commit & push code to Git

---

## 🐛 TROUBLESHOOTING

### Lỗi: "Unknown column 'hososcbd_stt' in field list"
- **Nguyên nhân:** Migration chưa execute
- **Giải pháp:** Chạy migration SQL

### Lỗi: "Dropdown hồ sơ SCBD rỗng"
- **Check:** `console.log` trong browser DevTools
- **Nguyên nhân:** Endpoint `getHoSoList` lỗi hoặc không có dữ liệu
- **Giải pháp:**
  ```sql
  SELECT COUNT(*) FROM hososcbd_iso WHERE ngay_sua_chua >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH);
  ```

### Lỗi: "Thông tin thiết bị không hiển thị"
- **Check:** Network tab trong DevTools
- **Nguyên nhân:** AJAX `getHoSoInfo` fail
- **Giải pháp:** Kiểm tra hososcbd_iso có mavt/somay/thietbi_stt

### Lỗi TRIGGER: "Tổng số giờ vượt quá 8 giờ"
- **Nguyên nhân:** Logic đúng, ngăn nhập quá 8h/ngày
- **Giải pháp:** Giảm số giờ hoặc chọn ngày khác

---

## 📚 TÀI LIỆU LIÊN QUAN

- [HUONGDAN_CAPNHAT_CONGVIEC_KPI.md](HUONGDAN_CAPNHAT_CONGVIEC_KPI.md) - Hướng dẫn chi tiết kỹ thuật
- [LUUDO_CONGVIEC_KPI.md](LUUDO_CONGVIEC_KPI.md) - Flowcharts hệ thống
- `migrations/20260224_ALTER_congviec_based_on_hososcbd.sql` - Migration script

---

## 👤 HỖ TRỢ

Nếu gặp vấn đề, liên hệ:
- Developer: [Team Lead]
- Email: [support@company.com]
- Slack: #iso-system-support

---

**Cập nhật lần cuối:** 24/02/2026  
**Người tạo:** AI Assistant  
**Phiên bản:** 1.0.0
