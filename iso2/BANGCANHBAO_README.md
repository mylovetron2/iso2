# Hệ Thống Quản Lý Bảng Cảnh Báo Hiệu Chuẩn/Kiểm Định

## 📋 Tổng Quan

Hệ thống quản lý toàn diện quy trình Hiệu chuẩn/Kiểm định thiết bị theo chuẩn ISO, bao gồm 4 chức năng chính được tích hợp hoàn toàn vào project theo kiến trúc MVC.

---

## 🎯 Các Chức Năng Đã Tích Hợp

### 1. **Bảng Cảnh Báo** (`bangcanhbao.php`)
- Hiển thị danh sách thiết bị cần hiệu chuẩn theo tháng/năm
- Phân trang dữ liệu (10 dòng/trang)
- Mã màu trạng thái:
  - **Trắng**: Chưa hiệu chuẩn
  - **Xanh**: Đã HC - Tốt
  - **Đỏ**: Đã HC - Hỏng
- Link nhanh đến form nhập liệu

### 2. **Nhập Hồ Sơ HC** (`?action=formhoso`)
- Form nhập/cập nhật thông tin hiệu chuẩn
- Chọn tối đa 5 thiết bị dẫn chuẩn
- Tự động generate số hồ sơ (format: YY-TMM-XX)
- Auto-fill thông tin từ database
- Tự động tính ngày HC tiếp theo

### 3. **Phiếu Yêu Cầu** (`?action=phieuyc`)
- Danh sách thiết bị cần HC trong tháng
- Phân trang 20 dòng/trang
- Có thể in phiếu yêu cầu
- Link trực tiếp đến form nhập

### 4. **Phiếu Kiểm Tra** (`?action=phieukt`)
- Form nhập kết quả kiểm tra sau HC
- Chọn tình trạng: Tốt/Hỏng
- Chọn thiết bị dẫn chuẩn sử dụng
- Có thể in phiếu kiểm tra

---

## 📁 Cấu Trúc Files Đã Tạo

```
iso2/
├── bangcanhbao.php                    # File chính - Routing
├── controllers/
│   └── BangCanhBaoController.php      # Controller xử lý logic
├── models/
│   ├── KeHoachISO.php                 # Model kế hoạch HC
│   ├── HoSoHCKD.php                   # Model hồ sơ HC (đã mở rộng)
│   ├── ThietBiHCKD.php                # Model thiết bị (đã mở rộng)
│   └── Resume.php                     # Model nhân viên
├── views/
│   └── bangcanhbao/
│       ├── index.php                  # View bảng cảnh báo
│       ├── form_hoso.php              # View form nhập hồ sơ
│       ├── phieu_yeucau.php           # View phiếu yêu cầu
│       └── phieu_kiemtra.php          # View phiếu kiểm tra
├── api/
│   └── bangcanhbao.php                # API endpoints
└── assets/
    └── js/
        └── bangcanhbao.js             # JavaScript logic
```

---

## 🗄️ Database Tables Sử Dụng

### Bảng chính (đã có sẵn):
1. **kehoach_iso** - Kế hoạch hiệu chuẩn theo tháng
2. **hosohckd_iso** - Hồ sơ hiệu chuẩn/kiểm định
3. **thietbihckd_iso** - Danh mục thiết bị HC/KĐ
4. **resume** - Danh sách nhân viên

### Không cần tạo thêm bảng mới!

---

## 🚀 Cách Sử Dụng

### Truy cập hệ thống:
```
http://your-domain/bangcanhbao.php
```

### Các URL hợp lệ:

1. **Bảng cảnh báo:**
   ```
   bangcanhbao.php
   bangcanhbao.php?month=12&year=2025
   ```

2. **Form nhập hồ sơ:**
   ```
   bangcanhbao.php?action=formhoso&mavattu=TB001
   ```

