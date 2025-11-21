# KẾ HOẠCH BỔ SUNG CHỨC NĂNG HOSOSCBD
## Theo mô tả file nhap_phieu_yeu_cau_description.html

---

## 🎯 MỤC TIÊU
Nâng cấp module hososcbd từ **70% → 100%** so với yêu cầu nghiệp vụ

---

## 📋 DANH SÁCH CÔNG VIỆC

### ✅ PHASE 1: BỔ SUNG AUTO-GENERATE (1-2 giờ)
**Priority: HIGH - Cần làm ngay**

#### Task 1.1: Auto-generate `maql` 
**File:** `controllers/HoSoScBdController.php`

```php
private function generateMaQL(string $madv, string $phieu, int $index = 1): string
{
    $date = date('Ymd'); // 20251121
    return "{$date}-{$madv}-{$phieu}-N{$index}";
    // Output: 20251121-XDT-0126-N1
}
```

**Thay đổi:**
- Remove field `maql` từ form create/edit
- Auto-generate trong `create()` và `edit()` methods
- Update validation: bỏ check required cho maql

#### Task 1.2: Auto-generate `hoso`
```php
private function generateHoSo(string $madv, string $phieu, string $mavt, string $somay): string
{
    return "{$madv}-{$phieu}-{$mavt}-{$somay}";
    // Output: XDT-0126-PM001-SN12345
}
```

**Thay đổi:**
- Remove field `hoso` từ form
- Auto-generate trong controller
- Update validation

#### Task 1.3: Cập nhật View & Controller
- `views/hososcbd/create.php`: Xóa 2 input maql, hoso
- `views/hososcbd/edit.php`: Hiển thị readonly (không cho sửa)
- `getPostData()`: Remove maql, hoso
- `validate()`: Remove validation cho 2 field này

**Thời gian ước tính:** 1 giờ

---

### ✅ PHASE 2: VALIDATION BG=0 (30 phút)
**Priority: HIGH - Logic nghiệp vụ quan trọng**

#### Task 2.1: Thêm method check trong Model
**File:** `models/HoSoSCBD.php`

```php
/**
 * Kiểm tra thiết bị có đơn hàng chưa hoàn thành không
 * @return bool true = available (bg=1 hoặc không tồn tại), false = busy (bg=0)
 */
public function isDeviceAvailable(string $mavt, string $somay, ?int $excludeStt = null): bool
{
    $sql = "SELECT bg FROM {$this->table} 
            WHERE mavt = ? AND somay = ?";
    
    if ($excludeStt) {
        $sql .= " AND stt != ?";
        $stmt = $this->query($sql, [$mavt, $somay, $excludeStt]);
    } else {
        $stmt = $this->query($sql, [$mavt, $somay]);
    }
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Không tồn tại hoặc đã bàn giao (bg=1) → OK
    return !$result || $result['bg'] == 1;
}
```

#### Task 2.2: Thêm validation vào Controller
```php
private function validate(array $data, ?int $currentStt = null): array
{
    $errors = [];
    
    // ... existing validations ...
    
    // NEW: Check device availability
    if (!empty($data['mavt']) && !empty($data['somay'])) {
        if (!$this->model->isDeviceAvailable($data['mavt'], $data['somay'], $currentStt)) {
            $errors[] = "Thiết bị {$data['mavt']}-{$data['somay']} đang có đơn hàng chưa hoàn thành (chưa bàn giao)";
        }
    }
    
    return $errors;
}
```

**Thời gian ước tính:** 30 phút

---

### ✅ PHASE 3: BATCH INSERT 5 THIẾT BỊ (3-4 giờ)
**Priority: HIGH - Tính năng nghiệp vụ cốt lõi**

#### Task 3.1: Cập nhật View - Form có 5 slots
**File:** `views/hososcbd/create.php`

Thay đổi cấu trúc:

```php
<!-- Thông tin phiếu chung (1 lần) -->
<div class="border-l-4 border-blue-500 pl-4">
    <h2>Thông tin phiếu</h2>
    <!-- phieu (auto), ngayyc, madv, ngyeucau, ngnhyeucau, dienthoai -->
</div>

<!-- Thông tin chung toàn phiếu -->
<div class="border-l-4 border-purple-500 pl-4">
    <h2>Thông tin chung</h2>
    <!-- ycthemkh, xemxetxuong, lo, mo, gieng -->
</div>

<!-- LOOP 5 THIẾT BỊ -->
<?php for ($i = 1; $i <= 5; $i++): ?>
<div class="border-l-4 border-green-500 pl-4 mb-6">
    <h2 class="flex items-center justify-between">
        <span>Thiết bị <?php echo $i; ?></span>
        <button type="button" class="text-sm text-red-600" onclick="clearDevice(<?php echo $i; ?>)">
            <i class="fas fa-times"></i> Xóa
        </button>
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- mavt[i], somay[i], model[i] -->
        <!-- vitrimaybd[i] -->
        <!-- ttktbefore[i] (Tình trạng) -->
        <!-- cv[i] (Yêu cầu) -->
    </div>
</div>
<?php endfor; ?>
```

