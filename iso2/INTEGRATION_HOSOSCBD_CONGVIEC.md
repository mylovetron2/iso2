# 🔗 Integration: Hồ sơ SC/BĐ ↔ Công việc sửa chữa KPI

## 📋 Tổng quan

Chức năng tích hợp giữa **Hồ sơ sửa chữa/bảo dưỡng** (hososcbd_iso) và **Công việc sửa chữa hàng ngày** (congviec_suachua_iso) cho phép:

1. ✅ Xem danh sách công việc liên quan đến một hồ sơ SC/BĐ
2. ✅ Thêm công việc mới trực tiếp từ hồ sơ
3. ✅ Theo dõi tổng số giờ làm việc cho mỗi hồ sơ
4. ✅ Đánh giá KPI theo cấp độ bảo dưỡng (CAP1/CAP2/CAP3)

---

## 🎯 Cách sử dụng

### 1. Xem công việc từ hồ sơ SC/BĐ

**Bước 1:** Vào danh sách hồ sơ SC/BĐ
```
URL: /iso2/hososcbd.php
```

**Bước 2:** Tìm hồ sơ muốn xem → Trong cột **"Chi tiết"** (cuối cùng), click vào icon:
- 🔧 **Cờ lê** (màu cam) = Thông tin sửa chữa & Thiết bị đo
- 🤝 **Bắt tay** (màu tím) = Thông tin bàn giao

> **Lưu ý:** Icon cờ lê 🔧 nằm ở cột cuối cùng của bảng, bên cạnh các nút Xem/Sửa/Xóa

**Bước 3:** Cuộn xuống dưới, sau phần "Thiết bị hỗ trợ" sẽ thấy section:
```
🔧 Công việc sửa chữa liên quan
```

Hiển thị:
- 📊 **Số công việc:** Tổng số công việc đã làm cho hồ sơ này
- ⏱️ **Tổng số giờ:** Tổng thời gian (giờ) đã sửa chữa
- 📈 **Trung bình/công việc:** Thời gian trung bình mỗi công việc

---

### 2. Thêm công việc từ hồ sơ

**Bước 1:** Trong trang chi tiết hồ sơ, click nút:
```
[+ Thêm công việc]
```

**Bước 2:** Popup hiện ra với form:
- **Nhân viên** ⭐ (required) - Chọn từ danh sách
- **Ngày làm** ⭐ (required) - Mặc định hôm nay
- **Cấp độ bảo dưỡng** ⭐ (required - CAP1/CAP2/CAP3)
  - CAP1: 2h chuẩn (Bảo dưỡng cơ bản)
  - CAP2: 4h chuẩn (Bảo dưỡng trung cấp)
  - CAP3: 8h chuẩn (Đại tu/sửa lớn)
- **Số giờ làm** ⭐ (required) - Tối đa 8h/ngày
- **Giờ bắt đầu/kết thúc** (optional)
- **Nội dung công việc** ⭐ (required) - Mô tả chi tiết
- **Trạng thái** - Đang thực hiện / Hoàn thành / Tạm dừng
- **Ghi chú** (optional)

**Lưu ý:**
- `mavt` và `somay` được tự động lấy từ hồ sơ SC/BĐ
- `hososcbd_stt` được tự động link đến hồ sơ hiện tại
- Hệ thống sẽ kiểm tra tổng số giờ trong ngày không vượt quá 8h

**Bước 3:** Click **[Lưu công việc]**

Kết quả:
- ✅ Công việc được thêm vào database
- ✅ Trang reload và hiển thị công việc mới trong danh sách
- ✅ Thống kê được cập nhật (tổng giờ, số công việc)

---

### 3. Xem/Xóa công việc

Trong bảng danh sách công việc, mỗi dòng có 2 nút:

**👁️ Xem chi tiết:**
```javascript
viewCongViecDetail(stt)
```
- Mở tab mới: `/iso2/congviec_suachua.php?stt=123`
- Hiển thị đầy đủ thông tin công việc

**🗑️ Xóa:**
```javascript
deleteCongViec(stt)
```
- Confirm xác nhận
- Gọi AJAX DELETE → Reload trang

---

## 🔄 Luồng dữ liệu

```
hososcbd_iso (Hồ sơ SC/BĐ)
    ↓
    stt (PRIMARY KEY)
    ↓
congviec_suachua_iso (Công việc)
    ↓
    hososcbd_stt (FOREIGN KEY - nullable)
    
Mối quan hệ: 1 hososcbd → N congviec
```

**SQL Query:**
```sql
SELECT cv.*, cd.ma_capdo, cd.ten_capdo, cd.mau_sac
FROM congviec_suachua_iso cv
LEFT JOIN capdo_baocuong_iso cd ON cv.capdo_stt = cd.stt
WHERE cv.hososcbd_stt = :hososcbd_stt
ORDER BY cv.ngay_lam DESC
```

---

## 📊 Ý nghĩa các chỉ số KPI

