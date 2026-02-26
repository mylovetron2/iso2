# Hệ Thống Quản Lý Công Việc Sửa Chữa Với KPI Theo Cấp Độ

## 📋 Tổng Quan

Hệ thống quản lý công việc sửa chữa/bảo dưỡng thiết bị hàng ngày với tính năng:
- Theo dõi công việc của từng nhân viên theo ngày
- Giới hạn tổng số giờ làm việc không quá 8 giờ/ngày
- Quản lý 3 cấp độ bảo dưỡng với KPI riêng
- Báo cáo thống kê hiệu suất so với KPI

**Ngày tạo:** 24/02/2026  
**Phiên bản:** 1.0

---

## 🗄️ Cấu Trúc Database

### 1. Bảng `capdo_baocuong_iso` - Cấp Độ Bảo Dưỡng

Quản lý 3 cấp độ bảo dưỡng với KPI chuẩn.

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `stt` | INT(11) | ID cấp độ (PK) |
| `ma_capdo` | VARCHAR(20) | Mã cấp độ: CAP1, CAP2, CAP3 |
| `ten_capdo` | VARCHAR(100) | Tên: Bảo dưỡng Cấp 1/2/3 |
| `mo_ta` | TEXT | Mô tả công việc của cấp độ |
| `kpi_gio_chuan` | DECIMAL(5,2) | Số giờ KPI chuẩn |
| `mau_sac` | VARCHAR(20) | Mã màu hiển thị |
| `thu_tu` | INT(3) | Thứ tự sắp xếp |
| `trang_thai` | TINYINT(1) | 1=Kích hoạt, 0=Vô hiệu |

**Dữ liệu mặc định:**
- **Cấp 1:** Bảo dưỡng cơ bản - KPI: 2 giờ
- **Cấp 2:** Bảo dưỡng trung cấp - KPI: 4 giờ  
- **Cấp 3:** Bảo dưỡng nâng cao/đại tu - KPI: 8 giờ

---

### 2. Bảng `thietbi_capdo_kpi_iso` - Liên Kết Thiết Bị & KPI

Quản lý KPI riêng cho từng thiết bị theo cấp độ.

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `stt` | INT(11) | ID (PK) |
| `mavt` | VARCHAR(80) | Mã vật tư thiết bị |
| `somay` | VARCHAR(80) | Serial number |
| `capdo_stt` | INT(11) | Link đến `capdo_baocuong_iso.stt` |
| `kpi_gio_du_kien` | DECIMAL(5,2) | KPI dự kiến cho thiết bị này |
| `ghi_chu` | TEXT | Ghi chú đặc thù |

**Quan hệ:**
- UNIQUE: (mavt, somay, capdo_stt) - 1 thiết bị chỉ có 1 KPI/cấp độ
- FOREIGN KEY: capdo_stt → capdo_baocuong_iso.stt

---

### 3. Bảng `congviec_suachua_iso` - Công Việc Hàng Ngày

Ghi nhận công việc sửa chữa hàng ngày của nhân viên.

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `stt` | INT(11) | ID (PK) |
| `nhanvien_stt` | INT(11) | Link đến `resume.stt` |
| `nhanvien_ten` | VARCHAR(100) | Tên nhân viên (copy) |
| `ngay_lam` | DATE | Ngày làm việc |
| `mavt` | VARCHAR(80) | Mã thiết bị |
| `somay` | VARCHAR(80) | Serial number |
| `ten_thietbi` | VARCHAR(255) | Tên thiết bị (copy) |
| `capdo_stt` | INT(11) | Cấp độ bảo dưỡng |
| `capdo_ten` | VARCHAR(100) | Tên cấp độ (copy) |
| `kpi_gio_chuan` | DECIMAL(5,2) | KPI chuẩn (copy) |
| `noi_dung` | TEXT | Nội dung công việc |
| `so_gio_lam` | DECIMAL(5,2) | Số giờ thực tế |
| `gio_bat_dau` | TIME | Giờ bắt đầu |
| `gio_ket_thuc` | TIME | Giờ kết thúc |
| `trang_thai` | VARCHAR(50) | Đang thực hiện/Hoàn thành |
| `ghi_chu` | TEXT | Ghi chú |
| `hososcbd_stt` | INT(11) | Link đến `hososcbd_iso.stt` |
| `created_by` | VARCHAR(80) | Username người tạo |

