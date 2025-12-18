# Tài liệu chức năng: bangcanhbao.php

## 📋 TỔNG QUAN HỆ THỐNG

**File:** `bangcanhbao.php`  
**Mục đích:** Quản lý toàn diện quy trình Hiệu chuẩn/Kiểm định thiết bị theo chuẩn ISO  
**Ngôn ngữ:** PHP (MySQL legacy)  
**Mô hình:** Multi-mode system - 1 file xử lý nhiều chức năng khác nhau

---

## 🎯 CÁC CHỨC NĂNG CHÍNH

Hệ thống hoạt động dựa trên tham số `$hosohc` để chuyển đổi giữa các chế độ:

### 1. **BẢNG CẢNH BÁO** (`hosohc=canhbao`)
- **Mục đích:** Hiển thị danh sách thiết bị cần hiệu chuẩn theo tháng
- **Tính năng:**
  - Xem kế hoạch hiệu chuẩn theo tháng/năm
  - Phân trang dữ liệu (10 dòng/trang)
  - Mã màu trạng thái:
    - `Trắng (#FFFFFF)`: Chưa hiệu chuẩn
    - `Xanh (#A0FFFF)`: Đã HC - Tốt
    - `Đỏ (#FFA0A0)`: Đã HC - Hỏng
  - Link đến form nhập liệu cho từng thiết bị

### 2. **NHẬP HỒ SƠ HIỆU CHUẨN** (`hosohc=hoso`)
- **Mục đích:** Nhập/cập nhật thông tin hiệu chuẩn thiết bị
- **Tính năng:**
  - Form chi tiết thông tin hiệu chuẩn
  - Chọn tối đa 5 thiết bị dẫn chuẩn
  - Tự động generate số hồ sơ (format: YY-TMM-XX)
  - Tự động điền thông tin từ database
  - Ghi nhận phương pháp chuẩn và loại hiệu chuẩn

### 3. **PHIẾU YÊU CẦU** (`hosohc=phieuyeucau`)
- **Mục đích:** Hiển thị danh sách thiết bị cần hiệu chuẩn trong tháng
- **Tính năng:**
  - Danh sách phân trang
  - Hiển thị thông tin cơ bản (tên máy, số máy, nơi thực hiện, chủ sở hữu)
  - Link đến form nhập liệu

### 4. **PHIẾU KIỂM TRA** (`hosohc=hosokt`)
- **Mục đích:** Form nhập thông tin kiểm tra thiết bị sau hiệu chuẩn
- **Tính năng:**
  - Nhập tình trạng kiểm tra (Tốt/Hỏng)
  - Checkbox dẫn chuẩn/mẫu chuẩn
  - Dropdown thiết bị dẫn chuẩn với điều kiện lọc

---

## 🗄️ CẤU TRÚC DATABASE

### Bảng chính sử dụng:

#### 1. **kehoach_iso** (Kế hoạch hiệu chuẩn)
```sql
Các trường chính:
- stt (PK)
- tenthietbi
- mahieu
- somay
- hangsx
- noithuchien (XNKT, MN, XSCCMDVL)
- thang
- namkh
- loaitb
- ghichu
```

#### 2. **hosohckd_iso** (Hồ sơ hiệu chuẩn/kiểm định)
```sql
Các trường chính:
- stt (PK)
- sohs (số hồ sơ)
- tenmay (mavattu)
- congviec (HC/CM/BD)
- thietbidc1...thietbidc5 (5 thiết bị dẫn chuẩn)
- danchuan (checkbox)
- mauchuan (checkbox)
- dinhky (checkbox)
- dotxuat (checkbox)
- ngayhc (ngày hiệu chuẩn)
- ngayhctt (ngày hiệu chuẩn tiếp theo)
- nhanvien
- noithuchien
- ttkt (tình trạng kiểm tra: Tốt/Hỏng)
- namkh
```

#### 3. **thietbihckd_iso** (Danh mục thiết bị)
```sql
Các trường chính:
- stt (PK)
- mavattu (mã vật tư - unique ID)
- tenthietbi
- tenviettat (tên viết tắt)
- somay
- hangsx
- bophansh (bộ phận sử hữu)
- chusohuu
- loaitb (1: thiết bị chuẩn, khác: thiết bị thường)
- danchuan (1: dùng làm thiết bị dẫn chuẩn)
- thoihankd
```

#### 4. **kehoach_temp** (Bảng tạm - phân trang)
```sql
Cấu trúc: Copy từ kehoach_iso
Mục đích: Tối ưu query phân trang
Vòng đời: Xóa và tạo mới mỗi lần load
```

