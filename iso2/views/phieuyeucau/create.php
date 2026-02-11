<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Tạo Phiếu Yêu Cầu Mới';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-plus-circle mr-2"></i> Tạo Phiếu Yêu Cầu Mới
        </h1>
        <a href="phieuyeucau.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="space-y-6">
        <!-- Thông tin chung phiếu -->
        <div class="bg-gray-50 rounded-lg p-4">
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                <i class="fas fa-file-alt mr-2"></i> Thông tin phiếu
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold mb-1">
                        Số phiếu <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="phieu" value="<?php echo isset($_POST['phieu']) ? htmlspecialchars($_POST['phieu']) : $nextPhieu; ?>" 
                           class="w-full border rounded px-3 py-2" required>
                    <p class="text-xs text-gray-600 mt-1">Số phiếu tự động: <?php echo $nextPhieu; ?></p>
                </div>
                
                <div>
                    <label class="block font-semibold mb-1">
                        Ngày yêu cầu <span class="text-red-600">*</span>
                    </label>
                    <input type="date" name="ngayyc" value="<?php echo isset($_POST['ngayyc']) ? htmlspecialchars($_POST['ngayyc']) : date('Y-m-d'); ?>" 
                           class="w-full border rounded px-3 py-2" required>
                </div>
                
                <div>
                    <label class="block font-semibold mb-1">
                        Đơn vị <span class="text-red-600">*</span>
                    </label>
                    <select name="madv" class="w-full border rounded px-3 py-2" required>
                        <option value="">-- Chọn đơn vị --</option>
                        <?php foreach ($donViList as $dv): ?>
                            <option value="<?php echo htmlspecialchars($dv['madv']); ?>"
                                    <?php echo (isset($_POST['madv']) && $_POST['madv'] === $dv['madv']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dv['tendv']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block font-semibold mb-1">Nhóm sửa chữa</label>
                    <select name="nhomsc" class="w-full border rounded px-3 py-2">
                        <option value="">-- Chọn nhóm --</option>
                        <option value="CNC" <?php echo (isset($_POST['nhomsc']) && $_POST['nhomsc'] === 'CNC') ? 'selected' : ''; ?>>CNC</option>
                        <option value="RDNGA" <?php echo (isset($_POST['nhomsc']) && $_POST['nhomsc'] === 'RDNGA') ? 'selected' : ''; ?>>RDNGA</option>
                    </select>
                </div>
                
                <div>
                    <label class="block font-semibold mb-1">Người yêu cầu</label>
                    <input type="text" name="ngyeucau" value="<?php echo isset($_POST['ngyeucau']) ? htmlspecialchars($_POST['ngyeucau']) : ''; ?>" 
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block font-semibold mb-1">Người nhận yêu cầu</label>
                    <input type="text" name="ngnhyeucau" value="<?php echo isset($_POST['ngnhyeucau']) ? htmlspecialchars($_POST['ngnhyeucau']) : ''; ?>" 
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block font-semibold mb-1">Điện thoại</label>
                    <input type="text" name="dienthoai" value="<?php echo isset($_POST['dienthoai']) ? htmlspecialchars($_POST['dienthoai']) : ''; ?>" 
                           class="w-full border rounded px-3 py-2">
                </div>
            </div>
            
            <div class="mt-4">
                <label class="block font-semibold mb-1">Công việc yêu cầu</label>
                <textarea name="cv" rows="3" class="w-full border rounded px-3 py-2"><?php echo isset($_POST['cv']) ? htmlspecialchars($_POST['cv']) : ''; ?></textarea>
            </div>
            
            <div class="mt-4">
                <label class="block font-semibold mb-1">Yêu cầu thêm từ khách hàng</label>
                <textarea name="ycthemkh" rows="3" class="w-full border rounded px-3 py-2"><?php echo isset($_POST['ycthemkh']) ? htmlspecialchars($_POST['ycthemkh']) : ''; ?></textarea>
            </div>
        </div>

        <!-- Danh sách thiết bị -->
        <div class="bg-gray-50 rounded-lg p-4">
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                <i class="fas fa-cogs mr-2"></i> Danh sách thiết bị <span class="text-red-600">*</span>
            </h2>
            
            <div id="devices-container" class="space-y-3">
                <!-- Device row template sẽ được thêm bằng JS -->
            </div>
            
            <button type="button" onclick="addDeviceRow()" class="mt-3 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                <i class="fas fa-plus mr-1"></i> Thêm thiết bị
            </button>
        </div>

        <!-- Submit buttons -->
        <div class="flex gap-2">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                <i class="fas fa-save mr-1"></i> Tạo phiếu
            </button>
            <a href="phieuyeucau.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded text-center">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
        </div>
    </form>
</div>

<script>
let deviceCounter = 0;

function addDeviceRow() {
    deviceCounter++;
    const container = document.getElementById('devices-container');
    const row = document.createElement('div');
    row.className = 'device-row bg-white rounded p-3 border';
    row.id = 'device-' + deviceCounter;
    row.innerHTML = `
        <div class="flex justify-between items-center mb-2">
            <h3 class="font-semibold">Thiết bị #${deviceCounter}</h3>
            <button type="button" onclick="removeDeviceRow(${deviceCounter})" class="text-red-600 hover:text-red-800">
                <i class="fas fa-trash"></i> Xóa
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
            <div>
                <label class="block text-sm mb-1">Mã vật tư <span class="text-red-600">*</span></label>
                <input type="text" name="mavt[]" class="w-full border rounded px-2 py-1 text-sm" required>
            </div>
            <div>
                <label class="block text-sm mb-1">Số máy <span class="text-red-600">*</span></label>
                <input type="text" name="somay[]" class="w-full border rounded px-2 py-1 text-sm" required>
            </div>
            <div>
                <label class="block text-sm mb-1">Model</label>
                <input type="text" name="model[]" class="w-full border rounded px-2 py-1 text-sm">
            </div>
            <div>
                <label class="block text-sm mb-1">Số lượng</label>
                <input type="number" name="solg[]" value="1" min="1" class="w-full border rounded px-2 py-1 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm mb-1">Vị trí thiết bị</label>
                <input type="text" name="vitrimaybd[]" class="w-full border rounded px-2 py-1 text-sm">
            </div>
        </div>
    `;
    container.appendChild(row);
}

function removeDeviceRow(id) {
    const row = document.getElementById('device-' + id);
    if (row) {
        row.remove();
    }
}

// Add first device row on load
document.addEventListener('DOMContentLoaded', function() {
    addDeviceRow();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
