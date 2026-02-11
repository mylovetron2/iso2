# Quản lý Số Phiếu Yêu Cầu

## 📋 Tổng quan

Module **Quản lý Số Phiếu Yêu Cầu** cung cấp giao diện quản lý tập trung theo số phiếu, trong đó mỗi phiếu có thể chứa nhiều hồ sơ bảo dưỡng (thiết bị).

### Đặc điểm chính:
- **Một phiếu - nhiều thiết bị**: Mỗi số phiếu YC có thể bao gồm nhiều hồ sơ bảo dưỡng
- **Quản lý tập trung**: Xem tổng quan trạng thái của tất cả thiết bị trong phiếu
- **Thống kê chi tiết**: Theo dõi tiến độ xử lý từng phiếu
- **Phân quyền rõ ràng**: Quyền riêng biệt cho module này

---

## 📁 Cấu trúc File

### 1. Model
- **`models/PhieuYeuCau.php`**
  - Quản lý dữ liệu phiếu (group by số phiếu)
  - Lấy danh sách phiếu với thống kê
  - Xem chi tiết phiếu và danh sách thiết bị
  - Cập nhật thông tin chung phiếu
  - Xóa phiếu (chỉ khi chưa thực hiện)

### 2. Controller
- **`controllers/PhieuYeuCauController.php`**
  - `index()`: Danh sách phiếu với filter
  - `view()`: Chi tiết phiếu và thiết bị
  - `create()`: Tạo phiếu mới với nhiều thiết bị
  - `edit()`: Sửa thông tin chung phiếu
  - `delete()`: Xóa phiếu

### 3. Views
- **`views/phieuyeucau/index.php`**: Danh sách phiếu
- **`views/phieuyeucau/view.php`**: Chi tiết phiếu
- **`views/phieuyeucau/create.php`**: Form tạo phiếu mới
- **`views/phieuyeucau/edit.php`**: Form sửa phiếu

### 4. Entry Point
- **`phieuyeucau.php`**: Điểm truy cập chính

---

## 🔐 Quyền truy cập

Module sử dụng các quyền riêng biệt:

| Quyền | Mô tả |
|-------|-------|
| `phieuyeucau.view` | Xem danh sách và chi tiết phiếu |
| `phieuyeucau.create` | Tạo phiếu yêu cầu mới |
| `phieuyeucau.edit` | Sửa thông tin chung của phiếu |
| `phieuyeucau.delete` | Xóa phiếu (chỉ khi chưa thực hiện) |

### Cấu hình quyền
Truy cập **Quản lý quyền Role** tại:
- URL: `/iso2/views/admin/permissions_manager.php`
- Menu: Admin → Quản lý quyền
- Nhóm quyền: **"Quản lý số phiếu YC"**

---

## 🌐 URL và Routing

| Action | URL | Method | Quyền cần thiết |
|--------|-----|--------|----------------|
| Danh sách | `/iso2/phieuyeucau.php` | GET | `phieuyeucau.view` |
| Chi tiết | `/iso2/phieuyeucau.php?action=view&phieu=0001` | GET | `phieuyeucau.view` |
| Tạo mới | `/iso2/phieuyeucau.php?action=create` | GET/POST | `phieuyeucau.create` |
| Sửa | `/iso2/phieuyeucau.php?action=edit&phieu=0001` | GET/POST | `phieuyeucau.edit` |
| Xóa | `/iso2/phieuyeucau.php?action=delete` | POST | `phieuyeucau.delete` |

---

## ✨ Tính năng chi tiết

### 1. Danh sách phiếu (`index`)
**Hiển thị:**
- Số phiếu
- Ngày yêu cầu
- Đơn vị khách hàng
- Người yêu cầu
- Số lượng thiết bị
- Thống kê trạng thái chi tiết:
  - Chưa thực hiện
  - Đang làm
  - Hoàn thành (chưa bàn giao)
  - Đã bàn giao
- Trạng thái tổng thể

**Filter:**
- Tìm kiếm: Số phiếu, đơn vị, người yêu cầu
- Đơn vị khách hàng
- Trạng thái
- Nhóm sửa chữa (CNC, KTKT, ĐIỆN, KHÍ)
- Khoảng thời gian

**Phân trang:** 20 phiếu/trang

