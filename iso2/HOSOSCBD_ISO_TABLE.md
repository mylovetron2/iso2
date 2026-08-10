# Tài Liệu Bảng `hososcbd_iso`

## 📋 Tổng Quan

**Tên bảng:** `hososcbd_iso`  
**Mục đích:** Quản lý hồ sơ sửa chữa và bảo dưỡng thiết bị  
**Engine:** InnoDB  
**Charset:** latin1  
**Số cột:** 44 cột  
**Primary Key:** `stt` (AUTO_INCREMENT)

Bảng này là trung tâm của hệ thống quản lý sửa chữa/bảo dưỡng thiết bị, theo dõi toàn bộ quy trình từ lúc tiếp nhận yêu cầu từ khách hàng đến khi hoàn thành và bàn giao thiết bị.

---

## 🗂️ Cấu Trúc Bảng

### 1. Khóa Chính & Mã Định Danh

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `stt` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | Số thứ tự - ID duy nhất của hồ sơ |
| `maql` | VARCHAR(80) | NOT NULL | Mã quản lý hồ sơ |
| `hoso` | VARCHAR(80) | NOT NULL | Mã hồ sơ |
| `phieu` | CHAR(10) | NOT NULL | Số phiếu yêu cầu |

**Index:** `idx_phieu`, `idx_maql`, `idx_hoso`

---

### 2. Thông Tin Thiết Bị

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `mavt` | VARCHAR(80) | NOT NULL | Mã vật tư/thiết bị |
| `somay` | VARCHAR(80) | NOT NULL | Số máy/Serial number |
| `model` | VARCHAR(100) | DEFAULT '' | Model thiết bị |
| `solg` | INT(30) | DEFAULT 1 | Số lượng thiết bị |
| `vitrimaybd` | VARCHAR(200) | DEFAULT '' | Vị trí thiết bị bảo dưỡng |

**Index:** `idx_mavt`

**Ghi chú:** 
- `mavt` liên kết với bảng `thietbi_iso`
- Mỗi bản ghi là 1 thiết bị trong 1 phiếu yêu cầu

---

### 3. Thông Tin Thời Gian

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `ngayyc` | DATE | NOT NULL | Ngày yêu cầu sửa chữa/bảo dưỡng |
| `ngayth` | DATE | DEFAULT NULL | Ngày bắt đầu thực hiện |
| `ngaykt` | DATE | DEFAULT NULL | Ngày kết thúc/hoàn thành |
| `ngaybdtt` | DATE | DEFAULT NULL | Ngày bảo dưỡng tiếp theo (lịch định kỳ) |

**Index:** `idx_ngayyc`

**Quy trình thời gian:**
```
ngayyc → ngayth → ngaykt
         ↓
      [Làm việc]
         ↓
      ngaybdtt (Lập lịch lần sau)
```

---

### 4. Thông Tin Khách Hàng & Yêu Cầu

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `madv` | VARCHAR(80) | NOT NULL | Mã đơn vị khách hàng |
| `ngyeucau` | VARCHAR(80) | DEFAULT '' | Người yêu cầu (từ phía khách hàng) |
| `ngnhyeucau` | VARCHAR(80) | DEFAULT '' | Người nhận yêu cầu (từ phía xưởng) |
| `dienthoai` | VARCHAR(20) | DEFAULT '' | Số điện thoại liên hệ |
| `cv` | TEXT | - | Công việc yêu cầu (mô tả ngắn gọn) |
| `ycthemkh` | TEXT | - | Yêu cầu thêm từ khách hàng |

**Index:** `idx_madv`

**Liên kết:** `madv` → `donvi_iso.madv`

---

### 5. Chuẩn Đoán & Xử Lý

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `ttktbefore` | TEXT | - | Tình trạng kỹ thuật trước khi sửa chữa |
| `honghoc` | TEXT | - | Mô tả chi tiết hỏng hóc/vấn đề |
| `khacphuc` | TEXT | - | Cách khắc phục/biện pháp xử lý |
| `ttktafter` | TEXT | - | Tình trạng kỹ thuật sau khi sửa chữa |
| `xemxetxuong` | TEXT | - | Nhận xét/đánh giá của xưởng |

