# Migration Scripts - Hướng dẫn Chạy

## 📋 Thứ tự chạy migrations

### ⚠️ QUAN TRỌNG: Phải chạy theo đúng thứ tự!

---

## 🎯 Kịch bản 1: Cài đặt mới (Fresh Install)

Dành cho database mới, chưa có bảng nào.

### Bước 1: Tạo bảng hồ sơ SCBD và đơn vị

```bash
mysql -u root -p your_database_name < migrations/20251121_create_hososcbd_tables.sql
```

**Tạo ra:**
- ✅ `hososcbd_iso` - Hồ sơ sửa chữa/bảo dưỡng
- ✅ `donvi_iso` - Đơn vị khách hàng
- ✅ `thietbi_iso` - Thiết bị

### Bước 2: Tạo hệ thống quản lý công việc KPI

```bash
mysql -u root -p your_database_name < migrations/20260224_create_kpi_suachua_system_FIXED.sql
```

**Tạo ra:**
- ✅ `capdo_baocuong_iso` - 3 cấp độ bảo dưỡng (CAP1, CAP2, CAP3)
- ✅ `thietbi_capdo_kpi_iso` - KPI tùy chỉnh theo thiết bị
- ✅ `congviec_suachua_iso` - Công việc sửa chữa hàng ngày
- ✅ 3 VIEWs thống kê
- ✅ 2 TRIGGERs kiểm tra 8h/ngày

### Bước 3: (Optional) Chuẩn hóa FK - hososcbd_iso

```bash
mysql -u root -p your_database_name < migrations/ALTER_congviec_hososcbd_FK.sql
```

**Thực hiện:**
- 🗑️ DROP cột `mavt`, `somay`, `ten_thietbi`, `thietbi_stt`
- 🔗 Thêm FK: `hososcbd_stt → hososcbd_iso.stt` (NOT NULL)
- 📊 Cập nhật VIEWs để JOIN qua hososcbd_iso
- 📈 Tạo `view_congviec_full_info` - thông tin đầy đủ

**Lợi ích:**
- Tiết kiệm 415 bytes/record
- Third Normal Form (3NF)
- Data consistency
- Referential integrity

---

## 🔄 Kịch bản 2: Cập nhật từ hệ thống cũ

Database đã có bảng `congviec_suachua_iso` theo thiết kế cũ.

### Kiểm tra bảng hiện tại

```sql
-- Kiểm tra cấu trúc bảng
SHOW CREATE TABLE congviec_suachua_iso;

-- Kiểm tra có cột mavt/somay không
SHOW COLUMNS FROM congviec_suachua_iso LIKE 'mavt';
SHOW COLUMNS FROM congviec_suachua_iso LIKE 'hososcbd_stt';
```

### Nếu chưa có cột hososcbd_stt

Bạn đang dùng bảng cũ, cần migrate sang thiết kế mới:

```bash
# Chạy script tạo cấu trúc mới
mysql -u root -p your_database_name < migrations/20260224_create_kpi_suachua_system_FIXED.sql
```

### Nếu đã có cột hososcbd_stt (nullable)

Bạn có thể chuẩn hóa luôn:

```bash
mysql -u root -p your_database_name < migrations/ALTER_congviec_hososcbd_FK.sql
```

---

## 🛑 Lỗi Thường Gặp

### Lỗi 1: errno 150 - Foreign key constraint incorrectly formed

```
#1005 - Can't create table (errno: 150)
```

**Nguyên nhân:**
- Bảng `hososcbd_iso` chưa được tạo
- Chạy sai thứ tự migrations

**Giải pháp:**
1. Kiểm tra bảng `hososcbd_iso` có tồn tại:
   ```sql
   SELECT COUNT(*) FROM hososcbd_iso;
   ```
2. Nếu không, chạy:
   ```bash
   mysql -u root -p your_db < migrations/20251121_create_hososcbd_tables.sql
   ```
3. Sau đó chạy lại script bị lỗi

### Lỗi 2: #1054 - Unknown column 'cv.mavt'

```
#1054 - Unknown column 'cv.mavt' in 'SELECT'
```

