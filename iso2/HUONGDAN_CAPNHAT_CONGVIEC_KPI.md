# Tài liệu: Cập nhật Công việc KPI - Dựa trên Hồ sơ SCBD

> **Ngày cập nhật**: 24/02/2026  
> **Mục đích**: Thay đổi logic từ nhập mavt/somay sang chọn hồ sơ SCBD

---

## 📋 **Tổng quan Thay đổi**

### **Trước đây (Old Logic)**
```
Người dùng → Nhập mavt, somay thủ công
           → Nhập cấp độ, số giờ
           → Hệ thống lưu công việc
```

**Nhược điểm**:
- ❌ Nhập sai mavt/somay
- ❌ Trùng lặp dữ liệu (mavt, somay lưu ở nhiều nơi)
- ❌ Không liên kết với hồ sơ chính thức
- ❌ Khó truy vết công việc thuộc hồ sơ nào

### **Bây giờ (New Logic)** ✅
```
Người dùng → Chọn Hồ sơ SCBD từ dropdown
           → Thông tin thiết bị tự động lấy từ hồ sơ
           → Chọn cấp độ, nhập số giờ
           → Hệ thống lưu với hososcbd_stt
```

**Ưu điểm**:
- ✅ Chính xác: Chọn từ danh sách có sẵn
- ✅ Chuẩn hóa: Dữ liệu thiết bị lấy từ 1 nguồn duy nhất
- ✅ Liên kết: Mỗi công việc luôn thuộc 1 hồ sơ
- ✅ Truy vết: Dễ dàng xem lịch sử theo hồ sơ

---

## 🗄️ **Thay đổi Cấu trúc Database**

### **Bảng `congviec_suachua_iso` - CŨ**
```sql
CREATE TABLE congviec_suachua_iso (
    stt INT PRIMARY KEY,
    nhanvien_stt INT NOT NULL,
    ngay_lam DATE NOT NULL,
    
    -- ❌ Thông tin thiết bị nhập trực tiếp (DI SÃILICATES)
    mavt VARCHAR(80) NOT NULL,
    somay VARCHAR(80) NOT NULL,
    ten_thietbi VARCHAR(255),
    
    capdo_stt INT NOT NULL,
    so_gio_lam DECIMAL(5,2) NOT NULL,
    
    -- Optional link
    hososcbd_stt INT DEFAULT NULL
);
```

### **Bảng `congviec_suachua_iso` - MỚI** ✅
```sql
CREATE TABLE congviec_suachua_iso (
    stt INT PRIMARY KEY,
    nhanvien_stt INT NOT NULL,
    ngay_lam_viec DATE NOT NULL,
    
    -- ✅ BẮT BUỘC link đến hồ sơ
    hososcbd_stt INT NOT NULL,
    
    -- ✅ Optional cache (tăng tốc query)
    thietbi_stt INT DEFAULT NULL,
    
    capdo_stt INT NOT NULL,
    so_gio_lam DECIMAL(5,2) NOT NULL,
    kpi_gio_chuan DECIMAL(5,2), -- Snapshot KPI tại thời điểm làm
    
    noi_dung_congviec TEXT,
    ghi_chu TEXT,
    
    FOREIGN KEY (hososcbd_stt) REFERENCES hososcbd_iso(stt),
    FOREIGN KEY (thietbi_stt) REFERENCES thietbi_iso(stt)
);
```

### **Thay đổi chi tiết**

| Trường | Trước | Sau | Lý do |
|--------|-------|-----|-------|
| `mavt` | VARCHAR(80) NOT NULL | ❌ Xóa | Lấy từ hososcbd_iso |
| `somay` | VARCHAR(80) NOT NULL | ❌ Xóa | Lấy từ hososcbd_iso |
| `ten_thietbi` | VARCHAR(255) | ❌ Xóa | Lấy từ thietbi_iso qua JOIN |
| `ngay_lam` | DATE | → `ngay_lam_viec` | Đổi tên rõ nghĩa hơn |
| `hososcbd_stt` | NULL (optional) | NOT NULL (bắt buộc) | Luôn phải có hồ sơ |
| `thietbi_stt` | ❌ Không có | INT NULL | Cache để tăng tốc query |
| `noi_dung` | TEXT | → `noi_dung_congviec` | Rõ nghĩa hơn |

---

## 🔧 **Thay đổi Code**

### **1. Model - CongViecSuaChua.php**

#### **Cũ:**
```php
public function createWithValidation(array $data): array
{
    $requiredFields = [
        'nhanvien_stt', 
        'ngay_lam', 
        'mavt',        // ❌ Nhập thủ công
        'somay',       // ❌ Nhập thủ công
        'capdo_stt', 
        'so_gio_lam'
    ];
    // ...
}
```

