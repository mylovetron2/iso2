<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Chi Tiết Phiếu Bàn Giao';
require_once __DIR__ . '/../layouts/header.php'; 

if (!$item) {
    echo '<div class="max-w-7xl mx-auto"><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">';
    echo '<i class="fas fa-exclamation-triangle mr-2"></i>Không tìm thấy phiếu bàn giao này.</div></div>';
    require_once __DIR__ . '/../layouts/footer.php';
    exit;
}

// Debug: Log item data
error_log("View.php - Item STT: " . ($item['stt'] ?? 'NOT SET'));
error_log("View.php - Item data: " . print_r($item, true));

$statusClass = [
    0 => 'bg-yellow-100 text-yellow-800',
    1 => 'bg-green-100 text-green-800'
];
$statusText = [
    0 => 'Nháp',
    1 => 'Đã duyệt'
];
$statusIcon = [
    0 => 'fa-edit',
    1 => 'fa-check-circle'
];
?>
<div class="max-w-7xl mx-auto bg-white rounded-lg shadow-md p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-file-alt mr-2 text-blue-600"></i>Chi Tiết Phiếu Bàn Giao
        </h1>
        <div class="flex gap-2">
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                <i class="fas fa-print mr-2"></i>In phiếu
            </button>
            <a href="phieubangiao.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Thông tin chung -->
    <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-6 rounded-lg mb-6 border-l-4 border-blue-600">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-gray-600 text-sm font-semibold mb-1">
                    <i class="fas fa-hashtag mr-1"></i>Số Phiếu
                </label>
                <div class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($item['sophieu']); ?></div>
            </div>
            <div>
                <label class="block text-gray-600 text-sm font-semibold mb-1">
                    <i class="fas fa-file-invoice mr-1"></i>Phiếu YC
                </label>
                <div class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($item['phieuyc']); ?></div>
            </div>
            <div>
                <label class="block text-gray-600 text-sm font-semibold mb-1">
                    <i class="fas fa-calendar-check mr-1"></i>Ngày Bàn Giao
                </label>
                <div class="text-xl font-bold text-gray-900"><?php echo date('d/m/Y', strtotime($item['ngaybg'])); ?></div>
            </div>
            <div>
                <label class="block text-gray-600 text-sm font-semibold mb-1">
                    <i class="fas fa-user-tie mr-1"></i>Người Giao
                </label>
                <div class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($item['nguoigiao']); ?></div>
            </div>
            <div>
                <label class="block text-gray-600 text-sm font-semibold mb-1">
                    <i class="fas fa-user-check mr-1"></i>Người Nhận
                </label>
                <div class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($item['nguoinhan']); ?></div>
            </div>
            <div>
                <label class="block text-gray-600 text-sm font-semibold mb-1">
                    <i class="fas fa-flag mr-1"></i>Trạng Thái
                </label>
                <span class="<?php echo $statusClass[$item['trangthai']]; ?> px-3 py-1 rounded-full font-semibold inline-block">
                    <i class="fas <?php echo $statusIcon[$item['trangthai']]; ?> mr-1"></i>
                    <?php echo $statusText[$item['trangthai']]; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Đơn vị -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white border-2 border-blue-200 p-4 rounded-lg">
            <h3 class="font-bold text-lg text-gray-700 mb-3 flex items-center">
                <i class="fas fa-building mr-2 text-blue-600"></i>Đơn Vị Giao
            </h3>
            <div class="text-gray-800 font-semibold"><?php echo htmlspecialchars($item['donvigiao_tendv'] ?? $item['donvigiao']); ?></div>
            <div class="text-sm text-gray-600 mt-1">Mã: <?php echo htmlspecialchars($item['donvigiao']); ?></div>
        </div>
        <div class="bg-white border-2 border-green-200 p-4 rounded-lg">
            <h3 class="font-bold text-lg text-gray-700 mb-3 flex items-center">
                <i class="fas fa-building mr-2 text-green-600"></i>Đơn Vị Nhận
            </h3>
            <div class="text-gray-800 font-semibold"><?php echo htmlspecialchars($item['donvinhan_tendv'] ?? $item['donvinhan']); ?></div>
            <div class="text-sm text-gray-600 mt-1">Mã: <?php echo htmlspecialchars($item['donvinhan']); ?></div>
        </div>
    </div>

    <?php if (!empty($item['ghichu'])): ?>
    <!-- Ghi chú -->
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
        <h3 class="font-bold text-lg text-gray-700 mb-2 flex items-center">
            <i class="fas fa-comment mr-2 text-yellow-600"></i>Ghi Chú
        </h3>
        <div class="text-gray-800"><?php echo nl2br(htmlspecialchars($item['ghichu'])); ?></div>
    </div>
    <?php endif; ?>

    <!-- Danh sách thiết bị -->
    <div class="mb-6">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-list mr-2 text-green-600"></i>Thiết Bị Bàn Giao (<?php echo count($devices); ?>)
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-3 px-4 border text-left w-12">#</th>
                        <th class="py-3 px-4 border text-left">Mã VT</th>
                        <th class="py-3 px-4 border text-left">Tên Thiết Bị</th>
                        <th class="py-3 px-4 border text-left">Số Máy</th>
                        <th class="py-3 px-4 border text-left">Mã QL</th>
                        <th class="py-3 px-4 border text-left">Tình Trạng</th>
                        <th class="py-3 px-4 border text-left">Ghi Chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($devices as $device): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 border text-center font-semibold"><?php echo $no++; ?></td>
                        <td class="py-3 px-4 border font-mono"><?php echo htmlspecialchars($device['mavt']); ?></td>
                        <td class="py-3 px-4 border font-semibold"><?php echo htmlspecialchars($device['tenvt']); ?></td>
                        <td class="py-3 px-4 border font-mono"><?php echo htmlspecialchars($device['somay'] ?: '-'); ?></td>
                        <td class="py-3 px-4 border font-mono"><?php echo htmlspecialchars($device['maql']); ?></td>
                        <td class="py-3 px-4 border">
                            <?php if (!empty($device['tinhtrang'])): ?>
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">
                                    <i class="fas fa-check-circle mr-1"></i><?php echo htmlspecialchars($device['tinhtrang']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 border text-sm"><?php echo !empty($device['ghichu']) ? htmlspecialchars($device['ghichu']) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Thông tin audit -->
    <div class="bg-gray-50 border-t-2 border-gray-200 p-4 rounded-lg">
        <h3 class="font-bold text-sm text-gray-600 mb-3 flex items-center">
            <i class="fas fa-info-circle mr-2"></i>Thông Tin Hệ Thống
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
            <div>
                <i class="fas fa-user-plus mr-2"></i>
                <strong>Người tạo:</strong> <?php echo htmlspecialchars($item['nguoitao']); ?>
                <span class="ml-2 text-gray-500">
                    (<?php echo date('d/m/Y H:i', strtotime($item['ngaytao'])); ?>)
                </span>
            </div>
            <?php if ($item['nguoisua']): ?>
            <div>
                <i class="fas fa-user-edit mr-2"></i>
                <strong>Người sửa:</strong> <?php echo htmlspecialchars($item['nguoisua']); ?>
                <span class="ml-2 text-gray-500">
                    (<?php echo date('d/m/Y H:i', strtotime($item['ngaysua'])); ?>)
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action buttons -->
    <?php if ($item['trangthai'] == 0): ?>
    <div class="mt-6 pt-4 border-t flex gap-3">
        <?php if (hasPermission('phieubangiao.edit')): ?>
        <a href="phieubangiao.php?action=edit&id=<?php echo $item['stt']; ?>" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
            <i class="fas fa-edit mr-2"></i>Sửa Phiếu
        </a>
        <?php endif; ?>
        
        <?php if (hasPermission('phieubangiao.delete')): ?>
        <form method="POST" action="phieubangiao.php?action=delete" class="inline" 
              onsubmit="console.log('Deleting phieu ID:', this.querySelector('input[name=id]').value); return confirm('Bạn có chắc chắn muốn xóa phiếu nháp này? Các thiết bị sẽ được trả lại trạng thái chưa bàn giao.');">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string)$item['stt']); ?>">
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded">
                <i class="fas fa-trash mr-2"></i>Xóa Phiếu Nháp #<?php echo $item['stt']; ?>
            </button>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .no-print, button, a {
        display: none !important;
    }
    .bg-gradient-to-r {
        background: #eff6ff !important;
    }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