**Các trường lặp 5 lần:**
- `mavt[1-5]`, `somay[1-5]`, `model[1-5]`
- `vitrimaybd[1-5]`
- `ttktbefore[1-5]` (Tình trạng)
- `cv[1-5]` (Yêu cầu)

**Các trường chung (không lặp):**
- Thông tin phiếu: `phieu`, `ngayyc`, `madv`, `ngyeucau`, `ngnhyeucau`, `dienthoai`
- Thông tin chung: `ycthemkh`, `xemxetxuong`, `lo`, `mo`, `gieng`, `nhomsc`
- Thông tin SC (điền sau): `ngaybdtt`, `ngayth`, `ngaykt`, `honghoc`, `khacphuc`, `ttktafter`, `noidung`, `ketluan`
- TB đo SC: `tbdosc[0-4]`, `serialtbdosc[0-4]`
- Bàn giao: `bg`, `slbg`, `dong`, `ghichu`, `ghichufinal`

#### Task 3.2: Cập nhật Controller - Batch Insert Logic
**File:** `controllers/HoSoScBdController.php`

```php
public function create(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $commonData = $this->getCommonPostData();
        $devicesData = $this->getDevicesPostData(); // Array of 5 devices
        
        $errors = $this->validateCommon($commonData);
        
        // Validate each device
        $validDevices = [];
        foreach ($devicesData as $index => $device) {
            if ($this->isDeviceEmpty($device)) {
                continue; // Skip empty slots
            }
            
            $deviceErrors = $this->validateDevice($device, $index + 1);
            if (!empty($deviceErrors)) {
                $errors = array_merge($errors, $deviceErrors);
            } else {
                $validDevices[] = $device;
            }
        }
        
        if (empty($validDevices)) {
            $errors[] = 'Phải nhập ít nhất 1 thiết bị';
        }
        
        if (empty($errors)) {
            // Generate phieu if empty
            if (empty($commonData['phieu'])) {
                $commonData['phieu'] = $this->model->getNextPhieuNumber();
            }
            
            // Insert each device as separate record
            $insertedCount = 0;
            foreach ($validDevices as $index => $device) {
                $fullData = array_merge($commonData, $device);
                
                // Auto-generate maql and hoso
                $fullData['maql'] = $this->generateMaQL(
                    $commonData['madv'], 
                    $commonData['phieu'], 
                    $index + 1
                );
                $fullData['hoso'] = $this->generateHoSo(
                    $commonData['madv'],
                    $commonData['phieu'],
                    $device['mavt'],
                    $device['somay']
                );
                
                $id = $this->model->create($fullData);
                if ($id) {
                    $insertedCount++;
                    // TODO: Log to lichsudn_iso
                }
            }
            
            if ($insertedCount > 0) {
                header("Location: /iso2/hososcbd.php?success=created&count=$insertedCount");
                exit;
            }
            $errors[] = 'Có lỗi xảy ra khi tạo hồ sơ';
        }
        
        $error = implode(', ', $errors);
    }
    
    // ... render form
}

private function getCommonPostData(): array
{
    return [
        'phieu' => trim($_POST['phieu'] ?? ''),
        'ngayyc' => trim($_POST['ngayyc'] ?? date('Y-m-d')),
        'madv' => trim($_POST['madv'] ?? ''),
        'ngyeucau' => trim($_POST['ngyeucau'] ?? ''),
        'ngnhyeucau' => trim($_POST['ngnhyeucau'] ?? ''),
        'dienthoai' => trim($_POST['dienthoai'] ?? ''),
        'ycthemkh' => trim($_POST['ycthemkh'] ?? ''),
        'xemxetxuong' => trim($_POST['xemxetxuong'] ?? ''),
        'lo' => trim($_POST['lo'] ?? ''),
        'mo' => trim($_POST['mo'] ?? ''),
        'gieng' => trim($_POST['gieng'] ?? ''),
        'nhomsc' => trim($_POST['nhomsc'] ?? ''),
        // ... các field chung khác
    ];
}

private function getDevicesPostData(): array
{
    $devices = [];
    for ($i = 1; $i <= 5; $i++) {
        $devices[] = [
            'mavt' => trim($_POST["mavt{$i}"] ?? ''),
            'somay' => trim($_POST["somay{$i}"] ?? ''),
            'model' => trim($_POST["model{$i}"] ?? ''),
            'vitrimaybd' => trim($_POST["vitrimaybd{$i}"] ?? ''),
            'ttktbefore' => trim($_POST["ttktbefore{$i}"] ?? ''),
            'cv' => trim($_POST["cv{$i}"] ?? ''),
        ];
    }
    return $devices;
}

private function isDeviceEmpty(array $device): bool
{
    return empty($device['mavt']) && empty($device['somay']);
}
```