#### 5. **resume** (Danh sách nhân viên)
```sql
Các trường sử dụng:
- hoten
- chucdanh
- donvi
- nghiviec
```

---

## 🔄 LUỒNG XỬ LÝ DỮ LIỆU

### Luồng 1: Xem bảng cảnh báo
```
1. User chọn tháng/năm → Submit form
2. DELETE FROM kehoach_temp
3. INSERT INTO kehoach_temp SELECT FROM kehoach_iso WHERE thang=X AND namkh=Y
4. SELECT FROM kehoach_temp LIMIT offset, 10
5. JOIN thietbihckd_iso (lấy tên viết tắt, chủ sở hữu)
6. JOIN hosohckd_iso (lấy trạng thái HC)
7. Hiển thị với mã màu tương ứng
```

### Luồng 2: Nhập hồ sơ hiệu chuẩn
```
1. Click vào thiết bị từ bảng cảnh báo
2. Auto-fill thông tin thiết bị từ thietbihckd_iso
3. Auto-fill thông tin HC cũ từ hosohckd_iso (nếu có)
4. User nhập/chọn:
   - Số hồ sơ (auto-generate)
   - Ngày HC
   - Ngày HC tiếp theo
   - 5 thiết bị dẫn chuẩn
   - Phương pháp chuẩn (checkboxes)
   - Người thực hiện
   - Tình trạng KT
5. Submit → Validate duplicate (tenmay + ngayhc)
6. INSERT hoặc UPDATE hosohckd_iso
```

### Luồng 3: Thiết bị dẫn chuẩn
```
1. Combobox load: SELECT FROM thietbihckd_iso WHERE loaitb=1 AND danchuan=1
2. Hiển thị: tenviettat-somay
3. Value lưu: mavattu
4. Lưu vào 5 trường: thietbidc1...thietbidc5
```

---

## 📝 CÁC FORM CHI TIẾT

### Form 1: Bảng cảnh báo
**URL:** `bangcanhbao.php?hosohc=canhbao&month=X`

**Input:**
- `month`: Tháng cần xem (1-12)
- `start`: Offset phân trang
- `username`, `password`: Authentication

**Output:** Bảng HTML với các cột:
- STT
- Số hồ sơ
- Tên máy (link đến form nhập)
- Số máy
- Công việc
- Ngày thực hiện
- Nhân viên
- Nơi thực hiện
- Chủ sở hữu

### Form 2: Nhập hồ sơ (Edit mode)
**URL:** `bangcanhbao.php?hosohc=hoso&tenthietbi=X&ngayhc=Y`

**Các trường input:**
1. **Số hồ sơ** (text) - Auto-generate
2. **Tên thiết bị** (dropdown) - Group theo tenthietbi
3. **Số máy** (text) - Auto-fill
4. **Chủ phương tiện** (text) - Auto-fill từ bophansh
5. **Phương pháp chuẩn** (checkboxes):
   - Dẫn chuẩn
   - Chuẩn qua mẫu chuẩn
   - Định kỳ
   - Đột xuất
6. **Thiết bị dẫn chuẩn** (5 dropdowns):
   - Load từ: `loaitb=1 AND danchuan=1`
   - Hiển thị: tenviettat-somay
7. **Ngày hiệu chuẩn** (date)
8. **Ngày HC tiếp theo** (date)
9. **Nơi hiệu chuẩn** (dropdown): XSCCMDVL/MN/XNKT
10. **Người HC** (dropdown) - From resume table
11. **Tình trạng KT** (dropdown): Tốt/Hỏng

### Form 3: Nhập hồ sơ (Add new mode)
**URL:** `bangcanhbao.php?hosohc=phieuyeucau`

Tương tự Form 2 nhưng:
- Không pre-fill dữ liệu
- Thiết bị dẫn chuẩn: `WHERE loaitb=1` (không có điều kiện danchuan=1)

---

## 🔍 LOGIC ĐẶC BIỆT

### 1. Auto-generate Số hồ sơ
```php
Format: YY-TMM-XX
- YY: 2 số cuối của năm
- TMM: T + tháng (01-12)
- XX: Số thứ tự tăng dần

Ví dụ: 
- 24-T03-01: Hồ sơ đầu tiên tháng 3/2024
- 24-T03-02: Hồ sơ thứ 2 tháng 3/2024
```