### 2. Chi tiết phiếu (`view`)
**Thông tin phiếu:**
- Số phiếu, ngày YC
- Đơn vị, nhóm SC
- Người yêu cầu, người nhận YC
- Công việc yêu cầu
- Yêu cầu thêm từ khách hàng
- Thống kê thiết bị trong phiếu

**Danh sách thiết bị:**
- Mã quản lý (link đến chi tiết hồ sơ)
- Mã vật tư, tên thiết bị
- Số máy, model
- Trạng thái từng thiết bị
- Ngày thực hiện/kết thúc

**Actions:**
- Sửa thông tin phiếu
- Thêm thiết bị vào phiếu (link sang hososcbd.php)
- Xem/sửa từng thiết bị

### 3. Tạo phiếu mới (`create`)
**Form nhập liệu:**

**Thông tin phiếu:**
- Số phiếu (auto-generate hoặc tùy chỉnh)
- Ngày yêu cầu
- Đơn vị khách hàng *
- Nhóm sửa chữa
- Người yêu cầu
- Người nhận yêu cầu
- Điện thoại
- Công việc yêu cầu
- Yêu cầu thêm

**Danh sách thiết bị (Dynamic):**
- Mã vật tư *
- Số máy *
- Model
- Số lượng
- Vị trí thiết bị
- Nút "Thêm thiết bị" để thêm dòng mới
- Nút "Xóa" để xóa dòng

**Xử lý:**
- Validate dữ liệu
- Tạo nhiều bản ghi hososcbd_iso (mỗi thiết bị 1 bản ghi)
- Auto-generate `maql` và `hoso` cho từng thiết bị
- Redirect đến trang chi tiết phiếu

### 4. Sửa phiếu (`edit`)
**Chức năng:**
- Chỉ sửa **thông tin chung** của phiếu
- Áp dụng cho **TẤT CẢ thiết bị** trong phiếu
- Không thể thay đổi số phiếu
- Hiển thị cảnh báo trước khi lưu

**Thông tin có thể sửa:**
- Ngày yêu cầu
- Đơn vị
- Nhóm SC
- Người yêu cầu, người nhận
- Điện thoại
- Công việc yêu cầu
- Yêu cầu thêm

**Lưu ý:**
- Sửa thiết bị riêng lẻ → vào trang hososcbd.php

### 5. Xóa phiếu (`delete`)
**Điều kiện:**
- Chỉ xóa được nếu **TẤT CẢ thiết bị** trong phiếu đều CHƯA thực hiện
- Nếu có thiết bị đã thực hiện → không cho xóa

**Xử lý:**
- Xóa toàn bộ bản ghi trong `hososcbd_iso` có cùng số phiếu
- Confirm trước khi xóa

---

## 📊 Thống kê

### Dashboard (trên trang index):
- **Tổng số phiếu**: Tổng phiếu trong hệ thống
- **Tổng thiết bị**: Tổng thiết bị trên tất cả phiếu
- **Đang xử lý**: Thiết bị đang thực hiện
- **Đã bàn giao**: Thiết bị đã hoàn tất

### Từng phiếu:
- Chưa thực hiện
- Đang làm
- Hoàn thành (chưa BG)
- Đã bàn giao

---

## 🔄 Quan hệ với các module khác

### Hososcbd (Hồ sơ SCBĐ)
- PhieuYeuCau là **view tổng hợp** của Hososcbd (group by phieu)
- Mỗi phiếu YC → nhiều bản ghi hososcbd_iso
- Click vào thiết bị → chuyển sang hososcbd.php để xem/sửa chi tiết

### PhieuBanGiao
- Có thể tạo phiếu bàn giao từ các thiết bị trong phiếu YC
- Link "Theo phiếu YC" trong menu bàn giao

### Permissions
- Quyền riêng biệt cho phieuyeucau
- Cho phép phân quyền độc lập với hososcbd

---

## 🛠️ Cấu trúc Database

Module **KHÔNG tạo bảng mới**, sử dụng bảng hiện có:

### Bảng: `hososcbd_iso`
- **`phieu`**: Số phiếu yêu cầu (nhóm các thiết bị)
- **`maql`**: Mã quản lý (MADV.PHIEU)
- **`hoso`**: Mã hồ sơ (PHIEU-001, PHIEU-002...)
- **`mavt`, `somay`**: Thông tin thiết bị
- **`madv`**: Đơn vị khách hàng
- **`ngayyc`**: Ngày yêu cầu
- **`ngayth`, `ngaykt`**: Ngày thực hiện, kết thúc
- **`bg`**: Trạng thái bàn giao (0=chưa, 1=đã)
- **`nhomsc`**: Nhóm sửa chữa

