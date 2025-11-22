<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-check-square text-green-600 mr-2"></i>
            Bước 2: Chọn Thiết Bị Cần Bàn Giao
        </h1>
        <p class="text-gray-600 mt-1">Chọn các thiết bị cần bàn giao trong đợt này</p>
    </div>

    <!-- Progress Steps -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-green-600 text-white rounded-full flex items-center justify-center font-bold">
                    <i class="fas fa-check"></i>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-semibold text-gray-700">Bước 1</div>
                    <div class="text-xs text-gray-500">Chọn Phiếu YC</div>
                </div>
            </div>
            <div class="flex-1 h-1 bg-green-600 mx-4"></div>
            <div class="flex items-center">
                <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">
                    2
                </div>
                <div class="ml-3">
                    <div class="text-sm font-semibold text-gray-700">Bước 2</div>
                    <div class="text-xs text-gray-500">Chọn Thiết Bị</div>
                </div>
            </div>
            <div class="flex-1 h-1 bg-gray-300 mx-4"></div>
            <div class="flex items-center">
                <div class="w-10 h-10 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center font-bold">
                    3
                </div>
                <div class="ml-3">
                    <div class="text-sm font-semibold text-gray-700">Bước 3</div>
                    <div class="text-xs text-gray-500">Xác Nhận</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thông báo hướng dẫn -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded">
        <div class="flex">
            <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
            <div>
                <h3 class="font-semibold text-blue-800 mb-1">Lưu ý quan trọng</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• <strong>Không bắt buộc chọn tất cả</strong> - Bạn chỉ cần chọn thiết bị cần bàn giao trong đợt này</li>
                    <li>• Thiết bị không chọn sẽ <strong>giữ nguyên trạng thái bg=0</strong> để bàn giao các đợt sau</li>
                    <li>• Hỗ trợ <strong>bàn giao nhiều đợt</strong> cho cùng 1 phiếu YC</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="phieubangiao_phieuyc.php?action=select_devices" id="selectDevicesForm">
        <?php foreach ($groupedByPhieu as $phieu => $devices): ?>
        <div class="bg-white rounded-lg shadow-md border-l-4 border-blue-500 p-6 mb-6">
            <!-- Header phiếu -->
            <div class="flex items-center justify-between mb-4 pb-4 border-b">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-file-alt text-blue-600 mr-2"></i>
                        Phiếu YC: <span class="text-blue-600"><?php echo htmlspecialchars($phieu); ?></span>
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Tổng: <?php echo count($devices); ?> thiết bị chưa bàn giao
                    </p>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="selectAllInPhieu('<?php echo htmlspecialchars($phieu); ?>')" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm">
                        <i class="fas fa-check-double mr-1"></i>Chọn tất cả
                    </button>
                    <button type="button" onclick="deselectAllInPhieu('<?php echo htmlspecialchars($phieu); ?>')" 
                            class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded text-sm">
                        <i class="fas fa-times mr-1"></i>Bỏ chọn
                    </button>
                </div>
            </div>

            <!-- Danh sách thiết bị -->
            <div class="space-y-3">
                <?php foreach ($devices as $device): ?>
                <div class="border rounded-lg p-4 hover:bg-gray-50 transition device-item" data-phieu="<?php echo htmlspecialchars($phieu); ?>">
                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox" name="selected_devices[]" 
                               value="<?php echo $device['stt']; ?>"
                               class="device-checkbox phieu-<?php echo htmlspecialchars($phieu); ?> w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500 mt-1 mr-4"
                               onchange="updateCounts()">
                        <div class="flex-1">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900 text-lg">
                                        <?php echo htmlspecialchars($device['mavt']); ?>
                                        <?php if (!empty($device['tenvt'])): ?>
                                            <span class="text-gray-600 font-normal">- <?php echo htmlspecialchars($device['tenvt']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-sm text-gray-600 mt-2 grid grid-cols-2 md:grid-cols-4 gap-2">
                                        <div>
                                            <i class="fas fa-barcode text-gray-400 mr-1"></i>
                                            <strong>SN:</strong> <?php echo htmlspecialchars($device['somay'] ?: '-'); ?>
                                        </div>
                                        <div>
                                            <i class="fas fa-id-badge text-gray-400 mr-1"></i>
                                            <strong>Mã QL:</strong> <?php echo htmlspecialchars($device['maql']); ?>
                                        </div>
                                        <div>
                                            <i class="fas fa-building text-gray-400 mr-1"></i>
                                            <strong>Đơn vị:</strong> <?php echo htmlspecialchars($device['madv']); ?>
                                        </div>
                                        <?php if ($device['ngaykt'] && $device['ngaykt'] != '0000-00-00'): ?>
                                        <div>
                                            <i class="fas fa-calendar-check text-gray-400 mr-1"></i>
                                            <strong>Sửa xong:</strong> <?php echo date('d/m/Y', strtotime($device['ngaykt'])); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($device['ttktafter'])): ?>
                                    <div class="mt-2 text-sm">
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            <?php echo htmlspecialchars($device['ttktafter']); ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-4">
                                    <span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-xs font-semibold">
                                        Chưa BG
                                    </span>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Thống kê phiếu -->
            <div class="mt-4 p-3 bg-blue-50 rounded flex items-center justify-between">
                <div class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    Đã chọn: <strong class="count-phieu-<?php echo htmlspecialchars($phieu); ?>">0</strong> / <?php echo count($devices); ?> thiết bị
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Tổng kết -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-clipboard-check text-green-600 mr-2"></i>
                    Tổng đã chọn: <span id="totalSelected" class="text-green-600">0</span> thiết bị
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="selectAllDevices()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                        <i class="fas fa-check-double mr-2"></i>Chọn tất cả
                    </button>
                    <button type="button" onclick="deselectAllDevices()" 
                            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                        <i class="fas fa-times mr-2"></i>Bỏ chọn tất cả
                    </button>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex gap-3 justify-end bg-white rounded-lg shadow-md p-6">
            <a href="phieubangiao_phieuyc.php?action=select" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
            <button type="submit" 
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold">
                <i class="fas fa-arrow-right mr-2"></i>Tiếp theo
            </button>
        </div>
    </form>