**Thời gian ước tính:** 3-4 giờ

---

### ✅ PHASE 4: ENHANCED DROPDOWNS (2-3 giờ)
**Priority: MEDIUM - Cải thiện UX**

#### Task 4.1: AJAX Cascade Dropdown (Đơn vị → Thiết bị)
**File:** `views/hososcbd/create.php`

```javascript
<script>
document.getElementById('madv').addEventListener('change', function() {
    const madv = this.value;
    
    // Reload thiết bị dropdowns for all 5 slots
    for (let i = 1; i <= 5; i++) {
        fetch(`/iso2/api/thietbi.php?madv=${madv}`)
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById(`mavt${i}`);
                select.innerHTML = '<option value="">-- Chọn thiết bị --</option>';
                data.forEach(tb => {
                    select.innerHTML += `<option value="${tb.mavt}.${tb.model}">${tb.mavt} - ${tb.tenvt}</option>`;
                });
            });
    }
});
</script>
```

#### Task 4.2: Thiết bị → Serial Number Cascade
```javascript
document.querySelectorAll('[id^="mavt"]').forEach(select => {
    select.addEventListener('change', function() {
        const index = this.id.replace('mavt', '');
        const [mavt, model] = this.value.split('.');
        
        fetch(`/iso2/api/somay.php?mavt=${mavt}&model=${model}`)
            .then(res => res.json())
            .then(data => {
                const somaySelect = document.getElementById(`somay${index}`);
                somaySelect.innerHTML = '<option value="">-- Chọn số máy --</option>';
                data.forEach(item => {
                    somaySelect.innerHTML += `<option value="${item.somay}">${item.somay}</option>`;
                });
            });
    });
});
```

#### Task 4.3: Tạo API endpoints
**File:** `api/thietbi.php`
```php
<?php
require_once __DIR__ . '/../config/database.php';
$madv = $_GET['madv'] ?? '';
$db = getDBConnection();
$stmt = $db->prepare("SELECT mavt, model, tenvt FROM thietbi_iso WHERE madv = ?");
$stmt->execute([$madv]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
```

**File:** `api/somay.php`
```php
<?php
require_once __DIR__ . '/../config/database.php';
$mavt = $_GET['mavt'] ?? '';
$model = $_GET['model'] ?? '';
$db = getDBConnection();
$stmt = $db->prepare("SELECT DISTINCT somay FROM thietbi_iso WHERE mavt = ? AND model = ?");
$stmt->execute([$mavt, $model]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
```

#### Task 4.4: Dropdown cho Lo/Mo/Gieng/Vitri
Thay input text thành select dropdown từ các bảng:
- `lo_iso` → `lo` dropdown
- `mo_iso` → `mo` dropdown  
- `gieng_iso` → `gieng` dropdown
- `vitri_iso` → `vitrimaybd` dropdown

**Thời gian ước tính:** 2-3 giờ

---

### ✅ PHASE 5: LOGGING & AUDIT (1 giờ)
**Priority: LOW - Tính năng phụ**