3. **Phiếu yêu cầu:**
   ```
   bangcanhbao.php?action=phieuyc&month=12&year=2025
   ```

4. **Phiếu kiểm tra:**
   ```
   bangcanhbao.php?action=phieukt&stt=123
   ```

---

## 🔧 API Endpoints

File: `api/bangcanhbao.php`

### 1. Lấy thông tin thiết bị
```javascript
GET api/bangcanhbao.php?action=get_thietbi_info&mavattu=TB001
```

### 2. Lấy danh sách thiết bị dẫn chuẩn
```javascript
GET api/bangcanhbao.php?action=get_danchuan_list
```

### 3. Lấy hồ sơ mới nhất
```javascript
GET api/bangcanhbao.php?action=get_hoso_latest&mavattu=TB001
```

### 4. Tạo số hồ sơ tự động
```javascript
GET api/bangcanhbao.php?action=generate_sohs&month=12&year=2025
```

### 5. Kiểm tra trùng lặp
```javascript
POST api/bangcanhbao.php?action=check_duplicate
Body: mavattu=TB001&ngayhc=2025-12-18
```

---

## ⚡ Tính Năng JavaScript

File: `assets/js/bangcanhbao.js`

### Auto-fill thông tin:
- Tự động điền số máy, chủ phương tiện khi chọn thiết bị
- Tự động điền thiết bị dẫn chuẩn từ lần HC trước
- Tự động tính ngày HC tiếp theo

### Validation:
- Kiểm tra các trường bắt buộc
- Cảnh báo khi có thay đổi chưa lưu
- Kiểm tra trùng lặp trước khi lưu

### UX Enhancement:
- Loading state khi tạo số hồ sơ
- Smooth scrolling đến field lỗi
- Error highlighting

---

## 🎨 UI/UX Features

### Responsive Design:
- Tương thích mobile, tablet, desktop
- Sử dụng Tailwind CSS
- Grid layout linh hoạt

### Color Coding:
- Màu trắng: Chưa HC
- Màu xanh: HC tốt
- Màu đỏ: HC hỏng

### Icons:
- Font Awesome icons
- Intuitive visual cues

### Print Support:
- Có thể in Phiếu Yêu Cầu
- Có thể in Phiếu Kiểm Tra
- Print-optimized layout

---

## 🔐 Bảo Mật

### Đã Implement:
✅ Session-based authentication  
✅ Login required cho tất cả pages  
✅ PDO prepared statements  
✅ Input validation và sanitization  
✅ XSS protection với htmlspecialchars()  
✅ CSRF protection (session check)  
✅ Error logging thay vì hiển thị  

### Best Practices:
- Sử dụng PDO thay vì mysql_*
- Try-catch blocks cho error handling
- Type declarations (strict_types=1)
- Input validation ở cả client và server

---

## 📊 Workflow Logic

### 1. Xem Bảng Cảnh Báo
```
User → Chọn tháng/năm → Controller lấy data từ Model
→ JOIN với hosohckd_iso để lấy trạng thái
→ Hiển thị với màu tương ứng
```

### 2. Nhập Hồ Sơ HC
```
User → Click thiết bị → Auto-fill thông tin
→ Chọn/nhập dữ liệu → Submit
→ Controller kiểm tra trùng lặp
→ INSERT hoặc UPDATE database
→ Redirect về bảng cảnh báo với message
```

### 3. Tạo Số Hồ Sơ
```
User → Click "Tự động" → JavaScript lấy ngày HC
→ Call API generate_sohs
→ Model tìm số lớn nhất trong tháng
→ Tăng +1 và trả về (format: YY-TMM-XX)
```

### 4. Phiếu Kiểm Tra
```
User → Mở phiếu KT → Hiển thị thông tin HC
→ Nhập kết quả kiểm tra → Submit
→ UPDATE hosohckd_iso
→ Redirect về bảng cảnh báo
```

---

## 🔄 Integration với Project Hiện Có

