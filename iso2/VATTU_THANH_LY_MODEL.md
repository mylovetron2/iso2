# MÔ HÌNH VẬT TƯ THANH LÝ - HỆ THỐNG ISO2

## 📋 TỔNG QUAN

Hệ thống quản lý vật tư thanh lý là một module quản lý toàn diện cho việc theo dõi, kiểm soát và thanh lý vật tư/thiết bị trong tổ chức. Hệ thống hỗ trợ đa ngôn ngữ (Tiếng Việt, Tiếng Anh, Tiếng Nga) và theo dõi đầy đủ lịch sử sử dụng.

## 🗂️ CẤU TRÚC DATABASE

Hệ thống bao gồm 4 bảng chính:

### 1. **vattu_thanh_ly_iso** (Bảng chính - Master Data)
Quản lý thông tin vật tư/thiết bị thanh lý.

**Cấu trúc:**
```sql
CREATE TABLE `vattu_thanh_ly_iso` (
  `stt` int(11) PRIMARY KEY AUTO_INCREMENT,
  `mavattu` varchar(50) -- Mã vật tư
  `so_serial` varchar(100) -- Số serial (thêm sau)
  `phanloai_id` int(11) DEFAULT 1 -- FK -> phanloai_vattu_thanh_ly_iso
  `vi_tri_sap_xep` int(11) DEFAULT 999 -- Thứ tự sắp xếp
  
  -- Tên đa ngôn ngữ
  `ten_tienganh` text
  `ten_tiengnga` text
  `ten_tiengviet` text
  
  -- Đặc tính kỹ thuật
  `dactinhkt_tiengnga` text
  `dactinhkt_tiengviet` text
  
  -- Đơn vị tính
  `dvt_tiengnga` varchar(50)
  `dvt_tiengviet` varchar(50)
  
  -- Thông tin số lượng và giá
  `soluong_conlai` decimal(10,2) -- Số lượng còn lại trong kho
  `dongia` decimal(15,2) -- Đơn giá
  
  -- Thông tin hợp đồng
  `ngaynhan` date -- Ngày nhận
  `sohd` varchar(50) -- Số hợp đồng
  `ngaykyhd` date -- Ngày ký hợp đồng
  
  -- Quản lý
  `nguoiquanly` varchar(100) -- Người quản lý
  `vitribaoquan` varchar(200) -- Vị trí bảo quản
  `ghichu` text
  
  `created_at` timestamp
  `updated_at` timestamp
  
  INDEX `idx_mavattu` (`mavattu`),
  INDEX `idx_ngaynhan` (`ngaynhan`),
  INDEX `idx_phanloai_id` (`phanloai_id`),
  CONSTRAINT `fk_vattu_phanloai` FOREIGN KEY (`phanloai_id`) 
    REFERENCES `phanloai_vattu_thanh_ly_iso` (`id`)
)
```

**Ý nghĩa:**
- Lưu thông tin cơ bản của vật tư
- `soluong_conlai` được tự động cập nhật khi có xuất/thanh lý
- Hỗ trợ 3 ngôn ngữ: Việt, Anh, Nga

### 2. **vattu_thanh_ly_sudung_iso** (Chi tiết sử dụng/thanh lý)
Theo dõi chi tiết việc sử dụng và thanh lý vật tư.

