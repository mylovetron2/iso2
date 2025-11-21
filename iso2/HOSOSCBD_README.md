# Hệ Thống Quản Lý Sửa Chữa/Bảo Dưỡng Thiết Bị - ISO 2.0

## 📋 Tổng Quan 5 Quy Trình Nghiệp Vụ Đã Được Thêm Vào

Dựa trên file mô tả `hososcbd_iso_description.html` và code legacy `formsc.php`, hệ thống đã được nâng cấp với 5 quy trình nghiệp vụ hoàn chỉnh:

### 1. 📝 Quản Lý Phiếu Yêu Cầu Dịch Vụ (HoSoSCBD)
**Bảng:** `hososcbd_iso` (44 cột)

**Chức năng:**
- Tiếp nhận yêu cầu sửa chữa/bảo dưỡng từ khách hàng
- Theo dõi toàn bộ quy trình từ yêu cầu → chuẩn đoán → thực hiện → hoàn thành → bàn giao
- Quản lý thiết bị hỗ trợ sử dụng (tối đa 5 thiết bị)
- Lưu trữ lịch sử sửa chữa, bảo dưỡng định kỳ

**Trạng thái:**
- Chưa thực hiện
- Đang thực hiện
- Hoàn thành
- Chờ bàn giao
- Đã bàn giao

**Model:** `models/HoSoSCBD.php`
**Routes:** `/hososcbd.php`

---

### 2. 🏢 Quản Lý Đơn Vị Khách Hàng (DonVi)
**Bảng:** `donvi_iso`

**Chức năng:**
- Quản lý thông tin đơn vị/khách hàng
- Liên kết với phiếu yêu cầu và thiết bị
- Thống kê số lượng phiếu theo đơn vị

**Model:** `models/DonVi.php`
**Routes:** `/donvi.php`

---

### 3. 🔧 Quản Lý Thiết Bị (ThietBi)
**Bảng:** `thietbi_iso`

**Chức năng:**
- Danh mục thiết bị theo đơn vị
- Quản lý thông tin: Mã thiết bị, Serial, Model, Hãng SX
- Theo dõi tình trạng thiết bị
- Lịch sử sửa chữa/bảo dưỡng

**Model:** `models/ThietBi.php`
**Routes:** `/thietbi.php`

---

### 4. 🛠️ Quản Lý Thiết Bị Hỗ Trợ (ThietBiHoTro)
**Bảng:** `thietbihotro_iso` (ĐÃ CÓ SẴN)

**Chức năng:**
- Quản lý thiết bị đo, kiểm tra, sửa chữa
- Theo dõi lịch kiểm định
- Quản lý tài liệu kỹ thuật, hồ sơ máy

**Model:** `models/ThietBiHoTro.php` ✅ (Đã có)
**Routes:** `/thietbihotro.php` ✅ (Đã có)

---

### 5. ✅ Bàn Giao Thiết Bị (BanGiao)
**Sử dụng bảng:** `hososcbd_iso` (trường `bg`, `slbg`)

**Chức năng:**
- Lập biên bản bàn giao thiết bị đã sửa chữa
- Xác nhận tình trạng kỹ thuật sau SC/BD
- Ký nhận giữa xưởng và khách hàng
- Tự động cập nhật trạng thái bàn giao

---

## 🗄️ Cấu Trúc Database

### Bảng Chính

```sql
hososcbd_iso     - Hồ sơ SC/BD (44 cột)
donvi_iso        - Đơn vị khách hàng
thietbi_iso      - Danh mục thiết bị
thietbihotro_iso - Thiết bị đo/SC (đã có)
vitri_iso        - Vị trí lắp đặt
lo_iso           - Lô khai thác (dầu khí)
mo_iso           - Mỏ dầu khí
```

### Quan Hệ

```
donvi_iso (1) ----< (n) hososcbd_iso
                         |
                         |--< (n) thietbi_iso
                         |
                         |--< (n) thietbihotro_iso (qua tbdosc*)
```

---

## 📂 Cấu Trúc Files Đã Tạo

