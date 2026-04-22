# Quản lý Thông tin Người dùng

## Tổng quan

Hệ thống quản lý thông tin cá nhân cho phép người dùng:
- Xem thông tin tài khoản của mình
- Cập nhật email
- Thay đổi mật khẩu

## Cấu trúc File

### Controllers
- **controllers/UserProfileController.php**: Xử lý logic quản lý profile
  - `view()`: Hiển thị thông tin người dùng
  - `edit()`: Hiển thị form chỉnh sửa
  - `update()`: Cập nhật email và tên
  - `changePassword()`: Thay đổi mật khẩu

### Models
- **models/User.php**: Đã thêm các method mới
  - `updateProfile($userStt, $data)`: Cập nhật thông tin profile
  - `updatePassword($userStt, $hashedPassword)`: Cập nhật mật khẩu

### Views
- **views/profile/view.php**: Trang hiển thị thông tin người dùng
- **views/profile/edit.php**: Trang chỉnh sửa thông tin và đổi mật khẩu

### Routes
- **profile.php**: File route chính xử lý các action

## Cách sử dụng

### Truy cập Profile
Người dùng có thể truy cập profile của mình qua:
1. Menu sidebar → **Thông tin cá nhân** (phía dưới cùng, trước nút Logout)
2. Trực tiếp qua URL: `/iso2/profile.php`

### Xem thông tin
Trang profile hiển thị:
- Tên đăng nhập (username)
- Họ và tên
- Email
- Vai trò (Role)
- Đơn vị (nếu có)
- Nhóm (nếu có)

### Chỉnh sửa thông tin
1. Nhấn nút **"Chỉnh sửa"** ở góc phải trên cùng
2. Cập nhật:
   - **Họ và tên**: Bắt buộc
   - **Email**: Không bắt buộc, nhưng phải đúng định dạng email
3. Nhấn **"Lưu thay đổi"**

### Đổi mật khẩu
1. Vào trang chỉnh sửa profile
2. Cuộn xuống phần **"Đổi mật khẩu"**
3. Nhập:
   - Mật khẩu hiện tại
   - Mật khẩu mới (tối thiểu 5 ký tự)
   - Xác nhận mật khẩu mới
4. Nhấn **"Đổi mật khẩu"**

## Bảo mật

### Validation
- Email được validate theo định dạng chuẩn
- Mật khẩu mới phải có ít nhất 5 ký tự
- Mật khẩu hiện tại phải đúng mới được đổi
- Mật khẩu xác nhận phải khớp với mật khẩu mới

### Password Hashing
- Mật khẩu mới được hash bằng `password_hash()` với `PASSWORD_DEFAULT`
- Hỗ trợ cả user cũ (password plaintext) và user mới (password hashed)

### Authentication
- Tất cả các trang đều yêu cầu đăng nhập (`requireAuth()`)
- Người dùng chỉ có thể sửa thông tin của chính mình

## API Routes

### GET /iso2/profile.php
Hiển thị trang thông tin người dùng

### GET /iso2/profile.php?action=edit
Hiển thị trang chỉnh sửa thông tin

### POST /iso2/profile.php?action=update
Cập nhật thông tin cơ bản (email, tên)

**Parameters:**
- `hoten` (required): Họ và tên
- `email` (optional): Email

### POST /iso2/profile.php?action=change_password
Thay đổi mật khẩu

**Parameters:**
- `current_password` (required): Mật khẩu hiện tại
- `new_password` (required): Mật khẩu mới
- `confirm_password` (required): Xác nhận mật khẩu mới

## Messages & Notifications

### Success Messages
- "Cập nhật thông tin thành công"
- "Thay đổi mật khẩu thành công"

### Error Messages
- "Email không hợp lệ"
- "Tên không được để trống"
- "Mật khẩu hiện tại không đúng"
- "Mật khẩu mới phải có ít nhất 5 ký tự"
- "Mật khẩu xác nhận không khớp"

## UI/UX Features

### Responsive Design
- Hỗ trợ đầy đủ mobile, tablet, desktop
- Sử dụng Tailwind CSS
- Icons từ Font Awesome 6

### Visual Highlights
- Header gradient màu xanh dương
- Form được tách biệt rõ ràng
- Màu sắc khác nhau cho các section
- Validation real-time cho password matching

### User Experience
- Thông báo rõ ràng sau mỗi hành động
- Xác nhận password phía client để tránh submit sai
- Các trường bắt buộc được đánh dấu (*)
- Gợi ý và hướng dẫn ngay tại form

## Database Schema

Sử dụng bảng `users` với các cột:
- `stt` (INT): Primary key
- `username` (VARCHAR): Tên đăng nhập
- `password` (VARCHAR): Mật khẩu (hashed)
- `hoten` (VARCHAR): Họ và tên
- `email` (VARCHAR): Email
- `role` (VARCHAR): Vai trò
- `madv` (VARCHAR): Mã đơn vị
- `nhom` (VARCHAR): Nhóm

## Notes

1. **Username không thể thay đổi** - Đây là identifier duy nhất của user
2. **Hỗ trợ backward compatibility** - User cũ với password plaintext vẫn hoạt động
3. **Session update** - Email được cập nhật vào session sau khi thay đổi
4. **Client-side validation** - Kiểm tra password match trước khi submit để UX tốt hơn
