# Logic Checkbox BDDK trong formsc.php

## 1. Tổng quan

Checkbox **BDDK** (Bảo Dưỡng Định Kỳ) trong phiếu SC dùng để đánh dấu rằng lần sửa chữa/bảo dưỡng này thuộc kế hoạch bảo dưỡng định kỳ đã lên lịch trước.

Dữ liệu kế hoạch được lưu trong bảng: `ke_hoach_bao_duong_dinh_ky_iso`

---

## 2. Cấu trúc bảng `ke_hoach_bao_duong_dinh_ky_iso`

| Cột | Mô tả |
|-----|-------|
| `id` | Primary key |
| `thietbi_id` | FK → `thietbi_iso.stt` |
| `nam` | Năm kế hoạch (VD: 2026) |
| `ten_thietbi` | Tên thiết bị (denormalized) |
| `so_serial` | Số serial (denormalized) |
| `qui_1` | Kế hoạch Q1: `'TO'` = có kế hoạch, `NULL` = không |
| `qui_2` | Kế hoạch Q2 |
| `qui_3` | Kế hoạch Q3 |
| `qui_4` | Kế hoạch Q4 |
| `qui_1_hoantat` | Trạng thái thực hiện Q1: `1` = đã hoàn thành, `0` = chưa |
| `qui_2_hoantat` | Trạng thái thực hiện Q2 |
| `qui_3_hoantat` | Trạng thái thực hiện Q3 |
| `qui_4_hoantat` | Trạng thái thực hiện Q4 |
| `nhomsc` | Nhóm sửa chữa phụ trách |
| `created_by` | Người tạo kế hoạch |

**Lưu ý quan trọng:**
- `qui_X` = `'TO'` là **kế hoạch** (được lập trước, không được thay đổi qua phiếu SC)
- `qui_X_hoantat` là **trạng thái thực hiện** (được cập nhật khi submit phiếu SC)

---

## 3. Logic hiển thị checkbox BDDK (render form)

### Bước 1: Lấy `thietbi_id`
```php
$thietbi_id = 0; // Reset trước để tránh dùng giá trị cũ
SELECT stt FROM thietbi_iso WHERE mavt=? AND somay=? AND model=?
```

### Bước 2: Kiểm tra tự động tick checkbox
```sql
SELECT id FROM ke_hoach_bao_duong_dinh_ky_iso
WHERE thietbi_id = ?
  AND nam = $nam_check
  AND (qui_1_hoantat=1 OR qui_2_hoantat=1 OR qui_3_hoantat=1 OR qui_4_hoantat=1)
```
→ Nếu có bản ghi: `$bddk = 'BDDK'` → checkbox tự được tích

### Bước 3: Kiểm tra disabled/enabled checkbox
```sql
SELECT id FROM ke_hoach_bao_duong_dinh_ky_iso
WHERE thietbi_id = ? LIMIT 1
```
- **Có kế hoạch** → checkbox **enabled**
- **Không có kế hoạch** → checkbox **disabled** + tooltip "Không có kế hoạch bảo dưỡng định kỳ"

### Bước 4: Hiển thị bảng thông tin BDDK
```sql
SELECT * FROM ke_hoach_bao_duong_dinh_ky_iso
WHERE thietbi_id = ? ORDER BY nam DESC LIMIT 1
```
Hiển thị bảng 4 cột Quý:
- Ô trắng (`-`) = không có kế hoạch quý đó
- Ô cam = có kế hoạch (`qui_X = 'TO'`) nhưng chưa hoàn thành
- Ô cam + ✓ xanh = đã hoàn thành (`qui_X_hoantat = 1`)

---

## 4. Logic xử lý khi submit form (POST)

### Điều kiện kích hoạt
Sau khi lưu phiếu SC chính, đoạn code BDDK được chạy.

### Tính quý từ tháng
```php
$quy = ceil($thang / 3);
// Tháng 1-3  → Q1
// Tháng 4-6  → Q2
// Tháng 7-9  → Q3
// Tháng 10-12 → Q4
```

### Tính `hoantat`
```php
// hoantat = 1 chỉ khi phiếu có CẢ ngày bắt đầu VÀ ngày kết thúc hợp lệ
$hoantat = ($is_valid_start_date && $is_valid_end_date) ? 1 : 0;
```

---

### Nhánh A: Checkbox BDDK được tích (`$bddk == 'BDDK'`)

```
1. Validate tháng/năm (1 ≤ tháng ≤ 12, năm ≥ 2000)
2. Tìm thietbi_id từ thietbi_iso theo mavt + somay + model
3. Tính $quy từ tháng
4. SELECT kế hoạch: WHERE thietbi_id=? AND nam=?
   ├─ Có kế hoạch:
   │   ├─ Kiểm tra qui_X có = 'TO' không (quý này nằm trong kế hoạch)
   │   ├─ Có → UPDATE qui_X_hoantat = $hoantat
   │   └─ Không → bỏ qua (không thêm quý ngoài kế hoạch)
   └─ Không có kế hoạch → ghi log WARNING, bỏ qua
```