```
iso2/
├── migrations/
│   ├── 20251121_create_hososcbd_tables.sql  ✅ MỚI
│   └── 20251120_create_activity_logs.sql     (Đã có)
│
├── models/
│   ├── HoSoSCBD.php                          ✅ MỚI
│   ├── DonVi.php                             ✅ MỚI
│   ├── ThietBi.php                           ✅ MỚI
│   ├── ThietBiHoTro.php                      (Đã có)
│   └── BaseModel.php                         (Đã có)
│
├── controllers/
│   ├── HoSoSCBDController.php                📌 CẦN TẠO
│   ├── DonViController.php                   📌 CẦN TẠO
│   └── ThietBiController.php                 📌 CẦN TẠO
│
└── views/
    ├── hososcbd/
    │   ├── index.php                         📌 CẦN TẠO
    │   ├── create.php                        📌 CẦN TẠO
    │   ├── edit.php                          📌 CẦN TẠO
    │   └── view.php                          📌 CẦN TẠO
    │
    ├── donvi/
    │   └── index.php                         📌 CẦN TẠO
    │
    └── thietbi/
        └── index.php                         📌 CẦN TẠO
```

---

## 🚀 Hướng Dẫn Cài Đặt

### Bước 1: Chạy Migration

```bash
# Đăng nhập MySQL
mysql -u root -p diavatly_db

# Chạy migration
source D:/projectISO2/iso2/migrations/20251121_create_hososcbd_tables.sql;
```

### Bước 2: Kiểm Tra Models

Models đã được tạo với các phương thức:

**HoSoSCBD.php:**
- `getList()` - Lấy danh sách có filter
- `getNextPhieuNumber()` - Tạo số phiếu tự động
- `getStats()` - Thống kê theo trạng thái
- `findByMaQL()` - Tìm theo mã quản lý
- `updateBanGiao()` - Cập nhật bàn giao

**DonVi.php:**
- `getAll()` - Lấy tất cả đơn vị
- `findByMaDV()` - Tìm theo mã
- `existsMaDV()` - Kiểm tra tồn tại

**ThietBi.php:**
- `getByDonVi()` - Lấy TB theo đơn vị
- `findByMaVtAndSoMay()` - Tìm chính xác
- `getSoMayByMaVt()` - Lấy danh sách số máy

### Bước 3: Tạo Controllers (Mẫu)

**controllers/HoSoSCBDController.php:**
```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/HoSoSCBD.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';

class HoSoSCBDController
{
    private HoSoSCBD $model;
    
    public function __construct()
    {
        $this->model = new HoSoSCBD();
    }
    
    public function index(): void
    {
        requireAuth();
        
        $search = $_GET['search'] ?? '';
        $trangthai = $_GET['trangthai'] ?? '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 15;
        $offset = ($page - 1) * $limit;
        
        $hosoList = $this->model->getList($search, '', $trangthai, '', $offset, $limit);
        $totalRecords = $this->model->countList($search, '', $trangthai);
        $totalPages = max(1, (int)ceil($totalRecords / $limit));
        $stats = $this->model->getStats();
        
        require_once __DIR__ . '/../views/hososcbd/index.php';
    }
    
    public function create(): void
    {
        requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Xử lý tạo phiếu mới
            $phieu = $this->model->getNextPhieuNumber();
            
            $data = [
                'phieu' => $phieu,
                'maql' => $_POST['maql'] ?? '',
                'hoso' => $_POST['hoso'] ?? '',
                'ngayyc' => $_POST['ngayyc'] ?? date('Y-m-d'),
                'madv' => $_POST['madv'] ?? '',
                'ngyeucau' => $_POST['ngyeucau'] ?? '',
                'mavt' => $_POST['mavt'] ?? '',
                'somay' => $_POST['somay'] ?? '',
                // ... các trường khác
            ];
            
            $this->model->create($data);
            header('Location: /iso2/hososcbd.php');
            exit;
        }
        
        require_once __DIR__ . '/../views/hososcbd/create.php';
    }
}
```

### Bước 4: Tạo Views (Mẫu)

