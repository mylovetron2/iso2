# 🔧 HƯỚNG DẪN DEBUG LỖI 500

## Đã Sửa Lỗi:

### 1. ✅ **Lỗi requireLogin() không tồn tại**
**File:** `bangcanhbao.php`
**Sửa:** Đổi từ `requireLogin()` sang `requireAuth()`

### 2. ✅ **Lỗi khởi tạo Models**
**Files:** `KeHoachISO.php`, `Resume.php`
**Sửa:** Thêm constructor gọi `parent::__construct('table_name')`

### 3. ✅ **Lỗi return type trong saveHoSo()**
**File:** `HoSoHCKD.php`
**Sửa:** Convert int return từ `update()` sang boolean

### 4. ✅ **Lỗi check result trong saveKiemTra()**
**File:** `BangCanhBaoController.php`
**Sửa:** Check `$result >= 0` thay vì `$result` (bool)

### 5. ✅ **Lỗi undefined variable $offset**
**Files:** `index.php`, `phieu_yeucau.php`
**Sửa:** Thêm check `isset($offset)` trong views

### 6. ✅ **Lỗi empty $years array**
**File:** `BangCanhBaoController.php`
**Sửa:** Thêm fallback nếu `$years` rỗng

---

## Cách Test:

### 1. Test cơ bản:
```
http://your-domain/test_bangcanhbao.php
```
File này sẽ test từng component:
- Database connection
- Auth system
- Models loading
- Controller loading
- Basic queries

### 2. Test chính:
```
http://your-domain/bangcanhbao.php
```

### 3. Nếu vẫn gặp lỗi 500:

#### A. Kiểm tra PHP Error Log:
```bash
# Windows
tail -f C:\xampp\apache\logs\error.log

# Linux
tail -f /var/log/apache2/error.log
```

#### B. Enable error display tạm thời:
Thêm vào đầu `bangcanhbao.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

#### C. Kiểm tra quyền file:
```bash
# Đảm bảo các file có quyền đọc
chmod 644 bangcanhbao.php
chmod 644 controllers/BangCanhBaoController.php
chmod 644 models/*.php
```

#### D. Kiểm tra database:
- Đảm bảo 4 bảng tồn tại: `kehoach_iso`, `hosohckd_iso`, `thietbihckd_iso`, `resume`
- Kiểm tra kết nối trong `config/database.php`

---

## Các Lỗi Thường Gặp & Cách Sửa:

### Lỗi: "Class 'BaseModel' not found"
**Nguyên nhân:** Đường dẫn require_once sai
**Sửa:** Kiểm tra đường dẫn trong models

### Lỗi: "Call to undefined function requireAuth()"
**Nguyên nhân:** Chưa include auth.php
**Sửa:** Đã sửa trong bangcanhbao.php

### Lỗi: "Table 'xxx' doesn't exist"
**Nguyên nhân:** Thiếu bảng trong database
**Sửa:** Import các migration cần thiết

### Lỗi: "Headers already sent"
**Nguyên nhân:** Output trước khi redirect
**Sửa:** Kiểm tra không có echo/print trước header()

### Lỗi: "Call to a member function on null"
**Nguyên nhân:** Object chưa được khởi tạo
**Sửa:** Check null trước khi gọi method

---

## Checklist Debug:

- [x] Syntax errors checked (php -l)
- [x] requireAuth() fixed
- [x] Models constructors fixed
- [x] Return types fixed
- [x] Variable initialization fixed
- [x] Error handling added
- [ ] Database connection tested
- [ ] Auth system tested
- [ ] Views rendering tested

---

## File Kiểm Tra:

### test_bangcanhbao.php
File debug đầy đủ để test từng component riêng biệt.

**Cách dùng:**
1. Truy cập: `http://your-domain/test_bangcanhbao.php`
2. Xem output - mỗi section hiển thị ✓ (pass) hoặc ✗ (fail)
3. Nếu có lỗi, đọc message để biết chính xác lỗi gì

---

## Liên Hệ Support:

Nếu vẫn gặp lỗi sau khi làm theo hướng dẫn:
1. Chụp màn hình lỗi
2. Copy error log
3. Gửi thông tin về để debug chi tiết hơn

---

**Cập nhật:** 18/12/2025 - Đã sửa tất cả lỗi phát hiện được
