# Tài liệu Tính năng Tạm dừng / Tiếp tục Hồ sơ

> **Documentation cho dự án khác muốn áp dụng tính năng này**

## 📋 Tổng quan

Tính năng **Tạm dừng / Tiếp tục** cho phép người dùng:
- ✅ Tạm dừng xử lý hồ sơ khi gặp vấn đề (thiếu vật tư, chờ thông tin, v.v.)
- ✅ Ghi nhận lý do tạm dừng và thời gian
- ✅ Tiếp tục xử lý khi đã giải quyết vấn đề
- ✅ Theo dõi lịch sử tạm dừng/tiếp tục
- ✅ Báo cáo thống kê hồ sơ tạm dừng

---

## 🗄️ Cấu trúc Database

### Bảng: `hososcbd_tamdung`

```sql
CREATE TABLE hososcbd_tamdung (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hoso VARCHAR(50) NOT NULL COMMENT 'Số hồ sơ (FK)',
    mavt VARCHAR(50) NOT NULL COMMENT 'Mã vật tư',
    somay VARCHAR(50) NOT NULL COMMENT 'Số máy',
    trangthai ENUM('dang_tam_dung', 'da_tiep_tuc') NOT NULL COMMENT 'Trạng thái: đang tạm dừng hoặc đã tiếp tục',
    lydo TEXT NULL COMMENT 'Lý do tạm dừng hoặc tiếp tục',
    nguoitao VARCHAR(50) NULL COMMENT 'Username người tạo action',
    ngaytao DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm tạo action',
    
    INDEX idx_hoso (hoso),
    INDEX idx_trangthai (trangthai),
    INDEX idx_ngaytao (ngaytao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử tạm dừng/tiếp tục hồ sơ SCBĐ';
```

### Cột bổ sung trong bảng chính: `hososcbd_iso`

```sql
ALTER TABLE hososcbd_iso 
ADD COLUMN is_tamdung TINYINT(1) DEFAULT 0 COMMENT '1 = đang tạm dừng, 0 = bình thường'
AFTER bg;
```

**Logic cập nhật:**
- Khi **Tạm dừng**: `is_tamdung = 1`
- Khi **Tiếp tục**: `is_tamdung = 0`

---

## 🔌 API Endpoints

### 1. API Tạm dừng hồ sơ

**Endpoint:** `POST api/hososcbd_tamdung.php?action=tam_dung`

**Request Body:**
```json
{
    "hoso": "1997-1",
    "mavt": "MV001",
    "somay": "SM001",
    "lydo": "Chờ vật tư từ kho"
}
```

**Response Success:**
```json
{
    "success": true,
    "message": "Đã tạm dừng hồ sơ 1997-1"
}
```

**Response Error:**
```json
{
    "success": false,
    "message": "Hồ sơ đang trong trạng thái tạm dừng"
}
```

---

### 2. API Tiếp tục hồ sơ

**Endpoint:** `POST api/hososcbd_tamdung.php?action=tiep_tuc`

**Request Body:**
```json
{
    "hoso": "1997-1",
    "lydo": "Đã nhận được vật tư, tiếp tục xử lý"
}
```

**Response Success:**
```json
{
    "success": true,
    "message": "Đã tiếp tục hồ sơ 1997-1"
}
```

---

### 3. API Kiểm tra trạng thái

**Endpoint:** `GET api/hososcbd_tamdung.php?action=check_status&hoso=1997-1`

**Response:**
```json
{
    "success": true,
    "is_tamdung": true,
    "last_action": {
        "id": 15,
        "trangthai": "dang_tam_dung",
        "lydo": "Chờ vật tư từ kho",
        "nguoitao": "admin",
        "ngaytao": "2026-04-14 10:30:00"
    }
}
```

---

### 4. API Lấy lịch sử

**Endpoint:** `GET api/hososcbd_tamdung.php?action=lich_su&hoso=1997-1`