**views/hososcbd/index.php:**
```php
<?php
$pageTitle = 'Hồ Sơ Sửa Chữa/Bảo Dưỡng';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">
        <i class="fas fa-tools mr-2"></i>Hồ Sơ SC/BD
    </h1>
    
    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-blue-100 p-4 rounded-lg">
            <div class="text-2xl font-bold"><?= $stats['total'] ?></div>
            <div class="text-sm">Tổng số</div>
        </div>
        <div class="bg-yellow-100 p-4 rounded-lg">
            <div class="text-2xl font-bold"><?= $stats['chuath'] ?></div>
            <div class="text-sm">Chưa thực hiện</div>
        </div>
        <div class="bg-orange-100 p-4 rounded-lg">
            <div class="text-2xl font-bold"><?= $stats['danglam'] ?></div>
            <div class="text-sm">Đang làm</div>
        </div>
        <div class="bg-purple-100 p-4 rounded-lg">
            <div class="text-2xl font-bold"><?= $stats['chuabg'] ?></div>
            <div class="text-sm">Chờ bàn giao</div>
        </div>
        <div class="bg-green-100 p-4 rounded-lg">
            <div class="text-2xl font-bold"><?= $stats['dabg'] ?></div>
            <div class="text-sm">Đã bàn giao</div>
        </div>
    </div>
    
    <!-- Filter & Search -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" class="flex gap-4">
            <input type="text" name="search" placeholder="Tìm kiếm..." 
                   value="<?= htmlspecialchars($search) ?>"
                   class="flex-1 px-4 py-2 border rounded">
            
            <select name="trangthai" class="px-4 py-2 border rounded">
                <option value="">Tất cả trạng thái</option>
                <option value="chuath" <?= $trangthai === 'chuath' ? 'selected' : '' ?>>Chưa thực hiện</option>
                <option value="danglam" <?= $trangthai === 'danglam' ? 'selected' : '' ?>>Đang làm</option>
                <option value="chuabg" <?= $trangthai === 'chuabg' ? 'selected' : '' ?>>Chờ bàn giao</option>
                <option value="dabg" <?= $trangthai === 'dabg' ? 'selected' : '' ?>>Đã bàn giao</option>
            </select>
            
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>Tìm kiếm
            </button>
            
            <a href="/iso2/hososcbd.php?action=create" 
               class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i>Tạo mới
            </a>
        </form>
    </div>
    
    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">Số Phiếu</th>
                    <th class="px-4 py-3 text-left">Mã QL</th>
                    <th class="px-4 py-3 text-left">Ngày YC</th>
                    <th class="px-4 py-3 text-left">Đơn Vị</th>
                    <th class="px-4 py-3 text-left">Thiết Bị</th>
                    <th class="px-4 py-3 text-left">Trạng Thái</th>
                    <th class="px-4 py-3 text-left">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hosoList as $hoso): ?>
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-4 py-3"><?= htmlspecialchars($hoso['phieu']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($hoso['maql']) ?></td>
                    <td class="px-4 py-3"><?= date('d/m/Y', strtotime($hoso['ngayyc'])) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($hoso['tendv'] ?? '') ?></td>
                    <td class="px-4 py-3">
                        <?= htmlspecialchars($hoso['mavt']) ?>
                        <?php if ($hoso['somay']): ?>
                            - <?= htmlspecialchars($hoso['somay']) ?>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <?php if ($hoso['bg'] == 1): ?>
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded">Đã bàn giao</span>
                        <?php elseif ($hoso['ngaykt'] && $hoso['ngaykt'] != '0000-00-00'): ?>
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded">Chờ bàn giao</span>
                        <?php elseif ($hoso['ngayth'] && $hoso['ngayth'] != '0000-00-00'): ?>
                            <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded">Đang làm</span>
                        <?php else: ?>
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded">Chưa thực hiện</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <a href="/iso2/hososcbd.php?action=view&stt=<?= $hoso['stt'] ?>" 
                           class="text-blue-600 hover:text-blue-800 mr-2">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="/iso2/hososcbd.php?action=edit&stt=<?= $hoso['stt'] ?>" 
                           class="text-green-600 hover:text-green-800">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mt-6 flex justify-center gap-2">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&trangthai=<?= urlencode($trangthai) ?>"
               class="px-4 py-2 <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white' ?> rounded border">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

### Bước 5: Thêm Menu

Cập nhật `views/layouts/header.php`:

```php
<li>
    <a href="/iso2/hososcbd.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
        <i class="fas fa-tools mr-2"></i> Hồ Sơ SC/BD
    </a>
