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

<div class="w-full px-3 py-4">
    <!-- Header -->
    <div class="flex flex-wrap justify-between items-center mb-4 gap-2">
        <h1 class="text-xl font-bold text-gray-800">
            <i class="fas fa-exchange-alt mr-2 text-blue-600"></i>
            Quản Lý Giao Nhận Thiết Bị
        </h1>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="giaonhanthietbi.php?action=create" 
               class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm transition duration-200 whitespace-nowrap">
                <i class="fas fa-plus mr-1"></i>Tạo Phiếu Mới
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
    <div class="bg-gradient-to-r from-blue-50 to-green-50 rounded-lg p-2 mb-3 border border-gray-200">
        <div class="flex flex-wrap items-center justify-center gap-2 text-xs">
            <span class="bg-blue-500 text-white px-2 py-0.5 rounded-full font-medium">
                <i class="fas fa-box mr-1"></i>1. Nhận từ Đội
            </span>
            <i class="fas fa-arrow-right text-gray-400"></i>
            <span class="bg-orange-500 text-white px-2 py-0.5 rounded-full font-medium">
                <i class="fas fa-shipping-fast mr-1"></i>2. Gửi Kiểm Định
            </span>
            <i class="fas fa-arrow-right text-gray-400"></i>
            <span class="bg-green-500 text-white px-2 py-0.5 rounded-full font-medium">
                <i class="fas fa-check-circle mr-1"></i>3. Giao Lại
            </span>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 mb-4">
        <form method="GET" action="giaonhanthietbi.php" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 items-end">
            <input type="hidden" name="action" value="index">
            
            <!-- Search -->
            <div class="col-span-2 lg:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Tìm kiếm</label>
                <input type="text" 
                       name="search" 
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                       placeholder="Tên TB, người giao/nhận..."
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <!-- Trạng thái -->
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Trạng thái</label>
                <select name="trangthai" 
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tất cả --</option>
                    <option value="da_nhan" <?= ($_GET['trangthai'] ?? '') === 'da_nhan' ? 'selected' : '' ?>>Đã Nhận</option>
                    <option value="dang_kiem_dinh" <?= ($_GET['trangthai'] ?? '') === 'dang_kiem_dinh' ? 'selected' : '' ?>>Đang Kiểm Định</option>
                    <option value="da_giao" <?= ($_GET['trangthai'] ?? '') === 'da_giao' ? 'selected' : '' ?>>Đã Giao</option>
                </select>
            </div>
            
            <!-- Đơn vị -->
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Đơn vị</label>
                <select name="donvi" 
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                <label class="block text-xs font-medium text-gray-600 mb-1">Từ ngày</label>
                <input type="date" 
                       name="tu_ngay" 
                       value="<?= htmlspecialchars($_GET['tu_ngay'] ?? '') ?>"
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <!-- Đến ngày -->
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Đến ngày</label>
                <input type="date" 
                       name="den_ngay" 
                       value="<?= htmlspecialchars($_GET['den_ngay'] ?? '') ?>"
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <!-- Buttons -->
            <div class="col-span-2 lg:col-span-6 flex gap-2">
                <button type="submit" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 text-sm rounded-lg transition duration-200">
                    <i class="fas fa-search mr-1"></i>Lọc
                </button>
                <a href="giaonhanthietbi.php" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1.5 text-sm rounded-lg transition duration-200">
                    <i class="fas fa-redo mr-1"></i>Reset
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
            <div class="w-full overflow-x-auto">
                <table class="w-full table-auto text-xs divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap w-12">
                                ID
                            </th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap">
                                Trạng Thái
                            </th>
                            <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap w-14">
                                Số TB
                            </th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase tracking-wide">
                                Thiết Bị
                            </th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase tracking-wide">
                                Đơn Vị Giao
                            </th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap">
                                Người Giao
                            </th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap">
                                Ngày Nhận
                            </th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap">
                                Ngày Giao Lại
                            </th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600 uppercase tracking-wide">
                                Ghi Chú
                            </th>
                            <th class="px-3 py-2 text-center font-semibold text-gray-600 uppercase tracking-wide whitespace-nowrap">
                                Thao Tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php foreach ($records as $record): 
                            $status = $statusInfo[$record['trangthai']] ?? ['label' => 'N/A', 'color' => 'gray', 'icon' => 'fa-question'];
                        ?>
                            <tr class="hover:bg-blue-50 transition-colors duration-100">
                                <td class="px-3 py-2 whitespace-nowrap font-medium text-gray-700">
                                    #<?= $record['id'] ?>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-<?= $status['color'] ?>-100 text-<?= $status['color'] ?>-800">
                                        <i class="fas <?= $status['icon'] ?> mr-1"></i>
                                        <?= $status['label'] ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs font-medium">
                                        <?= $record['so_thietbi'] ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-gray-900">
                                    <?php 
                                    $danhSachTB = $record['danh_sach_thietbi'] ?? 'Chưa có thiết bị';
                                    if ($danhSachTB && $danhSachTB !== 'Chưa có thiết bị') {
                                        $thietbiArray = explode(', ', $danhSachTB);
                                        foreach ($thietbiArray as $tb) {
                                            echo '<div class="leading-5">' . htmlspecialchars($tb) . '</div>';
                                        }
                                    } else {
                                        echo '<span class="text-gray-400 italic">Chưa có thiết bị</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-3 py-2 text-gray-800 break-words">
                                    <?= htmlspecialchars($record['ten_donvi_giao'] ?? 'N/A') ?>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-gray-800">
                                    <?= htmlspecialchars($record['nguoi_giao']) ?>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-gray-500">
                                    <?= date('d/m/Y', strtotime($record['ngay_giao'])) ?>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-gray-500">
                                    <?= $record['ngay_nhan'] ? date('d/m/Y', strtotime($record['ngay_nhan'])) : '<span class="text-gray-300">—</span>' ?>
                                </td>
                                <td class="px-3 py-2 text-gray-500 break-words max-w-[160px]">
                                    <?= htmlspecialchars($record['ghichu'] ?? '') ?>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-center">
                                    <div class="flex justify-center items-center gap-2">
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
    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 flex items-center gap-3">
            <div class="text-2xl font-bold text-gray-800"><?= count($records) ?></div>
            <div class="text-xs text-gray-500">Tổng phiếu</div>
        </div>
        <div class="bg-blue-50 rounded-lg shadow-sm border border-blue-100 p-3 flex items-center gap-3">
            <div class="text-2xl font-bold text-blue-700"><?= $countDaNhan ?></div>
            <div class="text-xs text-blue-600">Đã nhận</div>
        </div>
        <div class="bg-orange-50 rounded-lg shadow-sm border border-orange-100 p-3 flex items-center gap-3">
            <div class="text-2xl font-bold text-orange-700"><?= $countDangKD ?></div>
            <div class="text-xs text-orange-600">Kiểm định</div>
        </div>
        <div class="bg-green-50 rounded-lg shadow-sm border border-green-100 p-3 flex items-center gap-3">
            <div class="text-2xl font-bold text-green-700"><?= $countDaGiao ?></div>
            <div class="text-xs text-green-600">Đã giao</div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