### 2. Kiểm tra trùng lặp
```php
Query: SELECT FROM hosohckd_iso 
       WHERE tenmay=X AND ngayhc=Y

if (exists) → UPDATE
else → INSERT
```

### 3. Xử lý công việc
```php
if (tenviettat IN ['KIT','DL/60','DL/76','KITA','KITB','ION'] 
    OR loaitb IN [5,6])
    → congviec = 'CM' (Chuẩn mẫu)
else
    → congviec = 'HC' (Hiệu chuẩn)
```

### 4. Mã màu tình trạng
```php
if (ngayhc == null) 
    → background = #FFFFFF (Trắng - chưa HC)
else if (ttkt == 'Tốt')
    → background = #A0FFFF (Xanh - HC tốt)
else if (ttkt == 'Hỏng')
    → background = #FFA0A0 (Đỏ - HC hỏng)
```

---

## ⚙️ TÍCH HỢP VÀO PROJECT MỚI

### Bước 1: Chuẩn bị Database
```sql
-- Tạo 4 bảng chính
CREATE TABLE kehoach_iso (...);
CREATE TABLE hosohckd_iso (...);
CREATE TABLE thietbihckd_iso (...);
CREATE TABLE kehoach_temp (...);
```

### Bước 2: Config Database
File `select_data.php` cần chứa:
```php
$hostname = "localhost";
$usernamehost = "root";
$passwordhost = "";
$databasename = "iso_database";
```

### Bước 3: Dependencies
- `myfunctions.php`: Các hàm helper
- MySQL extension (legacy - cần migrate sang MySQLi/PDO)

### Bước 4: Điều chỉnh
1. **Authentication:** Thêm session validation
2. **SQL Injection:** Sử dụng prepared statements
3. **Encoding:** Đảm bảo UTF-8 consistency
4. **Date format:** Chuyển từ mysql_* sang mysqli_*

---

## 🔐 BẢO MẬT & LƯU Ý

### Vấn đề bảo mật hiện tại:
1. ❌ SQL Injection: Không sử dụng prepared statements
2. ❌ Authentication yếu: username/password qua GET/POST
3. ❌ Sử dụng mysql_* (deprecated từ PHP 5.5)
4. ❌ Không có CSRF protection

### Khuyến nghị nâng cấp:
```php
// Thay thế
mysql_query($sql);

// Bằng
$stmt = $conn->prepare("SELECT * FROM table WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
```

---

## 📊 WORKFLOW DIAGRAM

```
┌─────────────────┐
│  User Access    │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────┐
│  bangcanhbao.php                    │
│  Check: $hosohc parameter           │
└──┬──────┬──────┬──────────┬────────┘
   │      │      │          │
   ▼      ▼      ▼          ▼
┌──────┐ ┌────┐ ┌─────┐  ┌──────┐
│canhbao│hoso│hosokt│ phieuyc│
└───┬──┘ └─┬──┘ └──┬──┘  └───┬──┘
    │      │       │          │
    ▼      ▼       ▼          ▼
┌─────────────────────────────────┐
│  Database Operations            │
│  - kehoach_iso                  │
│  - hosohckd_iso                 │
│  - thietbihckd_iso              │
│  - kehoach_temp                 │
└─────────────────────────────────┘
```

---

## 📞 LIÊN HỆ & HỖ TRỢ

Khi tích hợp vào project mới, cần lưu ý:

1. **Tương thích PHP:** File viết cho PHP 5.x
2. **Database:** MySQL 5.x+
3. **Character encoding:** UTF-8
4. **Timezone:** Asia/Ho_Chi_Minh

### Test checklist:
- [ ] Hiển thị bảng cảnh báo theo tháng
- [ ] Phân trang hoạt động
- [ ] Form nhập hồ sơ load đúng dữ liệu
- [ ] Dropdown thiết bị dẫn chuẩn lọc đúng
- [ ] Auto-generate số hồ sơ
- [ ] Insert/Update database thành công
- [ ] Mã màu trạng thái hiển thị đúng

---

## 📄 PHIÊN BẢN

- **Ngày tạo tài liệu:** 18/12/2025
- **Phiên bản:** 1.0
- **Tác giả:** Documentation Team
- **Ghi chú:** Tài liệu dựa trên phân tích source code hiện tại

---

**LƯU Ý QUAN TRỌNG:**  
File này sử dụng MySQL legacy extension đã deprecated. Khi migrate sang project mới, 
nên chuyển sang MySQLi hoặc PDO để đảm bảo tương thích với PHP 7.x+ và bảo mật tốt hơn.