#### Task 5.1: Tạo bảng lichsudn_iso (nếu chưa có)
```sql
CREATE TABLE IF NOT EXISTS lichsudn_iso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100),
    action VARCHAR(50),
    maql VARCHAR(100),
    phieu VARCHAR(10),
    ip_address VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

#### Task 5.2: Thêm logging vào Controller
```php
private function logHistory(string $action, string $maql, string $phieu): void
{
    $db = getDBConnection();
    $stmt = $db->prepare(
        "INSERT INTO lichsudn_iso (username, action, maql, phieu, ip_address) 
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $_SESSION['user_name'] ?? 'unknown',
        $action, // 'create', 'update', 'delete'
        $maql,
        $phieu,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
}
```

**Gọi sau mỗi create/update/delete**

**Thời gian ước tính:** 1 giờ

---

### ✅ PHASE 6: POPUP THÊM ĐƠN VỊ (1 giờ)
**Priority: LOW - Nice to have**

#### Task 6.1: Thêm button và modal
```html
<select name="madv" id="madv">
    <option value="">-- Chọn đơn vị --</option>
    <?php foreach ($donViList as $dv): ?>
        <option value="<?php echo $dv['madv']; ?>"><?php echo $dv['tendv']; ?></option>
    <?php endforeach; ?>
</select>
<button type="button" onclick="openAddDonViModal()" class="text-blue-600">
    <i class="fas fa-plus"></i> Thêm đơn vị mới
</button>

<!-- Modal -->
<div id="addDonViModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50">
    <div class="bg-white p-6 rounded-lg max-w-md mx-auto mt-20">
        <h3>Thêm đơn vị mới</h3>
        <input type="text" id="new_madv" placeholder="Mã đơn vị">
        <input type="text" id="new_tendv" placeholder="Tên đơn vị">
        <button onclick="submitAddDonVi()">Lưu</button>
        <button onclick="closeAddDonViModal()">Hủy</button>
    </div>
</div>
```

#### Task 6.2: AJAX submit
```javascript
function submitAddDonVi() {
    const madv = document.getElementById('new_madv').value;
    const tendv = document.getElementById('new_tendv').value;
    
    fetch('/iso2/api/donvi.php', {
        method: 'POST',
        body: JSON.stringify({madv, tendv})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Reload to show new option
        }
    });
}
```

**Thời gian ước tính:** 1 giờ

---

## 📅 TIMELINE

### Giai đoạn 1: Core Logic (Ưu tiên cao) - 5-6 giờ
- **Day 1 Morning:** Phase 1 - Auto-generate (1h)
- **Day 1 Morning:** Phase 2 - Validation bg=0 (30min)
- **Day 1 Afternoon:** Phase 3 - Batch insert 5 thiết bị (3-4h)

### Giai đoạn 2: Enhanced UX (Ưu tiên trung) - 2-3 giờ
- **Day 2 Morning:** Phase 4 - Cascade dropdowns (2-3h)

### Giai đoạn 3: Additional Features (Ưu tiên thấp) - 2 giờ
- **Day 2 Afternoon:** Phase 5 - Logging (1h)
- **Day 2 Afternoon:** Phase 6 - Popup thêm đơn vị (1h)

**TỔNG THỜI GIAN:** 9-11 giờ làm việc

---

## 🎯 MỨC ĐỘ ƯU TIÊN

### 🔴 MUST HAVE (Phase 1-3)
- Auto-generate maql, hoso
- Validation bg=0
- Batch insert 5 thiết bị

### 🟡 SHOULD HAVE (Phase 4)
- Cascade dropdowns

### 🟢 NICE TO HAVE (Phase 5-6)
- Logging
- Popup thêm đơn vị

---

## ✅ CHECKLIST HOÀN THÀNH

- [ ] Phase 1: Auto-generate maql, hoso
  - [ ] Remove input fields
  - [ ] Add generate methods
  - [ ] Update validation
  - [ ] Test với nhiều records

- [ ] Phase 2: Validation bg=0
  - [ ] Add isDeviceAvailable() method
  - [ ] Update validate() method
  - [ ] Test case: Tạo phiếu cho thiết bị đang busy
  - [ ] Test case: Sửa phiếu (exclude current)

- [ ] Phase 3: Batch insert 5 thiết bị
  - [ ] Update create.php form (5 slots)
  - [ ] Update getPostData() methods
  - [ ] Update controller create() logic
  - [ ] Test: 1 thiết bị
  - [ ] Test: 3 thiết bị
  - [ ] Test: 5 thiết bị đầy
  - [ ] Test: Validation từng thiết bị

- [ ] Phase 4: Cascade dropdowns
  - [ ] Create API endpoints
  - [ ] Add JavaScript handlers
  - [ ] Test on Chrome, Firefox

- [ ] Phase 5: Logging
  - [ ] Create/check table
  - [ ] Add log method
  - [ ] Test insert logs

- [ ] Phase 6: Popup thêm đơn vị
  - [ ] Create modal HTML
  - [ ] Add JavaScript
  - [ ] Create API endpoint
  - [ ] Test add new unit

---

## 🚀 BẮT ĐẦU THỰC HIỆN

Để bắt đầu, chạy lệnh:
1. Git checkout -b feature/hososcbd-batch-insert
2. Bắt đầu từ Phase 1 (quan trọng nhất)

---

*Tài liệu này được tạo tự động dựa trên phân tích file nhap_phieu_yeu_cau_description.html*
