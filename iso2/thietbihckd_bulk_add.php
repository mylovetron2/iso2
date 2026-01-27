<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/ThietBiHCKD.php';

requireAuth();

// Check permissions
if (!hasPermission('thietbi.create')) {
    header('Location: /iso2/thietbihckd.php?error=no_permission');
    exit;
}

$model = new ThietBiHCKD();
$errors = [];
$success = false;
$insertedCount = 0;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $devices = [];
    
    // Get common data (mavattu and tenviettat will be set from somay)
    $commonData = [
        'tenthietbi' => trim($_POST['tenthietbi'] ?? ''),
        'hangsx' => trim($_POST['hangsx'] ?? ''),
        'bophansh' => trim($_POST['bophansh'] ?? 'KTKT'),
        'chusohuu' => trim($_POST['chusohuu'] ?? 'KTKT'),
        'thoihankd' => trim($_POST['thoihankd'] ?? '12'),
        'ngayktnghiemthu' => trim($_POST['ngayktnghiemthu'] ?? '1970-01-01 00:00:00'),
        'loaitb' => trim($_POST['loaitb'] ?? '1'),
        'tlkt' => trim($_POST['tlkt'] ?? ''),
        'danchuan' => isset($_POST['danchuan']) ? 1 : 0
    ];
    
    // Get số máy pattern
    $somayPrefix = trim($_POST['somay_prefix'] ?? '');
    $somayStartNum = trim($_POST['somay_start_num'] ?? '1');
    $somayDigits = (int)($_POST['somay_digits'] ?? 2);
    $deviceCount = (int)($_POST['device_count'] ?? 1);
    
    // Validate common fields
    if (empty($commonData['tenthietbi'])) {
        $errors[] = 'Tên thiết bị không được để trống';
    }
    if (empty($somayPrefix) && $deviceCount > 1) {
        $errors[] = 'Prefix số máy không được để trống khi tạo nhiều thiết bị';
    }
    if ($deviceCount < 1 || $deviceCount > 50) {
        $errors[] = 'Số lượng thiết bị phải từ 1 đến 50';
    }
    
    // Format date for MySQL
    if (!empty($commonData['ngayktnghiemthu']) && $commonData['ngayktnghiemthu'] !== '1970-01-01 00:00:00') {
        try {
            $date = new DateTime($commonData['ngayktnghiemthu']);
            $commonData['ngayktnghiemthu'] = $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $errors[] = 'Ngày KT nghiệm thu không đúng định dạng';
        }
    }
    
    // Generate devices with auto-incremented số máy
    if (empty($errors)) {
        $startNum = (int)$somayStartNum;
        for ($i = 0; $i < $deviceCount; $i++) {
            $device = $commonData;
            
            // Generate số máy
            if ($deviceCount == 1 && !empty($_POST['somay_single'])) {
                // Single device, use custom số máy
                $device['somay'] = trim($_POST['somay_single']);
            } else {
                // Multiple devices, use pattern
                $currentNum = $startNum + $i;
                $device['somay'] = $somayPrefix . str_pad((string)$currentNum, $somayDigits, '0', STR_PAD_LEFT);
            }
            
            // Auto-populate mavattu and tenviettat with somay value
            $device['mavattu'] = $device['somay'];
            $device['tenviettat'] = $device['somay'];
            
            $devices[] = $device;
        }
    }
    
    // Insert devices if no errors
    if (empty($errors) && !empty($devices)) {
        try {
            foreach ($devices as $device) {
                $id = $model->create($device);
                if ($id) {
                    $insertedCount++;
                }
            }
            
            if ($insertedCount > 0) {
                $success = true;
                $_SESSION['success_message'] = "Đã thêm thành công $insertedCount thiết bị";
                header('Location: /iso2/thietbihckd.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Lỗi khi thêm thiết bị: ' . $e->getMessage();
        }
    }
}

// Get lists for dropdowns
$boPhanList = $model->getAllBoPhanSH();
$loaiTBList = $model->getAllLoaiTB();

$title = 'Thêm Nhiều Thiết Bị HC/KĐ';
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-plus-circle mr-2"></i> Thêm Nhiều Thiết Bị HC/KĐ
    </h1>
    
    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded">
        <p class="text-sm text-blue-800">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Hướng dẫn:</strong> Nhập thông tin chung cho tất cả thiết bị. Số máy sẽ được tự động tạo theo pattern. Mã vật tư và Tên viết tắt sẽ tự động = Số máy.
        </p>
        <p class="text-sm text-blue-800 mt-2">
            <strong>Ví dụ:</strong> Tên thiết bị = "Container", Prefix số máy = "CH-CON-", Số bắt đầu = 1, Số lượng = 10
            → Tạo: CH-CON-01, CH-CON-02, ..., CH-CON-10 (Mã vật tư và Tên viết tắt cũng = CH-CON-01, CH-CON-02, ...)
        </p>
        <p class="text-sm text-blue-800 mt-2">
            <strong>Giá trị mặc định:</strong> Bộ phận SH = KTKT, Chủ sở hữu = KTKT, Thời hạn KD = 12 tháng, Ngày KT nghiệm thu = 01/01/1970, Loại TB = 1, Dẫn chuẩn = 0
        </p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul class="list-disc list-inside">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <!-- Thông tin chung -->
        <div class="border-2 border-green-500 rounded-lg p-4">
            <h2 class="text-lg font-semibold mb-3 text-green-700">
                <i class="fas fa-info-circle mr-2"></i>Thông tin chung cho tất cả thiết bị
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Row 1 -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Tên thiết bị <span class="text-red-500">*</span></label>
                    <input type="text" name="tenthietbi" 
                           class="w-full border rounded px-3 py-2" 
                           value="<?php echo htmlspecialchars($_POST['tenthietbi'] ?? ''); ?>"
                           placeholder="Ví dụ: Container 20 feet">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Hãng sản xuất</label>
                    <input type="text" name="hangsx" 
                           class="w-full border rounded px-3 py-2" 
                           value="<?php echo htmlspecialchars($_POST['hangsx'] ?? ''); ?>">
                </div>
                
                <!-- Row 2 -->
                <div>
                    <label class="block text-sm font-medium mb-1">Bộ phận SH</label>
                    <input type="text" name="bophansh" 
                           class="w-full border rounded px-3 py-2" 
                           value="<?php echo htmlspecialchars($_POST['bophansh'] ?? 'KTKT'); ?>"
                           placeholder="KTKT">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Chủ sở hữu</label>
                    <input type="text" name="chusohuu" 
                           class="w-full border rounded px-3 py-2" 
                           value="<?php echo htmlspecialchars($_POST['chusohuu'] ?? 'KTKT'); ?>"
                           placeholder="KTKT">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Thời hạn KD (tháng)</label>
                    <input type="text" name="thoihankd" 
                           class="w-full border rounded px-3 py-2" 
                           value="<?php echo htmlspecialchars($_POST['thoihankd'] ?? '12'); ?>"
                           placeholder="12">
                </div>
                
                <!-- Row 4 -->
                <div>
                    <label class="block text-sm font-medium mb-1">Ngày KT nghiệm thu</label>
                    <input type="datetime-local" name="ngayktnghiemthu" 
                           class="w-full border rounded px-3 py-2" 
                           value="<?php echo htmlspecialchars($_POST['ngayktnghiemthu'] ?? '1970-01-01T00:00'); ?>"
                           placeholder="1970-01-01">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Loại TB</label>
                    <select name="loaitb" class="w-full border rounded px-3 py-2">
                        <option value="1" <?php echo (!isset($_POST['loaitb']) || $_POST['loaitb'] == '1') ? 'selected' : ''; ?>>1 - Thiết bị theo dõi và đo lường</option>
                        <?php foreach ($loaiTBList as $loai): ?>
                            <?php if ($loai != '1'): ?>
                            <option value="<?php echo htmlspecialchars($loai); ?>" 
                                    <?php echo (isset($_POST['loaitb']) && $_POST['loaitb'] == $loai) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loai); ?>
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">TLKT</label>
                    <input type="text" name="tlkt" 
                           class="w-full border rounded px-3 py-2" 
                           value="<?php echo htmlspecialchars($_POST['tlkt'] ?? ''); ?>">
                </div>
                
                <!-- Row 5 -->
                <div class="flex items-center pt-6">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="danchuan" class="mr-2" 
                               <?php echo (isset($_POST['danchuan'])) ? 'checked' : ''; ?>>
                        <span class="text-sm font-medium">Dẫn chuẩn</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Pattern số máy -->
        <div class="border-2 border-blue-500 rounded-lg p-4">
            <h2 class="text-lg font-semibold mb-3 text-blue-700">
                <i class="fas fa-list-ol mr-2"></i>Cấu hình số máy tự động
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Số lượng thiết bị <span class="text-red-500">*</span></label>
                    <input type="number" name="device_count" id="device_count"
                           class="w-full border rounded px-3 py-2" 
                           value="<?php echo htmlspecialchars($_POST['device_count'] ?? '1'); ?>"
                           min="1" max="50"
                           onchange="toggleSomayFields()"
                           placeholder="1-50">
                    <p class="text-xs text-gray-500 mt-1">Tối đa 50 thiết bị</p>
                </div>
                
                <div id="somay_single_div">
                    <label class="block text-sm font-medium mb-1">Số máy</label>
                    <input type="text" name="somay_single" id="somay_single"
                           class="w-full border rounded px-3 py-2" 
                           value="<?php echo htmlspecialchars($_POST['somay_single'] ?? ''); ?>"
                           placeholder="Số máy cho 1 thiết bị">
                </div>
                
                <div id="somay_pattern_div" style="display: none;" class="md:col-span-3">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Prefix số máy <span class="text-red-500">*</span></label>
                            <input type="text" name="somay_prefix" id="somay_prefix"
                                   class="w-full border rounded px-3 py-2" 
                                   value="<?php echo htmlspecialchars($_POST['somay_prefix'] ?? ''); ?>"
                                   placeholder="CH-CON-">
                            <p class="text-xs text-gray-500 mt-1">Ví dụ: CH-CON-</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-1">Số bắt đầu</label>
                            <input type="number" name="somay_start_num" 
                                   class="w-full border rounded px-3 py-2" 
                                   value="<?php echo htmlspecialchars($_POST['somay_start_num'] ?? '1'); ?>"
                                   min="0"
                                   placeholder="1">
                            <p class="text-xs text-gray-500 mt-1">Mặc định: 1</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-1">Số chữ số</label>
                            <select name="somay_digits" class="w-full border rounded px-3 py-2">
                                <option value="2" <?php echo (!isset($_POST['somay_digits']) || $_POST['somay_digits'] == '2') ? 'selected' : ''; ?>>2 (01, 02, ...)</option>
                                <option value="3" <?php echo (isset($_POST['somay_digits']) && $_POST['somay_digits'] == '3') ? 'selected' : ''; ?>>3 (001, 002, ...)</option>
                                <option value="4" <?php echo (isset($_POST['somay_digits']) && $_POST['somay_digits'] == '4') ? 'selected' : ''; ?>>4 (0001, 0002, ...)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="preview" class="mt-4 p-3 bg-gray-50 border rounded" style="display: none;">
                <p class="text-sm font-medium mb-2">Preview số máy sẽ tạo:</p>
                <div id="preview_content" class="text-sm text-gray-700"></div>
            </div>
        </div>
        
        <div class="flex gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                <i class="fas fa-save mr-2"></i> Lưu tất cả
            </button>
            <a href="thietbihckd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded inline-block">
                <i class="fas fa-times mr-2"></i> Hủy
            </a>
        </div>
    </form>