**Response:**
```json
{
    "success": true,
    "items": [
        {
            "id": 15,
            "trangthai": "dang_tam_dung",
            "lydo": "Chờ vật tư từ kho",
            "nguoitao": "admin",
            "ngaytao": "2026-04-14 10:30:00"
        },
        {
            "id": 12,
            "trangthai": "da_tiep_tuc",
            "lydo": "Đã nhận vật tư",
            "nguoitao": "admin",
            "ngaytao": "2026-04-13 14:20:00"
        }
    ]
}
```

---

## 🎨 UI Components

### 1. Modal Quản lý Tạm dừng (Unified)

**File:** `views/hososcbd/partials/tamdung_modals.php`

**Cấu trúc:**
```
┌─────────────────────────────────────┐
│ [X] Quản lý Tạm dừng               │  ← Header động (xanh/cam)
├─────────────────────────────────────┤
│ 📋 Thông tin hồ sơ                  │  ← Section 1: Info
│   - Hồ sơ: 1997-1                  │
│   - Mã VT: MV001 / Số máy: SM001   │
├─────────────────────────────────────┤
│ ⏸️ Tạm dừng hồ sơ                   │  ← Section 2: Form (dynamic)
│   Lý do: [________________]        │     - Tạm dừng (if NOT paused)
│   [Xác nhận tạm dừng]              │     - Tiếp tục (if paused)
├─────────────────────────────────────┤
│ 📜 Lịch sử tạm dừng/tiếp tục ▼     │  ← Section 3: History (collapsible)
│   Timeline với icon và màu sắc      │
│   [Báo cáo] ← Link to report page  │
└─────────────────────────────────────┘
```

**JavaScript Open Function:**
```javascript
function openQuanLyTamDungModal(hoso, mavt, somay, isTamDung) {
    // isTamDung: true = đang tạm dừng, false = đang hoạt động
    // Modal tự động switch giữa form Tạm dừng/Tiếp tục
}
```

---

### 2. Badge Cảnh báo trong Table

**Vị trí:** Cột "Số hồ sơ" trong danh sách

```html
<span class="inline-block ml-2 bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded" 
      title="Hồ sơ đang tạm dừng">
    <i class="fas fa-pause-circle mr-1"></i>TẠM DỪNG
</span>
```

**Điều kiện hiển thị:**
```php
<?php if (!empty($item['is_tamdung']) && $item['is_tamdung'] == 1): ?>
    <!-- Badge tạm dừng -->
<?php endif; ?>
```

---

### 3. Filter Dropdown

**Vị trí:** Form lọc trong danh sách hồ sơ

```html
<select name="trangthai" class="border rounded px-3 py-2">
    <option value="">Tất cả trạng thái</option>
    <option value="chuath">Chưa thực hiện</option>
    <option value="danglam">Đang làm</option>
    <option value="hoanthanh">Hoàn thành</option>
    <option value="tamdung">Tạm dừng</option> <!-- New filter -->
</select>
```

---

## 📊 Trang Báo cáo

### Báo cáo Lịch sử: `baocao_hososcbd_tamdung.php`

**Tính năng:**
1. **Thống kê Cards (Interactive):**
   - 🟠 **Card 1:** Đang tạm dừng → Click filter `?trangthai=dang_tam_dung`
   - 🔵 **Card 2:** Toàn bộ lịch sử → Click xem tất cả
   - 🟢 **Card 3:** Quay lại hồ sơ SCBĐ

2. **Bộ lọc:**
   - Trạng thái: Tất cả / Đang tạm dừng / Các lượt tạm dừng / Các lượt tiếp tục
   - Đơn vị
   - Khoảng thời gian

3. **Bảng dữ liệu:**
   - Hồ sơ, Mã VT/Số máy
   - Trạng thái (badge màu)
   - Lý do
   - Người tạo
   - Thời gian

**URL Parameters:**
```
baocao_hososcbd_tamdung.php?trangthai=dang_tam_dung&madv=PHONGKT&from_date=2026-01-01&to_date=2026-12-31
```

---

## 🔄 Workflow Logic

### Kịch bản 1: Tạm dừng hồ sơ

