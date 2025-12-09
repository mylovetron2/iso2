<?php 
$title = 'Thống kê Hồ sơ SCBD';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="max-w-full mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl md:text-2xl font-bold flex items-center">
            <i class="fas fa-exclamation-triangle mr-2 text-orange-600"></i> Thống kê Hồ sơ SCBD
        </h1>
        
        <div class="flex items-center gap-4">
            <!-- Filter -->
            <form method="GET" class="flex items-center gap-2">
                <label class="font-semibold">Năm:</label>
                <select name="year" onchange="this.form.submit()" 
                        class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php 
                    $currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
                    for ($y = (int)date('Y'); $y >= 2020; $y--): 
                    ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $currentYear ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                
                <label class="font-semibold ml-4">Số ngày trễ:</label>
                <select name="min_days" onchange="this.form.submit()" 
                        class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <?php 
                    $currentMinDays = isset($_GET['min_days']) ? (int)$_GET['min_days'] : 30;
                    $options = [7, 15, 30, 45, 60, 90];
                    foreach ($options as $days): 
                    ?>
                        <option value="<?php echo $days; ?>" <?php echo $days == $currentMinDays ? 'selected' : ''; ?>>
                            >= <?php echo $days; ?> ngày
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="year" value="<?php echo $currentYear; ?>">
                <input type="hidden" name="min_days" value="<?php echo $currentMinDays; ?>">
            </form>
            
            <!-- Export Button -->
            <a href="thongke_hososcbd.php?action=exportpdf&min_days=<?php echo $currentMinDays; ?>&year=<?php echo $currentYear; ?>" 
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded flex items-center gap-2 text-sm">
                <i class="fas fa-file-pdf"></i> Xuất PDF
            </a>
        </div>
    </div>

    <!-- Summary Cards - Hidden -->
    <div class="hidden grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded">
            <div class="text-sm text-gray-600">Tổng hồ sơ trễ</div>
            <div class="text-2xl font-bold text-orange-700"><?php echo $statistics['summary']['total']; ?></div>
        </div>
        
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
            <div class="text-sm text-gray-600">Trễ nhiều nhất</div>
            <div class="text-2xl font-bold text-red-700"><?php echo $statistics['summary']['max_days']; ?> ngày</div>
        </div>
        
        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
            <div class="text-sm text-gray-600">Trễ ít nhất</div>
            <div class="text-2xl font-bold text-yellow-700"><?php echo $statistics['summary']['min_days']; ?> ngày</div>
        </div>
        
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
            <div class="text-sm text-gray-600">Trung bình</div>
            <div class="text-2xl font-bold text-blue-700"><?php echo $statistics['summary']['avg_days']; ?> ngày</div>
        </div>
    </div>

    <!-- Details Table -->
    <div class="bg-white border rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số phiếu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thiết bị</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số máy</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đơn vị</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày TH</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày KT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số ngày trễ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($statistics['records'])): ?>
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-check-circle text-4xl text-green-500 mb-2"></i>
                                <p>Không có hồ sơ nào trễ hạn >= 30 ngày</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($statistics['records'] as $index => $record): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm"><?php echo $index + 1; ?></td>
                                <td class="px-4 py-3 text-sm font-medium text-blue-600">
                                    <?php echo htmlspecialchars($record['phieu'] ?? '-'); ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo htmlspecialchars($record['tentb'] ?? '-'); ?>
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold">
                                    <?php echo htmlspecialchars($record['somay'] ?? '-'); ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo htmlspecialchars($record['tendv'] ?? '-'); ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo $record['ngayth'] ? date('d/m/Y', strtotime($record['ngayth'])) : '-'; ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo $record['ngaykt'] ? date('d/m/Y', strtotime($record['ngaykt'])) : '-'; ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php 
                                        if ($record['so_ngay_tre'] >= 90) {
                                            echo 'bg-red-100 text-red-800';
                                        } elseif ($record['so_ngay_tre'] >= 60) {
                                            echo 'bg-orange-100 text-orange-800';
                                        } else {
                                            echo 'bg-yellow-100 text-yellow-800';
                                        }
                                        ?>">
                                        <?php echo $record['so_ngay_tre']; ?> ngày
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="hososcbd.php?action=view&stt=<?php echo $record['stt']; ?>" 
                                       class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-eye"></i> Xem
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