#### **Mới:** ✅
```php
public function createWithValidation(array $data): array
{
    $requiredFields = [
        'nhanvien_stt', 
        'ngay_lam_viec',
        'hososcbd_stt',  // ✅ Chọn từ danh sách
        'capdo_stt', 
        'so_gio_lam'
    ];
    // Thông tin thiết bị tự động lấy từ hososcbd_iso
}

// ✅ Method mới
public function getByHoSoScBd(int $hososcbdStt): array
{
    // Lấy tất cả công việc của 1 hồ sơ
}
```

### **2. View - Form nhập công việc**

#### **Cũ:**
```html
<form>
    <select name="nhanvien_stt">...</select>
    <input type="date" name="ngay_lam">
    
    <!-- ❌ Nhập thủ công -->
    <input type="text" name="mavt" placeholder="Mã vật tư">
    <input type="text" name="somay" placeholder="Số máy">
    
    <select name="capdo_stt">...</select>
    <input type="number" name="so_gio_lam">
</form>
```

#### **Mới:** ✅
```html
<form>
    <select name="nhanvien_stt">...</select>
    <input type="date" name="ngay_lam_viec">
    
    <!-- ✅ Chọn từ danh sách hồ sơ -->
    <select name="hososcbd_stt" id="hososcbd_select">
        <option value="">-- Chọn hồ sơ SCBD --</option>
        <?php foreach ($hosoList as $hs): ?>
        <option value="<?= $hs['stt'] ?>" 
                data-mavt="<?= $hs['mavt'] ?>"
                data-somay="<?= $hs['somay'] ?>"
                data-ten="<?= $hs['ten_thietbi'] ?>">
            <?= $hs['mavt'] ?> - <?= $hs['somay'] ?> | 
            <?= $hs['ten_thietbi'] ?> | 
            HS: <?= $hs['ma_hoso'] ?>
        </option>
        <?php endforeach; ?>
    </select>
    
    <!-- Hiển thị thông tin thiết bị (readonly) -->
    <div id="thietbi-info" class="hidden">
        <p><strong>Thiết bị:</strong> <span id="ten-tb"></span></p>
        <p><strong>Mã VT - Số máy:</strong> <span id="mavt-somay"></span></p>
    </div>
    
    <select name="capdo_stt">...</select>
    <input type="number" name="so_gio_lam">
</form>

<script>
// Khi chọn hồ sơ, hiển thị thông tin thiết bị
document.getElementById('hososcbd_select').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    if (option.value) {
        document.getElementById('ten-tb').textContent = option.dataset.ten || 'N/A';
        document.getElementById('mavt-somay').textContent = 
            option.dataset.mavt + ' - ' + option.dataset.somay;
        document.getElementById('thietbi-info').classList.remove('hidden');
    } else {
        document.getElementById('thietbi-info').classList.add('hidden');
    }
});
</script>
```

### **3. Controller - Lấy danh sách hồ sơ**

```php
// File: controllers/CongViecSuaChuaController.php

class CongViecSuaChuaController
{
    private HoSoSCBD $hosoModel;
    
    public function __construct($db)
    {
        // ...
        $this->hosoModel = new HoSoSCBD($db);
    }
    
    public function index()
    {
        // Lấy danh sách hồ sơ 6 tháng gần đây
        $hosoList = $this->hosoModel->getActiveHoSo(100);
        
        // Lấy danh sách nhân viên
        $nhanvienList = $this->resumeModel->getActiveEmployees();
        
        // Lấy cấp độ
        $capdoList = $this->capdoModel->getActiveLevels();
        
        // Render view
        include 'views/congviec/index.php';
    }
    
    public function create()
    {
        $data = [
            'nhanvien_stt' => $_POST['nhanvien_stt'],
            'ngay_lam_viec' => $_POST['ngay_lam_viec'],
            'hososcbd_stt' => $_POST['hososcbd_stt'],  // ✅ Chỉ cần này
            'capdo_stt' => $_POST['capdo_stt'],
            'so_gio_lam' => (float)$_POST['so_gio_lam'],
            'noi_dung_congviec' => $_POST['noi_dung'] ?? '',
            'ghi_chu' => $_POST['ghi_chu'] ?? ''
        ];
        
        // Lấy KPI chuẩn
        $kpiChuan = $this->capdoModel->getKPIChuan($data['capdo_stt']);
        $data['kpi_gio_chuan'] = $kpiChuan;
        
        // ✅ Optional: Lấy thietbi_stt để cache
        $hoso = $this->hosoModel->getHoSoWithThietBi($data['hososcbd_stt']);
        if ($hoso) {
            $thietbi = $this->thietbiModel->findByMaVtAndSoMay(
                $hoso['mavt'], 
                $hoso['somay']
            );
            if ($thietbi) {
                $data['thietbi_stt'] = $thietbi['stt'];
            }
        }
        
        // Lưu
        $result = $this->congviecModel->createWithValidation($data);
        
        echo json_encode($result);
    }
}
```

---

## 📊 **Query Dữ liệu**

### **Lấy thông tin đầy đủ công việc**

