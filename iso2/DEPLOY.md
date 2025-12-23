# Hướng dẫn Deploy tự động

## Cách 1: SSH + Git Pull (Khuyên dùng)
```bash
ssh your_username@diavatly.cloud
cd /home/mapselli676e/domains/diavatly.cloud/public_html/iso2
git pull origin main
```

## Cách 2: cPanel Git Version Control
1. Login cPanel
2. Git™ Version Control → Manage repository iso2
3. Click "Pull or Deploy"

## Cách 3: GitHub Webhook (Tự động)

### Setup (1 lần):

1. **Upload deploy.php lên server:**
   - Copy file `deploy.php` lên `/home/mapselli676e/domains/diavatly.cloud/public_html/iso2/`

2. **Tạo secret key:**
   - Mở deploy.php trên server
   - Đổi `your-secret-key-here-change-this` thành key riêng (ví dụ: `mySecretKey123!@#`)

3. **Cấu hình GitHub Webhook:**
   - Vào https://github.com/mylovetron2/iso2/settings/hooks
   - Click "Add webhook"
   - Payload URL: `https://diavatly.cloud/iso2/deploy.php`
   - Content type: `application/json`
   - Secret: [secret key bạn đã tạo ở bước 2]
   - Events: Chọn "Just the push event"
   - Active: ✓
   - Click "Add webhook"

4. **Cấp quyền thực thi:**
   ```bash
   chmod 755 /home/mapselli676e/domains/diavatly.cloud/public_html/iso2/deploy.php
   ```

### Sử dụng:
- Push code lên GitHub → Tự động deploy lên hosting
- Xem log: `/home/mapselli676e/domains/diavatly.cloud/public_html/iso2/deploy.log`

### Test webhook:
```bash
curl -X POST https://diavatly.cloud/iso2/deploy.php
```

## So sánh:

| Phương pháp | Ưu điểm | Nhược điểm |
|-------------|---------|------------|
| SSH + Git Pull | Nhanh, kiểm soát tốt | Cần SSH access |
| cPanel Git | Dễ dùng, GUI | Chậm hơn SSH |
| Webhook | Tự động hoàn toàn | Setup phức tạp hơn |

## Khuyến nghị:
- **Development**: Dùng SSH + Git Pull
- **Production**: Setup Webhook để tự động

---

## Nhật ký hoạt động

### 2024-12-18: Cải thiện hệ thống phân quyền
**Vấn đề:** User chỉ có quyền `hieuchuan.view` vẫn có thể tạo và chỉnh sửa hồ sơ

**Giải pháp:** Thêm kiểm tra quyền chi tiết cho từng action trong `bangcanhbao.php`:
- `formhoso`, `savehoso`: Yêu cầu quyền `hieuchuan.create` hoặc `hieuchuan.edit`
- `phieukt`, `savekt`: Yêu cầu quyền `hieuchuan.edit`
- `api_generatesohs`: Yêu cầu quyền `hieuchuan.create`

**Tác động:**
- Bảo mật tốt hơn: User chỉ xem không thể tạo/sửa hồ sơ
- Phân quyền rõ ràng theo từng thao tác (view/create/edit/delete)

**Files thay đổi:**
- `bangcanhbao.php`: Thêm permission checks trong switch statement

### 2024-12-17: Triển khai hệ thống phân quyền toàn diện
**Các thay đổi:**
1. Menu sidebar hiển thị dựa trên quyền user
2. Redirect tự động về trang thống kê khi không có quyền
3. Thêm phân quyền cho module Lô và Hiệu Chuẩn/Kiểm Định
4. Đổi tên menu "Bảng Cảnh Báo HC/KĐ" → "Hiệu Chuẩn/Kiểm Định"
5. Thay icon thành fa-certificate

**Files thay đổi:**
- `views/layouts/header.php`: Permission checks cho menu items
- `hososcbd.php`, `thietbi.php`, `thietbihckd.php`, `phieubangiao.php`, `donvi.php`, `lo.php`: Redirect logic
- `views/admin/permissions_manager.php`: Thêm permissions mới
- `bangcanhbao.php`: Thêm entry-level permission check