**Cấu trúc:**
```sql
CREATE TABLE `vattu_thanh_ly_sudung_iso` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `vattu_stt` int(11) NOT NULL -- FK -> vattu_thanh_ly_iso
  `nguoisudung` varchar(100) -- Người nhận/sử dụng
  `ngaysd_nhan` date -- Ngày nhận sử dụng
  `soluong` decimal(10,2) -- Số lượng xuất
  `bophan` varchar(100) -- Bộ phận nhận
  `mucdich_sudung` text -- Mục đích sử dụng
  `trangthai` varchar(20) DEFAULT 'dangdung' -- dangdung|dahoan|thanh_ly
  `ngayhoanthanh` date -- Ngày hoàn thành/trả lại
  `ghichu` text
  `created_at` timestamp
  `updated_at` timestamp
  
  INDEX `idx_nguoisudung` (`nguoisudung`),
  INDEX `idx_ngaysd` (`ngaysd_nhan`),
  INDEX `idx_trangthai` (`trangthai`),
  CONSTRAINT `fk_vattu_sudung` FOREIGN KEY (`vattu_stt`) 
    REFERENCES `vattu_thanh_ly_iso` (`stt`) 
    ON DELETE CASCADE ON UPDATE CASCADE
)
```

**Ý nghĩa:**
- 1 vật tư có thể có nhiều lần sử dụng/thanh lý (1-n)
- Mỗi lần xuất kho sẽ tạo 1 record mới
- `trangthai`:
  - `dangdung`: Đang được sử dụng
  - `dahoan`: Đã hoàn trả
  - `thanh_ly`: Đã thanh lý (không thu hồi)

### 3. **vattu_thanh_ly_lichsu_iso** (Lịch sử thay đổi số lượng)
Audit log cho mọi thay đổi về số lượng vật tư.

**Cấu trúc:**
```sql
CREATE TABLE `vattu_thanh_ly_lichsu_iso` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `vattu_stt` int(11) NOT NULL -- FK -> vattu_thanh_ly_iso
  `loai_thaydoi` ENUM('nhap','xuat','dieu_chinh','thanh_ly')
  `soluong_truoc` decimal(10,2) -- Số lượng trước thay đổi
  `soluong_thaydoi` decimal(10,2) -- Số lượng thay đổi (+/-)
  `soluong_sau` decimal(10,2) -- Số lượng sau thay đổi
  `nguoi_thuchien` varchar(100) -- Người thực hiện
  `ngay_thuchien` datetime DEFAULT CURRENT_TIMESTAMP
  `lydothaydo` text -- Lý do thay đổi
  `created_at` timestamp
  
  INDEX `idx_ngay_thuchien` (`ngay_thuchien`),
  INDEX `idx_loai_thaydoi` (`loai_thaydoi`),
  CONSTRAINT `fk_vattu_lichsu` FOREIGN KEY (`vattu_stt`) 
    REFERENCES `vattu_thanh_ly_iso` (`stt`) 
    ON DELETE CASCADE ON UPDATE CASCADE
)
```

**Ý nghĩa:**
- Theo dõi mọi thay đổi về số lượng
- Không cho phép xóa (audit trail)
- Tự động ghi log khi nhập/xuất/thanh lý

### 4. **phanloai_vattu_thanh_ly_iso** (Phân loại vật tư)
Danh mục phân loại cho vật tư.

**Cấu trúc:**
```sql
CREATE TABLE `phanloai_vattu_thanh_ly_iso` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `ma_phanloai` varchar(50) UNIQUE NOT NULL
  `ten_phanloai` varchar(100) NOT NULL
  `mau_sac` varchar(50) -- CSS classes (VD: bg-blue-100 text-blue-800)
  `thu_tu` int(11) DEFAULT 0 -- Thứ tự hiển thị
  `mo_ta` text
  `created_at` timestamp
  
  UNIQUE KEY `uk_ma_phanloai` (`ma_phanloai`)
)
```

**Dữ liệu mặc định:**
```sql
INSERT INTO phanloai_vattu_thanh_ly_iso VALUES
(1, 'VATTU', 'Vật tư', 'bg-blue-100 text-blue-800', 1, 'Vật tư chung'),
(2, 'CONGCU_DUNGCU', 'Công cụ dụng cụ', 'bg-purple-100 text-purple-800', 2, 'CCDC sản xuất'),
(3, 'TAISAN', 'Tài sản', 'bg-green-100 text-green-800', 3, 'Tài sản cố định'),
(4, 'PHELIEU', 'Phế liệu', 'bg-gray-100 text-gray-800', 4, 'Phế liệu không sử dụng');
```