**Quy trình chuẩn đoán:**
```
1. ttktbefore  → Kiểm tra tình trạng ban đầu
2. honghoc     → Xác định nguyên nhân hỏng
3. khacphuc    → Thực hiện sửa chữa/bảo dưỡng
4. ttktafter   → Kiểm tra sau khi hoàn thành
5. xemxetxuong → Nhận xét chung
```

---

### 6. Thiết Bị Hỗ Trợ (Tối Đa 5 Thiết Bị)

Hệ thống cho phép ghi nhận tối đa 5 thiết bị đo/kiểm tra/sửa chữa được sử dụng trong quá trình làm việc:

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `tbdosc` | VARCHAR(80) | DEFAULT '' | Thiết bị đo/SC #1 |
| `serialtbdosc` | VARCHAR(80) | DEFAULT '' | Serial thiết bị #1 |
| `tbdosc1` | VARCHAR(80) | DEFAULT '' | Thiết bị đo/SC #2 |
| `serialtbdosc1` | VARCHAR(80) | DEFAULT '' | Serial thiết bị #2 |
| `tbdosc2` | VARCHAR(80) | DEFAULT '' | Thiết bị đo/SC #3 |
| `serialtbdosc2` | VARCHAR(80) | DEFAULT '' | Serial thiết bị #3 |
| `tbdosc3` | VARCHAR(80) | DEFAULT '' | Thiết bị đo/SC #4 |
| `serialtbdosc3` | VARCHAR(80) | DEFAULT '' | Serial thiết bị #4 |
| `tbdosc4` | VARCHAR(80) | DEFAULT '' | Thiết bị đo/SC #5 |
| `serialtbdosc4` | VARCHAR(80) | DEFAULT '' | Serial thiết bị #5 |

**Liên kết:** Các giá trị này có thể liên kết với bảng `thietbihotro_iso`

**Ví dụ sử dụng:**
- Thiết bị đo điện trở, volt kế
- Thiết bị kiểm định (cân, đồng hồ đo)
- Dụng cụ sửa chữa chuyên dụng

---

### 7. Bàn Giao & Hoàn Thành

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `bg` | INT(2) | DEFAULT 0 | Trạng thái bàn giao: 0 = Chưa, 1 = Đã bàn giao |
| `slbg` | INT(3) | DEFAULT NULL | Số lần bàn giao (trường hợp sửa nhiều lần) |
| `ghichu` | TEXT | - | Ghi chú chung trong quá trình làm việc |
| `ghichufinal` | TEXT | - | Ghi chú cuối cùng khi hoàn thành |

**Index:** `idx_bg`

**Trạng thái bàn giao:**
- `bg = 0`: Chưa bàn giao (đang sửa hoặc chờ bàn giao)
- `bg = 1`: Đã bàn giao cho khách hàng

---

### 8. Phân Loại

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `nhomsc` | VARCHAR(100) | DEFAULT '' | Nhóm sửa chữa (CNC, Điện, KTKT, Cơ khí...) |
| `dong` | VARCHAR(80) | DEFAULT '' | Dòng thiết bị |

**Index:** `idx_nhomsc`

**Các nhóm sửa chữa phổ biến:**
- CNC - Thiết bị điều khiển số
- KTKT - Kiểm tra kỹ thuật
- Điện - Hệ thống điện
- Cơ khí - Máy móc cơ khí
- Điện tử - Thiết bị điện tử

---

### 9. Vị Trí Giếng Khoan (Đặc Thù Ngành Dầu Khí)

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `lo` | VARCHAR(200) | DEFAULT '' | Lô khai thác dầu khí |
| `gieng` | VARCHAR(200) | DEFAULT '' | Tên/mã giếng khoan |
| `mo` | VARCHAR(250) | DEFAULT '' | Tên mỏ dầu khí |

**Liên kết:**
- `lo` → `lo_iso.malo` (tùy chọn)
- `mo` → `mo_iso.mamo` (tùy chọn)