```
1. User click nút "Tạm dừng" (nút cam)
   ↓
2. Modal mở → Form "Tạm dừng hồ sơ"
   ↓
3. User nhập lý do → Click "Xác nhận"
   ↓
4. AJAX POST → api/hososcbd_tamdung.php?action=tam_dung
   ↓
5. Backend:
   - INSERT record (trangthai='dang_tam_dung', lydo, nguoitao, ngaytao)
   - UPDATE hososcbd_iso SET is_tamdung=1
   ↓
6. Frontend:
   - Reload table
   - Nút chuyển từ "Tạm dừng" (cam) → "Tiếp tục" (xanh)
   - Badge "TẠM DỪNG" xuất hiện
```

### Kịch bản 2: Tiếp tục hồ sơ

```
1. User click nút "Tiếp tục" (nút xanh)
   ↓
2. Modal mở → Form "Tiếp tục hồ sơ"
   ↓
3. User nhập lý do → Click "Xác nhận"
   ↓
4. AJAX POST → api/hososcbd_tamdung.php?action=tiep_tuc
   ↓
5. Backend:
   - INSERT record (trangthai='da_tiep_tuc', lydo, nguoitao, ngaytao)
   - UPDATE hososcbd_iso SET is_tamdung=0
   ↓
6. Frontend:
   - Reload table
   - Nút chuyển từ "Tiếp tục" (xanh) → "Tạm dừng" (cam)
   - Badge "TẠM DỪNG" biến mất
```

### Kịch bản 3: Xem lịch sử trong Modal

```
1. User click "Lịch sử tạm dừng/tiếp tục ▼" trong modal
   ↓
2. AJAX GET → api/hososcbd_tamdung.php?action=lich_su&hoso=XXX
   ↓
3. Hiển thị timeline với icons:
   - 🟠 Tạm dừng: bg-orange-100, text-orange-700
   - 🟢 Tiếp tục: bg-green-100, text-green-700
```

---

## 🛠️ Model Methods

### HoSoScBdTamDung Model

**File:** `models/HoSoScBdTamDung.php`

#### 1. `tamDung($hoso, $mavt, $somay, $lydo, $nguoitao)`
```php
// Tạm dừng hồ sơ
// - INSERT record với trangthai='dang_tam_dung'
// - UPDATE hososcbd_iso SET is_tamdung=1
```

#### 2. `tiepTuc($hoso, $lydo, $nguoitao)`
```php
// Tiếp tục hồ sơ
// - INSERT record với trangthai='da_tiep_tuc'
// - UPDATE hososcbd_iso SET is_tamdung=0
```

#### 3. `getLatestStatus($hoso)`
```php
// Lấy trạng thái tạm dừng mới nhất
// Return: ['trangthai' => 'dang_tam_dung'|'da_tiep_tuc', ...]
```

#### 4. `getLichSu($hoso)`
```php
// Lấy toàn bộ lịch sử tạm dừng/tiếp tục
// ORDER BY ngaytao DESC
```

#### 5. `getBaoCaoLichSu($trangthai, $madv, $fromDate, $toDate, $offset, $limit)`
```php
// Báo cáo lịch sử với filter
// $trangthai: '' | 'dang_tam_dung' | 'da_tiep_tuc' | 'all'
// Đặc biệt: 'dang_tam_dung' sử dụng NOT EXISTS để show chỉ records chưa tiếp tục
```

#### 6. `countBaoCaoLichSu($trangthai, $madv, $fromDate, $toDate)`
```php
// Đếm records cho pagination
```

---

## 📝 Query Patterns

### Pattern 1: Lấy trạng thái hiện tại

```sql
SELECT hoso, trangthai
FROM hososcbd_tamdung td1
WHERE id = (
    SELECT MAX(id) 
    FROM hososcbd_tamdung td2 
    WHERE td2.hoso = td1.hoso
)
GROUP BY hoso;
```

**Logic:** Lấy record có `id` lớn nhất (mới nhất) của mỗi hồ sơ.

---

### Pattern 2: Filter "Đang tạm dừng" (chỉ records chưa tiếp tục)

```sql
SELECT *
FROM hososcbd_tamdung
WHERE trangthai = 'dang_tam_dung'
  AND NOT EXISTS (
      SELECT 1
      FROM hososcbd_tamdung t2
      WHERE t2.hoso = hososcbd_tamdung.hoso
        AND t2.trangthai = 'da_tiep_tuc'
        AND t2.id > hososcbd_tamdung.id
  )
ORDER BY ngaytao DESC;
```