**Ràng buộc:**
- Tổng `so_gio_lam` của 1 nhân viên trong 1 ngày ≤ 8 giờ (trigger)
- FOREIGN KEY: capdo_stt → capdo_baocuong_iso.stt

---

### 4. View `view_congviec_nhanvien_thongke`

Thống kê tổng quan công việc theo nhân viên và ngày.

```sql
SELECT 
    nhanvien_stt,
    nhanvien_ten,
    ngay_lam,
    COUNT(*) AS so_cong_viec,
    SUM(so_gio_lam) AS tong_so_gio,
    ROUND(8.0 - SUM(so_gio_lam), 2) AS gio_con_lai,
    CASE 
        WHEN SUM(so_gio_lam) > 8 THEN 'Vượt giờ'
        WHEN SUM(so_gio_lam) = 8 THEN 'Đủ giờ'
        ELSE 'Còn giờ trống'
    END AS trang_thai_gio
FROM congviec_suachua_iso
GROUP BY nhanvien_stt, nhanvien_ten, ngay_lam
```

---

### 5. View `view_kpi_thietbi_thongke`

Thống kê KPI theo thiết bị và cấp độ.

```sql
SELECT 
    mavt, somay, ten_thietbi, capdo_stt, capdo_ten, kpi_gio_chuan,
    COUNT(*) AS so_lan_sua,
    SUM(so_gio_lam) AS tong_gio_thuc_te,
    ROUND(AVG(so_gio_lam), 2) AS gio_trung_binh,
    ROUND((kpi_gio_chuan / AVG(so_gio_lam)) * 100, 2) AS hieu_suat_percent,
    CASE 
        WHEN AVG(so_gio_lam) <= kpi_gio_chuan THEN 'Đạt KPI'
        WHEN AVG(so_gio_lam) <= (kpi_gio_chuan * 1.2) THEN 'Gần đạt KPI'
        ELSE 'Chưa đạt KPI'
    END AS danh_gia_kpi
FROM congviec_suachua_iso
GROUP BY mavt, somay, capdo_stt
```

---

### 6. View `view_thongke_theo_capdo`

Thống kê theo cấp độ bảo dưỡng.

```sql
SELECT 
    c.stt, c.ma_capdo, c.ten_capdo, c.kpi_gio_chuan,
    COUNT(cv.stt) AS so_cong_viec,
    ROUND(AVG(cv.so_gio_lam), 2) AS gio_trung_binh,
    ROUND(SUM(cv.so_gio_lam), 2) AS tong_gio_lam,
    ROUND((c.kpi_gio_chuan / AVG(cv.so_gio_lam)) * 100, 2) AS hieu_suat_percent
FROM capdo_baocuong_iso c
LEFT JOIN congviec_suachua_iso cv ON c.stt = cv.capdo_stt
GROUP BY c.stt
```

---

## 🚀 Hướng Dẫn Cài Đặt

### Bước 1: Chạy Migration SQL

```bash
mysql -u root -p diavatly_db < migrations/20260224_create_kpi_suachua_system.sql
```

Hoặc trong phpMyAdmin:
1. Chọn database `diavatly_db`
2. Import file `migrations/20260224_create_kpi_suachua_system.sql`

### Bước 2: Kiểm Tra Dữ Liệu Mẫu

Sau khi chạy migration, kiểm tra 3 cấp độ đã được tạo:

```sql
SELECT * FROM capdo_baocuong_iso;
```

Kết quả mong đợi:
```
+-----+----------+--------------------+------------------+
| stt | ma_capdo | ten_capdo          | kpi_gio_chuan    |
+-----+----------+--------------------+------------------+
|  1  | CAP1     | Bảo dưỡng Cấp 1    | 2.00             |
|  2  | CAP2     | Bảo dưỡng Cấp 2    | 4.00             |
|  3  | CAP3     | Bảo dưỡng Cấp 3    | 8.00             |
+-----+----------+--------------------+------------------+
```

### Bước 3: Phân Quyền (Tùy Chọn)

Nếu hệ thống có phân quyền, cấp quyền cho role:

