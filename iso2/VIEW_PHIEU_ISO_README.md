# Hướng dẫn tạo VIEW view_phieu_iso

## Mục đích
Tạo view MySQL tên `view_phieu_iso` với các cột:
- `phieu` - Số phiếu
- `ngayyc` - Ngày yêu cầu
- `ngyeucau` - Nội dung yêu cầu

## Nguồn dữ liệu
Bảng: `hososcbd_iso`

## Cách thực hiện

### Cách 1: Chạy file SQL trực tiếp

```bash
mysql -h diavatly.com -u diavatly_master -p -P 3306 diavatly_db < create_view_phieu_iso.sql
```

### Cách 2: Sử dụng PHP script

Truy cập URL sau trong trình duyệt:
```
http://your-domain.com/iso2/execute_create_view_phieu.php
```

Hoặc chạy trong terminal:
```bash
php execute_create_view_phieu.php
```

### Cách 3: Chạy SQL trực tiếp trong MySQL client

```sql
CREATE OR REPLACE VIEW view_phieu_iso AS
SELECT 
    phieu,
    ngayyc,
    ngyeucau
FROM 
    hososcbd_iso
ORDER BY 
    ngayyc DESC, phieu DESC;
```

## Kiểm tra view đã tạo

```sql
-- Xem cấu trúc view
DESCRIBE view_phieu_iso;

-- Xem dữ liệu
SELECT * FROM view_phieu_iso LIMIT 10;

-- Đếm số records
SELECT COUNT(*) FROM view_phieu_iso;
```

## Files đã tạo

1. `create_view_phieu_iso.sql` - File SQL tạo view
2. `execute_create_view_phieu.php` - Script PHP để thực thi và test view

## Lưu ý

- View sẽ tự động cập nhật khi dữ liệu trong bảng `hososcbd_iso` thay đổi
- View được sắp xếp theo ngày yêu cầu giảm dần (`ngayyc DESC`)
- Nếu view đã tồn tại, nó sẽ được thay thế bằng `CREATE OR REPLACE VIEW`
