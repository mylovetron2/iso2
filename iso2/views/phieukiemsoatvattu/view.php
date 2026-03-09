<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
require_once __DIR__ . '/../../includes/permissions.php';
$title = 'Chi Tiết Phiếu Kiểm Soát Vật Tư';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        margin: 0;
        padding: 0;
    }
    .print-content {
        width: 100%;
        margin: 0;
        padding: 20px;
    }
}
</style>

<div class="container mx-auto px-4 py-6 print-content">
    <div class="flex justify-between items-center mb-6 no-print">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-file-alt mr-2 text-blue-600"></i> Chi Tiết Phiếu Kiểm Soát Vật Tư
        </h1>
        <div class="flex gap-2">
            <?php if ($phieu['trang_thai'] !== 'Đã hủy'): ?>
            <a href="phieukiemsoatvattu.php?action=export_word&id=<?php echo $phieu['id']; ?>" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm inline-flex items-center">
                <i class="fas fa-file-word mr-1"></i> In Word
            </a>
            <?php if (hasPermission('phieukiemsoatvattu.edit')): ?>
            <button onclick="cancelPhieu()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-ban mr-1"></i> Hủy phiếu
            </button>
            <?php endif; ?>
            <?php endif; ?>
            <a href="phieukiemsoatvattu.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>
    </div>

    <!-- Status Badge -->
    <?php 
    $statusColor = 'bg-gray-500';
    $statusText = $phieu['trang_thai'];
    
    if ($phieu['trang_thai'] === 'Đang thực hiện') {
        $statusColor = 'bg-blue-500';
        $statusText = 'active';
    } elseif ($phieu['trang_thai'] === 'Đã hoàn thành') {
        $statusColor = 'bg-green-500';
    } elseif ($phieu['trang_thai'] === 'Đã hủy') {
        $statusColor = 'bg-red-500';
    }
    ?>
    <div class="mb-4 no-print">
        <span class="<?php echo $statusColor; ?> text-white px-3 py-1 rounded-full text-sm">
            <?php echo htmlspecialchars($statusText); ?>
        </span>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <!-- Header -->
        <div class="text-center mb-6">
            <h2 class="text-xl font-bold uppercase">PHIẾU KIỂM SOÁT VẬT TƯ</h2>
            <p class="text-sm text-gray-600">Số: <?php echo htmlspecialchars($phieu['so_phieu']); ?></p>
            <p class="text-sm text-gray-600">Ngày lập: <?php echo date('d/m/Y', strtotime($phieu['created_at'])); ?></p>
        </div>

        <!-- Thông tin cơ bản -->
        <div class="mb-6">
            <table class="w-full border">
                <tr>
                    <td class="border px-4 py-2 bg-gray-50 font-semibold w-1/4">1. Loại công việc:</td>
                    <td class="border px-4 py-2"><?php echo htmlspecialchars($phieu['loai_congviec']); ?></td>
                </tr>
                <tr>
                    <td class="border px-4 py-2 bg-gray-50 font-semibold">2. Bộ phận đặt hàng:</td>
                    <td class="border px-4 py-2"><?php echo htmlspecialchars($phieu['bophan_dathang']); ?></td>
                </tr>
                <tr>
                    <td class="border px-4 py-2 bg-gray-50 font-semibold">3. Tên TB:</td>
                    <td class="border px-4 py-2"><?php echo htmlspecialchars($phieu['ten_thietbi']); ?></td>
                </tr>
                <?php if ($phieu['ky_mahieu']): ?>
                <tr>
                    <td class="border px-4 py-2 bg-gray-50 font-semibold">Ký mã hiệu:</td>
                    <td class="border px-4 py-2"><?php echo htmlspecialchars($phieu['ky_mahieu']); ?></td>
                </tr>
                <?php endif; ?>
                <?php /* Ẩn Người lập phiếu và Bộ phận
                <tr>
                    <td class="border px-4 py-2 bg-gray-50 font-semibold">4. Người lập phiếu:</td>
                    <td class="border px-4 py-2"><?php echo htmlspecialchars($phieu['nguoi_lap_phieu']); ?></td>
                </tr>
                <tr>
                    <td class="border px-4 py-2 bg-gray-50 font-semibold">Bộ phận:</td>
                    <td class="border px-4 py-2"><?php echo htmlspecialchars($phieu['bophan_nguoilap']); ?></td>
                </tr>
                */ ?>
                <tr>
                    <td class="border px-4 py-2 bg-gray-50 font-semibold">5. Phiếu xuất kho số:</td>
                    <td class="border px-4 py-2">
                        <?php if ($phieu['phieu_xuat_kho_so']): ?>
                            <?php echo htmlspecialchars($phieu['phieu_xuat_kho_so']); ?>
                            <?php if ($phieu['ngay_xuat_kho']): ?>
                                - Ngày: <?php echo date('d/m/Y', strtotime($phieu['ngay_xuat_kho'])); ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-gray-400">Chưa có</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($phieu['ghi_chu']): ?>
                <tr>
                    <td class="border px-4 py-2 bg-gray-50 font-semibold">Ghi chú:</td>
                    <td class="border px-4 py-2"><?php echo nl2br(htmlspecialchars($phieu['ghi_chu'])); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Danh sách vật tư -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-3">6. Danh mục vật tư</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full border">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2 text-sm">STT</th>
                            <th class="border px-2 py-2 text-sm">Mã vật tư</th>
                            <th class="border px-2 py-2 text-sm">Tên vật tư</th>
                            <th class="border px-2 py-2 text-sm">ĐVT</th>
                            <th class="border px-2 py-2 text-sm">Nhận</th>
                            <th class="border px-2 py-2 text-sm">Tiêu hao</th>
                            <th class="border px-2 py-2 text-sm">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($chitiets)): ?>
                        <tr>
                            <td colspan="7" class="border px-4 py-3 text-center text-gray-500">
                                Chưa có vật tư nào
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($chitiets as $index => $item): ?>
                        <tr>
                            <td class="border px-2 py-2 text-center text-sm"><?php echo $index + 1; ?></td>
                            <td class="border px-2 py-2 text-sm"><?php echo htmlspecialchars($item['mavattu']); ?></td>
                            <td class="border px-2 py-2 text-sm"><?php echo htmlspecialchars($item['ten_vattu']); ?></td>
                            <td class="border px-2 py-2 text-center text-sm"><?php echo htmlspecialchars($item['donvi']); ?></td>
                            <td class="border px-2 py-2 text-right text-sm"><?php echo number_format($item['soluong_nhan'], 2, ',', '.'); ?></td>
                            <td class="border px-2 py-2 text-right text-sm"><?php echo number_format($item['soluong_tieuhao'], 2, ',', '.'); ?></td>
                            <td class="border px-2 py-2 text-sm"><?php echo htmlspecialchars($item['ghichu']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <!-- Tổng cộng -->
                        <tr class="bg-gray-50 font-semibold">
                            <td colspan="4" class="border px-2 py-2 text-right">Tổng cộng:</td>
                            <td class="border px-2 py-2 text-right text-sm">
                                <?php 
                                $totalNhan = array_sum(array_column($chitiets, 'soluong_nhan'));
                                echo number_format($totalNhan, 2, ',', '.');
                                ?>
                            </td>
                            <td class="border px-2 py-2 text-right text-sm">
                                <?php 
                                $totalTieuhao = array_sum(array_column($chitiets, 'soluong_tieuhao'));
                                echo number_format($totalTieuhao, 2, ',', '.');
                                ?>
                            </td>
                            <td class="border px-2 py-2"></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Ghi chú về tự động ghi nhận sử dụng -->
            <?php if ($phieu['trang_thai'] !== 'Đã hủy'): ?>
            <div class="mt-3 p-3 bg-blue-50 border-l-4 border-blue-500 text-sm no-print">
                <p class="text-blue-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    <strong>Lưu ý:</strong> Số lượng tiêu hao đã được tự động ghi nhận vào 
                    <a href="/iso2/vattuthanhly.php" class="text-blue-600 hover:underline">hệ thống quản lý chi tiết sử dụng vật tư</a>.
                </p>
            </div>
            <?php else: ?>
            <div class="mt-3 p-3 bg-red-50 border-l-4 border-red-500 text-sm no-print">
                <p class="text-red-800">
                    <i class="fas fa-ban mr-1"></i>
                    <strong>Phiếu đã hủy:</strong> Các bản ghi chi tiết sử dụng đã được xóa và số lượng đã được hoàn trả.
                </p>
            </div>
            <?php endif; ?>
        </div>

        <?php /* Ẩn phần chữ ký
        <!-- Chữ ký -->
        <div class="mt-8">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="font-semibold mb-12">Người lập phiếu</p>
                    <p class="border-t inline-block px-8">
                        <?php echo htmlspecialchars($phieu['nguoi_lap_phieu']); ?>
                    </p>
                </div>
                <div>
                    <p class="font-semibold mb-12">Thủ kho</p>
                    <p class="border-t inline-block px-8">&nbsp;</p>
                </div>
                <div>
                    <p class="font-semibold mb-12">Trưởng bộ phận</p>
                    <p class="border-t inline-block px-8">&nbsp;</p>
                </div>
            </div>
        </div>
        */ ?>

        <!-- Thông tin thêm (không in) -->
        <div class="mt-6 pt-6 border-t no-print">
            <div class="text-sm text-gray-600">
                <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i', strtotime($phieu['created_at'])); ?></p>
                <p><strong>Ngày cập nhật:</strong> <?php echo date('d/m/Y H:i', strtotime($phieu['updated_at'])); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
function cancelPhieu() {
    if (confirm('Bạn có chắc chắn muốn hủy phiếu này?\n\nPhiếu đã hủy không thể khôi phục lại.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'phieukiemsoatvattu.php?action=cancel&id=<?php echo $phieu['id']; ?>';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