**Ghi chú:** Các trường này đặc thù cho ngành dầu khí, có thể để trống với các ngành khác.

---

### 10. Báo Cáo Chi Tiết

| Cột | Kiểu | Ràng buộc | Mô tả |
|-----|------|-----------|-------|
| `noidung` | TEXT | - | Nội dung công việc chi tiết đã thực hiện |
| `ketluan` | TEXT | - | Kết luận/đánh giá sau khi hoàn thành |

**Mục đích:**
- `noidung`: Mô tả chi tiết các bước đã làm, phụ tùng thay thế...
- `ketluan`: Tóm tắt kết quả, khuyến nghị bảo dưỡng tiếp theo

---

## 📊 Các Index Của Bảng

| Tên Index | Loại | Cột | Mục đích |
|-----------|------|-----|----------|
| PRIMARY | PRIMARY KEY | `stt` | Khóa chính |
| `idx_phieu` | INDEX | `phieu` | Tìm kiếm theo số phiếu |
| `idx_maql` | INDEX | `maql` | Tìm kiếm theo mã quản lý |
| `idx_hoso` | INDEX | `hoso` | Tìm kiếm theo mã hồ sơ |
| `idx_mavt` | INDEX | `mavt` | Tìm kiếm theo mã thiết bị |
| `idx_madv` | INDEX | `madv` | Lọc theo đơn vị khách hàng |
| `idx_bg` | INDEX | `bg` | Lọc theo trạng thái bàn giao |
| `idx_ngayyc` | INDEX | `ngayyc` | Lọc theo ngày yêu cầu |
| `idx_nhomsc` | INDEX | `nhomsc` | Lọc theo nhóm sửa chữa |

**Tổng số index:** 9 (bao gồm PRIMARY KEY)

---

## 🔄 Quy Trình Nghiệp Vụ

### Quy Trình 5 Bước

```mermaid
graph LR
    A[1. Tiếp nhận YC] --> B[2. Chuẩn đoán]
    B --> C[3. Thực hiện SC/BD]
    C --> D[4. Kiểm tra KT]
    D --> E[5. Bàn giao KH]
```

### Chi Tiết Từng Bước

#### Bước 1: Tiếp Nhận Yêu Cầu
- Khách hàng gửi yêu cầu → Tạo phiếu mới
- Điền thông tin: `phieu`, `ngayyc`, `madv`, `ngyeucau`, `cv`
- Nhân viên tiếp nhận: `ngnhyeucau`
- Trạng thái: `bg = 0`, `ngayth = NULL`

#### Bước 2: Chuẩn Đoán
- Kiểm tra thiết bị → Điền `ttktbefore`
- Xác định nguyên nhân → Điền `honghoc`
- Đề xuất biện pháp → Điền `khacphuc`

#### Bước 3: Thực Hiện Sửa Chữa/Bảo Dưỡng
- Bắt đầu làm việc → Cập nhật `ngayth`
- Ghi nhận thiết bị hỗ trợ → Điền `tbdosc`, `serialtbdosc`...
- Ghi chi tiết công việc → Điền `noidung`

#### Bước 4: Kiểm Tra Kỹ Thuật
- Kiểm tra sau sửa chữa → Điền `ttktafter`
- Nhận xét của xưởng → Điền `xemxetxuong`
- Kết luận → Điền `ketluan`
- Hoàn thành → Cập nhật `ngaykt`

#### Bước 5: Bàn Giao Khách Hàng
- Lập biên bản bàn giao → Cập nhật `bg = 1`
- Tăng số lần bàn giao → `slbg++`
- Ghi chú cuối → Điền `ghichufinal`
- Lên lịch BD tiếp theo (nếu cần) → Điền `ngaybdtt`

---

## 🔗 Quan Hệ Với Các Bảng Khác

### 1. Với Bảng `donvi_iso` (Đơn vị)
```sql
hososcbd_iso.madv → donvi_iso.madv
```
**Quan hệ:** N:1 (Nhiều hồ sơ thuộc 1 đơn vị)