**Nguyên nhân:**
- Đã chạy `ALTER_congviec_hososcbd_FK.sql` (DROP cột mavt/somay)
- Sau đó chạy lại `20260224_create_kpi_suachua_system_FIXED.sql`
- CREATE TABLE IF NOT EXISTS giữ bảng cũ (không có mavt/somay)
- Nhưng VIEWs tạo lại → lỗi

**Giải pháp:**
Script `20260224_create_kpi_suachua_system_FIXED.sql` đã được fix:
- Tự động DROP tất cả VIEWs, TRIGGERs, TABLEs cũ
- Tạo mới hoàn toàn

Chạy lại:
```bash
mysql -u root -p your_db < migrations/20260224_create_kpi_suachua_system_FIXED.sql
```

### Lỗi 3: Table doesn't exist

```
Table 'database.congviec_suachua_iso' doesn't exist
```

**Nguyên nhân:**
- Chưa chạy migration tạo bảng

**Giải pháp:**
Xem **Kịch bản 1** ở trên, chạy đúng thứ tự.

---

## 📊 Kiểm tra kết quả

Sau khi chạy xong tất cả migrations:

```sql
-- 1. Kiểm tra tất cả bảng
SHOW TABLES LIKE '%iso%';

-- 2. Kiểm tra FK constraints
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('thietbi_capdo_kpi_iso', 'congviec_suachua_iso')
  AND REFERENCED_TABLE_NAME IS NOT NULL;

-- 3. Kiểm tra VIEWs
SHOW FULL TABLES WHERE Table_Type = 'VIEW';

-- 4. Kiểm tra TRIGGERs
SHOW TRIGGERS LIKE 'congviec_suachua_iso';

-- 5. Kiểm tra dữ liệu mẫu
SELECT * FROM capdo_baocuong_iso;  -- Phải có 3 records: CAP1, CAP2, CAP3
```

---

## 📁 Danh sách Migration Files

| File | Mô tả | Tạo bảng | Phụ thuộc |
|------|-------|----------|-----------|
| `20251121_create_hososcbd_tables.sql` | Tạo bảng hồ sơ SCBD, đơn vị, thiết bị | hososcbd_iso, donvi_iso, thietbi_iso | Không |
| `20260224_create_kpi_suachua_system_FIXED.sql` | Tạo hệ thống KPI công việc (bản FIXED) | capdo_baocuong_iso, thietbi_capdo_kpi_iso, congviec_suachua_iso | Không (độc lập) |
| `ALTER_congviec_hososcbd_FK.sql` | Chuẩn hóa FK congviec → hososcbd | Không (chỉ ALTER) | hososcbd_iso, congviec_suachua_iso |
| `CREATE_thietbi_capdo_kpi_SIMPLE.sql` | Tạo bảng KPI riêng cho thiết bị (clean slate) | capdo_baocuong_iso, thietbi_capdo_kpi_iso | Không |

---

## 🚀 Quick Start (TL;DR)

**Fresh install - chạy 1 dòng:**

```bash
# Windows PowerShell
Get-Content migrations/20251121_create_hososcbd_tables.sql | mysql -u root -p your_db
Get-Content migrations/20260224_create_kpi_suachua_system_FIXED.sql | mysql -u root -p your_db

# Linux/Mac
cat migrations/20251121_create_hososcbd_tables.sql | mysql -u root -p your_db
cat migrations/20260224_create_kpi_suachua_system_FIXED.sql | mysql -u root -p your_db
```

**Chuẩn hóa FK (optional):**

```bash
Get-Content migrations/ALTER_congviec_hososcbd_FK.sql | mysql -u root -p your_db
```

---

## 📞 Hỗ trợ

Nếu gặp lỗi không có trong danh sách trên, kiểm tra:

1. **MySQL version**: >= 5.7 (khuyến nghị 8.0+)
2. **Engine**: InnoDB (bắt buộc cho FK)
3. **Charset**: utf8mb4 hoặc latin1 (nhất quán)
4. **Permissions**: User phải có quyền CREATE, ALTER, DROP
5. **Database name**: Đúng tên database

**Logs chi tiết:**

```bash
mysql -u root -p your_db < migrations/script.sql > output.log 2>&1
```
