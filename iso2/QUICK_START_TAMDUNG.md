# Quick Start: Tạm dừng/Tiếp tục

> **Hướng dẫn nhanh 5 phút cho dự án khác**

## 🚀 Setup nhanh

### 1. Chạy migration (1 phút)
```bash
# Option A: Tự động
php run_migration_tamdung.php

# Option B: Thủ công
mysql -u username -p database_name < migrations/create_hososcbd_tamdung_table.sql
```

### 2. Copy files (2 phút)
```
api/hososcbd_tamdung.php               → API endpoints
models/HoSoScBdTamDung.php             → Data layer
views/hososcbd/partials/tamdung_modals.php → UI Modal
baocao_hososcbd_tamdung.php            → Report page
```

### 3. Customize (2 phút)

**Tìm & thay đổi:**
```
hososcbd_iso     → your_table
hoso             → your_id_field
mavt/somay       → your_identifiers (hoặc bỏ)
```

**Update permission check:**
```php
// Trong api/hososcbd_tamdung.php
if (!hasPermission('hososcbd.edit')) {
    // Thay bằng permission của bạn
    if (!hasPermission('your_module.edit')) {
```

---

## 📊 Database Schema

```sql
-- Bảng lịch sử (mới)
hososcbd_tamdung (
    id, hoso, mavt, somay,
    trangthai ENUM('dang_tam_dung', 'da_tiep_tuc'),
    lydo, nguoitao, ngaytao
)

-- Bảng chính (thêm 1 cột)
ALTER TABLE your_table 
ADD COLUMN is_tamdung TINYINT(1) DEFAULT 0;
```

---

## 🎨 UI Integration

### Nút Tạm dừng/Tiếp tục
```php
<?php if ($item['is_tamdung']): ?>
    <button onclick="openQuanLyTamDungModal('<?= $item['hoso'] ?>', '<?= $item['mavt'] ?>', '<?= $item['somay'] ?>', true)"
            class="bg-green-600 text-white px-3 py-1 rounded">
        <i class="fas fa-play-circle"></i> Tiếp tục
    </button>
<?php else: ?>
    <button onclick="openQuanLyTamDungModal('<?= $item['hoso'] ?>', '<?= $item['mavt'] ?>', '<?= $item['somay'] ?>', false)"
            class="bg-orange-600 text-white px-3 py-1 rounded">
        <i class="fas fa-pause-circle"></i> Tạm dừng
    </button>
<?php endif; ?>
```

### Badge cảnh báo
```php
<?php if ($item['is_tamdung']): ?>
    <span class="bg-orange-500 text-white text-xs px-2 py-0.5 rounded">
        <i class="fas fa-pause-circle"></i> TẠM DỪNG
    </span>
<?php endif; ?>
```

### Filter dropdown
```html
<select name="trangthai">
    <option value="">Tất cả</option>
    <option value="tamdung">Tạm dừng</option>
    <!-- ... other options -->
</select>
```

---

## 🔌 API Usage

### JavaScript (AJAX)
```javascript
// Tạm dừng
fetch('api/hososcbd_tamdung.php?action=tam_dung', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        hoso: '1997-1',
        mavt: 'MV001',
        somay: 'SM001',
        lydo: 'Chờ vật tư'
    })
});

// Tiếp tục
fetch('api/hososcbd_tamdung.php?action=tiep_tuc', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        hoso: '1997-1',
        lydo: 'Đã có vật tư'
    })
});

// Lịch sử
fetch('api/hososcbd_tamdung.php?action=lich_su&hoso=1997-1')
    .then(r => r.json())
    .then(data => console.log(data.items));
```

---

## 🗂️ Model Methods

```php
$tamDungModel = new HoSoScBdTamDung();

// Tạm dừng
$tamDungModel->tamDung($hoso, $mavt, $somay, $lydo, $nguoitao);

// Tiếp tục
$tamDungModel->tiepTuc($hoso, $lydo, $nguoitao);

// Lấy trạng thái
$status = $tamDungModel->getLatestStatus($hoso);

// Lịch sử
$history = $tamDungModel->getLichSu($hoso);

// Báo cáo
$items = $tamDungModel->getBaoCaoLichSu($trangthai, $madv, $from, $to, $offset, $limit);
$total = $tamDungModel->countBaoCaoLichSu($trangthai, $madv, $from, $to);
```

---

## ⚠️ Common Issues

### ❌ HTTP 500 Error
```
Lỗi: Method signature mismatch
Fix: Đảm bảo pass đủ 6 params
getBaoCaoLichSu($trangthai, $madv, $from, $to, $offset, $limit)
                                   ↑↑↑↑↑  Đừng quên $madv = ''
```

### ❌ Badge không hiển thị
```
Lỗi: Thiếu JOIN với hososcbd_tamdung
Fix: Thêm LEFT JOIN trong getList()
LEFT JOIN (SELECT hoso, trangthai FROM hososcbd_tamdung ...) td_latest
```

### ❌ Filter "Tạm dừng" không hoạt động
```
Lỗi: WHERE clause sai
Fix: Thêm điều kiện
if ($trangthai === 'tamdung') {
    $where[] = "td_latest.trangthai = 'dang_tam_dung'";
}
```

---

## 📈 Performance Tips

✅ **Tốt:** Chỉ JOIN khi filter tạm dừng
```php
if ($trangthai === 'tamdung') {
    // LEFT JOIN hososcbd_tamdung
} else {
    // Simple query without JOIN
}
```

✅ **Tốt:** Dùng subquery thay vì multiple LEFT JOIN
```sql
LEFT JOIN (
    SELECT hoso, trangthai 
    FROM hososcbd_tamdung 
    WHERE id = (SELECT MAX(id) ...)
) td_latest
```

✅ **Tốt:** Index đúng columns
```sql
INDEX idx_hoso (hoso)
INDEX idx_trangthai (trangthai)
INDEX idx_ngaytao (ngaytao)
```

---

## 📚 Full Documentation

Chi tiết đầy đủ: [TAMDUNG_TIEPTUC_DOCUMENTATION.md](TAMDUNG_TIEPTUC_DOCUMENTATION.md)

---

**Ready to go!** 🎉  
Total setup time: ~5 minutes