### ✅ Đạt KPI
```
so_gio_lam ≤ kpi_gio_chuan
```
Ví dụ: CAP1 chuẩn 2h, làm xong trong 1.5h → **Đạt KPI**

### ⚠️ Gần đạt KPI
```
kpi_gio_chuan < so_gio_lam ≤ kpi_gio_chuan × 1.2
```
Ví dụ: CAP2 chuẩn 4h, làm mất 4.5h → **Gần đạt** (trong ngưỡng 20%)

### ❌ Chưa đạt KPI
```
so_gio_lam > kpi_gio_chuan × 1.2
```
Ví dụ: CAP3 chuẩn 8h, làm mất 10h → **Chưa đạt**

---

## 🎨 Màu sắc cấp độ

| Cấp độ | Màu | Hex | KPI chuẩn |
|--------|-----|-----|-----------|
| CAP1 | 🟢 Xanh lá | #4CAF50 | 2 giờ |
| CAP2 | 🟠 Cam | #FF9800 | 4 giờ |
| CAP3 | 🔴 Đỏ | #F44336 | 8 giờ |

---

## 📁 Files liên quan

### Backend
- `/iso2/congviec_suachua.php` - AJAX endpoints (save/delete/get)
- `/iso2/controllers/CongViecSuaChuaController.php` - Business logic
- `/iso2/models/CongViecSuaChua.php` - Model

### Frontend
- `/iso2/views/hososcbd/repair_details.php` - Trang chi tiết hồ sơ
- `/iso2/views/hososcbd/components/congviec_widget.php` - Widget công việc ⭐ **FILE MỚI**
- `/iso2/views/congviec/index.php` - Trang quản lý công việc

### Database
- `congviec_suachua_iso` - Bảng công việc
- `capdo_baocuong_iso` - Bảng cấp độ (CAP1/CAP2/CAP3)
- `hososcbd_iso` - Bảng hồ sơ SC/BĐ

---

## 🔧 Cấu trúc Widget

File: `views/hososcbd/components/congviec_widget.php`

**Input:** Cần biến `$stt` (hososcbd_iso.stt)

**Output:**
```html
<!-- Thống kê tổng quan (3 cards) -->
<div class="grid grid-cols-3">
    - Số công việc
    - Tổng số giờ
    - Trung bình/công việc
</div>

<!-- Bảng danh sách công việc -->
<table>
    - Ngày làm
    - Nhân viên
    - Cấp độ (badge màu)
    - Nội dung
    - Số giờ / KPI chuẩn
    - Đánh giá (icon ✓/⚠/✗)
    - Trạng thái
    - Thao tác (Xem/Xóa) 
</table>

<!-- Modal thêm công việc -->
<div id="addCongViecModal">
    <form id="formAddCongViec">
        <!-- Form fields -->
    </form>
</div>
```

---

## 🚀 API Endpoints

### POST `/iso2/congviec_suachua.php?action=save`
**Tạo/Cập nhật công việc**

Request:
```javascript
FormData {
    action: 'save',
    stt: '',  // empty = create, có giá trị = update
    nhanvien_stt: 123,
    ngay_lam: '2026-02-25',
    mavt: 'VT001',
    somay: 'SN12345',
    capdo_stt: 1,  // CAP1/CAP2/CAP3
    noi_dung: 'Thay dầu, bôi trơn...',
    so_gio_lam: 1.5,
    gio_bat_dau: '08:00',
    gio_ket_thuc: '09:30',
    trang_thai: 'Hoàn thành',
    ghi_chu: '',
    hososcbd_stt: 456  // Link to hososcbd
}
```

Response:
```json
{
    "success": true,
    "message": "Đã thêm công việc thành công!",
    "id": 789
}
```

### POST `/iso2/congviec_suachua.php?action=delete`
**Xóa công việc**

Request:
```javascript
FormData {
    action: 'delete',
    stt: 789
}
```

Response:
```json
{
    "success": true,
    "message": "Đã xóa công việc thành công!"
}
```

---

## ⚙️ Kiểm tra tính năng

### Test 1: Hiển thị widget
```
1. Vào: /iso2/hososcbd.php
2. Click vào bất kỳ hồ sơ nào
3. Click "Chi tiết sửa chữa"
4. Kiểm tra: Có section "Công việc sửa chữa liên quan" ở cuối trang?
   - Nếu có công việc: Hiển thị bảng + thống kê
   - Nếu trống: Hiển thị thông báo "Chưa có công việc nào"
```

### Test 2: Thêm công việc
```
1. Click nút [+ Thêm công việc]
2. Kiểm tra: Modal hiện ra?
3. Fill form:
   - Chọn nhân viên
   - Chọn cấp độ: CAP1 (KPI: 2h)
   - Nhập số giờ: 1.5h
   - Nhập nội dung: "Test công việc"
4. Click [Lưu công việc]
5. Kiểm tra:
   - Alert "Đã thêm công việc thành công!"?
   - Trang reload?
   - Công việc mới xuất hiện trong bảng?
   - Thống kê cập nhật: Số công việc +1, Tổng giờ +1.5h?
```

