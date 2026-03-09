<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
require_once __DIR__ . '/../../includes/permissions.php';
$title = 'Danh sách Phiếu Kiểm Soát Vật Tư';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-clipboard-check mr-2 text-blue-600"></i> Danh sách Phiếu Kiểm Soát Vật Tư
        </h1>
        <div class="flex gap-2">
            <?php if (hasPermission('phieukiemsoatvattu.create')): ?>
            <a href="phieukiemsoatvattu.php?action=create" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-plus mr-1"></i> Tạo phiếu mới
            </a>
            <?php endif; ?>
            <a href="vattuthanhly.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>
    </div>

    <!-- Search and Filter -->
    <form method="GET" action="phieukiemsoatvattu.php" class="bg-white p-4 rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Tìm kiếm:</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                       placeholder="Số phiếu, thiết bị, người lập..." 
                       class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Trạng thái:</label>
                <select name="trangthai" class="w-full border rounded px-3 py-2">
                    <option value="">Tất cả</option>
                    <option value="dang_thuc_hien" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] == 'dang_thuc_hien') ? 'selected' : ''; ?>>Đang thực hiện</option>
                    <option value="hoan_thanh" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] == 'hoan_thanh') ? 'selected' : ''; ?>>Hoàn thành</option>
                    <option value="huy" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] == 'huy') ? 'selected' : ''; ?>>Hủy</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mr-2">
                    <i class="fas fa-search mr-1"></i> Tìm
                </button>
                <a href="phieukiemsoatvattu.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-redo mr-1"></i> Xóa lọc
                </a>
            </div>
        </div>
    </form>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-600 font-medium">Tổng số phiếu</p>
                    <p class="text-2xl font-bold text-blue-800"><?php echo number_format($total); ?></p>
                </div>
                <i class="fas fa-file-alt text-3xl text-blue-300"></i>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số phiếu</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại công việc</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên thiết bị</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Người lập</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày xuất kho</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $index => $item): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm"><?php echo $index + 1; ?></td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-blue-700 font-semibold">
                                <?php echo htmlspecialchars($item['so_phieu']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($item['loai_congviec'] ?? ''); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($item['ten_thietbi'] ?? ''); ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($item['nguoi_lap_phieu'] ?? ''); ?></td>
                        <td class="px-4 py-3 text-sm">
                            <?php echo $item['ngay_xuat_kho'] ? date('d/m/Y', strtotime($item['ngay_xuat_kho'])) : ''; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                            $statusClass = 'bg-gray-100 text-gray-800';
                            $statusText = $item['trang_thai'];
                            
                            if ($item['trangthai'] == 'dang_thuc_hien') {
                                $statusClass = 'bg-blue-100 text-blue-800';
                                $statusText = 'active';
                            } elseif ($item['trangthai'] == 'hoan_thanh') {
                                $statusClass = 'bg-green-100 text-green-800';
                            } elseif ($item['trangthai'] == 'huy') {
                                $statusClass = 'bg-red-100 text-red-800';
                            }
                            ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($statusText); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="phieukiemsoatvattu.php?action=view&id=<?php echo $item['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-800" title="Xem">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Chưa có phiếu kiểm soát vật tư nào</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