---

## 📝 Ví dụ sử dụng

### Tạo phiếu với 3 thiết bị:
```
Phiếu: 0025
Đơn vị: DV01
Ngày YC: 11/02/2026

Thiết bị 1: MAY01, SM001
Thiết bị 2: MAY02, SM002
Thiết bị 3: MAY03, SM003
```

**Kết quả trong DB:**
```
hososcbd_iso:
- stt=100, phieu=0025, maql=DV01.0025, hoso=0025-001, mavt=MAY01, somay=SM001
- stt=101, phieu=0025, maql=DV01.0025, hoso=0025-002, mavt=MAY02, somay=SM002
- stt=102, phieu=0025, maql=DV01.0025, hoso=0025-003, mavt=MAY03, somay=SM003
```

### Xem phiếu:
- Truy cập: `/iso2/phieuyeucau.php?action=view&phieu=0025`
- Hiển thị thông tin chung + 3 thiết bị

### Sửa phiếu:
- Sửa ngày YC → áp dụng cho cả 3 thiết bị
- Sửa đơn vị → cập nhật 3 bản ghi

### Xóa phiếu:
- Chỉ xóa được nếu cả 3 thiết bị đều chưa thực hiện

---

## 🎨 Giao diện

- **Responsive**: Tương thích mobile/tablet/desktop
- **Icons**: Font Awesome
- **Colors**: Tailwind CSS
- **Trạng thái**:
  - 🟡 Vàng: Chưa thực hiện
  - 🟠 Cam: Đang làm
  - 🟣 Tím: Hoàn thành (chưa BG)
  - 🟢 Xanh: Đã bàn giao

---

## ⚙️ Cấu hình

### Menu Navigation
File: `views/layouts/header.php`
```php
<?php if (isLoggedIn() && hasPermission('phieuyeucau.view')): ?>
<li>
    <a href="/iso2/phieuyeucau.php">
        <i class="fas fa-file-alt mr-2"></i> Quản lý số phiếu YC
    </a>
</li>
<?php endif; ?>
```

### Permissions Manager
File: `views/admin/permissions_manager.php`
- Nhóm: "Quản lý số phiếu YC"
- 4 quyền: view, create, edit, delete

---

## 🐛 Troubleshooting

### Không thấy menu "Quản lý số phiếu YC"
✅ Kiểm tra quyền `phieuyeucau.view` trong permissions manager

### Lỗi khi tạo phiếu mới
✅ Kiểm tra quyền `phieuyeucau.create`  
✅ Kiểm tra kết nối DB  
✅ Xem error log PHP

### Không xóa được phiếu
✅ Kiểm tra có thiết bị nào đã thực hiện chưa  
✅ Chỉ xóa được khi TẤT CẢ thiết bị chưa thực hiện

### Sửa phiếu không ảnh hưởng đến thiết bị
✅ Đúng! Chỉ sửa thông tin chung  
✅ Muốn sửa từng thiết bị → vào hososcbd.php

---

## 📚 Tài liệu liên quan

- [HOSOSCBD_README.md](HOSOSCBD_README.md) - Hồ sơ sửa chữa bảo dưỡng
- [PHIEUBANGIAO_SETUP.md](PHIEUBANGIAO_SETUP.md) - Phiếu bàn giao
- [DOCUMENTATION_bangcanhbao.md](DOCUMENTATION_bangcanhbao.md) - Bảng cảnh báo

---

## 🔮 Phát triển tương lai

- [ ] Export danh sách phiếu ra Excel/PDF
- [ ] Thống kê theo thời gian, đơn vị, nhóm SC
- [ ] Dashboard riêng cho phiếu YC
- [ ] Email thông báo khi tạo/hoàn thành phiếu
- [ ] Clone phiếu (tạo phiếu mới từ phiếu cũ)
- [ ] Lịch sử thay đổi phiếu
- [ ] Comment/ghi chú cho phiếu

---

**Phiên bản:** 1.0  
**Ngày tạo:** 11/02/2026  
**Tác giả:** GitHub Copilot