```sql
-- Xem các file comment trong migration để uncomment
```

---

## 📖 Hướng Dẫn Sử Dụng

### 1. Nhập Công Việc Hàng Ngày

**URL:** `congviec_suachua.php`

#### Các bước:

1. **Chọn nhân viên** từ dropdown (danh sách từ bảng `resume`)
2. **Chọn ngày làm** (mặc định là hôm nay)
3. Click **"Lọc"** để xem công việc trong ngày

#### Thông tin hiển thị:
- ✅ Tổng giờ đã làm / 8h
- ⏳ Giờ còn lại
- 📊 Số công việc

#### Thêm công việc mới:

1. Click **"Thêm Công Việc Mới"**
2. Điền thông tin:
   - **Mã thiết bị** (mavt)
   - **Serial / Số máy** (somay)
   - **Cấp độ bảo dưỡng** (chọn Cấp 1/2/3)
   - **Số giờ làm** (tối đa = giờ còn lại)
   - **Giờ bắt đầu / kết thúc** (tùy chọn)
   - **Nội dung công việc** (bắt buộc)
   - **Ghi chú** (tùy chọn)
3. Click **"Lưu"**

#### Validation:
- ❌ Không cho phép nhập nếu tổng giờ vượt quá 8h/ngày
- ✅ Hiển thị thông báo nếu thành công
- ❌ Hiển thị lỗi chi tiết nếu thất bại

---

### 2. Xem Báo Cáo KPI

**URL:** `baocao_kpi.php`

#### Báo cáo theo nhân viên:

Hiển thị:
- Số công việc
- Tổng giờ làm
- Giờ trung bình/công việc
- Số ngày làm việc
- Số thiết bị đã sửa

#### Báo cáo theo cấp độ:

So sánh KPI chuẩn vs thực tế:
- **Hiệu suất (%) = (KPI chuẩn / Giờ TB thực tế) × 100**
- ✅ Đạt KPI: ≥ 100%
- ⚠️ Gần đạt: 80% - 99%
- ❌ Chưa đạt: < 80%

#### Tính năng:
- 📅 Chọn khoảng thời gian
- 📊 Biểu đồ trực quan (Chart.js)
- 📥 Xuất Excel

---

## 📊 Công Thức Tính KPI

### 1. Hiệu Suất Công Việc

```
Hiệu suất (%) = (KPI chuẩn / Số giờ thực tế) × 100
```

**Ví dụ:**
- Cấp độ 2 có KPI chuẩn: 4 giờ
- Nhân viên hoàn thành trong: 3.5 giờ
- Hiệu suất = (4 / 3.5) × 100 = 114% → **Đạt KPI** ✅

### 2. Đánh Giá KPI

| Hiệu suất | Đánh giá | Màu sắc |
|-----------|----------|---------|
| ≥ 100% | Đạt KPI | 🟢 Xanh |
| 80% - 99% | Gần đạt KPI | 🟠 Cam |
| < 80% | Chưa đạt KPI | 🔴 Đỏ |

---

## 🔧 API Endpoints

### 1. Tạo Công Việc

**POST** `congviec_suachua.php?action=create`

**Body (form-data):**
```
nhanvien_stt: 1
ngay_lam: 2026-02-24
mavt: TB001
somay: SN12345
capdo_stt: 2
noi_dung: Thay dầu, kiểm tra hệ thống
so_gio_lam: 3.5
gio_bat_dau: 08:00
gio_ket_thuc: 11:30
ghi_chu: Đã thay dầu mới
```

**Response:**
```json
{
  "success": true,
  "message": "Tạo công việc thành công",
  "data": { ... }
}
```

---

### 2. Cập Nhật Công Việc

**POST** `congviec_suachua.php?action=update`

**Body:**
```
stt: 5
noi_dung: Nội dung cập nhật
so_gio_lam: 4.0
trang_thai: Hoàn thành
```

---

### 3. Xóa Công Việc

**POST** `congviec_suachua.php?action=delete`

**Body:**
```
stt: 5
```

---

### 4. Kiểm Tra Giờ Còn Lại

**GET** `congviec_suachua.php?action=check_gio&nhanvien_stt=1&ngay_lam=2026-02-24&so_gio=2.5`