### Test 3: Kiểm tra giới hạn 8h
```
1. Thêm công việc: Nhân viên A, ngày hôm nay, 6h
2. Thêm công việc: Nhân viên A, ngày hôm nay, 3h
3. Kiểm tra: Alert lỗi "Tổng số giờ vượt quá 8 giờ"?
```

### Test 4: Đánh giá KPI
```
1. Thêm công việc CAP1 (KPI: 2h):
   - Số giờ 1.5h → Icon ✓ xanh (Đạt KPI)
   - Số giờ 2.3h → Icon ⚠ cam (Gần đạt)
   - Số giờ 3.0h → Icon ✗ đỏ (Chưa đạt)
```

### Test 5: Xóa công việc
```
1. Click icon 🗑️ ở một công việc
2. Confirm "Bạn có chắc chắn?"
3. Kiểm tra:
   - Alert "Đã xóa thành công!"?
   - Trang reload?
   - Công việc biến mất khỏi bảng?
   - Thống kê giảm tương ứng?
```

---

## 🐛 Troubleshooting

### Lỗi: "Lỗi: Thiếu tham số $stt"
**Nguyên nhân:** Biến `$stt` không được truyền vào widget

**Giải pháp:**
```php
// Kiểm tra trong repair_details.php
$stt = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Đảm bảo biến $stt có giá trị trước khi include widget
if (!$stt) {
    header("Location: hososcbd.php");
    exit;
}
```

### Lỗi: "Không tìm thấy file congviec_widget.php"
**Nguyên nhân:** Path include sai

**Giải pháp:**
```php
// Trong repair_details.php, đảm bảo đường dẫn đúng:
<?php include __DIR__ . '/components/congviec_widget.php'; ?>
// KHÔNG PHẢI:
// <?php include 'components/congviec_widget.php'; ?>
```

### Lỗi AJAX: "Lỗi kết nối"
**Nguyên nhân:** URL endpoint sai hoặc server không response

**Giải pháp:**
```javascript
// Kiểm tra URL trong widget
fetch('/iso2/congviec_suachua.php', ...)

// Kiểm tra Chrome DevTools → Network tab
// Response phải là JSON: {"success": true, ...}
```

### Công việc không hiển thị
**Kiểm tra:**
```sql
-- 1. Kiểm tra dữ liệu
SELECT * FROM congviec_suachua_iso WHERE hososcbd_stt = 123;

-- 2. Kiểm tra FK
SELECT cv.*, cd.ten_capdo 
FROM congviec_suachua_iso cv
LEFT JOIN capdo_baocuong_iso cd ON cv.capdo_stt = cd.stt
WHERE cv.hososcbd_stt = 123;

-- 3. Nếu trống → Chưa có công việc cho hồ sơ này
```

---

## 📝 Changelog

### v1.0.0 (2026-02-25)
- ✅ Initial release
- ✅ Widget hiển thị công việc trong hososcbd repair_details
- ✅ Form AJAX thêm công việc
- ✅ Thống kê: số công việc, tổng giờ, trung bình
- ✅ Đánh giá KPI theo cấp độ (icon ✓/⚠/✗)
- ✅ Xóa công việc
- ✅ Auto-fill mavt/somay từ hồ sơ
- ✅ Responsive design (Tailwind CSS)

---

## 🎓 Best Practices

### 1. Luôn ghi nhận công việc ngay khi sửa xong
```
✅ Hôm nay sửa xong → Hôm nay nhập vào hệ thống
❌ Nhập sau vài ngày → Dữ liệu không chính xác
```

### 2. Chọn đúng cấp độ
```
CAP1: Công việc đơn giản (vệ sinh, thay dầu, kiểm tra)
CAP2: Công việc trung bình (điều chỉnh, thay linh kiện nhỏ)
CAP3: Công việc phức tạp (đại tu, sửa lớn, thay linh kiện chính)
```

### 3. Mô tả công việc rõ ràng
```
✅ "Thay dầu thủy lực, bôi trơn các trục chính, kiểm tra hệ thống điện"
❌ "Bảo dưỡng"
```

### 4. Kiểm tra tổng giờ trước khi nhập
```
Hệ thống chỉ cho phép tối đa 8 giờ/ngày/nhân viên
Nếu cần nhập nhiều hơn → Tách thành nhiều ngày hoặc nhiều nhân viên
```

---

## 📞 Liên hệ & Hỗ trợ

**Tác giả:** GitHub Copilot  
**Ngày tạo:** 2026-02-25  
**Phiên bản:** 1.0.0  
**License:** Internal Use Only

**Báo lỗi/Góp ý:**
- Tạo issue trên Git repository
- Hoặc liên hệ team phát triển

---

**🎉 Tích hợp thành công! Chúc sử dụng hiệu quả!**