**Logic:** Chọn records tạm dừng mà KHÔNG có record tiếp tục nào sau đó (id lớn hơn).

---

### Pattern 3: JOIN với bảng chính để hiển thị badge

```sql
SELECT h.*, 
       COALESCE(td_latest.trangthai, 'none') as tamdung_status,
       IF(td_latest.trangthai = 'dang_tam_dung', 1, 0) as is_tamdung
FROM hososcbd_iso h
LEFT JOIN (
    SELECT hoso, trangthai
    FROM hososcbd_tamdung td1
    WHERE id = (
        SELECT MAX(id) 
        FROM hososcbd_tamdung td2 
        WHERE td2.hoso = td1.hoso
    )
    GROUP BY hoso
) td_latest ON h.hoso = td_latest.hoso
WHERE h.madv = 'PHONGKT';
```

**Kết quả:**
- `is_tamdung = 1` → Hiển thị badge "TẠM DỪNG"
- `is_tamdung = 0` → Không badge

---

## 🚀 Hướng dẫn Triển khai cho Dự án Khác

### Bước 1: Tạo Database

```sql
-- Chạy migration
source migrations/create_hososcbd_tamdung_table.sql;

-- Hoặc chạy file migration helper
-- Truy cập: run_migration_tamdung.php
```

### Bước 2: Copy các file cần thiết

```
Cấu trúc file:
project/
├── api/
│   └── hososcbd_tamdung.php          (API endpoints)
├── models/
│   └── HoSoScBdTamDung.php           (Data layer)
├── views/
│   └── hososcbd/
│       └── partials/
│           └── tamdung_modals.php    (UI Modal)
├── baocao_hososcbd_tamdung.php       (Report page)
└── migrations/
    └── create_hososcbd_tamdung_table.sql
```

### Bước 3: Customize cho bảng của bạn

**Thay đổi tên bảng:**
- Tìm: `hososcbd_iso` → Thay: `your_main_table`
- Tìm: `hososcbd_tamdung` → Thay: `your_pause_table`

**Thay đổi khóa chính:**
- Tìm: `hoso` → Thay: `your_primary_key` (e.g., `order_id`, `ticket_id`)

**Thay đổi field identifier:**
- Tìm: `mavt`, `somay` → Thay: `product_code`, `serial_number` (hoặc bỏ nếu không cần)

### Bước 4: Update JOIN queries

Tìm các query JOIN trong:
- `models/HoSoSCBD.php` → Method `getList()`
- `baocao_hososcbd_tamdung.php` → Main query

Thay đổi:
```php
// FROM
FROM hososcbd_iso h
LEFT JOIN hososcbd_tamdung ...

// TO
FROM your_main_table h
LEFT JOIN your_pause_table ...
```

### Bước 5: Test workflow

1. ✅ Tạm dừng 1 record → Kiểm tra `is_tamdung = 1`
2. ✅ Tiếp tục record → Kiểm tra `is_tamdung = 0`
3. ✅ Xem lịch sử trong modal
4. ✅ Filter "Tạm dừng" trong danh sách
5. ✅ Xem báo cáo thống kê

---

## ⚠️ Lưu ý quan trọng

### 1. ENUM Values

**Production database sử dụng:**
- ✅ `'dang_tam_dung'` (13 ký tự)
- ✅ `'da_tiep_tuc'` (11 ký tự)

**KHÔNG dùng:**
- ❌ `'tamdung'` (legacy, chỉ để test)
- ❌ `'tieptuc'` (legacy)

### 2. Security

```php
// Luôn validate user permission
if (!hasPermission('hososcbd.edit')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền']);
    exit;
}

// Luôn escape input
$hoso = trim($_POST['hoso'] ?? '');
$lydo = trim($_POST['lydo'] ?? '');
```

### 3. Transaction Safety