**Response:**
```json
{
  "success": true,
  "can_add": true,
  "tong_gio_hien_tai": 5.5,
  "tong_gio_sau_them": 8.0,
  "gio_con_lai": 2.5,
  "vuot_gio": 0
}
```

---

### 5. Lịch Sử Thiết Bị

**GET** `congviec_suachua.php?action=lichsu_thietbi&mavt=TB001&somay=SN12345&limit=10`

---

## 📁 Cấu Trúc Files

```
iso2/
├── migrations/
│   └── 20260224_create_kpi_suachua_system.sql  ✅ Migration
│
├── models/
│   ├── CapDoBaoCuong.php                       ✅ Model cấp độ
│   ├── ThietBiCapDoKPI.php                     ✅ Model KPI thiết bị
│   └── CongViecSuaChua.php                     ✅ Model công việc
│
├── controllers/
│   └── CongViecSuaChuaController.php           ✅ Controller
│
├── views/
│   └── congviec/
│       └── index.php                           ✅ View nhập công việc
│
├── congviec_suachua.php                        ✅ Route chính
├── baocao_kpi.php                              ✅ Route báo cáo
└── CONGVIEC_KPI_README.md                      ✅ Tài liệu này
```

---

## ⚠️ Lưu Ý Quan Trọng

### 1. Ràng Buộc Tổng Giờ

**Trigger** tự động kiểm tra:
- Tổng giờ trong ngày của nhân viên không vượt quá 8 giờ
- Khi INSERT hoặc UPDATE, nếu vượt → báo lỗi

**Xử lý bên PHP:**
- Model `CongViecSuaChua::canAddGio()` kiểm tra trước khi insert
- Hiển thị thông báo rõ ràng cho user

### 2. Copy Dữ Liệu

Các trường sau được copy từ bảng khác để tăng hiệu năng query:
- `nhanvien_ten` ← resume.hoten
- `ten_thietbi` ← thietbi_iso.tenvt
- `capdo_ten` ← capdo_baocuong_iso.ten_capdo
- `kpi_gio_chuan` ← capdo_baocuong_iso.kpi_gio_chuan

### 3. Hiệu Năng

- Sử dụng **VIEW** để tính toán sẵn KPI
- Đánh **INDEX** cho các trường thường query
- Limit kết quả khi lấy lịch sử

### 4. Bảo Mật

- Kiểm tra `requireAuth()` trước khi truy cập
- Validate input trước khi insert
- Sử dụng prepared statement để tránh SQL injection

---

## 🐛 Troubleshooting

### Lỗi: "Tổng số giờ làm việc trong ngày không được vượt quá 8 giờ"

**Nguyên nhân:** Trigger kiểm tra tổng giờ

**Giải pháp:**
1. Kiểm tra tổng giờ hiện tại:
   ```sql
   SELECT SUM(so_gio_lam) FROM congviec_suachua_iso 
   WHERE nhanvien_stt = ? AND ngay_lam = ?
   ```
2. Giảm số giờ của công việc mới hoặc sửa công việc cũ

---

### Lỗi: "Không tìm thấy nhân viên"

**Nguyên nhân:** `nhanvien_stt` không tồn tại trong bảng `resume`

**Giải pháp:**
1. Kiểm tra danh sách nhân viên:
   ```sql
   SELECT * FROM resume WHERE nghiviec != 'yes'
   ```
2. Sử dụng `stt` đúng

---

### View không có dữ liệu

**Nguyên nhân:** Chưa có công việc nào được tạo

**Giải pháp:** Tạo công việc mẫu và kiểm tra lại

---

## 📞 Hỗ Trợ

Nếu cần hỗ trợ hoặc báo lỗi, vui lòng liên hệ team phát triển.

**Phiên bản:** 1.0  
**Ngày cập nhật:** 24/02/2026

---

## 🎯 Tính Năng Tương Lai (Roadmap)

- [ ] Thêm chức năng duyệt công việc
- [ ] Tích hợp với bảng `hososcbd_iso`
- [ ] Gửi email thông báo KPI hàng tuần
- [ ] Mobile app cho nhân viên nhập công việc
- [ ] Dashboard realtime
- [ ] Xuất PDF báo cáo