</li>
<li>
    <a href="/iso2/donvi.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
        <i class="fas fa-building mr-2"></i> Đơn Vị KH
    </a>
</li>
<li>
    <a href="/iso2/thietbi.php" class="flex items-center px-3 py-2 rounded hover:bg-blue-600">
        <i class="fas fa-cogs mr-2"></i> Thiết Bị
    </a>
</li>
```

---

## 📊 Quy Trình Nghiệp Vụ Chi Tiết

### Quy Trình 1: Tiếp Nhận Yêu Cầu

1. Khách hàng gọi điện hoặc gửi yêu cầu
2. Nhân viên xưởng tạo phiếu yêu cầu (số phiếu tự động)
3. Nhập thông tin: Đơn vị, thiết bị, tình trạng, yêu cầu
4. Xem xét của xưởng → Phân công nhóm SC

### Quy Trình 2: Chuẩn Đoán & Thực Hiện

1. Nhập ngày thực hiện
2. Ghi nhận tình trạng kỹ thuật trước SC/BD
3. Mô tả hỏng hóc chi tiết
4. Chọn thiết bị hỗ trợ sử dụng (max 5)
5. Thực hiện sửa chữa/bảo dưỡng
6. Ghi cách khắc phục

### Quy Trình 3: Hoàn Thành

1. Nhập ngày kết thúc
2. Kiểm tra tình trạng kỹ thuật sau SC/BD
3. Kết luận: Đạt / Hỏng / Chờ vật tư
4. Ghi chú final

### Quy Trình 4: Bàn Giao

1. Xem danh sách hồ sơ hoàn thành
2. Chọn hồ sơ cần bàn giao
3. In biên bản bàn giao
4. Khách hàng ký nhận
5. Cập nhật trạng thái `bg = 1`

### Quy Trình 5: Thống Kê & Báo Cáo

1. Dashboard hiển thị số liệu theo trạng thái
2. Lọc theo nhóm SC, đơn vị, ngày tháng
3. Xuất báo cáo Excel/PDF
4. Lịch sử sửa chữa theo thiết bị

---

## 🔐 Phân Quyền

Thêm vào `views/admin/permissions_manager.php`:

```php
'hososcbd.view' => 'Xem hồ sơ SC/BD',
'hososcbd.create' => 'Tạo hồ sơ SC/BD',
'hososcbd.edit' => 'Sửa hồ sơ SC/BD',
'hososcbd.delete' => 'Xóa hồ sơ SC/BD',
'hososcbd.bangiao' => 'Bàn giao thiết bị',

'donvi.view' => 'Xem đơn vị',
'donvi.create' => 'Tạo đơn vị',
'donvi.edit' => 'Sửa đơn vị',

'thietbi.view' => 'Xem thiết bị',
'thietbi.create' => 'Tạo thiết bị',
'thietbi.edit' => 'Sửa thiết bị',
```

---

## 📝 Notes

- File migration đã tạo: `migrations/20251121_create_hososcbd_tables.sql`
- 3 Models đã tạo: `HoSoSCBD.php`, `DonVi.php`, `ThietBi.php`
- Controllers và Views cần tạo thêm dựa trên mẫu trên
- ActivityLogger sẽ tự động log tất cả thao tác
- Tham khảo file `formsc.php` cũ để hiểu logic chi tiết

---

## 🚦 Roadmap Tiếp Theo

1. ✅ Migration tables
2. ✅ Models cơ bản
3. 📌 Controllers cho 3 modules
4. 📌 Views đầy đủ (CRUD)
5. 📌 In phiếu/biên bản (PDF)
6. 📌 Xuất báo cáo Excel
7. 📌 API endpoints (nếu cần)

---

**Author:** GitHub Copilot  
**Date:** November 21, 2025  
**Version:** 1.0