## 📊 QUAN HỆ DỮ LIỆU (ERD)

```
┌─────────────────────────────┐
│ phanloai_vattu_thanh_ly_iso │
│ ─────────────────────────── │
│ • id (PK)                   │
│   ma_phanloai (UNIQUE)      │
│   ten_phanloai              │
│   mau_sac                   │
│   thu_tu                    │
└──────────┬──────────────────┘
           │ 1
           │ has many
           │ n
┌──────────▼──────────────────┐         1        ┌─────────────────────────────┐
│   vattu_thanh_ly_iso        │◄────────────────┤ vattu_thanh_ly_sudung_iso   │
│ ─────────────────────────── │                 │ ─────────────────────────── │
│ • stt (PK)                  │                 │ • id (PK)                   │
│   mavattu                   │ has many        │   vattu_stt (FK)            │
│   so_serial                 │                 │   nguoisudung               │
│   phanloai_id (FK)          │                 │   ngaysd_nhan               │
│   ten_tienganh/nga/viet     │                 │   soluong                   │
│   dactinhkt_tiengnga/viet   │                 │   bophan                    │
│   dvt_tiengnga/viet         │                 │   mucdich_sudung            │
│   soluong_conlai ⚡         │                 │   trangthai                 │
│   dongia                    │                 │   ngayhoanthanh             │
│   ngaynhan, sohd            │                 └─────────────────────────────┘
│   nguoiquanly               │
│   vitribaoquan              │
└──────────┬──────────────────┘
           │ 1
           │ has many
           │ n
┌──────────▼──────────────────┐
│ vattu_thanh_ly_lichsu_iso   │
│ ─────────────────────────── │
│ • id (PK)                   │
│   vattu_stt (FK)            │
│   loai_thaydoi              │
│   soluong_truoc/sau         │
│   soluong_thaydoi           │
│   nguoi_thuchien            │
│   ngay_thuchien             │
└─────────────────────────────┘

⚡ soluong_conlai: Tự động cập nhật khi thêm/xóa chi tiết sử dụng
```

## 🔄 LUỒNG HOẠT ĐỘNG

### 1. **Nhập vật tư mới**
```
User → Create Form → VatTuThanhLyController::create()
  → VatTuThanhLy::create($data)
  → INSERT INTO vattu_thanh_ly_iso
  → ActivityLogger::log() (ghi log)
  → Redirect → Success
```

### 2. **Xuất vật tư / Thanh lý**
```
User → View Detail → Add Chi Tiết Button → AJAX
  → VatTuThanhLyController::addChiTiet()
  → VatTuThanhLy::addChiTietSuDung($data)
  → INSERT INTO vattu_thanh_ly_sudung_iso
  → UPDATE vattu_thanh_ly_iso 
      SET soluong_conlai = soluong_conlai - :soluong
  → Return JSON success
  → Refresh detail table (AJAX)
```

**Logic tự động trừ số lượng:**
```php
private function deductSoLuongConLai(int $vattuStt, float $soluongThanhLy): void
{
    $sql = "UPDATE vattu_thanh_ly_iso 
            SET soluong_conlai = soluong_conlai - :soluong_thanhly
            WHERE stt = :vattu_stt";
    
    $this->query($sql, [
        ':soluong_thanhly' => $soluongThanhLy,
        ':vattu_stt' => $vattuStt
    ]);
}
```

### 3. **Xóa chi tiết thanh lý**
```
User → Delete Button → Confirm → AJAX
  → VatTuThanhLyController::deleteChiTiet()
  → Get detail info (để lấy số lượng)
  → DELETE FROM vattu_thanh_ly_sudung_iso
  → UPDATE vattu_thanh_ly_iso 
      SET soluong_conlai = soluong_conlai + :soluong (cộng lại)
  → Return JSON success
```

