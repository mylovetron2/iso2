<?php 
$title = 'Thiết bị chưa Kiểm định';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="max-w-full mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl md:text-2xl font-bold flex items-center">
            <i class="fas fa-clipboard-list mr-2 text-blue-600"></i> Thiết bị chưa Kiểm định theo Bộ phận
        </h1>
        
        <div class="flex items-center gap-4">
            <!-- Filter -->
            <form method="GET" class="flex items-center gap-2">
                <label class="font-semibold">Bộ phận:</label>
                <select name="madv" onchange="this.form.submit()" 
                        class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tất cả</option>
                    <?php 
                    $currentMadv = isset($_GET['madv']) ? $_GET['madv'] : '';
                    foreach ($departments_list as $dept): 
                    ?>
                        <option value="<?php echo htmlspecialchars($dept['madv']); ?>" 
                                <?php echo $dept['madv'] == $currentMadv ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['tendv']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <label class="font-semibold ml-4">Năm:</label>
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
            </form>
            
            <!-- Export Button -->
            <a href="thongke_thietbi_chuakd.php?action=exportpdf&year=<?php echo $currentYear; ?>&madv=<?php echo urlencode($currentMadv); ?>"
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded inline-flex items-center">
                <i class="fas fa-file-pdf mr-2"></i>Xuất PDF
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
            <div class="text-sm text-gray-600">Tổng bộ phận</div>
            <div class="text-2xl font-bold text-blue-700"><?php echo $statistics['summary']['total_departments']; ?></div>
        </div>
        
        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
            <div class="text-sm text-gray-600">Tổng thiết bị</div>
            <div class="text-2xl font-bold text-green-700"><?php echo $statistics['summary']['total_devices']; ?></div>
        </div>
        
        <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded">
            <div class="text-sm text-gray-600">Đã kiểm định</div>
            <div class="text-2xl font-bold text-emerald-700"><?php echo $statistics['summary']['inspected_devices']; ?></div>
        </div>
        
        <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded">
            <div class="text-sm text-gray-600">Chưa kiểm định</div>
            <div class="text-2xl font-bold text-orange-700"><?php echo $statistics['summary']['not_inspected_devices']; ?></div>
        </div>
    </div>

    <!-- Departments List -->
    <?php if (empty($statistics['departments'])): ?>
        <div class="text-center py-8">
            <i class="fas fa-check-circle text-green-500 text-5xl mb-4"></i>
            <p class="text-xl text-gray-600">Tất cả thiết bị đã được kiểm định trong năm <?php echo $currentYear; ?></p>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($statistics['departments'] as $dept): ?>
                <?php if ($dept['not_inspected_devices'] > 0): ?>
                <div class="border rounded-lg overflow-hidden">
                    <!-- Department Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold"><?php echo htmlspecialchars($dept['tendv']); ?></h3>
                                <p class="text-sm opacity-90">Mã: <?php echo htmlspecialchars($dept['madv']); ?></p>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold"><?php echo $dept['not_inspected_devices']; ?></div>
                                <div class="text-sm opacity-90">thiết bị chưa kiểm định</div>
                            </div>
                        </div>
                        <div class="mt-2 flex gap-4 text-sm">
                            <span>📦 Tổng: <?php echo $dept['total_devices']; ?></span>
                            <span>✅ Đã KĐ: <?php echo $dept['inspected_devices']; ?></span>
                            <span>⏳ Chưa KĐ: <?php echo $dept['not_inspected_devices']; ?></span>
                        </div>
                    </div>

                    <!-- Devices Table -->
                    <?php if (!empty($dept['devices'])): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã máy</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên thiết bị</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số máy</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Model</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($dept['devices'] as $index => $device): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm"><?php echo $index + 1; ?></td>
                                    <td class="px-4 py-3 text-sm font-medium text-blue-600">
                                        <?php echo htmlspecialchars($device['mavattu']); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($device['tenthietbi']); ?></td>
                                    <td class="px-4 py-3 text-sm font-medium">
                                        <?php echo htmlspecialchars($device['somay']); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($device['hangsx'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
