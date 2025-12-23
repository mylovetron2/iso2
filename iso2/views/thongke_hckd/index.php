<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Thống Kê HC/KĐ Theo Khoảng Thời Gian';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-chart-line mr-2 text-blue-600"></i> Thống Kê Hiệu Chuẩn/Kiểm Định
        </h1>
        <?php if (!empty($items)): ?>
        <a href="thongke_hckd.php?action=exportPDF&tungay=<?php echo urlencode($tungay); ?>&denngay=<?php echo urlencode($denngay); ?>&search=<?php echo urlencode($search); ?>" 
           class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
            <i class="fas fa-file-pdf mr-1"></i> Xuất PDF
        </a>
        <?php endif; ?>
    </div>

    <!-- Messages -->
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Filter Form -->
    <form method="get" class="mb-6 bg-gray-50 p-4 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-calendar-alt text-blue-600"></i> Từ ngày
                </label>
                <input type="date" 
                       name="tungay" 
                       value="<?php echo htmlspecialchars($tungay); ?>" 
                       class="border rounded px-3 py-2 w-full">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-calendar-check text-blue-600"></i> Đến ngày
                </label>
                <input type="date" 
                       name="denngay" 
                       value="<?php echo htmlspecialchars($denngay); ?>" 
                       class="border rounded px-3 py-2 w-full">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-search text-blue-600"></i> Tìm kiếm
                </label>
                <input type="text" 
                       name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Mã VT, Số HS, Tên TB, Nhân viên..."
                       class="border rounded px-3 py-2 w-full">
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex-1">
                    <i class="fas fa-filter mr-1"></i> Lọc
                </button>
                <a href="thongke_hckd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </div>
    </form>

    <!-- Data Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 border text-left text-sm">STT</th>
                    <th class="px-4 py-2 border text-left text-sm">Số HS</th>
                    <th class="px-4 py-2 border text-left text-sm">Mã VT</th>
                    <th class="px-4 py-2 border text-left text-sm hidden md:table-cell">Tên thiết bị</th>
                    <th class="px-4 py-2 border text-center text-sm">Công việc</th>
                    <th class="px-4 py-2 border text-center text-sm">Loại HC</th>
                    <th class="px-4 py-2 border text-center text-sm">Ngày HC</th>
                    <th class="px-4 py-2 border text-center text-sm hidden lg:table-cell">Ngày HC tiếp theo</th>
                    <th class="px-4 py-2 border text-left text-sm hidden lg:table-cell">Người HC</th>
                    <th class="px-4 py-2 border text-center text-sm hidden lg:table-cell">Trạng thái</th>
                    <th class="px-4 py-2 border text-left text-sm hidden xl:table-cell">Bộ phận</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="11" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Không có dữ liệu trong khoảng thời gian này</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php $stt = 1; foreach ($items as $item): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border text-center"><?php echo $stt++; ?></td>
                    <td class="px-4 py-2 border">
                        <span class="font-mono text-sm"><?php echo htmlspecialchars($item['sohs']); ?></span>
                    </td>
                    <td class="px-4 py-2 border">
                        <span class="font-mono text-sm"><?php echo htmlspecialchars($item['tenmay']); ?></span>
                    </td>
                    <td class="px-4 py-2 border hidden md:table-cell">
                        <?php echo htmlspecialchars($item['tenthietbi'] ?? ''); ?>
                    </td>
                    <td class="px-4 py-2 border text-center">
                        <?php if ($item['congviec'] === 'CM'): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800">
                                <i class="fas fa-certificate mr-1"></i> CM
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i> HC
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2 border text-center">
                        <?php 
                        $loai_hc = $item['loai_hc'] ?? '';
                        if ($loai_hc === 'DK'): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                <i class="fas fa-calendar-check mr-1"></i> DK
                            </span>
                        <?php elseif ($loai_hc === 'DX'): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                <i class="fas fa-exclamation-circle mr-1"></i> DX
                            </span>
                        <?php else: ?>
                            <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2 border text-center">
                        <?php echo date('d/m/Y', strtotime($item['ngayhc'])); ?>
                    </td>
                    <td class="px-4 py-2 border text-center hidden lg:table-cell">
                        <?php 
                        if (!empty($item['ngayhc_tieptheo'])): 
                            $ngayTiepTheo = date('d/m/Y', strtotime($item['ngayhc_tieptheo']));
                            $daysLeft = floor((strtotime($item['ngayhc_tieptheo']) - time()) / 86400);
                            
                            if ($daysLeft < 0): ?>
                                <span class="text-red-600 font-semibold"><?php echo $ngayTiepTheo; ?></span>
                            <?php elseif ($daysLeft <= 30): ?>
                                <span class="text-orange-600 font-semibold"><?php echo $ngayTiepTheo; ?></span>
                            <?php else: ?>
                                <span><?php echo $ngayTiepTheo; ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2 border hidden lg:table-cell">
                        <?php echo htmlspecialchars($item['nhanvien']); ?>
                    </td>
                    <td class="px-4 py-2 border text-center hidden lg:table-cell">
                        <?php 
                        $ttkt = $item['ttkt'] ?? '';
                        if ($ttkt === 'Tốt'): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                <i class="fas fa-check mr-1"></i> Tốt
                            </span>
                        <?php elseif ($ttkt === 'Không đạt'): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                <i class="fas fa-times mr-1"></i> Không đạt
                            </span>
                        <?php else: ?>
                            <span class="text-gray-600"><?php echo htmlspecialchars($ttkt); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2 border hidden xl:table-cell">
                        <?php echo htmlspecialchars($item['bophansh'] ?? ''); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Summary footer -->
    <?php if ($total > 0): ?>
    <div class="mt-4 text-sm text-gray-600">
        Hiển thị <strong><?php echo $total; ?></strong> hồ sơ 
        từ <strong><?php echo date('d/m/Y', strtotime($tungay)); ?></strong> 
        đến <strong><?php echo date('d/m/Y', strtotime($denngay)); ?></strong>
    </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        background: white !important;
    }
    table {
        page-break-inside: auto;
    }
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