### 4. **Tìm kiếm & Lọc**
```
User → Search Form → VatTuThanhLyController::index()
  → Build WHERE conditions:
    - Search: mavattu, ten_*, nguoiquanly (đa ngôn ngữ)
    - Filter: phanloai_id
  → VatTuThanhLy::getAllWithStats($where, $params)
  → JOIN với phanloai và sudung
  → COUNT, SUM, GROUP BY để tính thống kê
  → Display với pagination
```

## 🎯 TÍNH NĂNG CHÍNH

### ✅ CRUD Vật tư
- **Create**: Tạo vật tư mới với đầy đủ thông tin đa ngôn ngữ
- **Read**: Xem danh sách, chi tiết, lịch sử sử dụng
- **Update**: Sửa thông tin vật tư (không cho sửa số lượng trực tiếp)
- **Delete**: Xóa vật tư (CASCADE xóa cả chi tiết sử dụng)

### 📦 Quản lý Chi tiết Sử dụng/Thanh lý
- **Thêm chi tiết**: Xuất vật tư cho người/bộ phận
- **Cập nhật trạng thái**: dangdung → dahoan → thanh_ly
- **Xóa chi tiết**: Tự động cộng lại số lượng
- **Không cho sửa số lượng** sau khi đã tạo (tránh sai lệch)

### 📊 Thống kê & Báo cáo
- **Tổng số lượng còn lại** (từ bảng chính)
- **Số lượng đang sử dụng** (SUM từ bảng sudung)
- **Tổng giá trị** (soluong_conlai × dongia)
- **Số lần sử dụng** (COUNT từ bảng sudung)

### 🔍 Tìm kiếm nâng cao
- Tìm theo: Mã vật tư, Tên (3 ngôn ngữ), Người quản lý
- Lọc theo phân loại (VATTU, CONGCU_DUNGCU, TAISAN, PHELIEU)
- Hỗ trợ case-insensitive (lowercase + capitalized)
- Pagination (20 items/page)

### 📋 Phân loại
- Quản lý danh mục phân loại
- Gán màu sắc cho mỗi loại (Tailwind CSS classes)
- Kiểm tra phân loại đang được sử dụng trước khi xóa

### 🔐 Phân quyền
```php
Permissions:
- vattu.view    : Xem danh sách, chi tiết
- vattu.create  : Tạo vật tư mới
- vattu.edit    : Sửa thông tin, thêm/sửa chi tiết sử dụng
- vattu.delete  : Xóa vật tư, xóa chi tiết sử dụng
```

## 🛣️ ROUTES & ACTIONS

### Main Routes (vattuthanhly.php)
```php
GET  /iso2/vattuthanhly.php                    → index (danh sách)
GET  /iso2/vattuthanhly.php?action=view&id=X   → view (chi tiết)
GET  /iso2/vattuthanhly.php?action=create      → create form
POST /iso2/vattuthanhly.php?action=create      → create (submit)
GET  /iso2/vattuthanhly.php?action=edit&id=X   → edit form
POST /iso2/vattuthanhly.php?action=edit&id=X   → edit (submit)
POST /iso2/vattuthanhly.php?action=delete&id=X → delete

// AJAX endpoints
GET  /iso2/vattuthanhly.php?action=getChiTiet&vattu_stt=X → Get chi tiết JSON
POST /iso2/vattuthanhly.php?action=addChiTiet             → Add chi tiết (JSON)
POST /iso2/vattuthanhly.php?action=editChiTiet&id=X      → Edit chi tiết (JSON)
POST /iso2/vattuthanhly.php?action=deleteChiTiet&id=X    → Delete chi tiết (JSON)
```

