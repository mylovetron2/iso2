<?php
/**
 * Danh sách phiếu giao nhận thiết bị - REFACTORED
 */
require_once __DIR__ . '/../layouts/header.php';

// Mapping trạng thái
$statusInfo = [
    'da_nhan' => [
        'label' => 'Đã Nhận',
        'color' => 'blue',
        'icon' => 'fa-box'
    ],
    'dang_kiem_dinh' => [
        'label' => 'Đang Kiểm Định',
        'color' => 'orange',
        'icon' => 'fa-cog fa-spin'
    ],
    'da_giao' => [
        'label' => 'Đã Giao',
        'color' => 'green',
        'icon' => 'fa-check-circle'
    ]
];
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-exchange-alt mr-2 text-blue-600"></i>
            Quản Lý Giao Nhận Thiết Bị
        </h1>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="giaonhanthietbi.php?action=create" 
               class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-200">
                <i class="fas fa-plus mr-2"></i>Tạo Phiếu Mới
            </a>
        <?php endif; ?>
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

    <!-- Workflow Info -->
    <div class="bg-gradient-to-r from-blue-50 to-green-50 rounded-lg p-4 mb-6 shadow-sm border border-gray-200">
        <div class="flex items-center justify-center gap-4 text-sm">
            <div class="flex items-center">
                <span class="bg-blue-500 text-white px-3 py-1 rounded-full font-medium">
                    <i class="fas fa-box mr-1"></i>1. Nhận từ Đội
                </span>
            </div>
            <i class="fas fa-arrow-right text-gray-400"></i>
            <div class="flex items-center">
                <span class="bg-orange-500 text-white px-3 py-1 rounded-full font-medium">
                    <i class="fas fa-shipping-fast mr-1"></i>2. Gửi Kiểm Định
                </span>
            </div>
            <i class="fas fa-arrow-right text-gray-400"></i>
            <div class="flex items-center">
                <span class="bg-green-500 text-white px-3 py-1 rounded-full font-medium">
                    <i class="fas fa-check-circle mr-1"></i>3. Giao Lại
                </span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <form method="GET" action="giaonhanthietbi.php" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input type="hidden" name="action" value="index">
            
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tìm kiếm</label>
                <input type="text" 
                       name="search" 
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                       placeholder="Tên TB, người giao/nhận..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <!-- Trạng thái -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
                <select name="trangthai" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tất cả --</option>
                    <option value="da_nhan" <?= ($_GET['trangthai'] ?? '') === 'da_nhan' ? 'selected' : '' ?>>
                        Đã Nhận
                    </option>
                    <option value="dang_kiem_dinh" <?= ($_GET['trangthai'] ?? '') === 'dang_kiem_dinh' ? 'selected' : '' ?>>
                        Đang Kiểm Định
                    </option>
                    <option value="da_giao" <?= ($_GET['trangthai'] ?? '') === 'da_giao' ? 'selected' : '' ?>>
                        Đã Giao
                    </option>
                </select>
            </div>
            
            <!-- Đơn vị -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Đơn vị</label>
                <select name="donvi" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($donviList as $dv): ?>
                        <option value="<?= htmlspecialchars($dv['madv']) ?>"
                                <?= ($_GET['donvi'] ?? '') === $dv['madv'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dv['tendv']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Từ ngày -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Từ ngày</label>
                <input type="date" 
                       name="tu_ngay" 
                       value="<?= htmlspecialchars($_GET['tu_ngay'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <!-- Đến ngày -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Đến ngày</label>
                <input type="date" 
                       name="den_ngay" 
                       value="<?= htmlspecialchars($_GET['den_ngay'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <!-- Buttons -->
            <div class="md:col-span-5 flex gap-2">
                <button type="submit" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-search mr-2"></i>Lọc
                </button>
                <a href="giaonhanthietbi.php" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-redo mr-2"></i>Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <?php if (empty($records)): ?>
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-inbox text-4xl mb-4"></i>
                <p>Không tìm thấy phiếu nào</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Trạng Thái
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Số TB
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Thiết Bị
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Đơn Vị Giao
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Người Giao
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ngày Nhận
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ngày Giao Lại
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Thao Tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($records as $record): 
                            $status = $statusInfo[$record['trangthai']] ?? ['label' => 'N/A', 'color' => 'gray', 'icon' => 'fa-question'];
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?= $record['id'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-<?= $status['color'] ?>-100 text-<?= $status['color'] ?>-800">
                                        <i class="fas <?= $status['icon'] ?> mr-1"></i>
                                        <?= $status['label'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium">
                                        <i class="fas fa-cubes mr-1 text-gray-500"></i>
                                        <?= $record['so_thietbi'] ?> thiết bị
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="max-w-md">
                                        <?php 
                                        $danhSachTB = $record['danh_sach_thietbi'] ?? 'Chưa có thiết bị';
                                        if ($danhSachTB && $danhSachTB !== 'Chưa có thiết bị') {
                                            $thietbiArray = explode(', ', $danhSachTB);
                                            foreach ($thietbiArray as $tb) {
                                                echo '<div class="py-0.5 whitespace-nowrap">' . htmlspecialchars($tb) . '</div>';
                                            }
                                        } else {
                                            echo '<span class="text-gray-400 italic">Chưa có thiết bị</span>';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($record['ten_donvi_giao'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($record['nguoi_giao']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('d/m/Y', strtotime($record['ngay_giao'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $record['ngay_nhan'] ? date('d/m/Y', strtotime($record['ngay_nhan'])) : '-' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <!-- View -->
                                        <a href="giaonhanthietbi.php?action=view&id=<?= $record['id'] ?>" 
                                           class="text-blue-600 hover:text-blue-900"
                                           title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <!-- Edit (only for da_nhan status) -->
                                        <?php if ($record['trangthai'] === 'da_nhan' && isset($_SESSION['user_id'])): ?>
                                            <a href="giaonhanthietbi.php?action=edit&id=<?= $record['id'] ?>" 
                                               class="text-purple-600 hover:text-purple-900"
                                               title="Sửa phiếu">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <!-- Action buttons based on status -->
                                        <?php if ($record['trangthai'] === 'da_nhan' && isset($_SESSION['user_id'])): ?>
                                            <a href="giaonhanthietbi.php?action=editGuiKiemDinh&id=<?= $record['id'] ?>" 
                                               class="text-orange-600 hover:text-orange-900"
                                               title="Gửi kiểm định">
                                                <i class="fas fa-shipping-fast"></i>
                                            </a>
                                        <?php elseif ($record['trangthai'] === 'dang_kiem_dinh' && isset($_SESSION['user_id'])): ?>
                                            <a href="giaonhanthietbi.php?action=editGiaoLai&id=<?= $record['id'] ?>" 
                                               class="text-green-600 hover:text-green-900"
                                               title="Giao lại cho đội">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <!-- Delete (only if not completed) -->
                                        <?php if ($record['trangthai'] !== 'da_giao' && isset($_SESSION['user_id'])): ?>
                                            <form method="POST" 
                                                  action="giaonhanthietbi.php?action=delete"
                                                  style="display: inline;"
                                                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa phiếu này?')">
                                                <input type="hidden" name="id" value="<?= $record['id'] ?>">
                                                <button type="submit" 
                                                        class="text-red-600 hover:text-red-900"
                                                        title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Statistics -->
    <?php 
    $countDaNhan = count(array_filter($records, fn($r) => $r['trangthai'] === 'da_nhan'));
    $countDangKD = count(array_filter($records, fn($r) => $r['trangthai'] === 'dang_kiem_dinh'));
    $countDaGiao = count(array_filter($records, fn($r) => $r['trangthai'] === 'da_giao'));
    ?>
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="text-sm text-gray-600 mb-1">Tổng phiếu</div>
            <div class="text-2xl font-bold text-gray-800"><?= count($records) ?></div>
        </div>
        <div class="bg-blue-50 rounded-lg shadow-md p-4">
            <div class="text-sm text-blue-600 mb-1">Đã nhận</div>
            <div class="text-2xl font-bold text-blue-700"><?= $countDaNhan ?></div>
        </div>
        <div class="bg-orange-50 rounded-lg shadow-md p-4">
            <div class="text-sm text-orange-600 mb-1">Đang kiểm định</div>
            <div class="text-2xl font-bold text-orange-700"><?= $countDangKD ?></div>
        </div>
        <div class="bg-green-50 rounded-lg shadow-md p-4">
            <div class="text-sm text-green-600 mb-1">Đã giao</div>
            <div class="text-2xl font-bold text-green-700"><?= $countDaGiao ?></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
