<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/HoSoScBdTamDung.php';
require_once __DIR__ . '/models/DonVi.php';

requireAuth();

if (!hasPermission('hososcbd.view')) {
    header('Location: /iso2/index.php?error=no_permission');
    exit;
}

$model = new HoSoScBdTamDung();
$donViModel = new DonVi();

// Filters
$trangthai = $_GET['trangthai'] ?? '';
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Get data
$items = $model->getBaoCaoLichSu($trangthai, '', $fromDate, $toDate, $offset, $limit);
$total = $model->countBaoCaoLichSu($trangthai, '', $fromDate, $toDate);
$totalPages = ceil($total / $limit);

// Get paused records count
$pausedCount = $model->countDanhSachTamDung();

$title = 'Báo cáo Lịch sử Tạm dừng';
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-history mr-2 text-blue-600"></i> 
        Báo cáo Lịch sử Tạm dừng / Tiếp tục Hồ sơ SCBĐ
    </h1>
    
    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-orange-100 rounded-lg p-4 text-center">
            <a href="?trangthai=dang_tam_dung" class="block hover:bg-orange-200 transition-colors rounded-lg">
                <div class="text-3xl font-bold text-orange-700"><?php echo $pausedCount; ?></div>
                <div class="text-sm text-gray-600">Hồ sơ đang tạm dừng</div>
                <?php if ($trangthai === 'dang_tam_dung'): ?>
                    <div class="text-xs text-orange-600 mt-1">
                        <i class="fas fa-check-circle"></i> Đang xem
                    </div>
                <?php else: ?>
                    <div class="text-xs text-orange-600 mt-1 opacity-0 group-hover:opacity-100">
                        <i class="fas fa-arrow-right"></i> Click để xem
                    </div>
                <?php endif; ?>
            </a>
        </div>
        <div class="bg-blue-100 rounded-lg p-4 text-center">
            <a href="?" class="block hover:bg-blue-200 transition-colors rounded-lg">
                <div class="text-3xl font-bold text-blue-700"><?php echo $total; ?></div>
                <div class="text-sm text-gray-600">Tổng số lượt thay đổi</div>
                <?php if ($trangthai === ''): ?>
                    <div class="text-xs text-blue-600 mt-1">
                        <i class="fas fa-check-circle"></i> Đang xem
                    </div>
                <?php else: ?>
                    <div class="text-xs text-blue-600 mt-1 opacity-0 group-hover:opacity-100">
                        <i class="fas fa-arrow-right"></i> Click để xem tất cả
                    </div>
                <?php endif; ?>
            </a>
        </div>
        <div class="bg-green-100 rounded-lg p-4 text-center">
            <a href="hososcbd.php" class="block hover:bg-green-200 transition-colors rounded-lg">
                <div class="text-2xl font-bold text-green-700">
                    <i class="fas fa-arrow-left mr-2"></i>Quay lại
                </div>
                <div class="text-sm text-gray-600">Hồ sơ SCBĐ</div>
            </a>
        </div>
    </div>

    <!-- Filters -->
    <form method="get" class="mb-6 bg-gray-50 p-4 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">
                    <i class="fas fa-filter mr-1"></i>Trạng thái
                </label>
                <select name="trangthai" class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">📋 Tất cả lịch sử</option>
                    <option value="dang_tam_dung" <?php echo $trangthai === 'dang_tam_dung' ? 'selected' : ''; ?>>⏸️ Đang tạm dừng (hiện tại)</option>
                    <option value="tamdung" <?php echo $trangthai === 'tamdung' ? 'selected' : ''; ?>>🟠 Các lượt tạm dừng</option>
                    <option value="tieptuc" <?php echo $trangthai === 'tieptuc' ? 'selected' : ''; ?>>🟢 Các lượt tiếp tục</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-info-circle"></i> "Đang tạm dừng" = chưa tiếp tục
                </p>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">
                    <i class="fas fa-calendar-alt mr-1"></i>Từ ngày
                </label>
                <input type="date" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>" 
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">
                    <i class="fas fa-calendar-check mr-1"></i>Đến ngày
                </label>
                <input type="date" name="to_date" value="<?php echo htmlspecialchars($toDate); ?>" 
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        
        <div class="flex gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-semibold text-sm transition-colors">
                <i class="fas fa-search mr-2"></i>Lọc
            </button>
            <a href="baocao_hososcbd_tamdung.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded font-semibold text-sm transition-colors">
                <i class="fas fa-redo mr-2"></i>Xóa lọc
            </a>
            <a href="hososcbd.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-semibold text-sm ml-auto transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
        </div>
    </form>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-2 py-2 border text-left text-xs font-bold">STT</th>
                    <th class="px-3 py-2 border text-left text-xs font-bold">Ngày giờ</th>
                    <th class="px-3 py-2 border text-left text-xs font-bold">Hồ sơ</th>
                    <th class="px-3 py-2 border text-left text-xs font-bold">Thiết bị</th>
                    <th class="px-3 py-2 border text-center text-xs font-bold">Hành động</th>
                    <th class="px-3 py-2 border text-left text-xs font-bold">Người thực hiện</th>
                    <th class="px-3 py-2 border text-left text-xs font-bold">Lý do / Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-3xl mb-2 block"></i>
                        <p>Không có dữ liệu</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php $stt = $offset + 1; ?>
                <?php foreach ($items as $item): ?>
                <?php 
                $isTamDung = ($item['trangthai'] === 'dang_tam_dung' || $item['trangthai'] === 'tamdung');
                $bgColor = $isTamDung ? 'bg-orange-50 hover:bg-orange-100' : 'bg-green-50 hover:bg-green-100';
                ?>
                <tr class="<?php echo $bgColor; ?> transition-colors">
                    <td class="px-2 py-2 border text-xs text-gray-600"><?php echo $stt++; ?></td>
                    <td class="px-3 py-2 border text-xs whitespace-nowrap">
                        <?php 
                        $ngay = new DateTime($item['ngay_thuchien']);
                        echo $ngay->format('d/m H:i');
                        ?>
                    </td>
                    <td class="px-3 py-2 border">
                        <a href="hososcbd.php?search=<?php echo urlencode($item['hoso']); ?>" 
                           class="text-blue-600 hover:text-blue-800 hover:underline font-mono font-bold text-xs">
                            <?php echo htmlspecialchars($item['hoso']); ?>
                        </a>
                    </td>
                    <td class="px-3 py-2 border text-xs">
                        <div class="font-semibold"><?php echo htmlspecialchars($item['mavt']); ?></div>
                        <div class="text-gray-600"><?php echo htmlspecialchars($item['somay']); ?></div>
                    </td>
                    <td class="px-3 py-2 border text-center">
                        <?php if ($isTamDung): ?>
                            <span class="inline-flex items-center bg-orange-500 text-white px-2 py-1 rounded-full text-xs font-bold whitespace-nowrap">
                                <i class="fas fa-pause mr-1"></i>TẠM DỪNG
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center bg-green-500 text-white px-2 py-1 rounded-full text-xs font-bold whitespace-nowrap">
                                <i class="fas fa-play mr-1"></i>TIẾP TỤC
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2 border text-xs font-semibold">
                        <?php echo htmlspecialchars($item['nguoi_thuchien']); ?>
                    </td>
                    <td class="px-3 py-2 border text-xs">
                        <?php 
                        if ($isTamDung && !empty($item['lydo_tamdung'])) {
                            echo '<div class="text-orange-800">';
                            echo '<strong class="text-orange-600">Lý do:</strong> ';
                            echo htmlspecialchars($item['lydo_tamdung']);
                            echo '</div>';
                        } elseif (!$isTamDung && !empty($item['ghichu_tieptuc'])) {
                            echo '<div class="text-green-800">';
                            echo '<strong class="text-green-600">Ghi chú:</strong> ';
                            echo htmlspecialchars($item['ghichu_tieptuc']);
                            echo '</div>';
                        } else {
                            echo '<span class="text-gray-400">-</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mt-6 flex justify-center">
        <nav class="flex space-x-2">
            <?php
            $queryParams = $_GET;
            
            if ($page > 1):
                $queryParams['page'] = $page - 1;
                $url = 'baocao_hososcbd_tamdung.php?' . http_build_query($queryParams);
            ?>
                <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            
            if ($start > 1):
                $queryParams['page'] = 1;
                $url = 'baocao_hososcbd_tamdung.php?' . http_build_query($queryParams);
            ?>
                <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">1</a>
                <?php if ($start > 2): ?>
                    <span class="px-3 py-2">...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start; $i <= $end; $i++):
                $queryParams['page'] = $i;
                $url = 'baocao_hososcbd_tamdung.php?' . http_build_query($queryParams);
                $active = ($page === $i);
            ?>
                <a href="<?php echo $url; ?>" 
                   class="px-3 py-2 rounded <?php echo $active ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?>
                    <span class="px-3 py-2">...</span>
                <?php endif; ?>
                <?php
                $queryParams['page'] = $totalPages;
                $url = 'baocao_hososcbd_tamdung.php?' . http_build_query($queryParams);
                ?>
                <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300"><?php echo $totalPages; ?></a>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
                <?php
                $queryParams['page'] = $page + 1;
                $url = 'baocao_hososcbd_tamdung.php?' . http_build_query($queryParams);
                ?>
                <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
    
    <div class="mt-4 text-sm text-gray-600 text-center">
        Hiển thị <?php echo count($items); ?> / <?php echo $total; ?> record
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
