<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// Get record ID from URL
$stt = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Capture filter params from URL to preserve them
$filterParams = [];
foreach (['search', 'madv', 'nhomsc', 'trangthai', 'page'] as $key) {
    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        $filterParams[$key] = $_GET[$key];
    }
}

// Load model first
require_once __DIR__ . '/../../models/HoSoSCBD.php';
$model = new HoSoSCBD();

// If no ID, redirect
if (!$stt) {
    header("Location: hososcbd.php");
    exit;
}

// Load the record
$item = $model->findById($stt);

if (!$item) {
    header("Location: hososcbd.php");
    exit;
}

// Handle form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'nhomsc' => trim($_POST['nhomsc'] ?? ''),
            'ngaybdtt' => !empty(trim($_POST['ngaybdtt'] ?? '')) ? trim($_POST['ngaybdtt']) : null,
            'ngayth' => !empty(trim($_POST['ngayth'] ?? '')) ? trim($_POST['ngayth']) : null,
            'ngaykt' => !empty(trim($_POST['ngaykt'] ?? '')) ? trim($_POST['ngaykt']) : null,
            'solg' => (int)($_POST['solg'] ?? 0),
            'ttktbefore' => trim($_POST['ttktbefore'] ?? ''),
            'honghoc' => trim($_POST['honghoc'] ?? ''),
            'khacphuc' => trim($_POST['khacphuc'] ?? ''),
            'ttktafter' => trim($_POST['ttktafter'] ?? ''),
            'noidung' => trim($_POST['noidung'] ?? ''),
            'ketluan' => trim($_POST['ketluan'] ?? ''),
            'xemxetxuong' => trim($_POST['xemxetxuong'] ?? ''),
            'tbdosc' => trim($_POST['tbdosc'] ?? ''),
            'serialtbdosc' => trim($_POST['serialtbdosc'] ?? ''),
            'tbdosc1' => trim($_POST['tbdosc1'] ?? ''),
            'serialtbdosc1' => trim($_POST['serialtbdosc1'] ?? ''),
            'tbdosc2' => trim($_POST['tbdosc2'] ?? ''),
            'serialtbdosc2' => trim($_POST['serialtbdosc2'] ?? ''),
            'tbdosc3' => trim($_POST['tbdosc3'] ?? ''),
            'serialtbdosc3' => trim($_POST['serialtbdosc3'] ?? ''),
            'tbdosc4' => trim($_POST['tbdosc4'] ?? ''),
            'serialtbdosc4' => trim($_POST['serialtbdosc4'] ?? '')
        ];
        
        $success = $model->update($stt, $data);
        if ($success !== false) {
            // Build redirect URL with preserved filters
            $redirectUrl = '/iso2/hososcbd.php';
            // Get filter params from POST (hidden inputs) or from initial GET
            $postFilters = [];
            foreach (['search', 'madv', 'nhomsc', 'trangthai', 'page'] as $key) {
                if (isset($_POST['filter_' . $key]) && $_POST['filter_' . $key] !== '') {
                    $postFilters[$key] = $_POST['filter_' . $key];
                }
            }
            $params = !empty($postFilters) ? $postFilters : $filterParams;
            if (!empty($params)) {
                $redirectUrl .= '?' . http_build_query($params);
            }
            header("Location: $redirectUrl");
            exit;
        } else {
            $errorMessage = 'Có lỗi xảy ra khi cập nhật';
        }
    } catch (Exception $e) {
        error_log("Error updating repair details: " . $e->getMessage());
        $errorMessage = 'Lỗi: ' . $e->getMessage();
    }
}

