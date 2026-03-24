<?php
/**
 * Chi tiết phiếu giao nhận - REFACTORED
 */
require_once __DIR__ . '/../views/layouts/header.php';

// Mapping trạng thái
$statusInfo = [
    'da_nhan' => [
        'label' => 'Đã Nhận Từ Đội',
        'color' => 'blue',
        'icon' => 'fa-box',
        'nextStep' => 'Bước tiếp theo: Gửi đi kiểm định'
    ],
    'dang_kiem_dinh' => [
        'label' => 'Đang Kiểm Định',
        'color' => 'orange',
        'icon' => 'fa-cog fa-spin',
        'nextStep' => 'Bước tiếp theo: Giao lại cho đội'
    ],
    'da_giao' => [
        'label' => 'Đã Giao Lại Cho Đội',
        'color' => 'green',
        'icon' => 'fa-check-circle',
        'nextStep' => 'Hoàn tất'
    ]
];

$currentStatus = $statusInfo[$record['trangthai']] ?? ['label' => 'N/A', 'color' => 'gray', 'icon' => 'fa-question'];
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-file-alt mr-2 text-blue-600"></i>
            Chi Tiết Phiếu #<?= $record['id'] ?>
        </h1>
        <div class="flex gap-2">
            <!-- Action buttons based on status -->
            <?php if ($record['trangthai'] === 'da_nhan' && hasPermission('giaonhanthietbi.edit')): ?>
                <a href="giaonhanthietbi.php?action=editGuiKiemDinh&id=<?= $record['id'] ?>" 
                   class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-shipping-fast mr-2"></i>Gửi Kiểm Định
                </a>
            <?php elseif ($record['trangthai'] === 'dang_kiem_dinh' && hasPermission('giaonhanthietbi.edit')): ?>
                <a href="giaonhanthietbi.php?action=editGiaoLai&id=<?= $record['id'] ?>" 
                   class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-check-circle mr-2"></i>Giao Lại Cho Đội
                </a>
            <?php endif; ?>
            
            <!-- Delete (only if not completed) -->
            <?php if ($record['trangthai'] !== 'da_giao' && hasPermission('giaonhanthietbi.delete')): ?>
                <form method="POST" action="giaonhanthietbi.php?action=delete" 
                      onsubmit="return confirm('Bạn có chắc chắn muốn xóa phiếu này?\n\nLưu ý: Tất cả thiết bị trong phiếu cũng sẽ bị xóa.')"
                      style="display: inline;">
                    <input type="hidden" name="id" value="<?= $record['id'] ?>">
                    <button type="submit" 
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-trash mr-2"></i>Xóa
                    </button>
                </form>
            <?php endif; ?>
            
            <a href="giaonhanthietbi.php" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
        </div>
    </div>

    <!-- Thông báo -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
            <i class="fas fa-check-circle mr-2"></i>
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Status Badge & Progress -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-<?= $currentStatus['color'] ?>-100 text-<?= $currentStatus['color'] ?>-800">
                <i class="fas <?= $currentStatus['icon'] ?> mr-2"></i>
                <?= $currentStatus['label'] ?>
            </span>
            <span class="text-sm text-gray-600">
                <i class="fas fa-arrow-right mr-2"></i><?= $currentStatus['nextStep'] ?>
            </span>
        </div>

        <!-- Workflow Progress -->
        <div class="flex items-center justify-between relative">
            <!-- Step 1 -->
            <div class="flex flex-col items-center flex-1">
                <div class="w-12 h-12 rounded-full flex items-center justify-center <?= $record['trangthai'] === 'da_nhan' || $record['trangthai'] === 'dang_kiem_dinh' || $record['trangthai'] === 'da_giao' ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-600' ?>">
                    <i class="fas fa-box"></i>
                </div>
                <span class="text-xs mt-2 font-medium">Nhận từ đội</span>
            </div>
            
            <!-- Connector 1 -->
            <div class="flex-1 h-1 <?= $record['trangthai'] === 'dang_kiem_dinh' || $record['trangthai'] === 'da_giao' ? 'bg-orange-500' : 'bg-gray-300' ?> mx-2"></div>
            
            <!-- Step 2 -->
            <div class="flex flex-col items-center flex-1">
                <div class="w-12 h-12 rounded-full flex items-center justify-center <?= $record['trangthai'] === 'dang_kiem_dinh' || $record['trangthai'] === 'da_giao' ? 'bg-orange-500 text-white' : 'bg-gray-300 text-gray-600' ?>">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <span class="text-xs mt-2 font-medium">Gửi kiểm định</span>
            </div>
            
            <!-- Connector 2 -->
            <div class="flex-1 h-1 <?= $record['trangthai'] === 'da_giao' ? 'bg-green-500' : 'bg-gray-300' ?> mx-2"></div>
            
            <!-- Step 3 -->
            <div class="flex flex-col items-center flex-1">
                <div class="w-12 h-12 rounded-full flex items-center justify-center <?= $record['trangthai'] === 'da_giao' ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-600' ?>">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span class="text-xs mt-2 font-medium">Giao lại</span>
            </div>
        </div>
    </div>

    <!-- BƯỚC 1: Thông tin nhận từ đội -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">
            <i class="fas fa-box mr-2 text-blue-500"></i>
            Bước 1: Nhận Thiết Bị Từ Đội
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Người Giao</label>
                <div class="text-gray-800 font-medium"><?= htmlspecialchars($record['nguoi_giao']) ?></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Đơn Vị Giao</label>
                <div class="text-gray-800 font-medium"><?= htmlspecialchars($record['ten_donvi_giao'] ?? $record['donvi_giao']) ?></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Ngày Giao</label>
                <div class="text-gray-800 font-medium"><?= date('d/m/Y', strtotime($record['ngay_giao'])) ?></div>
            </div>
        </div>

        <?php if ($record['ghichu']): ?>
            <div class="mt-3 bg-gray-50 p-3 rounded">
                <label class="block text-sm font-medium text-gray-600 mb-1">Ghi Chú Chung</label>
                <div class="text-gray-800"><?= nl2br(htmlspecialchars($record['ghichu'])) ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Danh sách thiết bị -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">
            <i class="fas fa-list mr-2 text-green-500"></i>
            Danh Sách Thiết Bị (<?= count($thietbiList) ?> thiết bị)
        </h2>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">STT</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Tên Thiết Bị</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Ký Mã Hiệu</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Tình Trạng Khi Nhận</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Ghi Chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($thietbiList as $index => $tb): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm"><?= $index + 1 ?></td>
                            <td class="px-4 py-2 text-sm font-medium"><?= htmlspecialchars($tb['ten_thietbi']) ?></td>
                            <td class="px-4 py-2 text-sm"><?= htmlspecialchars($tb['ky_ma_hieu']) ?></td>
                            <td class="px-4 py-2 text-sm"><?= htmlspecialchars($tb['tinhtrang'] ?? '') ?></td>
                            <td class="px-4 py-2 text-sm text-gray-600"><?= htmlspecialchars($tb['ghichu'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- BƯỚC 2: Thông tin gửi kiểm định (nếu có) -->
    <?php if ($record['trangthai'] === 'dang_kiem_dinh' || $record['trangthai'] === 'da_giao'): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">
                <i class="fas fa-shipping-fast mr-2 text-orange-500"></i>
                Bước 2: Gửi Đi Kiểm Định
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Người Gửi</label>
                    <div class="text-gray-800 font-medium"><?= htmlspecialchars($record['nguoi_gui_kiemdinh'] ?? 'Chưa cập nhật') ?></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Đơn Vị Gửi</label>
                    <div class="text-gray-800 font-medium"><?= htmlspecialchars($record['donvi_gui_kiemdinh'] ?? 'Chưa cập nhật') ?></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Ngày Gửi</label>
                    <div class="text-gray-800 font-medium">
                        <?= $record['ngay_gui_kiemdinh'] ? date('d/m/Y', strtotime($record['ngay_gui_kiemdinh'])) : 'Chưa cập nhật' ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- BƯỚC 3: Thông tin giao lại cho đội (nếu có) -->
    <?php if ($record['trangthai'] === 'da_giao'): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">
                <i class="fas fa-check-circle mr-2 text-green-500"></i>
                Bước 3: Giao Lại Cho Đội
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Người Nhận</label>
                    <div class="text-gray-800 font-medium"><?= htmlspecialchars($record['nguoi_nhan'] ?? 'Chưa cập nhật') ?></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Đơn Vị Nhận</label>
                    <div class="text-gray-800 font-medium"><?= htmlspecialchars($record['ten_donvi_nhan'] ?? $record['donvi_nhan'] ?? 'Chưa cập nhật') ?></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Ngày Giao Lại</label>
                    <div class="text-gray-800 font-medium">
                        <?= $record['ngay_nhan'] ? date('d/m/Y', strtotime($record['ngay_nhan'])) : 'Chưa cập nhật' ?>
                    </div>
                </div>
            </div>

            <?php if ($record['noidung_kiemdinh']): ?>
                <div class="mt-3 bg-green-50 p-4 rounded border-l-4 border-green-500">
                    <label class="block text-sm font-medium text-green-700 mb-2">Kết Quả Kiểm Định</label>
                    <div class="text-gray-800 whitespace-pre-wrap"><?= htmlspecialchars($record['noidung_kiemdinh']) ?></div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Metadata -->
    <div class="bg-gray-50 rounded-lg shadow-sm p-4">
        <div class="text-sm text-gray-600 grid grid-cols-2 gap-2">
            <div>
                <i class="fas fa-user mr-1"></i>
                <strong>Tạo bởi:</strong> <?= htmlspecialchars($record['created_by'] ?? 'N/A') ?>
            </div>
            <div>
                <i class="fas fa-clock mr-1"></i>
                <strong>Tạo lúc:</strong> <?= $record['created_at'] ? date('d/m/Y H:i', strtotime($record['created_at'])) : 'N/A' ?>
            </div>
            <div>
                <i class="fas fa-edit mr-1"></i>
                <strong>Cập nhật:</strong> <?= $record['updated_at'] ? date('d/m/Y H:i', strtotime($record['updated_at'])) : 'N/A' ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/layouts/footer.php'; ?>