</div>

<script>
// Chọn tất cả thiết bị trong 1 phiếu YC
function selectAllInPhieu(phieu) {
    document.querySelectorAll('.phieu-' + phieu).forEach(cb => cb.checked = true);
    updateCounts();
}

// Bỏ chọn tất cả thiết bị trong 1 phiếu YC
function deselectAllInPhieu(phieu) {
    document.querySelectorAll('.phieu-' + phieu).forEach(cb => cb.checked = false);
    updateCounts();
}

// Chọn tất cả thiết bị
function selectAllDevices() {
    document.querySelectorAll('.device-checkbox').forEach(cb => cb.checked = true);
    updateCounts();
}

// Bỏ chọn tất cả thiết bị
function deselectAllDevices() {
    document.querySelectorAll('.device-checkbox').forEach(cb => cb.checked = false);
    updateCounts();
}

// Cập nhật số lượng đã chọn
function updateCounts() {
    // Tổng tất cả
    const totalSelected = document.querySelectorAll('.device-checkbox:checked').length;
    document.getElementById('totalSelected').textContent = totalSelected;
    
    // Từng phiếu
    document.querySelectorAll('[class*="count-phieu-"]').forEach(el => {
        const phieu = el.className.match(/count-phieu-(.+)/)[1];
        const count = document.querySelectorAll('.phieu-' + phieu + ':checked').length;
        el.textContent = count;
    });
}

// Validate form
document.getElementById('selectDevicesForm').addEventListener('submit', function(e) {
    const count = document.querySelectorAll('.device-checkbox:checked').length;
    if (count === 0) {
        e.preventDefault();
        alert('Vui lòng chọn ít nhất 1 thiết bị cần bàn giao');
    }
});

// Initialize counts
updateCounts();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
