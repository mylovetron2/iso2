# Tính Năng Tìm Kiếm - Bảng Cảnh Báo HC/KĐ

## Mô Tả
Đã thêm tính năng tìm kiếm đầy đủ cho bảng cảnh báo hiệu chuẩn/kiểm định.

## Các Loại Tìm Kiếm

### 1. Tất cả (all)
Tìm kiếm trong tất cả các trường:
- Tên thiết bị
- Tên viết tắt
- Số máy
- Mã vật tư
- Chủ sở hữu

### 2. Tên thiết bị (device)
Tìm kiếm theo:
- Tên thiết bị đầy đủ
- Tên viết tắt

### 3. Số máy/Mã vật tư (code)
Tìm kiếm theo:
- Số máy
- Mã vật tư

### 4. Chủ sở hữu (owner)
Tìm kiếm theo tên chủ sở hữu thiết bị

## Cách Sử Dụng

1. **Chọn loại tìm kiếm** từ dropdown "Loại Tìm Kiếm"
2. **Nhập từ khóa** vào ô "Từ Khóa"
3. **Nhấn nút "Tìm Kiếm"** (màu tím)
4. **Xóa bộ lọc** bằng nút X (màu xám)

## Tính Năng

### Form Tìm Kiếm
- Dropdown chọn loại tìm kiếm (4 loại)
- Input nhập từ khóa
- Nút tìm kiếm với icon search
- Nút xóa bộ lọc để reset về view ban đầu

### Thông Tin Tìm Kiếm
- Hiển thị từ khóa đang tìm
- Hiển thị loại tìm kiếm đang áp dụng
- Hiển thị số kết quả tìm được

### Pagination
- Giữ nguyên tham số tìm kiếm khi chuyển trang
- Hiển thị đúng kết quả trên mỗi trang

## Files Đã Chỉnh Sửa

### 1. controllers/BangCanhBaoController.php
```php
// Thêm xử lý tham số search
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchType = isset($_GET['search_type']) ? $_GET['search_type'] : 'all';

// Truyền vào model
$data = $this->keHoachModel->getWithHCStatus($month, $year, $limit, $offset, $searchTerm, $searchType);
$total = $this->keHoachModel->countByMonthYear($month, $year, $searchTerm, $searchType);
```

### 2. models/KeHoachISO.php
```php
// Cập nhật method getWithHCStatus() với tham số search
public function getWithHCStatus(int $month, int $year, int $limit = 10, int $offset = 0, string $searchTerm = '', string $searchType = 'all'): array

// Cập nhật method countByMonthYear() với tham số search
public function countByMonthYear(int $month, int $year, string $searchTerm = '', string $searchType = 'all'): int

// Thêm điều kiện WHERE động dựa trên searchType
switch ($searchType) {
    case 'device': // Tìm theo tên thiết bị
    case 'code':   // Tìm theo số máy/mã vật tư
    case 'owner':  // Tìm theo chủ sở hữu
    default:       // Tìm tất cả
}
```

### 3. views/bangcanhbao/index.php
```html
<!-- Form tìm kiếm mới -->
<form method="get" class="mb-6">
    <input type="hidden" name="month" value="...">
    <input type="hidden" name="year" value="...">
    
    <select name="search_type">...</select>
    <input type="text" name="search" placeholder="Nhập từ khóa...">
    <button type="submit">Tìm Kiếm</button>
    <a href="...">Xóa bộ lọc</a>
</form>

<!-- Hiển thị thông tin tìm kiếm -->
<?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
    Đang tìm kiếm: <strong>...</strong> (... kết quả)
<?php endif; ?>

<!-- Pagination với tham số search -->
<?php 
$paginationParams = http_build_query([
    'month' => $month,
    'year' => $year,
    'search' => $_GET['search'] ?? '',
    'search_type' => $_GET['search_type'] ?? 'all'
]);
?>
```

## SQL Query

### Điều kiện tìm kiếm
```sql
-- Tất cả
AND (k.tenthietbi LIKE :search OR t.tenviettat LIKE :search 
     OR k.somay LIKE :search OR t.mavattu LIKE :search 
     OR t.chusohuu LIKE :search)

-- Tên thiết bị
AND (k.tenthietbi LIKE :search OR t.tenviettat LIKE :search)

-- Số máy/Mã vật tư
AND (k.somay LIKE :search OR t.mavattu LIKE :search)

-- Chủ sở hữu
AND t.chusohuu LIKE :search
```

## Testing

### Test Cases
1. ✅ Tìm kiếm tất cả với từ khóa "CNC"
2. ✅ Tìm kiếm theo tên thiết bị "Máy khoan"
3. ✅ Tìm kiếm theo số máy "123"
4. ✅ Tìm kiếm theo chủ sở hữu "Phòng A"
5. ✅ Pagination giữ nguyên tham số search
6. ✅ Xóa bộ lọc reset về trang chủ
7. ✅ Không có lỗi syntax PHP

## URL Examples

```
# Xem tất cả tháng 12/2025
bangcanhbao.php?month=12&year=2025

# Tìm kiếm tất cả với "CNC"
bangcanhbao.php?month=12&year=2025&search=CNC&search_type=all

# Tìm kiếm theo tên thiết bị
bangcanhbao.php?month=12&year=2025&search=Máy%20khoan&search_type=device

# Tìm kiếm theo số máy
bangcanhbao.php?month=12&year=2025&search=123&search_type=code

# Tìm kiếm theo chủ sở hữu
bangcanhbao.php?month=12&year=2025&search=Phòng%20A&search_type=owner

# Trang 2 với tìm kiếm
bangcanhbao.php?month=12&year=2025&search=CNC&search_type=all&page=2
```

## Ghi Chú

- Tìm kiếm sử dụng LIKE với % ở 2 đầu (tìm kiếm một phần)
- Không phân biệt hoa thường (MySQL default)
- Kết quả được phân trang (10 items/trang)
- Tham số search được giữ nguyên khi chuyển trang
- Form tìm kiếm độc lập với form filter tháng/năm

## Ngày Tạo
18/12/2025