### Controller Methods (VatTuThanhLyController.php)
```php
public function index(): void          // Danh sách + search + filter
public function create(): void         // Form + submit create
public function edit(): void           // Form + submit edit
public function delete(): void         // Soft/hard delete
public function view(): void           // Chi tiết vật tư + lịch sử
public function getChiTiet(): void     // API: Lấy chi tiết sử dụng (JSON)
public function addChiTiet(): void     // API: Thêm chi tiết (JSON)
public function editChiTiet(): void    // API: Sửa chi tiết (JSON)
public function deleteChiTiet(): void  // API: Xóa chi tiết (JSON)
```

### Model Methods (VatTuThanhLy.php)
```php
// CRUD cơ bản (kế thừa từ BaseModel)
public function create(array $data): bool
public function update(int $id, array $data): bool
public function delete(int $id): bool
public function findById(int $id): ?array

// Specialized methods
public function getAllWithStats(string $where, array $params, int $limit, int $offset): array
public function count(string $where, array $params): int

// Chi tiết sử dụng
public function getChiTietSuDung(int $vattuStt): array
public function addChiTietSuDung(array $data): bool
public function updateChiTietSuDung(int $id, array $data): bool
public function deleteChiTietSuDung(int $id): bool
public function getChiTietById(int $id): ?array

// Private helpers
private function deductSoLuongConLai(int $vattuStt, float $soluong): void
private function addBackSoLuongConLai(int $vattuStt, float $soluong): void
```

## 📁 CẤU TRÚC FILE

```
iso2/
├── vattuthanhly.php                          # Entry point / Router
├── controllers/
│   └── VatTuThanhLyController.php            # Controller chính
├── models/
│   ├── VatTuThanhLy.php                      # Model vật tư
│   └── PhanLoaiVatTu.php                     # Model phân loại
├── views/vattuthanhly/
│   ├── index.php                             # Danh sách
│   ├── view.php                              # Chi tiết + lịch sử
│   ├── create.php                            # Form tạo mới
│   ├── edit.php                              # Form sửa
│   ├── thongke.php                           # Thống kê thanh lý
│   ├── export_word.php                       # Xuất Word vật tư
│   └── export_word_congcu.php                # Xuất Word công cụ
├── create_table_vattu_thanh_ly.sql           # SQL tạo bảng
├── add_phanloai_vattu_thanh_ly.sql           # SQL thêm phân loại
├── add_dactinhkythuat_columns.sql            # SQL thêm đặc tính kỹ thuật
├── add_serial_column.sql                     # SQL thêm cột serial
├── add_vitri_sapxep_column.sql               # SQL thêm vị trí sắp xếp
└── debug_vattu_index.php                     # Debug tool
```

## 🔒 BẢO MẬT & KIỂM SOÁT

### Authentication & Authorization
```php
// Trong vattuthanhly.php
requireAuth();  // Yêu cầu đăng nhập

// Check quyền cho từng action
if (!hasPermission('vattu.view')) {
    header('Location: /iso2/index.php?error=no_permission');
    exit;
}
```

### Activity Logging
```php
// Ghi log mọi thao tác quan trọng
$this->logger->log(
    'vattu_thanh_ly_iso',     // Bảng
    'INSERT',                  // Action
    $insertId,                 // Record ID
    null,                      // Old data
    [                          // New data
        'mavattu' => '...',
        'ten_tiengviet' => '...',
        'soluong_conlai' => 100
    ]
);
```

### Data Integrity
- **Foreign Key Constraints**: Đảm bảo tính toàn vẹn quan hệ
- **CASCADE ON DELETE**: Tự động xóa chi tiết khi xóa master
- **Transaction**: Sử dụng transaction cho các thao tác phức tạp
- **Validation**: Kiểm tra dữ liệu trước khi lưu

## 📈 CÁCH TÍNH SỐ LƯỢNG

### Số lượng còn lại (soluong_conlai)
```
Số lượng còn lại = Số lượng ban đầu - Tổng số lượng đã xuất/thanh lý

Formula:
soluong_conlai = soluong_conlai - soluong (khi thêm chi tiết)
soluong_conlai = soluong_conlai + soluong (khi xóa chi tiết)
```