### 2. Với Bảng `thietbi_iso` (Thiết bị)
```sql
hososcbd_iso.mavt  = thietbi_iso.mavt
hososcbd_iso.somay = thietbi_iso.somay
```
**Quan hệ:** N:1 (Nhiều hồ sơ cho 1 thiết bị - lịch sử sửa chữa)

**⚠️ Lưu ý quan trọng:**
- `hososcbd_iso` **KHÔNG có cột lưu trực tiếp** `thietbi_iso.stt` (không có FK vật lý kiểu `thietbi_stt`).
- Giá trị `thietbi_stt` xuất hiện trong kết quả truy vấn (vd. [models/HoSoSCBD.php](../models/HoSoSCBD.php)) chỉ là alias được **tính lúc runtime** qua JOIN, KHÔNG phải cột thật:
  ```sql
  LEFT JOIN (SELECT MIN(stt) AS stt, mavt, somay FROM thietbi_iso GROUP BY mavt, somay) t
      ON h.mavt = t.mavt AND h.somay = t.somay
  -- t.stt AS thietbi_stt
  ```
- Phải JOIN bằng **cả `mavt` VÀ `somay`**, không chỉ `mavt`. Nếu chỉ dùng `mavt`, một hồ sơ có thể match nhiều dòng thiết bị khác `somay`, gây lặp dữ liệu (xem [FIX_DUPLICATE_DEVICES.md](../FIX_DUPLICATE_DEVICES.md)).
- Vì `thietbi_iso` có thể có nhiều bản ghi trùng `(mavt, somay)`, các query dùng `GROUP BY mavt, somay` + `MIN(stt)` để chọn 1 `stt` đại diện duy nhất, tránh nhân bản kết quả.

### 3. Với Bảng `thietbi_kpi_baoduong_iso` (Ánh xạ thiết bị ↔ KPI)
```sql
thietbi_kpi_baoduong_iso.thietbi_stt → thietbi_iso.stt
thietbi_kpi_baoduong_iso.kpi_baoduong_stt → kpi_baoduong_thietbi_iso.id
```
**Quan hệ:** 1:1 theo thiết bị (1 thiết bị trong `thietbi_iso` có đúng 1 bản ghi ánh xạ tới 1 dòng KPI)

**Ghi chú:**
- Migration: [migrations/20260810_create_thietbi_kpi_baoduong_iso.sql](../migrations/20260810_create_thietbi_kpi_baoduong_iso.sql)
- Dùng để gán thủ công hoặc tự động giữa `thietbi_iso` và `kpi_baoduong_thietbi_iso`
- Mỗi thiết bị chỉ có một liên kết KPI duy nhất, tránh trùng lặp và sai lệch trong báo cáo

### 4. Với Bảng `thietbihotro_iso` (Thiết bị hỗ trợ)
```sql
hososcbd_iso.tbdosc → thietbihotro_iso.matbht
```
**Quan hệ:** N:N (Nhiều hồ sơ dùng nhiều thiết bị hỗ trợ)

### 5. Với Bảng `ngthuchien_iso` (Người thực hiện)
```sql
hososcbd_iso.hoso = ngthuchien_iso.mahoso
```
**Quan hệ:** 1:N (1 hồ sơ có nhiều người thực hiện, tối đa 8 người/hồ sơ)

**Ghi chú:**
- Khóa liên kết là `hoso` (không phải `stt`) — xem [views/hososcbd/repair_details.php](../views/hososcbd/repair_details.php) truy vấn `WHERE mahoso = :mahoso` với `:mahoso = item['hoso']`.
- Mỗi dòng trong `ngthuchien_iso` lưu tên (`hoten`) và giờ làm việc theo tháng (`giolv1`...`giolv12`) của 1 người cho 1 hồ sơ.
- Khi xóa/sửa hồ sơ, các bản ghi liên quan trong `ngthuchien_iso` được xóa theo `mahoso` trước khi ghi lại (xem [LOGIC_NGUOI_THUC_HIEN.md](LOGIC_NGUOI_THUC_HIEN.md)).