### Nhánh B: Checkbox BDDK bỏ tích (`$bddk != 'BDDK'`)

```
1. Validate tháng/năm
2. Tìm thietbi_id
3. Tính $quy
4. SELECT kế hoạch: WHERE thietbi_id=? AND nam=?
   ├─ Có → UPDATE qui_X_hoantat = 0  (chỉ reset trạng thái, KHÔNG xóa qui_X)
   └─ Không có → bỏ qua
```

---

## 5. Luồng tổng thể

```
Mở formsc.php (edit)
    │
    ├─ Reset $thietbi_id = 0
    ├─ Tìm thietbi_id từ thietbi_iso
    ├─ Kiểm tra BDDK hoàn thành năm nay? → $bddk = 'BDDK' (auto tick)
    ├─ Kiểm tra có kế hoạch không?
    │       ├─ Có → checkbox enabled
    │       └─ Không → checkbox disabled
    └─ Hiển thị bảng BDDK (kế hoạch mới nhất)

Submit form
    │
    ├─ [BDDK tích] ──────────────────────────────────────
    │   ├─ Validate tháng/năm
    │   ├─ Tìm thietbi_id
    │   ├─ Tính quý
    │   ├─ Tìm kế hoạch năm
    │   │   ├─ Có & qui_X = 'TO' → UPDATE qui_X_hoantat = $hoantat
    │   │   ├─ Có & qui_X = NULL → WARNING, bỏ qua
    │   │   └─ Không có → WARNING, bỏ qua
    │
    └─ [BDDK bỏ tích] ───────────────────────────────────
        ├─ Validate tháng/năm
        ├─ Tìm thietbi_id
        ├─ Tính quý
        └─ Tìm kế hoạch năm
            ├─ Có → UPDATE qui_X_hoantat = 0 (giữ nguyên qui_X)
            └─ Không có → bỏ qua
```

---

## 6. Quy tắc quan trọng

| Quy tắc | Mô tả |
|---------|-------|
| Kế hoạch (`qui_X`) chỉ được lập từ trang quản lý kế hoạch | Phiếu SC không được thêm/xóa kế hoạch |
| Phiếu SC chỉ cập nhật `qui_X_hoantat` | Không thay đổi `qui_X`, `nhomsc`, `created_by` |
| Checkbox disabled nếu không có kế hoạch | Không thể đánh dấu BDDK cho thiết bị chưa có kế hoạch |
| `hoantat = 1` chỉ khi có cả ngày BĐ và ngày KT | Phiếu chưa đóng không được đánh hoàn thành |
| `$thietbi_id` phải được reset = 0 trước query | Tránh dùng giá trị cũ từ lần render trước |

---

## 7. Các lỗi đã fix

| Ngày fix | Lỗi | Mô tả |
|----------|-----|-------|
| 30/06/2026 | `$thietbi_id` không reset | Biến giữ giá trị cũ → checkbox enabled sai cho thiết bị không có kế hoạch → INSERT bản ghi không chính thức |
| 30/06/2026 | `qui_X = NULL` khi clear | Xóa cả kế hoạch khi bỏ tick → chỉ nên reset `qui_X_hoantat = 0` |
| 30/06/2026 | SQL Injection | Thêm `mysql_real_escape_string()` cho các biến POST |
| 30/06/2026 | `$quy = 0` khi tháng rỗng | Thêm validation tháng/năm trước khi xử lý |
| 30/06/2026 | Ghi đè `nhomsc` | Bỏ `nhomsc` khỏi câu UPDATE của phiếu SC |
| 30/06/2026 | Thêm quý ngoài kế hoạch | Kiểm tra `qui_X = 'TO'` trước khi UPDATE |
| 30/06/2026 | `hoantat = 1` khi phiếu chưa xong | Yêu cầu cả ngày BĐ và ngày KT hợp lệ |
| 30/06/2026 | INSERT ngoài kế hoạch | Bỏ nhánh INSERT tự động, chỉ ghi log WARNING |

---

## 8. Files liên quan

| File | Vai trò |
|------|---------|
| `formsc.php` | Phiếu SC chính — logic BDDK checkbox |
| `formsc2.php` | Bản sao formsc.php — cần áp dụng fix tương tự |
| `formsc_test.php` | Bản test — cần áp dụng fix tương tự |
| `baoduongxuong.php` | Trang quản lý kế hoạch BDDK (tạo/sửa `qui_X`) |
| `debug_bddk.log` | File log debug — ghi lại toàn bộ hoạt động BDDK |