### Số lượng đang sử dụng
```sql
SELECT SUM(soluong) 
FROM vattu_thanh_ly_sudung_iso 
WHERE vattu_stt = X AND trangthai = 'dangdung'
```

### Tổng giá trị
```sql
SELECT soluong_conlai * COALESCE(dongia, 0) AS tong_tien
FROM vattu_thanh_ly_iso
```

## 🌟 TÍNH NĂNG ĐẶC BIỆT

### 1. Đa ngôn ngữ (Multilingual)
- Hỗ trợ 3 ngôn ngữ: Tiếng Việt, Tiếng Anh, Tiếng Nga
- Lưu song song tất cả các ngôn ngữ
- Tìm kiếm được trên tất cả ngôn ngữ

### 2. Tự động cập nhật số lượng
- Khi thêm chi tiết → tự động trừ số lượng
- Khi xóa chi tiết → tự động cộng lại
- Không cho sửa số lượng trực tiếp trong chi tiết

### 3. Audit Trail
- Ghi log mọi thao tác thêm/sửa/xóa
- Lưu cả dữ liệu cũ và mới
- Theo dõi người thực hiện và thời gian

### 4. Phân loại màu sắc
- Mỗi phân loại có màu riêng (Tailwind CSS)
- Dễ phân biệt trực quan
- Có thể tùy chỉnh

### 5. View tổng hợp
```sql
CREATE VIEW view_vattu_thanh_ly_tonghop AS
SELECT 
    v.*,
    COUNT(DISTINCT s.id) as so_lan_sudung,
    SUM(CASE WHEN s.trangthai = 'dangdung' THEN s.soluong ELSE 0 END) as soluong_dangdung,
    v.soluong_conlai * v.dongia as tong_tien
FROM vattu_thanh_ly_iso v
LEFT JOIN vattu_thanh_ly_sudung_iso s ON v.stt = s.vattu_stt
GROUP BY v.stt;
```

## ⚠️ LƯU Ý QUAN TRỌNG

### 1. Không cho sửa số lượng sau khi tạo chi tiết
- Lý do: Tránh sai lệch dữ liệu
- Giải pháp: Xóa và tạo lại nếu sai

### 2. Cascade Delete
- Xóa vật tư → tự động xóa tất cả chi tiết sử dụng
- Xóa chi tiết → PHẢI cộng lại số lượng

### 3. Kiểm tra phân loại trước khi xóa
```php
public function isUsedInVatTu(int $id): bool
{
    $sql = "SELECT COUNT(*) FROM vattu_thanh_ly_iso WHERE phanloai_id = :id";
    $stmt = $this->query($sql, [':id' => $id]);
    return (int)$stmt->fetchColumn() > 0;
}
```

### 4. Transaction cho các thao tác phức tạp
- Thêm chi tiết: INSERT + UPDATE soluong_conlai
- Xóa chi tiết: DELETE + UPDATE soluong_conlai
- Đảm bảo atomic operation

## 🔮 HƯỚNG PHÁT TRIỂN

### Tính năng có thể thêm:
1. **Quét mã vạch/QR Code**: Tra cứu nhanh bằng scanner
2. **Tích hợp với Phiếu kiểm soát vật tư**: Link với bảng phieu_ks_vattu_iso
3. **Báo cáo Excel**: Xuất báo cáo thống kê
4. **Cảnh báo hết hàng**: Thông báo khi soluong_conlai < ngưỡng
5. **Lịch sử giá**: Theo dõi biến động giá theo thời gian
6. **Import hàng loạt**: Import từ Excel/CSV
7. **Dashboard**: Biểu đồ tổng quan vật tư
8. **Phê duyệt thanh lý**: Workflow approve thanh lý

---

**Tài liệu được tạo:** 18/03/2026  
**Phiên bản:** 1.0  
**Người tạo:** GitHub Copilot