```php
// Sử dụng transaction khi update 2 tables
$db->beginTransaction();
try {
    // INSERT vào hososcbd_tamdung
    $this->tamDungModel->tamDung(...);
    
    // UPDATE hososcbd_iso
    $this->model->updateTamDungStatus(...);
    
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    throw $e;
}
```

### 4. Index Performance

Đảm bảo có indexes:
```sql
-- Bảng hososcbd_tamdung
INDEX idx_hoso (hoso)           -- Tra cứu theo hồ sơ
INDEX idx_trangthai (trangthai) -- Filter theo trạng thái
INDEX idx_ngaytao (ngaytao)     -- Sort theo thời gian

-- Bảng hososcbd_iso
INDEX idx_is_tamdung (is_tamdung) -- Filter tạm dừng
```

---

## 📈 Thống kê & Metrics

### Dashboard Cards

```
┌─────────────────────┬─────────────────────┬─────────────────────┐
│ 🟠 Đang tạm dừng    │ 🔵 Tổng lượt        │ 🟢 Đã hoàn thành    │
│     15 hồ sơ       │   142 actions      │    127 lượt        │
│  (Click to filter) │  (View all)        │  (Back to list)    │
└─────────────────────┴─────────────────────┴─────────────────────┘
```

**Query cho "Đang tạm dừng":**
```sql
SELECT COUNT(*) 
FROM hososcbd_iso 
WHERE is_tamdung = 1;
```

**Query cho "Tổng lượt":**
```sql
SELECT COUNT(*) 
FROM hososcbd_tamdung;
```

---

## 🎯 Best Practices

### 1. Modal UX
- ✅ Dùng 1 modal duy nhất, chuyển đổi động giữa Tạm dừng/Tiếp tục
- ✅ History collapsible (default hidden để modal nhỏ gọn)
- ✅ Validate lý do (tối thiểu 10 ký tự)

### 2. Badge Display
- ✅ Chỉ hiển thị khi `is_tamdung = 1`
- ✅ Màu cam nổi bật (`bg-orange-500`)
- ✅ Icon pause circle (`fa-pause-circle`)

### 3. Filter Logic
- ✅ Filter "Tạm dừng" chỉ JOIN khi cần (performance)
- ✅ Sử dụng LEFT JOIN để không bỏ sót records
- ✅ Subquery cho latest status thay vì multiple JOINs

### 4. Report Page
- ✅ Statistics cards clickable (interactive)
- ✅ Active state indicator (checkmark khi filter đang active)
- ✅ Pagination cho danh sách dài

---

## 🔗 Related Files

| File | Mô tả |
|------|-------|
| `TAMDUNG_HOSOSCBD_README.md` | Tài liệu cho developers nội bộ |
| `TAMDUNG_HOSOSCBD_SUMMARY.md` | Tóm tắt tính năng |
| `HUONG_DAN_CHAY_MIGRATION_TAMDUNG.md` | Hướng dẫn chạy migration |
| `FIX_ERROR_500_TAMDUNG.md` | Troubleshooting |
| `check_tamdung_migration.php` | Kiểm tra migration status |
| `run_migration_tamdung.php` | Chạy migration tự động |

---

## 📞 Support & Questions

Nếu gặp vấn đề khi triển khai:

1. ✅ Kiểm tra migration đã chạy: `check_tamdung_migration.php`
2. ✅ Kiểm tra ENUM values: `'dang_tam_dung'` vs `'tamdung'`
3. ✅ Kiểm tra permissions: User có quyền `hososcbd.edit`?
4. ✅ Kiểm tra browser console: AJAX errors?
5. ✅ Kiểm tra PHP error log: `/var/log/php_errors.log`

---

## 📅 Version History

| Version | Date | Changes |
|---------|------|---------|
| 3.0 | 2026-04-14 | Gộp hososcbd_tamdung_list.php vào baocao, xóa file duplicate |
| 2.0 | 2026-04-13 | Gom 3 modals thành 1, giảm size 45%, thêm nút Báo cáo |
| 1.0 | 2026-04-01 | Release chính thức với đầy đủ tính năng |
| 0.5 | 2026-03-20 | Beta testing |

---

**License:** MIT  
**Author:** ISO2 Project Team  
**Last Updated:** April 14, 2026
