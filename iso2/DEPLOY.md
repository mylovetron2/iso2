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