### 6. Với Bảng `phieubangiao_iso` (Phiếu bàn giao)
```sql
phieubangiao_thietbi_iso.hososcbd_stt → hososcbd_iso.stt
```
**Quan hệ:** 1:N (1 hồ sơ có thể có nhiều lần bàn giao)

### 7. Với Bảng `lichsudn_iso` (Lịch sử thay đổi)
```sql
lichsudn_iso.record_id → hososcbd_iso.stt
lichsudn_iso.table_name = 'hososcbd_iso'
```
**Quan hệ:** 1:N (1 hồ sơ có nhiều thay đổi lịch sử)

### 8. Với Bảng `hososcbd_dinhmuc_iso` (Định mức KPI) — bảng mới
```sql
hososcbd_dinhmuc_iso.hososcbd_stt → hososcbd_iso.stt  (UNIQUE)
hososcbd_dinhmuc_iso.kpi_baoduong_stt → kpi_baoduong_thietbi_iso.id
```
**Quan hệ:** 1:1 (1 hồ sơ có đúng 1 định mức KPI, do người dùng chọn `loai_congviec`: `kiem_tra`/`bd_cap_1`/`bd_cap_2`/`bd_cap_3`/`hieu_chuan`)

**Ghi chú:**
- Migration: [migrations/20260810_create_hososcbd_dinhmuc_iso.sql](../migrations/20260810_create_hososcbd_dinhmuc_iso.sql) — chỉ thêm bảng/view, không sửa bảng cũ.
- Không lưu snapshot định mức; luôn JOIN real-time sang `kpi_baoduong_thietbi_iso` qua view `view_hososcbd_kpi_dinhmuc`.
- View tính thêm `gio_thuc_te = MAX(giolv)` trong số người thực hiện của hồ sơ, join theo `ngthuchien_iso.mahoso = hososcbd_iso.hoso`.

### Sơ Đồ Quan Hệ

```
┌─────────────┐
│  donvi_iso  │
└──────┬──────┘
       │ 1
       │
       │ N
┌──────▼──────────┐        N    ┌──────────────────┐
│  hososcbd_iso   ├─────────────>│  thietbi_iso     │
└─┬───┬────┬───┬──┘        1    └──────────────────┘
  │1  │1   │1  │1
  │   │    │   │
  │N  │N   │N  │1 (qua hososcbd_stt)
  │   │    ▼   ▼
  │   │  ┌──────────────────┐   ┌─────────────────────────────────────┐
  │   │  │  ngthuchien_iso  │◄──┤ hososcbd_dinhmuc_iso                │
  │   │  └──────────────────┘ N │ (MAX giolv qua hoso)                │
  │   └─────────────> thietbihotro_iso               │N (kpi_baoduong_stt)                │
  │                              └────────────┬──────────────┘
  │ N                                        1▼
  ▼                                 ┌──────────────────────────┐
┌──────────────────────────────┐    │ kpi_baoduong_thietbi_iso │
│ phieubangiao_thietbi_iso     │    └──────────────────────────┘
└──────────────────────────────┘
           ▲
           │ 1
┌───────────────────────────────────────┐
│ thietbi_kpi_baoduong_iso             │
│ (mavt/tenvt -> kpi row)              │
└───────────────────────────────────────┘
```

---

## 💡 Ví Dụ Sử Dụng

### 1. Tạo Hồ Sơ Sửa Chữa Mới

```sql
INSERT INTO hososcbd_iso (
    maql, hoso, phieu,
    mavt, somay, model, solg,
    ngayyc, madv, ngyeucau, cv,
    nhomsc, bg
)
VALUES (
    'QL2026001', 'HS-SC-001', 'P001',
    'TB001', 'SN123456', 'XYZ-2000', 1,
    '2026-02-24', 'DV001', 'Nguyễn Văn A', 'Bảo dưỡng định kỳ',
    'CNC', 0
);
```

### 2. Cập Nhật Trạng Thái Đang Thực Hiện

```sql
UPDATE hososcbd_iso
SET 
    ngayth = '2026-02-25',
    ttktbefore = 'Thiết bị hoạt động bình thường',
    honghoc = 'Cần bảo dưỡng hệ thống truyền động',
    khacphuc = 'Thay dầu, kiểm tra chi tiết'
WHERE stt = 1;
```

