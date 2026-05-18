# Backup Database

## Chức năng

Trang admin backup database cho phép:
- Backup cấu trúc và dữ liệu của 33 bảng quan trọng trong hệ thống ISO2
- Tải xuống file SQL backup
- Xem danh sách các bảng sẽ được backup
- Xem thông tin tổng quan (số bảng, kích thước tổng)

## Các bảng được Backup

Hệ thống chỉ backup các bảng quan trọng sau (33 bảng):

### Bảng Thiết bị & Vật tư (15 bảng)
- `danhmucvattu_iso` - Danh mục vật tư
- `thietbi_iso` - Thiết bị chính
- `thietbihckd_iso` - Thiết bị hiệu chuẩn/kiểm định
- `thietbihotro_iso` - Thiết bị hỗ trợ
- `linhkien_iso` - Linh kiện
- `vattu_thanh_ly_iso` - Vật tư thanh lý
- `vattu_thanh_ly_lichsu_iso` - Lịch sử thanh lý
- `vattu_thanh_ly_sudung_iso` - Sử dụng vật tư thanh lý
- `phanloai_vattu_thanh_ly_iso` - Phân loại vật tư thanh lý
- `giao_nhan_thietbi_iso` - Giao nhận thiết bị
- `phieubangiao_iso` - Phiếu bàn giao
- `phieubangiao_thietbi_iso` - Phiếu bàn giao thiết bị
- `phieu_kiem_soat_vattu_iso` - Phiếu kiểm soát vật tư
- `nhapxuat_iso` - Nhập xuất
- `lichsudn_iso` - Lịch sử đơn vị

### Bảng Bảo dưỡng & Kiểm định (6 bảng)
- `hososcbd_iso` - Hồ sơ sửa chữa bảo dưỡng
- `hosohckd_iso` - Hồ sơ hiệu chuẩn kiểm định
- `kehoach_iso` - Kế hoạch
- `kehoach_kiemdinh_2026_iso` - Kế hoạch kiểm định 2026
- `ke_hoach_bao_duong_dinh_ky_iso` - Kế hoạch bảo dưỡng định kỳ
- `kiemdinh_iso` - Kiểm định

### Bảng Hệ thống & Danh mục (12 bảng)
- `donvi_iso` - Đơn vị
- `vitri_iso` - Vị trí
- `nhanvien_iso` - Nhân viên
- `ngthuchien_iso` - Người thực hiện
- `lo_iso` - Lô
- `mo_iso` - Mô
- `link_iso` - Link
- `nhatky_iso` - Nhật ký
- `resume` - Resume/Hồ sơ
- `users` - Người dùng
- `roles` - Vai trò
- `role_user` - Phân quyền người dùng

**Lưu ý:** Các bảng hệ thống khác (activity_logs, sessions, cache, v.v.) không được backup để giảm kích thước file.

## Cách sử dụng

1. Đăng nhập với tài khoản Admin
2. Vào menu **Admin** > **Backup Database**
3. Click nút **"Tạo Backup & Tải xuống"**
4. File SQL sẽ được tải xuống với tên dạng: `backup_[tên_database]_[ngày-giờ].sql`

## Lưu ý

- File backup có thể rất lớn nếu database có nhiều dữ liệu
- Quá trình backup có thể mất vài phút với database lớn
- Nên backup định kỳ để đảm bảo an toàn dữ liệu
- Lưu trữ file backup ở nơi an toàn, tránh mất dữ liệu

## Restore từ Backup

Để khôi phục dữ liệu từ file backup:

### Cách 1: Sử dụng phpMyAdmin
1. Truy cập phpMyAdmin
2. Chọn database cần restore
3. Vào tab "Import"
4. Chọn file SQL backup
5. Click "Go"

### Cách 2: Sử dụng MySQL Command Line
```bash
mysql -u username -p database_name < backup_file.sql
```

### Cách 3: Sử dụng MySQL Workbench
1. Mở MySQL Workbench
2. Connect vào server
3. File > Run SQL Script
4. Chọn file backup
5. Execute

## Cấu trúc File Backup

File backup chứa:
- Cấu trúc bảng (CREATE TABLE)
- Dữ liệu (INSERT INTO)
- Foreign key constraints
- Indexes

Format: SQL Standard compatible với MySQL/MariaDB

## Bảo mật

- Chỉ Admin mới có quyền truy cập chức năng backup
- File backup chứa toàn bộ dữ liệu nhạy cảm
- Không public file backup lên internet
- Mã hóa file backup khi lưu trữ (khuyến nghị)
