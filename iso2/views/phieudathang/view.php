<?php
$title = 'Chi Tiết Phiếu Đặt Hàng - ' . $phieu['ma_phieu'];
require_once __DIR__ . '/../layouts/header.php';

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
    'ordered' => 'Đã đặt hàng',
    'partial_received' => 'Nhận một phần',
    'received' => 'Đã nhận đủ',
    'stocked' => 'Đã nhập kho',
    'cancelled' => 'Đã hủy'
];
$colorClass = $statusColors[$phieu['trang_thai']] ?? 'bg-gray-500';
$statusLabel = $statusLabels[$phieu['trang_thai']] ?? $phieu['trang_thai'];
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-blue-600"><?php echo htmlspecialchars($phieu['ma_phieu']); ?></h1>
                <span class="<?php echo $colorClass; ?> text-white px-3 py-1 rounded text-sm inline-block mt-2">
                    <?php echo $statusLabel; ?>
                </span>
            </div>
            <div class="flex gap-2">
                <!-- Action buttons based on status -->
                <?php if ($phieu['trang_thai'] === 'draft' && hasPermission('phieudathang.approve')): ?>
                    <form method="POST" action="phieudathang.php?action=approve" class="inline">
                        <input type="hidden" name="id" value="<?php echo $phieu['id']; ?>">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"
                                onclick="return confirm('Xác nhận duyệt phiếu?')">
                            <i class="fas fa-check mr-1"></i> Duyệt Phiếu
                        </button>
                    </form>
                <?php endif; ?>

                <?php if (in_array($phieu['trang_thai'], ['ordered', 'partial_received']) && hasPermission('phieudathang.receive')): ?>
                    <a href="phieudathang.php?action=receive&id=<?php echo $phieu['id']; ?>" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                        <i class="fas fa-box mr-1"></i> Nhập Hàng
                    </a>
                <?php endif; ?>

                <?php if (in_array($phieu['trang_thai'], ['draft', 'ordered']) && hasPermission('phieudathang.cancel')): ?>
                    <form method="POST" action="phieudathang.php?action=cancel" class="inline">
                        <input type="hidden" name="id" value="<?php echo $phieu['id']; ?>">
                        <input type="hidden" name="ly_do" value="Hủy phiếu">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded"
                                onclick="return confirm('Xác nhận hủy phiếu?')">
                            <i class="fas fa-ban mr-1"></i> Hủy
                        </button>
                    </form>
                <?php endif; ?>

                <a href="phieudathang.php?action=exportExcel&id=<?php echo $phieu['id']; ?>" 
                   class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded">
                    <i class="fas fa-file-excel mr-1"></i> Xuất Excel
                </a>

                <a href="phieudathang.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                    <i class="fas fa-arrow-left mr-1"></i> Quay lại
                </a>
            </div>
        </div>
    </div>

    <!-- Thông tin phiếu -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-lg mb-4">Thông tin phiếu</h3>
            <table class="w-full text-sm">
                <tr><td class="py-2 font-semibold">Ngày lập:</td><td><?php echo date('d/m/Y H:i', strtotime($phieu['ngay_lap'])); ?></td></tr>
                <tr><td class="py-2 font-semibold">Người lập:</td><td><?php echo htmlspecialchars($phieu['ten_nguoi_lap']); ?></td></tr>
                <?php if ($phieu['nguoi_duyet']): ?>
                <tr><td class="py-2 font-semibold">Người duyệt:</td><td><?php echo htmlspecialchars($phieu['ten_nguoi_duyet']); ?></td></tr>
                <tr><td class="py-2 font-semibold">Ngày duyệt:</td><td><?php echo date('d/m/Y H:i', strtotime($phieu['ngay_duyet'])); ?></td></tr>
                <?php endif; ?>
                <tr><td class="py-2 font-semibold">NCC:</td><td><?php echo htmlspecialchars($phieu['nha_cung_cap'] ?? 'Chưa có'); ?></td></tr>
                <tr><td class="py-2 font-semibold">Số HĐ:</td><td><?php echo htmlspecialchars($phieu['so_hd_ncc'] ?? 'Chưa có'); ?></td></tr>
            </table>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-lg mb-4">Thống kê</h3>
            <table class="w-full text-sm">
                <tr><td class="py-2 font-semibold">Tổng items:</td><td><?php echo count($chi_tiet); ?></td></tr>
                <tr><td class="py-2 font-semibold">Tổng SL đặt:</td><td><?php echo array_sum(array_column($chi_tiet, 'so_luong_dat')); ?></td></tr>
                <tr><td class="py-2 font-semibold">Đã nhận:</td><td><?php echo array_sum(array_column($chi_tiet, 'so_luong_nhan')); ?></td></tr>
            </table>
        </div>
    </div>

    <!-- Chi tiết vật tư -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4 bg-gray-50 border-b">
            <h3 class="font-semibold text-lg">Chi tiết vật tư</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-3 py-2">STT</th>
                        <th class="border px-3 py-2">Tên vật tư</th>
                        <th class="border px-3 py-2">ĐVT</th>
                        <th class="border px-3 py-2 text-center">SL đặt</th>
                        <th class="border px-3 py-2 text-center">SL nhận</th>
                        <th class="border px-3 py-2 text-right">Đơn giá</th>
                        <th class="border px-3 py-2 text-right">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chi_tiet as $index => $item): ?>
                        <tr>
                            <td class="border px-3 py-2 text-center"><?php echo $index + 1; ?></td>
                            <td class="border px-3 py-2"><?php echo htmlspecialchars($item['ten_tieng_viet']); ?></td>
                            <td class="border px-3 py-2 text-center"><?php echo htmlspecialchars($item['don_vi']); ?></td>
                            <td class="border px-3 py-2 text-center font-semibold"><?php echo $item['so_luong_dat']; ?></td>
                            <td class="border px-3 py-2 text-center text-green-600 font-semibold"><?php echo $item['so_luong_nhan']; ?></td>
                            <td class="border px-3 py-2 text-right"><?php echo $item['don_gia'] ? number_format($item['don_gia'], 0) : '-'; ?></td>
                            <td class="border px-3 py-2 text-right font-semibold"><?php echo $item['thanh_tien'] ? number_format($item['thanh_tien'], 0) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
