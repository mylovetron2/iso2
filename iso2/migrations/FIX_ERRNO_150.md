# 🛑 FIX: errno 150 - Foreign key constraint is incorrectly formed

## ❌ Lỗi bạn đang gặp:

```
Can't create table `database`.`congviec_suachua_iso` (errno: 150)
```

---

## 🔍 Nguyên nhân:

Lỗi này xảy ra vì **bảng `hososcbd_iso` CHƯA được tạo** trước khi tạo Foreign Key.

Script cần bảng `hososcbd_iso` phải tồn tại trước khi:
- Tạo FK trong `congviec_suachua_iso`
- Tạo VIEW `view_congviec_full_info`

---

## ✅ Giải pháp (3 bước đơn giản):

### **Bước 1: Kiểm tra bảng hososcbd_iso có chưa**

```sql
-- Trong phpMyAdmin hoặc MySQL client
SELECT COUNT(*) FROM hososcbd_iso;
```

**Nếu báo lỗi "Table doesn't exist"** → Chưa có bảng, làm Bước 2.  
**Nếu hiển thị số lượng** → Đã có bảng, bỏ qua Bước 2.

---

### **Bước 2: Tạo bảng hososcbd_iso**

**Import file này vào database:**

```
migrations/20251121_create_hososcbd_tables.sql
```

**Cách import:**

**A. Qua phpMyAdmin:**
1. Chọn database `mapselli676e_iso2`
2. Click tab **Import**
3. Chọn file `20251121_create_hososcbd_tables.sql`
4. Click **Go**

**B. Qua MySQL command line:**
```bash
mysql -u your_username -p mapselli676e_iso2 < migrations/20251121_create_hososcbd_tables.sql
```

**Kết quả:** Tạo ra 3 bảng:
- ✅ `hososcbd_iso` - Hồ sơ sửa chữa/bảo dưỡng
- ✅ `donvi_iso` - Đơn vị khách hàng
- ✅ `thietbi_iso` - Thiết bị

---

### **Bước 3: Chạy lại script bị lỗi**

**Import file này:**

```
migrations/20260224_create_kpi_suachua_system_FIXED.sql
```

**HOẶC nếu đang chạy script ALTER:**

```
migrations/ALTER_congviec_hososcbd_FK.sql
```

Lần này sẽ KHÔNG còn lỗi errno 150! ✅

---

## 📋 Checklist nhanh:

- [ ] Bảng `hososcbd_iso` đã tồn tại
- [ ] Bảng `donvi_iso` đã tồn tại
- [ ] Bảng `thietbi_iso` đã tồn tại
- [ ] Chạy script tạo hệ thống KPI

**Kiểm tra nhanh:**
```sql
SHOW TABLES LIKE '%_iso%';
```

Phải thấy ít nhất:
- capdo_baocuong_iso
- congviec_suachua_iso
- donvi_iso
- hososcbd_iso
- thietbi_iso
- thietbi_capdo_kpi_iso

---

## 🎯 Thứ tự chạy migrations ĐÚNG:

| Thứ tự | File | Mô tả |
|--------|------|-------|
| **1** | `20251121_create_hososcbd_tables.sql` | Tạo hososcbd_iso, donvi_iso, thietbi_iso |
| **2** | `20260224_create_kpi_suachua_system_FIXED.sql` | Tạo hệ thống KPI công việc |
| **3** | `ALTER_congviec_hososcbd_FK.sql` (optional) | Chuẩn hóa FK |

**Quy tắc vàng:** 
> Luôn tạo bảng PARENT (hososcbd_iso) TRƯỚC khi tạo FK từ bảng CHILD!

---

## 🚨 Vẫn gặp lỗi?

**Lỗi: "Access denied to information_schema"**
- Bình thường, script đã được sửa để không cần quyền này
- Bỏ qua thông báo này

**Lỗi: "Table already exists"**
- Nếu muốn tạo lại từ đầu, DROP trước:
  ```sql
  SET FOREIGN_KEY_CHECKS = 0;
  DROP TABLE IF EXISTS congviec_suachua_iso;
  DROP TABLE IF EXISTS thietbi_capdo_kpi_iso;
  DROP TABLE IF EXISTS capdo_baocuong_iso;
  SET FOREIGN_KEY_CHECKS = 1;
  ```
- Sau đó import lại script

**Lỗi: "Unknown column 'cv.mavt'"**
- Xem hướng dẫn trong `README_MIGRATIONS.md`
- Script FIXED đã tự động xử lý

---

## 📖 Đọc thêm:

- [README_MIGRATIONS.md](README_MIGRATIONS.md) - Hướng dẫn chi tiết
- [LUUDO_CONGVIEC_KPI.md](../LUUDO_CONGVIEC_KPI.md) - Mô tả hệ thống

---

**Tóm tắt 1 dòng:**
```bash
# Chạy file này TRƯỚC:
mysql -u user -p db < migrations/20251121_create_hososcbd_tables.sql

# Sau đó chạy file KPI:
mysql -u user -p db < migrations/20260224_create_kpi_suachua_system_FIXED.sql
```

✅ Done!