// Now include header after all logic
$title = 'Thông tin sửa chữa & Thiết bị đo';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-6xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl md:text-2xl font-bold flex items-center">
            <i class="fas fa-wrench mr-2 text-orange-600"></i> Thông tin sửa chữa & Thiết bị đo
        </h1>
        <?php
        // Build back URL with filter params
        $backUrl = 'hososcbd.php';
        if (!empty($filterParams)) {
            $backUrl .= '?' . http_build_query($filterParams);
        }
        ?>
        <a href="<?php echo htmlspecialchars($backUrl); ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
    </div>
    
    <!-- Record Info - Sticky Header -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 sticky top-0 z-10 shadow-md">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-base md:text-lg">
            <div class="bg-indigo-100 p-3 rounded-lg border-l-4 border-indigo-500">
                <span class="font-semibold text-indigo-700">Số phiếu:</span>
                <span class="ml-2 font-bold text-indigo-900"><?php echo htmlspecialchars($item['phieu']); ?></span>
            </div>
            <div class="bg-green-100 p-3 rounded-lg border-l-4 border-green-500">
                <span class="font-semibold text-green-700">Thiết bị:</span>
                <span class="ml-2 font-bold text-green-900"><?php echo htmlspecialchars($item['mavt'] . ' - ' . $item['somay']); ?></span>
            </div>
        </div>
    </div>

    <?php if (isset($errorMessage)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-times-circle mr-2"></i><?php echo $errorMessage; ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <!-- Hidden inputs to preserve filters -->
        <?php foreach ($filterParams as $key => $value): ?>
            <input type="hidden" name="filter_<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
        <?php endforeach; ?>
        
        <!-- Thông tin sửa chữa -->
        <div class="border-l-4 border-orange-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-orange-700">
                <i class="fas fa-wrench mr-2"></i>Thông tin sửa chữa
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Nhóm SC <span class="text-red-500">*</span></label>
                    <input type="text" name="nhomsc" required value="<?php echo htmlspecialchars($item['nhomsc']); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ngày bắt đầu TT</label>
                    <input type="date" name="ngaybdtt" value="<?php echo $item['ngaybdtt']; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ngày thực hiện</label>
                    <input type="date" name="ngayth" value="<?php echo $item['ngayth']; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ngày kết thúc</label>
                    <input type="date" name="ngaykt" value="<?php echo $item['ngaykt']; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Số lượng</label>
                    <input type="number" name="solg" min="0" value="<?php echo $item['solg']; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Tình trạng kỹ thuật trước khi SC/BĐ</label>
                    <textarea name="ttktbefore" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo displayText($item['ttktbefore']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Hỏng hóc</label>
                    <textarea name="honghoc" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo displayText($item['honghoc']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Khắc phục</label>
                    <textarea name="khacphuc" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo displayText($item['khacphuc']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Tình trạng kỹ thuật sau khi SC/BĐ</label>
                    <select name="ttktafter" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                        <option value="">-- Chọn trạng thái --</option>
                        <option value="Đạt" <?php echo empty($item['ttktafter']) || ($item['ttktafter'] ?? '') === 'Đạt' ? 'selected' : ''; ?>>Đạt</option>
                        <option value="Hỏng" <?php echo ($item['ttktafter'] ?? '') === 'Hỏng' ? 'selected' : ''; ?>>Hỏng (Không khắc phục được)</option>
                        <option value="Chờ vật tư thay thế" <?php echo ($item['ttktafter'] ?? '') === 'Chờ vật tư thay thế' ? 'selected' : ''; ?>>Chờ vật tư thay thế</option>
                        <option value="Chưa kết luận" <?php echo ($item['ttktafter'] ?? '') === 'Chưa kết luận' ? 'selected' : ''; ?>>Chưa kết luận</option>
                        <option value="Đang sửa chữa" <?php echo ($item['ttktafter'] ?? '') === 'Đang sửa chữa' ? 'selected' : ''; ?>>Đang sửa chữa</option>
                        <option value="TTKTDB" <?php echo ($item['ttktafter'] ?? '') === 'TTKTDB' ? 'selected' : ''; ?>>TTKT Đặc biệt</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Nội dung sửa chữa</label>
                    <textarea name="noidung" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo displayText($item['noidung']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Kết luận</label>
                    <textarea name="ketluan" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo displayText($item['ketluan']); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Xem xét xưởng</label>
                    <textarea name="xemxetxuong" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo displayText($item['xemxetxuong']); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Thiết bị đo SC -->
        <div class="border-l-4 border-teal-500 pl-4">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-lg font-bold text-teal-700">
                    <i class="fas fa-tools mr-2"></i>Thiết bị hỗ trợ
                </h2>
                <button type="button" onclick="addDeviceRow()" class="bg-teal-600 hover:bg-teal-700 text-white px-3 py-1 rounded text-sm">
                    <i class="fas fa-plus mr-1"></i> Thêm thiết bị
                </button>
            </div>
            <div id="deviceList" class="space-y-2">
                <?php 
                // Collect existing devices
                $devices = [];
                for ($i = 0; $i <= 4; $i++) {
                    $tbField = $i == 0 ? 'tbdosc' : "tbdosc$i";
                    $serialField = $i == 0 ? 'serialtbdosc' : "serialtbdosc$i";
                    if (!empty($item[$tbField]) || !empty($item[$serialField])) {
                        $devices[] = [
                            'tb' => $item[$tbField],
                            'serial' => $item[$serialField],
                            'tbField' => $tbField,
                            'serialField' => $serialField
                        ];
                    }
                }
                // If no devices, show at least one empty row
                if (empty($devices)) {
                    $devices[] = ['tb' => '', 'serial' => '', 'tbField' => 'tbdosc', 'serialField' => 'serialtbdosc'];
                }
                
                foreach ($devices as $idx => $device): 
                ?>
                <div class="device-row flex gap-2 items-start bg-teal-50 p-2 rounded">
                    <div class="flex-1">
                        <input type="text" name="<?php echo $device['tbField']; ?>" placeholder="Tên thiết bị hỗ trợ" 
                               value="<?php echo htmlspecialchars($device['tb']); ?>"
                               class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-teal-500">
                    </div>
                    <div class="flex-1">
                        <input type="text" name="<?php echo $device['serialField']; ?>" placeholder="Serial/Mã số" 
                               value="<?php echo htmlspecialchars($device['serial']); ?>"
                               class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-teal-500">
                    </div>
                    <button type="button" onclick="removeDeviceRow(this)" class="text-red-600 hover:text-red-800 px-2 py-1" title="Xóa">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
        let deviceIndex = <?php echo count($devices); ?>;
        
        function addDeviceRow() {
            const container = document.getElementById('deviceList');
            const fieldName = deviceIndex === 0 ? 'tbdosc' : `tbdosc${deviceIndex}`;
            const serialName = deviceIndex === 0 ? 'serialtbdosc' : `serialtbdosc${deviceIndex}`;
            
            const row = document.createElement('div');
            row.className = 'device-row flex gap-2 items-start bg-teal-50 p-2 rounded';
            row.innerHTML = `
                <div class="flex-1">
                    <input type="text" name="${fieldName}" placeholder="Tên thiết bị hỗ trợ" 
                           class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-teal-500">
                </div>
                <div class="flex-1">
                    <input type="text" name="${serialName}" placeholder="Serial/Mã số" 
                           class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-teal-500">
                </div>
                <button type="button" onclick="removeDeviceRow(this)" class="text-red-600 hover:text-red-800 px-2 py-1" title="Xóa">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(row);
            deviceIndex++;
        }
        
        function removeDeviceRow(button) {
            const row = button.closest('.device-row');
            if (document.querySelectorAll('.device-row').length > 1) {
                row.remove();
            } else {
                alert('Phải có ít nhất 1 dòng thiết bị');
            }
        }
        </script>

        <!-- Buttons -->
        <div class="flex flex-col md:flex-row gap-2 pt-4 border-t">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded text-base font-semibold w-full md:w-auto">
                <i class="fas fa-save mr-2"></i> Lưu thông tin
            </button>
            <a href="<?php echo htmlspecialchars($backUrl); ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded text-base font-semibold text-center w-full md:w-auto">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
