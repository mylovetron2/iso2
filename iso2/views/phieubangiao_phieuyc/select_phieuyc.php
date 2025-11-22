<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-check-square text-green-600 mr-2"></i>
            Bước 1: Chọn Phiếu Yêu Cầu
        </h1>
        <p class="text-gray-600 mt-1">Chọn các phiếu yêu cầu để tạo phiếu bàn giao</p>
    </div>

    <!-- Search Form -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
        <form method="GET" action="phieubangiao_phieuyc.php" class="flex gap-2">
            <input type="hidden" name="action" value="select">
            <input type="text" 
                   name="search" 
                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                   placeholder="Tìm theo số phiếu YC..."
                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                <i class="fas fa-search mr-2"></i>Tìm kiếm
            </button>
            <?php if (!empty($_GET['search'])): ?>
            <a href="phieubangiao_phieuyc.php?action=select" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-times mr-2"></i>Xóa lọc
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Form -->
    <form method="POST" action="phieubangiao_phieuyc.php?action=select" id="selectForm">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <!-- Nút chọn nhanh -->
            <div class="flex gap-2 mb-4">
                <button type="button" onclick="selectAll()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                    <i class="fas fa-check-double mr-1"></i>Chọn tất cả
                </button>
                <button type="button" onclick="deselectAll()" 
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm">
                    <i class="fas fa-times mr-1"></i>Bỏ chọn tất cả
                </button>
                <div class="ml-auto">
                    <span class="text-sm text-gray-600">Đã chọn: <strong id="selectedCount">0</strong> phiếu</span>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full border">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 border text-center w-16">
                                <input type="checkbox" id="selectAllCheckbox" 
                                       onchange="toggleAll(this)"
                                       class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            </th>
                            <th class="px-4 py-3 border text-left">Phiếu YC</th>
                            <th class="px-4 py-3 border text-center">Tổng TB</th>
                            <th class="px-4 py-3 border text-center">Chưa BG</th>
                            <th class="px-4 py-3 border text-left">Ngày sửa</th>
                            <th class="px-4 py-3 border text-center">Tiến độ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($phieuYCList)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>Không có phiếu yêu cầu nào có thiết bị chưa bàn giao</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($phieuYCList as $item): 
                                $percent = $item['tong_thietbi'] > 0 
                                    ? round(($item['da_bangiao'] / $item['tong_thietbi']) * 100, 1) 
                                    : 0;
                            ?>
                            <tr class="hover:bg-gray-50 phieu-row">
                                <td class="px-4 py-3 border text-center">
                                    <input type="checkbox" name="selected_phieuyc[]" 
                                           value="<?php echo htmlspecialchars($item['phieu']); ?>"
                                           onchange="updateCount()"
                                           class="phieu-checkbox w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                                </td>
                                <td class="px-4 py-3 border">
                                    <span class="font-semibold text-blue-600 text-lg">
                                        <?php echo htmlspecialchars($item['phieu']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 border text-center">
                                    <span class="bg-gray-100 px-3 py-1 rounded-full text-sm font-medium">
                                        <?php echo $item['tong_thietbi']; ?> TB
                                    </span>
                                </td>
                                <td class="px-4 py-3 border text-center">
                                    <span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-sm font-medium">
                                        <?php echo $item['chua_bangiao']; ?> TB
                                    </span>
                                </td>
                                <td class="px-4 py-3 border text-sm text-gray-600">
                                    <?php if ($item['ngay_sua_som_nhat'] && $item['ngay_sua_som_nhat'] != '0000-00-00'): ?>
                                        <?php echo date('d/m/Y', strtotime($item['ngay_sua_som_nhat'])); ?>
                                        <?php if ($item['ngay_sua_som_nhat'] != $item['ngay_sua_muon_nhat']): ?>
                                            <br><small class="text-gray-400">→ <?php echo date('d/m/Y', strtotime($item['ngay_sua_muon_nhat'])); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">Chưa có</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 border">
                                    <div class="flex items-center justify-center">
                                        <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-blue-500 h-2 rounded-full" 
                                                 style="width: <?php echo $percent; ?>%"></div>
                                        </div>
                                        <span class="text-xs font-medium"><?php echo $percent; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex gap-3 justify-end">
            <a href="phieubangiao_phieuyc.php" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded font-semibold">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
            <button type="submit" 
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded font-semibold">
                <i class="fas fa-arrow-right mr-2"></i>Tiếp theo
            </button>
        </div>
    </form>

    <!-- Hướng dẫn -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mt-6 rounded">
        <div class="flex">
            <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
            <div>
                <h3 class="font-semibold text-blue-800 mb-1">Hướng dẫn</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Chọn các phiếu yêu cầu cần tạo phiếu bàn giao</li>
                    <li>• Mỗi phiếu YC sẽ tạo ra 1 phiếu bàn giao riêng</li>
                    <li>• Chỉ hiển thị thiết bị <strong>chưa bàn giao</strong> (bg=0) của phiếu đã chọn</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAll(checkbox) {
    const checkboxes = document.querySelectorAll('.phieu-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateCount();
}

function selectAll() {
    document.querySelectorAll('.phieu-checkbox').forEach(cb => cb.checked = true);
    document.getElementById('selectAllCheckbox').checked = true;
    updateCount();
}

function deselectAll() {
    document.querySelectorAll('.phieu-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAllCheckbox').checked = false;
    updateCount();
}

function updateCount() {
    const count = document.querySelectorAll('.phieu-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count;
    
    // Update selectAll checkbox state
    const total = document.querySelectorAll('.phieu-checkbox').length;
    document.getElementById('selectAllCheckbox').checked = count === total && count > 0;
}

// Validate form
document.getElementById('selectForm').addEventListener('submit', function(e) {
    const count = document.querySelectorAll('.phieu-checkbox:checked').length;
    if (count === 0) {
        e.preventDefault();
        alert('Vui lòng chọn ít nhất 1 phiếu yêu cầu');
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