### Sử dụng chung:
- ✅ `config/database.php` - Kết nối DB
- ✅ `includes/auth.php` - Authentication
- ✅ `views/layouts/header.php` và `footer.php`
- ✅ Tailwind CSS styling
- ✅ Font Awesome icons

### Model Pattern:
- Extends từ `BaseModel`
- Sử dụng PDO
- Error logging với try-catch

### Controller Pattern:
- Khởi tạo models trong constructor
- Methods cho từng action
- Exception handling
- Redirect với messages

### View Pattern:
- Include header/footer từ layouts
- Responsive HTML
- Inline PHP logic tối thiểu
- External JavaScript

---

## 🧪 Testing Checklist

- [x] Hiển thị bảng cảnh báo theo tháng
- [x] Phân trang hoạt động
- [x] Chọn thiết bị auto-fill thông tin
- [x] Tạo số hồ sơ tự động
- [x] Lưu hồ sơ mới
- [x] Cập nhật hồ sơ có sẵn
- [x] Mã màu trạng thái hiển thị đúng
- [x] Dropdown thiết bị dẫn chuẩn lọc đúng
- [x] Phiếu yêu cầu hiển thị và phân trang
- [x] Phiếu kiểm tra load và lưu đúng
- [x] API endpoints trả về đúng data
- [x] JavaScript validation hoạt động
- [x] Responsive trên mobile
- [x] Print layout đúng format

---

## 📝 Ghi Chú Quan Trọng

### 1. Format Số Hồ Sơ:
```
YY-TMM-XX
24-T12-01 = Hồ sơ đầu tiên tháng 12/2024
24-T12-02 = Hồ sơ thứ 2 tháng 12/2024
```

### 2. Logic Công Việc:
- Nếu `tenviettat` IN ['KIT','DL/60','DL/76','KITA','KITB','ION'] hoặc `loaitb` IN [5,6]
  → `congviec = 'CM'` (Chuẩn mẫu)
- Ngược lại → `congviec = 'HC'` (Hiệu chuẩn)

### 3. Thiết Bị Dẫn Chuẩn:
- Chỉ hiển thị thiết bị có: `loaitb = 1` AND `danchuan = 1`
- Có thể chọn tối đa 5 thiết bị dẫn chuẩn
- Lưu vào các trường: `thietbidc1` đến `thietbidc5`

### 4. Mã Màu Trạng Thái:
```php
if (ngayhc == null) → #FFFFFF (Trắng)
else if (ttkt == 'Tốt') → #A0FFFF (Xanh)
else if (ttkt == 'Hỏng') → #FFA0A0 (Đỏ)
```

---

## 🐛 Troubleshooting

### Lỗi "Không tìm thấy thiết bị":
- Kiểm tra `mavattu` có tồn tại trong `thietbihckd_iso`
- Kiểm tra JOIN với các bảng khác

### Không tạo được số hồ sơ:
- Kiểm tra format ngày đúng: YYYY-MM-DD
- Kiểm tra quyền truy cập table `hosohckd_iso`

### API trả về 401 Unauthorized:
- Kiểm tra session đã login
- Kiểm tra `includes/auth.php` hoạt động

### Dropdown thiết bị dẫn chuẩn trống:
- Kiểm tra có thiết bị nào có `loaitb=1` và `danchuan=1`
- Kiểm tra method `getDanhChuan()` trong model

---

## 📞 Support

Nếu có vấn đề, kiểm tra:
1. Error log của PHP
2. Browser console (F12) cho JavaScript errors
3. Network tab để xem API responses
4. Database queries trong code

---

## 📄 Version History

- **v1.0** (18/12/2025) - Initial implementation
  - 4 chức năng chính hoàn chỉnh
  - MVC architecture
  - API endpoints
  - JavaScript enhancements
  - Responsive design
  - Print support

---

**Tích hợp thành công vào project ISO2!** 🎉