```sql
-- VIEW hiển thị đầy đủ thông tin
SELECT 
    cv.stt,
    cv.ngay_lam_viec,
    cv.so_gio_lam,
    
    -- Nhân viên
    nv.HOTEN as ten_nhanvien,
    
    -- Hồ sơ
    hs.hoso as ma_hoso,
    hs.phieu as so_phieu,
    
    -- Thiết bị (từ hososcbd_iso)
    hs.mavt,
    hs.somay,
    tb.TENVT as ten_thietbi,
    tb.MODEL as model,
    
    -- Cấp độ
    cd.ten_capdo,
    cd.kpi_gio_chuan,
    
    -- Hiệu suất
    ROUND((cv.kpi_gio_chuan / cv.so_gio_lam) * 100, 2) as hieu_suat
    
FROM congviec_suachua_iso cv
INNER JOIN hososcbd_iso hs ON cv.hososcbd_stt = hs.stt
INNER JOIN resume nv ON cv.nhanvien_stt = nv.stt
LEFT JOIN thietbi_iso tb ON hs.mavt = tb.MAVT AND hs.somay = tb.SOMAY
LEFT JOIN capdo_baocuong_iso cd ON cv.capdo_stt = cd.stt
ORDER BY cv.ngay_lam_viec DESC;
```

### **Lấy lịch sử công việc của thiết bị**

```sql
-- Lấy qua hososcbd_iso
SELECT cv.*, hs.hoso, hs.phieu
FROM congviec_suachua_iso cv
INNER JOIN hososcbd_iso hs ON cv.hososcbd_stt = hs.stt
WHERE hs.mavt = 'TB001' AND hs.somay = 'M001'
ORDER BY cv.ngay_lam_viec DESC;
```

### **Lấy công việc của 1 hồ sơ**

```sql
SELECT cv.*, nv.HOTEN, cd.ten_capdo
FROM congviec_suachua_iso cv
LEFT JOIN resume nv ON cv.nhanvien_stt = nv.stt
LEFT JOIN capdo_baocuong_iso cd ON cv.capdo_stt = cd.stt
WHERE cv.hososcbd_stt = 123
ORDER BY cv.ngay_lam_viec;
```

---

## 🎯 **Hướng dẫn Migration**

### **Bước 1: Backup dữ liệu**
```bash
mysqldump -u root -p diavatly_db congviec_suachua_iso > backup_congviec_20260224.sql
```

### **Bước 2: Chạy migration**
```bash
mysql -u root -p diavatly_db < migrations/20260224_ALTER_congviec_based_on_hososcbd.sql
```

### **Bước 3: Kiểm tra**
```sql
-- Xem cấu trúc bảng mới
DESCRIBE congviec_suachua_iso_new;

-- Kiểm tra số lượng records
SELECT COUNT(*) FROM congviec_suachua_iso; -- Cũ
SELECT COUNT(*) FROM congviec_suachua_iso_new; -- Mới

-- Xem VIEW
SELECT * FROM view_congviec_full LIMIT 10;
```

### **Bước 4: Replace bảng (SAU KHI KIỂM TRA KỸ!)**
```sql
-- Uncomment trong file migration:
RENAME TABLE congviec_suachua_iso TO congviec_suachua_iso_backup_20260224;
RENAME TABLE congviec_suachua_iso_new TO congviec_suachua_iso;
```

### **Bước 5: Test hệ thống**
1. Truy cập trang nhập công việc
2. Chọn nhân viên và ngày
3. Chọn hồ sơ SCBD từ dropdown
4. Kiểm tra thông tin thiết bị hiển thị đúng
5. Nhập cấp độ và số giờ
6. Submit và kiểm tra dữ liệu lưu đúng

---

## 🔄 **Rollback (Nếu cần)**

```sql
-- Khôi phục bảng cũ
DROP TABLE IF EXISTS congviec_suachua_iso;
RENAME TABLE congviec_suachua_iso_backup_20260224 TO congviec_suachua_iso;

-- Xóa VIEW mới
DROP VIEW IF EXISTS view_congviec_full;
DROP VIEW IF EXISTS view_kpi_thietbi_thongke;

-- Khôi phục TRIGGERs cũ (nếu cần)
```

---

## ✅ **Checklist**

- [ ] Backup database
- [ ] Chạy migration tạo bảng mới
- [ ] Kiểm tra dữ liệu migrate
- [ ] Cập nhật Model PHP
- [ ] Cập nhật Controller
- [ ] Cập nhật View (form + display)
- [ ] Test chức năng nhập công việc
- [ ] Test báo cáo KPI
- [ ] Test lấy lịch sử thiết bị
- [ ] Replace bảng cũ
- [ ] Deploy lên production
- [ ] Monitoring lỗi 24h đầu

---

## 📞 **Hỗ trợ**

Nếu gặp vấn đề trong quá trình migration:
1. Kiểm tra error log: `tail -f /var/log/mysql/error.log`
2. Kiểm tra PHP error: `debug_congviec.php`
3. Rollback về bảng cũ nếu cần
4. Liên hệ admin hệ thống

---

**Cập nhật: 24/02/2026**
