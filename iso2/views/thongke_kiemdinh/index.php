<?php 
$title = 'Thống kê Kiểm định';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="max-w-7xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl md:text-2xl font-bold flex items-center">
            <i class="fas fa-chart-bar mr-2 text-blue-600"></i> Thống kê Kiểm định
        </h1>
        
        <div class="flex items-center gap-4">
            <!-- Year Filter -->
            <form method="GET" class="flex items-center gap-2">
                <label class="font-semibold">Năm:</label>
                <select name="namkh" onchange="this.form.submit()" 
                        class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($availableYears as $year): ?>
                        <option value="<?php echo $year; ?>" <?php echo $year == $namkh ? 'selected' : ''; ?>>
                            <?php echo $year; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            
            <!-- Export Buttons -->
            <div class="flex gap-2">
                <button disabled
                   class="bg-gray-400 text-white px-4 py-2 rounded flex items-center gap-2 text-sm cursor-not-allowed opacity-50">
                    <i class="fas fa-file-word"></i> Xuất Word
                </button>
                <a href="thongke_kiemdinh.php?action=exportpdf&namkh=<?php echo $namkh; ?>" 
                   class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded flex items-center gap-2 text-sm">
                    <i class="fas fa-file-pdf"></i> Xuất PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
            <div class="text-sm text-gray-600">Tổng kế hoạch</div>
            <div class="text-2xl font-bold text-blue-700"><?php echo $statistics['summary']['total_plans']; ?></div>
        </div>
        
        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
            <div class="text-sm text-gray-600">Đúng kế hoạch</div>
            <div class="text-2xl font-bold text-green-700"><?php echo $statistics['summary']['dung_ke_hoach']; ?></div>
        </div>
        
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
            <div class="text-sm text-gray-600">Chưa kiểm định</div>
            <div class="text-2xl font-bold text-red-700"><?php echo $statistics['summary']['chua_kiem_dinh']; ?></div>
        </div>
        
        <div class="bg-teal-50 border-l-4 border-teal-600 p-4 rounded">
            <div class="text-sm text-gray-600">Trước hạn</div>
            <div class="text-2xl font-bold text-teal-700"><?php echo $statistics['summary']['truoc_han']; ?></div>
        </div>
        
        <div class="bg-cyan-50 border-l-4 border-cyan-600 p-4 rounded">
            <div class="text-sm text-gray-600">Sau hạn</div>
            <div class="text-2xl font-bold text-cyan-700"><?php echo $statistics['summary']['sau_han']; ?></div>
        </div>
    </div>

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
                    <p class="text-sm text-gray-600">Đã kiểm định: 
                        <span class="font-bold">
                            <?php echo ($statistics['summary']['dung_ke_hoach'] + $statistics['summary']['truoc_han'] + $statistics['summary']['sau_han']); ?>
                        </span> / 
                        <span class="font-bold"><?php echo $statistics['summary']['total_plans']; ?></span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-4 border-b">
        <ul class="flex flex-wrap -mb-px text-sm font-medium" id="statusTabs">
            <li class="mr-2">
                <button class="tab-button active inline-block p-4 border-b-2 border-green-600 text-green-600" 
                        data-tab="dung_ke_hoach">
                    <i class="fas fa-check-circle mr-1"></i>Đúng kế hoạch (<?php echo $statistics['summary']['dung_ke_hoach']; ?>)
                </button>
            </li>
            <li class="mr-2">
                <button class="tab-button inline-block p-4 border-b-2 border-transparent hover:text-gray-600" 
                        data-tab="chua_kiem_dinh">
                    <i class="fas fa-exclamation-triangle mr-1"></i>Chưa kiểm định (<?php echo $statistics['summary']['chua_kiem_dinh']; ?>)
                </button>
            </li>
            <li class="mr-2">
                <button class="tab-button inline-block p-4 border-b-2 border-transparent hover:text-gray-600" 
                        data-tab="truoc_han">
                    <i class="fas fa-fast-forward mr-1"></i>Trước hạn (<?php echo $statistics['summary']['truoc_han']; ?>)
                </button>
            </li>
            <li class="mr-2">
                <button class="tab-button inline-block p-4 border-b-2 border-transparent hover:text-gray-600" 
                        data-tab="sau_han">
                    <i class="fas fa-clock mr-1"></i>Sau hạn (<?php echo $statistics['summary']['sau_han']; ?>)
                </button>
            </li>
        </ul>
    </div>

    <!-- Tab Contents -->
    <?php foreach (['dung_ke_hoach', 'chua_kiem_dinh', 'truoc_han', 'sau_han'] as $status): ?>
    <div id="tab-<?php echo $status; ?>" class="tab-content <?php echo $status === 'dung_ke_hoach' ? '' : 'hidden'; ?>">
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
                            <th class="px-4 py-2 border text-left">Mã hiệu</th>
                            <th class="px-4 py-2 border text-left">Số máy</th>
                            <th class="px-4 py-2 border text-left">Hãng SX</th>
                            <th class="px-4 py-2 border text-center">Tháng KH</th>
                            <th class="px-4 py-2 border text-center">Khoảng cho phép</th>
                            <th class="px-4 py-2 border text-center">Ngày kiểm định</th>
                            <th class="px-4 py-2 border text-left">Loại TB</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statistics['details'][$status] as $idx => $item): ?>
                        <?php 
                            $plan = $item['plan'];
                            $inspection = $item['inspection'];
                            $dateRange = $item['date_range'];
                            $planCount = $item['plan_count'];
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border"><?php echo $idx + 1; ?></td>
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($plan['tenthietbi'] ?? '-'); ?></td>
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($plan['mahieu'] ?? '-'); ?></td>
                            <td class="px-4 py-2 border font-semibold"><?php echo htmlspecialchars($plan['somay']); ?></td>
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($plan['hangsx'] ?? '-'); ?></td>
                            <td class="px-4 py-2 border text-center">
                                <span class="bg-blue-100 px-2 py-1 rounded">Tháng <?php echo $plan['thang']; ?></span>
                                <?php if ($planCount > 1): ?>
                                    <span class="ml-1 text-xs text-gray-500">(<?php echo $planCount; ?> đợt)</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 border text-center text-sm">
                                <?php echo date('d/m/Y', strtotime($dateRange['start'])); ?><br>
                                <i class="fas fa-arrow-down text-gray-400"></i><br>
                                <?php echo date('d/m/Y', strtotime($dateRange['end'])); ?>
                            </td>
                            <td class="px-4 py-2 border text-center">
                                <?php if ($inspection && !empty($inspection['ngayhc'])): ?>
                                    <span class="font-semibold <?php 
                                        echo $status === 'dung_ke_hoach' ? 'text-green-600' : 
                                             ($status === 'truoc_han' ? 'text-teal-600' : 
                                             ($status === 'sau_han' ? 'text-cyan-600' : 'text-red-600')); 
                                    ?>">
                                        <?php echo date('d/m/Y', strtotime($inspection['ngayhc'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($plan['loaitb'] ?? '-'); ?></td>
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
new Chart(ctxPie, {
    type: 'doughnut',
    data: {
        labels: ['Đúng kế hoạch', 'Chưa kiểm định', 'Trước hạn', 'Sau hạn'],
        datasets: [{
            data: [
                <?php echo $statistics['summary']['dung_ke_hoach']; ?>,
                <?php echo $statistics['summary']['chua_kiem_dinh']; ?>,
                <?php echo $statistics['summary']['truoc_han']; ?>,
                <?php echo $statistics['summary']['sau_han']; ?>
            ],
            backgroundColor: [
                'rgba(34, 197, 94, 0.8)',   // Green - Đúng kế hoạch
                'rgba(239, 68, 68, 0.8)',   // Red - Chưa kiểm định
                'rgba(13, 148, 136, 0.8)',  // Teal - Trước hạn
                'rgba(8, 145, 178, 0.8)'    // Cyan - Sau hạn
            ],
            borderColor: [
                'rgb(34, 197, 94)',
                'rgb(239, 68, 68)',
                'rgb(13, 148, 136)',
                'rgb(8, 145, 178)'
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

// Tab switching
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        const targetTab = this.dataset.tab;
        
        // Remove active class from all buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active', 'border-green-600', 'text-green-600', 'border-red-600', 'text-red-600', 'border-teal-600', 'text-teal-600', 'border-cyan-600', 'text-cyan-600');
            btn.classList.add('border-transparent');
        });
        
        // Add active class to clicked button
        this.classList.add('active');
        this.classList.remove('border-transparent');
        
        // Color based on status
        if (targetTab === 'dung_ke_hoach') {
            this.classList.add('border-green-600', 'text-green-600');
        } else if (targetTab === 'chua_kiem_dinh') {
            this.classList.add('border-red-600', 'text-red-600');
        } else if (targetTab === 'truoc_han') {
            this.classList.add('border-teal-600', 'text-teal-600');
        } else if (targetTab === 'sau_han') {
            this.classList.add('border-cyan-600', 'text-cyan-600');
        }
        
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Show target tab content
        document.getElementById('tab-' + targetTab).classList.remove('hidden');
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