### 3. Hoàn Thành & Bàn Giao

```sql
UPDATE hososcbd_iso
SET 
    ngaykt = '2026-02-26',
    ttktafter = 'Thiết bị hoạt động tốt',
    ketluan = 'Đã bảo dưỡng thành công',
    bg = 1,
    slbg = 1,
    ngaybdtt = '2026-08-24'  -- BD lần sau sau 6 tháng
WHERE stt = 1;
```

### 4. Truy Vấn Hồ Sơ Chưa Bàn Giao

```sql
SELECT 
    h.stt, h.phieu, h.ngayyc,
    d.tendv AS don_vi,
    h.cv AS cong_viec,
    DATEDIFF(CURRENT_DATE, h.ngayyc) AS so_ngay_cho
FROM hososcbd_iso h
LEFT JOIN donvi_iso d ON h.madv = d.madv
WHERE h.bg = 0
ORDER BY h.ngayyc ASC;
```

### 5. Thống Kê Theo Đơn Vị

```sql
SELECT 
    d.tendv,
    COUNT(*) AS tong_phieu,
    SUM(CASE WHEN h.bg = 1 THEN 1 ELSE 0 END) AS da_ban_giao,
    SUM(CASE WHEN h.bg = 0 THEN 1 ELSE 0 END) AS chua_ban_giao
FROM hososcbd_iso h
LEFT JOIN donvi_iso d ON h.madv = d.madv
WHERE YEAR(h.ngayyc) = 2026
GROUP BY d.tendv
ORDER BY tong_phieu DESC;
```

### 6. Lịch Sử Sửa Chữa Của 1 Thiết Bị

```sql
SELECT 
    h.phieu, h.ngayyc, h.ngaykt,
    h.honghoc, h.khacphuc,
    h.bg AS trang_thai_bg
FROM hososcbd_iso h
WHERE h.mavt = 'TB001' AND h.somay = 'SN123456'
ORDER BY h.ngayyc DESC;
```

---

## ⚠️ Lưu Ý Quan Trọng

### 1. Ràng Buộc Dữ Liệu
- `phieu`, `mavt`, `somay`, `madv`, `ngayyc` là bắt buộc
- `ngaykt` phải >= `ngayth` >= `ngayyc`
- `bg = 1` thì phải có `ngaykt`

### 2. Hiệu Năng
- Sử dụng index khi query theo `phieu`, `madv`, `mavt`, `bg`
- Tránh query TEXT field (`honghoc`, `khacphuc`) khi không cần thiết
- Với báo cáo lớn, nên tạo VIEW hoặc stored procedure

### 3. Bảo Mật
- Kiểm tra quyền truy cập bảng `user2_role_permissions`
- Log mọi thay đổi vào `lichsudn_iso`
- Không cho phép xóa hồ sơ đã bàn giao

### 4. Tích Hợp
- Model PHP: `models/HoSoSCBD.php`
- Controller: `controllers/HoSoSCBDController.php`
- View: `views/hososcbd/*.php`

### 5. Migration
- File tạo bảng: `migrations/20251121_create_hososcbd_tables.sql`
- File phân quyền: `migrations/20251121_add_hososcbd_permissions.sql`

---

## 📚 Tài Liệu Liên Quan

- [HOSOSCBD_README.md](HOSOSCBD_README.md) - Hướng dẫn toàn bộ hệ thống
- [PHIEUYEUCAU_README.md](PHIEUYEUCAU_README.md) - Quản lý phiếu yêu cầu
- [PHIEUBANGIAO_SETUP.md](PHIEUBANGIAO_SETUP.md) - Quy trình bàn giao
- [migrations/20251121_create_hososcbd_tables.sql](migrations/20251121_create_hososcbd_tables.sql) - Schema SQL

---

## 📞 Hỗ Trợ

Nếu có thắc mắc hoặc cần hỗ trợ, vui lòng liên hệ team phát triển.

**Phiên bản:** 1.0  
**Ngày cập nhật:** 24/02/2026
