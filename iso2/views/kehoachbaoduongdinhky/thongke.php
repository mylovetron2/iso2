<?php 
$title = 'Thống kê Bảo dưỡng Định kỳ';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="max-w-7xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl md:text-2xl font-bold flex items-center">
            <i class="fas fa-chart-bar mr-2 text-blue-600"></i> Thống kê Bảo dưỡng Định kỳ
        </h1>
        
        <div class="flex items-center gap-4">
            <!-- Year Filter -->
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="action" value="thongke">
                <label class="font-semibold">Năm:</label>
                <select name="nam" onchange="this.form.submit()" 
                        class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($availableYears as $year): ?>
                        <option value="<?php echo $year; ?>" <?php echo $year == $nam ? 'selected' : ''; ?>>
                            <?php echo $year; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            
            <!-- Export PDF Button -->
            <a href="kehoachbaoduongdinhky.php?action=exportPdf&nam=<?php echo $nam; ?><?php 
                echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                echo !empty($_GET['qui']) ? '&qui=' . urlencode($_GET['qui']) : '';
                echo !empty($_GET['nhomsc']) ? '&nhomsc=' . urlencode($_GET['nhomsc']) : '';
            ?>" 
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded flex items-center gap-2 text-sm">
                <i class="fas fa-file-pdf"></i> Xuất PDF
            </a>
            
            <!-- Back Button -->
            <a href="/iso2/kehoachbaoduongdinhky.php?nam=<?php echo $nam; ?>" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded flex items-center gap-2 text-sm">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="bg-gray-50 p-4 rounded-lg mb-6 border">
        <input type="hidden" name="action" value="thongke">
        <input type="hidden" name="nam" value="<?php echo $nam; ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-search text-blue-600"></i> Tìm kiếm
                </label>
                <input type="text" 
                       name="search" 
                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                       placeholder="Tên thiết bị, số S/N..."
                       class="border rounded px-3 py-2 w-full">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-calendar-alt text-blue-600"></i> Quý
                </label>
                <select name="qui" class="border rounded px-3 py-2 w-full">
                    <option value="">-- Tất cả --</option>
                    <option value="1" <?php echo ($_GET['qui'] ?? '') === '1' ? 'selected' : ''; ?>>Quý 1</option>
                    <option value="2" <?php echo ($_GET['qui'] ?? '') === '2' ? 'selected' : ''; ?>>Quý 2</option>
                    <option value="3" <?php echo ($_GET['qui'] ?? '') === '3' ? 'selected' : ''; ?>>Quý 3</option>
                    <option value="4" <?php echo ($_GET['qui'] ?? '') === '4' ? 'selected' : ''; ?>>Quý 4</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-cogs text-blue-600"></i> Nhóm máy
                </label>
                <select name="nhomsc" class="border rounded px-3 py-2 w-full">
                    <option value="">-- Tất cả --</option>
                    <option value="RDNGA" <?php echo ($_GET['nhomsc'] ?? '') === 'RDNGA' ? 'selected' : ''; ?>>RDNGA</option>
                    <option value="CNC" <?php echo ($_GET['nhomsc'] ?? '') === 'CNC' ? 'selected' : ''; ?>>CNM</option>
                    <option value="CNC+RDNGA" <?php echo ($_GET['nhomsc'] ?? '') === 'CNC+RDNGA' ? 'selected' : ''; ?>>CNM + RDNGA</option>
                    <option value="KTKT" <?php echo ($_GET['nhomsc'] ?? '') === 'KTKT' ? 'selected' : ''; ?>>KTKT</option>
                </select>
            </div>
            
            <div class="flex gap-2 items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex-1">
                    <i class="fas fa-filter mr-1"></i> Lọc
                </button>
                <a href="kehoachbaoduongdinhky.php?action=thongke&nam=<?php echo $nam; ?>" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </div>
    </form>

    <!-- Active Filters Display -->
    <?php 
    $hasFilters = !empty($_GET['search']) || !empty($_GET['qui']) || !empty($_GET['nhomsc']);
    if ($hasFilters): 
    ?>
    <div class="bg-blue-50 border border-blue-300 rounded-lg p-3 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2 flex-wrap">
            <i class="fas fa-filter text-blue-600"></i>
            <span class="font-semibold text-blue-800">Bộ lọc đang áp dụng:</span>
            
            <?php if (!empty($_GET['search'])): ?>
                <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full text-sm">
                    <i class="fas fa-search mr-1"></i>
                    Tìm kiếm: "<?php echo htmlspecialchars($_GET['search']); ?>"
                </span>
            <?php endif; ?>
            
            <?php if (!empty($_GET['qui'])): ?>
                <span class="bg-orange-200 text-orange-800 px-3 py-1 rounded-full text-sm">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    Quý: <?php echo htmlspecialchars($_GET['qui']); ?>
                </span>
            <?php endif; ?>
            
            <?php if (!empty($_GET['nhomsc'])): ?>
                <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full text-sm">
                    <i class="fas fa-cogs mr-1"></i>
                    Nhóm: <?php 
                        $nhomscDisplay = $_GET['nhomsc'];
                        if ($nhomscDisplay === 'CNC') {
                            echo 'CNM';
                        } elseif ($nhomscDisplay === 'CNC+RDNGA') {
                            echo 'CNM + RDNGA';
                        } else {
                            echo htmlspecialchars($nhomscDisplay);
                        }
                    ?>
                </span>
            <?php endif; ?>
        </div>
        
        <a href="kehoachbaoduongdinhky.php?action=thongke&nam=<?php echo $nam; ?>" 
           class="text-blue-600 hover:text-blue-800 font-semibold text-sm whitespace-nowrap">
            <i class="fas fa-times-circle mr-1"></i>Xóa bộ lọc
        </a>
    </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <?php if (!empty($statistics['summary']['selected_qui'])): ?>
        <!-- Khi chọn quý: 4 trạng thái -->
        <div class="bg-blue-100 border-l-4 border-blue-600 p-3 mb-4 rounded">
            <p class="font-bold text-blue-800">
                <i class="fas fa-filter mr-1"></i> Thống kê theo Quý <?php echo $statistics['summary']['selected_qui']; ?>
            </p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <div class="text-sm text-gray-600">Tổng thiết bị</div>
                <div class="text-2xl font-bold text-blue-700"><?php echo $statistics['summary']['total_plans']; ?></div>
            </div>
            
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <div class="text-sm text-gray-600">Đúng hạn</div>
                <div class="text-2xl font-bold text-green-700"><?php echo $statistics['summary']['da_hoan_thanh']; ?></div>
            </div>
            
            <div class="bg-teal-50 border-l-4 border-teal-500 p-4 rounded">
                <div class="text-sm text-gray-600">Trước hạn</div>
                <div class="text-2xl font-bold text-teal-700"><?php echo $statistics['summary']['truoc_han']; ?></div>
            </div>
            
            <div class="bg-cyan-50 border-l-4 border-cyan-500 p-4 rounded">
                <div class="text-sm text-gray-600">Sau hạn</div>
                <div class="text-2xl font-bold text-cyan-700"><?php echo $statistics['summary']['sau_han']; ?></div>
            </div>
            
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="text-sm text-gray-600">Chưa hoàn thành</div>
                <div class="text-2xl font-bold text-red-700"><?php echo $statistics['summary']['chua_hoan_thanh']; ?></div>
            </div>
        </div>
    <?php else: ?>
        <!-- Khi không chọn quý: 2 trạng thái -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <div class="text-sm text-gray-600">Tổng thiết bị</div>
                <div class="text-2xl font-bold text-blue-700"><?php echo $statistics['summary']['total_plans']; ?></div>
            </div>
            
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <div class="text-sm text-gray-600">Đã hoàn thành</div>
                <div class="text-2xl font-bold text-green-700"><?php echo $statistics['summary']['da_hoan_thanh']; ?></div>
                <div class="text-xs text-gray-500 mt-1">Đã hoàn thành ít nhất 1 quý</div>
            </div>
            
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="text-sm text-gray-600">Chưa hoàn thành</div>
                <div class="text-2xl font-bold text-red-700"><?php echo $statistics['summary']['chua_hoan_thanh']; ?></div>
                <div class="text-xs text-gray-500 mt-1">Chưa hoàn thành quý nào</div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Charts Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <!-- Pie Chart -->
        <div class="bg-white border rounded-lg p-4">
            <h3 class="text-base font-bold mb-3 text-center">Phân bổ trạng thái</h3>
            <canvas id="statusPieChart" style="max-height: 250px;"></canvas>
        </div>
        
        <!-- Progress Bar Chart -->
        <div class="bg-white border rounded-lg p-4">
            <h3 class="text-base font-bold mb-3 text-center">Tỷ lệ hoàn thành</h3>
            <div class="flex items-center justify-center h-full">
                <div class="text-center w-full">
                    <div class="relative pt-1">
                        <div class="flex mb-2 items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-green-600 bg-green-200">
                                    Hoàn thành
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-semibold inline-block text-green-600">
                                    <?php echo $statistics['summary']['tyle_hoan_thanh']; ?>%
                                </span>
                            </div>
                        </div>
                        <div class="overflow-hidden h-6 mb-3 text-xs flex rounded-full bg-gray-200">
                            <div style="width:<?php echo $statistics['summary']['tyle_hoan_thanh']; ?>%" 
                                 class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-gradient-to-r from-green-400 to-green-600 transition-all duration-500">
                            </div>
                        </div>
                    </div>
                    <div class="text-4xl font-bold text-green-600 mb-2">
                        <?php echo $statistics['summary']['tyle_hoan_thanh']; ?>%
                    </div>
                    <?php if (!empty($statistics['summary']['selected_qui'])): ?>
                        <p class="text-sm text-gray-600">Tỷ lệ đã hoàn thành Quý <?php echo $statistics['summary']['selected_qui']; ?></p>
                    <?php else: ?>
                        <p class="text-sm text-gray-600">Đã hoàn thành: 
                            <span class="font-bold">
                                <?php echo $statistics['summary']['completed_quarters'] ?? 0; ?>
                            </span> / 
                            <span class="font-bold"><?php echo $statistics['summary']['total_quarters'] ?? 0; ?></span> quý
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-4 border-b">
        <ul class="flex flex-wrap -mb-px text-sm font-medium" id="statusTabs">
            <?php if (!empty($statistics['summary']['selected_qui'])): ?>
                <!-- Tabs khi chọn quý -->
                <li class="mr-2">
                    <button class="tab-button active inline-block p-4 border-b-2 border-green-600 text-green-600" 
                            data-tab="da_hoan_thanh">
                        <i class="fas fa-check-circle mr-1"></i>Đúng hạn (<?php echo $statistics['summary']['da_hoan_thanh']; ?>)
                    </button>
                </li>
                <li class="mr-2">
                    <button class="tab-button inline-block p-4 border-b-2 border-transparent hover:text-gray-600" 
                            data-tab="truoc_han">
                        <i class="fas fa-clock mr-1"></i>Trước hạn (<?php echo $statistics['summary']['truoc_han']; ?>)
                    </button>
                </li>
                <li class="mr-2">
                    <button class="tab-button inline-block p-4 border-b-2 border-transparent hover:text-gray-600" 
                            data-tab="sau_han">
                        <i class="fas fa-history mr-1"></i>Sau hạn (<?php echo $statistics['summary']['sau_han']; ?>)
                    </button>
                </li>
                <li class="mr-2">
                    <button class="tab-button inline-block p-4 border-b-2 border-transparent hover:text-gray-600" 
                            data-tab="chua_hoan_thanh">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Chưa hoàn thành (<?php echo $statistics['summary']['chua_hoan_thanh']; ?>)
                    </button>
                </li>
            <?php else: ?>
                <!-- Tabs khi không chọn quý -->
                <li class="mr-2">
                    <button class="tab-button active inline-block p-4 border-b-2 border-green-600 text-green-600" 
                            data-tab="da_hoan_thanh">
                        <i class="fas fa-check-circle mr-1"></i>Đã hoàn thành (<?php echo $statistics['summary']['da_hoan_thanh']; ?>)
                    </button>
                </li>
                <li class="mr-2">
                    <button class="tab-button inline-block p-4 border-b-2 border-transparent hover:text-gray-600" 
                            data-tab="chua_hoan_thanh">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Chưa hoàn thành (<?php echo $statistics['summary']['chua_hoan_thanh']; ?>)
                    </button>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Tab Contents -->
    <?php 
    $tabList = !empty($statistics['summary']['selected_qui']) 
        ? ['da_hoan_thanh', 'truoc_han', 'sau_han', 'chua_hoan_thanh']
        : ['da_hoan_thanh', 'chua_hoan_thanh'];
    
    foreach ($tabList as $status): 
    ?>
    <div id="tab-<?php echo $status; ?>" class="tab-content <?php echo $status === 'da_hoan_thanh' ? '' : 'hidden'; ?>">
        <?php if (empty($statistics['details'][$status])): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-4xl mb-2"></i>
                <p>Không có dữ liệu</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 border text-left">STT</th>
                            <th class="px-4 py-2 border text-left">Tên thiết bị</th>
                            <th class="px-4 py-2 border text-left">Số S/N</th>
                            <th class="px-4 py-2 border text-left">Nhóm SC</th>
                            <th class="px-4 py-2 border text-center">Quý 1</th>
                            <th class="px-4 py-2 border text-center">Quý 2</th>
                            <th class="px-4 py-2 border text-center">Quý 3</th>
                            <th class="px-4 py-2 border text-center">Quý 4</th>
                            <th class="px-4 py-2 border text-left">Đơn vị chính</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statistics['details'][$status] as $idx => $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border"><?php echo $idx + 1; ?></td>
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($item['ten_thietbi']); ?></td>
                            <td class="px-4 py-2 border font-semibold"><?php echo htmlspecialchars($item['so_serial']); ?></td>
                            <td class="px-4 py-2 border"><?php 
                                $nhomsc = $item['nhomsc'] ?? '-';
                                echo $nhomsc === 'CNC' ? 'CNM' : htmlspecialchars($nhomsc);
                            ?></td>
                            
                            <?php for ($q = 1; $q <= 4; $q++): ?>
                                <?php 
                                    $quiField = 'qui_' . $q;
                                    $quiHoanTat = 'qui_' . $q . '_hoantat';
                                    $hasValue = !empty($item[$quiField]) && trim($item[$quiField]) !== '';
                                    $isCompleted = !empty($item[$quiHoanTat]);
                                ?>
                                <td class="px-4 py-2 border text-center">
                                    <?php if ($hasValue): ?>
                                        <?php if ($isCompleted): ?>
                                            <span class="text-green-600 font-bold text-lg">✓</span>
                                        <?php else: ?>
                                            <span class="text-gray-400">○</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-gray-300">-</span>
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                            
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($item['donvi_lam_chinh'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Pie Chart
const ctxPie = document.getElementById('statusPieChart').getContext('2d');

<?php if (!empty($statistics['summary']['selected_qui'])): ?>
// Chart khi chọn quý (4 trạng thái)
new Chart(ctxPie, {
    type: 'doughnut',
    data: {
        labels: ['Đúng hạn', 'Trước hạn', 'Sau hạn', 'Chưa hoàn thành'],
        datasets: [{
            data: [
                <?php echo $statistics['summary']['da_hoan_thanh']; ?>,
                <?php echo $statistics['summary']['truoc_han']; ?>,
                <?php echo $statistics['summary']['sau_han']; ?>,
                <?php echo $statistics['summary']['chua_hoan_thanh']; ?>
            ],
            backgroundColor: [
                'rgba(34, 197, 94, 0.8)',   // Green - Đúng hạn
                'rgba(13, 148, 136, 0.8)',  // Teal - Trước hạn
                'rgba(8, 145, 178, 0.8)',   // Cyan - Sau hạn
                'rgba(239, 68, 68, 0.8)'    // Red - Chưa hoàn thành
            ],
            borderColor: [
                'rgb(34, 197, 94)',
                'rgb(13, 148, 136)',
                'rgb(8, 145, 178)',
                'rgb(239, 68, 68)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = <?php echo $statistics['summary']['total_plans']; ?>;
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
<?php else: ?>
// Chart khi không chọn quý (3 trạng thái)
new Chart(ctxPie, {
    type: 'doughnut',
    data: {
        labels: ['Đã hoàn thành', 'Chưa hoàn thành'],
        datasets: [{
            data: [
                <?php echo $statistics['summary']['da_hoan_thanh']; ?>,
                <?php echo $statistics['summary']['chua_hoan_thanh']; ?>
            ],
            backgroundColor: [
                'rgba(34, 197, 94, 0.8)',   // Green - Đã hoàn thành
                'rgba(239, 68, 68, 0.8)'    // Red - Chưa hoàn thành
            ],
            borderColor: [
                'rgb(34, 197, 94)',
                'rgb(239, 68, 68)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = <?php echo $statistics['summary']['total_plans']; ?>;
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
<?php endif; ?>

// Tab switching
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        const targetTab = this.dataset.tab;
        
        // Remove active class from all buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active', 'border-green-600', 'text-green-600', 
                'border-yellow-600', 'text-yellow-600', 'border-red-600', 'text-red-600',
                'border-teal-600', 'text-teal-600', 'border-cyan-600', 'text-cyan-600');
            btn.classList.add('border-transparent');
        });
        
        // Add active class to clicked button
        this.classList.add('active');
        this.classList.remove('border-transparent');
        
        // Color based on status
        if (targetTab === 'da_hoan_thanh') {
            this.classList.add('border-green-600', 'text-green-600');
        } else if (targetTab === 'truoc_han') {
            this.classList.add('border-teal-600', 'text-teal-600');
        } else if (targetTab === 'sau_han') {
            this.classList.add('border-cyan-600', 'text-cyan-600');
        } else if (targetTab === 'chua_hoan_thanh') {
            this.classList.add('border-red-600', 'text-red-600');
        }
        
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Show target tab content
        const targetContent = document.getElementById('tab-' + targetTab);
        if (targetContent) {
            targetContent.classList.remove('hidden');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
