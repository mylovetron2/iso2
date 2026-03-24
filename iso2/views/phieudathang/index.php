<?php
$title = 'Danh Sách Phiếu Đặt Hàng';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-file-invoice text-blue-600 mr-2"></i> Phiếu Đặt Hàng
            </h1>
            <div class="flex gap-3">
                <a href="vattuthanhly.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                    <i class="fas fa-arrow-left mr-1"></i> Quay lại
                </a>
                <a href="giohang.php" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded">
                    <i class="fas fa-shopping-cart mr-1"></i> Giỏ hàng
                    <span id="cart-badge" class="ml-1 bg-white text-orange-600 rounded-full px-2 py-0.5 text-xs font-bold"></span>
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <input type="text" name="search" placeholder="Tìm theo mã phiếu, NCC..."
                       value="<?php echo htmlspecialchars($search ?? ''); ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2">
            </div>
            <div>
                <select name="trang_thai" class="w-full border border-gray-300 rounded px-3 py-2">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="draft" <?php echo ($trang_thai ?? '') === 'draft' ? 'selected' : ''; ?>>Nháp</option>
                    <option value="ordered" <?php echo ($trang_thai ?? '') === 'ordered' ? 'selected' : ''; ?>>Đã đặt hàng</option>
                    <option value="partial_received" <?php echo ($trang_thai ?? '') === 'partial_received' ? 'selected' : ''; ?>>Nhận một phần</option>
                    <option value="received" <?php echo ($trang_thai ?? '') === 'received' ? 'selected' : ''; ?>>Đã nhận đủ</option>
                    <option value="stocked" <?php echo ($trang_thai ?? '') === 'stocked' ? 'selected' : ''; ?>>Đã nhập kho</option>
                    <option value="cancelled" <?php echo ($trang_thai ?? '') === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                </select>
            </div>
            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    <i class="fas fa-search mr-1"></i> Tìm kiếm
                </button>
                <a href="phieudathang.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-redo mr-1"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Mã phiếu</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Ngày lập</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Người lập</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold">Số items</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold">SL đặt/nhận</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>Không có phiếu đặt hàng nào</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-blue-600">
                                    <a href="phieudathang.php?action=view&id=<?php echo $item['id']; ?>" class="hover:underline">
                                        <?php echo htmlspecialchars($item['ma_phieu']); ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo date('d/m/Y H:i', strtotime($item['ngay_lap'])); ?>
                                </td>
                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($item['ten_nguoi_lap']); ?></td>
                                <td class="px-4 py-3 text-center"><?php echo $item['so_item']; ?></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-semibold">
                                        <?php echo $item['tong_sl_nhan']; ?> / <?php echo $item['tong_sl_dat']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php
                                    $statusColors = [
                                        'draft' => 'bg-gray-500',
                                        'ordered' => 'bg-blue-500',
                                        'partial_received' => 'bg-yellow-500',
                                        'received' => 'bg-green-500',
                                        'stocked' => 'bg-purple-500',
                                        'cancelled' => 'bg-red-500'
                                    ];
                                    $statusLabels = [
                                        'draft' => 'Nháp',
                                        'ordered' => 'Đã đặt',
                                        'partial_received' => 'Nhận 1 phần',
                                        'received' => 'Đã nhận',
                                        'stocked' => 'Đã nhập kho',
                                        'cancelled' => 'Đã hủy'
                                    ];
                                    $color = $statusColors[$item['trang_thai']] ?? 'bg-gray-500';
                                    $label = $statusLabels[$item['trang_thai']] ?? $item['trang_thai'];
                                    ?>
                                    <span class="<?php echo $color; ?> text-white px-3 py-1 rounded text-xs font-semibold">
                                        <?php echo $label; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="phieudathang.php?action=view&id=<?php echo $item['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-800 mr-2" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="border-t px-4 py-3 flex justify-between items-center bg-gray-50">
                <div class="text-sm text-gray-600">
                    Trang <?php echo $page; ?> / <?php echo $totalPages; ?> (Tổng: <?php echo $total; ?> phiếu)
                </div>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&trang_thai=<?php echo urlencode($trang_thai ?? ''); ?>" 
                           class="bg-white border px-3 py-1 rounded hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>&trang_thai=<?php echo urlencode($trang_thai ?? ''); ?>" 
                           class="<?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-white border'; ?> px-3 py-1 rounded hover:bg-blue-500 hover:text-white">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&trang_thai=<?php echo urlencode($trang_thai ?? ''); ?>" 
                           class="bg-white border px-3 py-1 rounded hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Load cart count
$(document).ready(function() {
    $.get('giohang.php?action=getCount', function(response) {
        if (response.success && response.count > 0) {
            $('#cart-badge').text(response.count).show();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