</div>

<script>
function toggleSomayFields() {
    const count = parseInt(document.getElementById('device_count').value) || 1;
    const singleDiv = document.getElementById('somay_single_div');
    const patternDiv = document.getElementById('somay_pattern_div');
    
    if (count === 1) {
        singleDiv.style.display = 'block';
        patternDiv.style.display = 'none';
    } else {
        singleDiv.style.display = 'none';
        patternDiv.style.display = 'block';
    }
    
    updatePreview();
}

function updatePreview() {
    const count = parseInt(document.getElementById('device_count').value) || 1;
    const prefix = document.querySelector('input[name="somay_prefix"]').value;
    const startNum = parseInt(document.querySelector('input[name="somay_start_num"]').value) || 1;
    const digits = parseInt(document.querySelector('select[name="somay_digits"]').value) || 2;
    const previewDiv = document.getElementById('preview');
    const previewContent = document.getElementById('preview_content');
    
    if (count > 1 && prefix) {
        const samples = [];
        const maxShow = Math.min(count, 10);
        
        for (let i = 0; i < maxShow; i++) {
            const num = startNum + i;
            const somay = prefix + String(num).padStart(digits, '0');
            samples.push(somay);
        }
        
        let html = samples.join(', ');
        if (count > 10) {
            html += `, ... (tổng ${count} thiết bị)`;
        }
        
        previewContent.innerHTML = html;
        previewDiv.style.display = 'block';
    } else {
        previewDiv.style.display = 'none';
    }
}

// Event listeners
document.getElementById('device_count').addEventListener('input', toggleSomayFields);
document.querySelector('input[name="somay_prefix"]').addEventListener('input', updatePreview);
document.querySelector('input[name="somay_start_num"]').addEventListener('input', updatePreview);
document.querySelector('select[name="somay_digits"]').addEventListener('change', updatePreview);

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleSomayFields();
});
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
