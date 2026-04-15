<?php
/**
 * Trang thống kê Kế hoạch Kiểm định Thiết bị 2026
 * Tương tự như kehoachbaoduongdinhky.php?action=thongke
 */

declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
requireAuth();

try {
    $db = getDBConnection();
    
    $nam = 2026; // Năm cố định
    $search = $_GET['search'] ?? '';
    $quy = $_GET['quy'] ?? ''; // Lọc theo quý
    $loaitb = $_GET['loaitb'] ?? '';
    $bophan = $_GET['bophan'] ?? '';
    
    // Hàm chuyển quý sang danh sách tháng
    function getMonthsInQuarter($quy) {
        switch((int)$quy) {
            case 1: return [1, 2, 3];
            case 2: return [4, 5, 6];
            case 3: return [7, 8, 9];
            case 4: return [10, 11, 12];
            default: return [];
        }
    }
    
    // Lấy danh sách thiết bị với kế hoạch
    $sql = "SELECT t.*, 
            GROUP_CONCAT(DISTINCT k.thang_thuchien ORDER BY k.thang_thuchien) as planned_months,
            GROUP_CONCAT(DISTINCT k.thang_dot2 ORDER BY k.thang_dot2) as planned_months_dot2,
            MIN(CAST(k.thang_thuchien AS UNSIGNED)) as first_month,
            MAX(k.donvi_thuchien) as donvi_thuchien,
            GROUP_CONCAT(DISTINCT MONTH(h.ngayhc) ORDER BY h.ngayhc) as inspected_months,
            COUNT(DISTINCT h.stt) as inspection_count
            FROM thietbihckd_iso t
            LEFT JOIN kehoach_kiemdinh_2026_iso k ON t.stt = k.stt AND k.nam_kehoach = 2026
            LEFT JOIN hosohckd_iso h ON (t.mavattu = h.tenmay OR t.somay = h.tenmay) 
                AND YEAR(h.ngayhc) = 2026
            WHERE 1=1";
    
    $params = [];
    
    // Tìm kiếm
    if (!empty($search)) {
        $sql .= " AND (t.tenthietbi LIKE :search1 OR t.somay LIKE :search2 OR t.tenviettat LIKE :search3)";
        $params[':search1'] = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
        $params[':search3'] = '%' . $search . '%';
    }
    
    // Lọc theo loại thiết bị
    if (!empty($loaitb)) {
        $sql .= " AND t.loaitb = :loaitb";
        $params[':loaitb'] = $loaitb;
    }
    
    // Lọc theo bộ phận
    if (!empty($bophan)) {
        $sql .= " AND t.bophansh = :bophan";
        $params[':bophan'] = $bophan;
    }
    
    $sql .= " GROUP BY t.stt ORDER BY first_month ASC, t.loaitb, t.tenthietbi";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $allEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tính thống kê
    // LOGIC:
    // - Tổng thiết bị: CHỈ đếm thiết bị CÓ KẾ HOẠCH
    // - Đã kiểm định: TẤT CẢ thiết bị đã kiểm định trong năm 2026 (cả có và không có kế hoạch)
    // - Chưa kiểm định: Thiết bị có kế hoạch nhưng chưa kiểm định
    // - Khi CHỌN quý: Chỉ tính thiết bị có kế hoạch ở quý đó (4 trạng thái)
    $statistics = [
        'da_hoan_thanh' => [],
        'chua_hoan_thanh' => [],
        'truoc_han' => [],
        'sau_han' => []
    ];
    
    $totalEquipmentWithPlan = 0; // Số thiết bị có kế hoạch
    $totalEquipmentInQuarter = 0; // Số thiết bị có kế hoạch ở quý được chọn (dùng khi filter theo quý)
    $totalMonths = 0;
    $completedMonths = 0;
    
    foreach ($allEquipment as $equipment) {
        // Lấy danh sách tháng kế hoạch
        $plannedMonths = array_merge(
            $equipment['planned_months'] ? explode(',', $equipment['planned_months']) : [],
            $equipment['planned_months_dot2'] ? explode(',', $equipment['planned_months_dot2']) : []
        );
        $plannedMonths = array_map('trim', $plannedMonths); // Loại bỏ khoảng trắng
        $plannedMonths = array_unique($plannedMonths);
        $plannedMonths = array_filter($plannedMonths, function($m) { return !empty($m); });
        
        // Đếm thiết bị có kế hoạch
        if (!empty($plannedMonths)) {
            $totalEquipmentWithPlan++;
        }
        
        // Lấy danh sách tháng đã kiểm định
        $inspectedMonths = $equipment['inspected_months'] ? explode(',', $equipment['inspected_months']) : [];
        $inspectedMonths = array_map('trim', $inspectedMonths); // Loại bỏ khoảng trắng
        
        // Nếu có filter theo quý cụ thể
        if (!empty($quy) && is_numeric($quy)) {
            // Khi chọn quý: xét TẤT CẢ thiết bị đã kiểm định trong quý đó (bất kể kế hoạch)
            if (empty($plannedMonths)) {
                continue; // Bỏ qua thiết bị không có kế hoạch
            }
            
            $monthsInQuarter = getMonthsInQuarter((int)$quy);
            
            // Kiểm tra đã kiểm định trong quý này chưa
            $hasInspectedInQuarter = false;
            foreach ($monthsInQuarter as $m) {
                if (in_array((string)$m, $inspectedMonths)) {
                    $hasInspectedInQuarter = true;
                    break;
                }
            }
            
            // Kiểm tra có kế hoạch trong quý này không
            $hasPlannedInQuarter = false;
            foreach ($monthsInQuarter as $m) {
                if (in_array((string)$m, $plannedMonths)) {
                    $hasPlannedInQuarter = true;
                    break;
                }
            }
            
            if ($hasInspectedInQuarter) {
                // Đã kiểm định trong quý này
                $totalEquipmentInQuarter++;
                
                if ($hasPlannedInQuarter) {
                    // Có kế hoạch trong quý này → Đúng hạn
                    $statistics['da_hoan_thanh'][] = $equipment;
                } else {
                    // Không có kế hoạch trong quý này (kế hoạch ở quý sau) → Trước hạn
                    $statistics['truoc_han'][] = $equipment;
                }
            } else {
                // Chưa kiểm định trong quý này
                if ($hasPlannedInQuarter) {
                    // Có kế hoạch trong quý này nhưng chưa kiểm định
                    $totalEquipmentInQuarter++;
                    
                    // Kiểm tra đã kiểm định ở quý sau chưa
                    $hasCompletedAfter = false;
                    foreach ($inspectedMonths as $inspMonth) {
                        $inspMonthInt = (int)$inspMonth;
                        if ($inspMonthInt > $monthsInQuarter[2]) {
                            $hasCompletedAfter = true;
                            break;
                        }
                    }
                    
                    if ($hasCompletedAfter) {
                        $statistics['sau_han'][] = $equipment;
                    } else {
                        $statistics['chua_hoan_thanh'][] = $equipment;
                    }
                }
                // Nếu không có kế hoạch trong quý này → bỏ qua (không liên quan)
            }
        } else {
            // Không filter theo quý - thống kê tổng quan
            // Đếm thiết bị đã kiểm định (CHỈ thiết bị có kế hoạch)
            if (!empty($inspectedMonths)) {
                // Chỉ đếm nếu có kế hoạch
                if (!empty($plannedMonths)) {
                    $statistics['da_hoan_thanh'][] = $equipment;
                    // Đếm số tháng đã hoàn thành
                    foreach ($plannedMonths as $month) {
                        if (in_array($month, $inspectedMonths)) {
                            $completedMonths++;
                        }
                    }
                }
            } else {
                // Chưa kiểm định: chỉ đếm nếu có kế hoạch
                if (!empty($plannedMonths)) {
                    $statistics['chua_hoan_thanh'][] = $equipment;
                }
            }
        }
        
        // Đếm tổng số tháng kế hoạch (chỉ cho thiết bị có kế hoạch)
        if (!empty($plannedMonths)) {
            $totalMonths += count($plannedMonths);
        }
    }
    
    // Tính tỷ lệ hoàn thành
    $tyLeHoanThanh = 0;
    if (!empty($quy)) {
        // Khi chọn quý: tỷ lệ = (đã hoàn thành) / tổng số
        $completed = count($statistics['da_hoan_thanh']) + count($statistics['truoc_han']) + count($statistics['sau_han']);
        $tyLeHoanThanh = $totalEquipmentInQuarter > 0 ? round(($completed / $totalEquipmentInQuarter) * 100, 2) : 0;
    } else {
        // Khi không chọn quý: tỷ lệ theo tháng kế hoạch
        $tyLeHoanThanh = $totalMonths > 0 ? round(($completedMonths / $totalMonths) * 100, 2) : 0;
    }
    
    // Summary
    $summary = [
        'total_plans' => $totalEquipmentWithPlan, // Chỉ đếm thiết bị có kế hoạch
        'da_hoan_thanh' => count($statistics['da_hoan_thanh'] ?? []),
        'truoc_han' => count($statistics['truoc_han'] ?? []),
        'sau_han' => count($statistics['sau_han'] ?? []),
        'chua_hoan_thanh' => count($statistics['chua_hoan_thanh'] ?? []),
        'tyle_hoan_thanh' => $tyLeHoanThanh,
        'selected_quy' => $quy,
        'total_months' => $totalMonths,
        'completed_months' => $completedMonths
    ];
    
    // Lấy danh sách loại TB và bộ phận cho filter
    $loaiTBList = $db->query("SELECT DISTINCT loaitb FROM thietbihckd_iso WHERE loaitb != '' ORDER BY loaitb")->fetchAll(PDO::FETCH_COLUMN);
    $boPhanList = $db->query("SELECT DISTINCT bophansh FROM thietbihckd_iso WHERE bophansh != '' ORDER BY bophansh")->fetchAll(PDO::FETCH_COLUMN);
    
    $title = 'Thống kê Kiểm định Thiết bị 2026';
    require_once __DIR__ . '/views/layouts/header.php';
    
} catch (Exception $e) {
    error_log("Error in kehoach_thietbi_2026_thongke.php: " . $e->getMessage());
    $_SESSION['error'] = 'Có lỗi xảy ra khi tải thống kê';
    header('Location: /iso2/kehoach_thietbi_2026.php');
    exit;
}
?>

<div class="max-w-7xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl md:text-2xl font-bold flex items-center">
            <i class="fas fa-chart-bar mr-2 text-blue-600"></i> Thống kê Kiểm định Thiết bị 2026
        </h1>
        
        <div class="flex items-center gap-4">
            <!-- Export PDF Button -->
            <a href="kehoach_thietbi_2026_thongke_pdf.php?<?php 
                $params = [];
                if (!empty($search)) $params[] = 'search=' . urlencode($search);
                if (!empty($quy)) $params[] = 'quy=' . urlencode($quy);
                if (!empty($loaitb)) $params[] = 'loaitb=' . urlencode($loaitb);
                if (!empty($bophan)) $params[] = 'bophan=' . urlencode($bophan);
                echo implode('&', $params);
            ?>" 
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded flex items-center gap-2 text-sm"
               target="_blank">
                <i class="fas fa-file-pdf"></i> Xuất PDF
            </a>
            
            <!-- Back Button -->
            <a href="/iso2/kehoach_thietbi_2026.php" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded flex items-center gap-2 text-sm">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" class="bg-gray-50 p-4 rounded-lg mb-6 border">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-search text-blue-600"></i> Tìm kiếm
                </label>
                <input type="text" 
                       name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Tên thiết bị, số máy..."
                       class="border rounded px-3 py-2 w-full">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-calendar-alt text-blue-600"></i> Quý
                </label>
                <select name="quy" class="border rounded px-3 py-2 w-full">
                    <option value="">-- Tất cả --</option>
                    <option value="1" <?php echo $quy == '1' ? 'selected' : ''; ?>>Quý 1 (Tháng 1-3)</option>
                    <option value="2" <?php echo $quy == '2' ? 'selected' : ''; ?>>Quý 2 (Tháng 4-6)</option>
                    <option value="3" <?php echo $quy == '3' ? 'selected' : ''; ?>>Quý 3 (Tháng 7-9)</option>
                    <option value="4" <?php echo $quy == '4' ? 'selected' : ''; ?>>Quý 4 (Tháng 10-12)</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-cogs text-blue-600"></i> Loại thiết bị
                </label>
                <select name="loaitb" class="border rounded px-3 py-2 w-full">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($loaiTBList as $loai): ?>
                        <option value="<?php echo htmlspecialchars($loai); ?>" <?php echo $loaitb === $loai ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loai); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-building text-blue-600"></i> Bộ phận
                </label>
                <select name="bophan" class="border rounded px-3 py-2 w-full">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($boPhanList as $bp): ?>
                        <option value="<?php echo htmlspecialchars($bp); ?>" <?php echo $bophan === $bp ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($bp); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex gap-2 items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex-1">
                    <i class="fas fa-filter mr-1"></i> Lọc
                </button>
                <a href="kehoach_thietbi_2026_thongke.php" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </div>
    </form>

    <!-- Active Filters Display -->
    <?php 
    $hasFilters = !empty($search) || !empty($quy) || !empty($loaitb) || !empty($bophan);
    if ($hasFilters): 
    ?>
    <div class="bg-blue-50 border border-blue-300 rounded-lg p-3 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2 flex-wrap">
            <i class="fas fa-filter text-blue-600"></i>
            <span class="font-semibold text-blue-800">Bộ lọc đang áp dụng:</span>
            
            <?php if (!empty($search)): ?>
                <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full text-sm">
                    <i class="fas fa-search mr-1"></i>
                    Tìm kiếm: "<?php echo htmlspecialchars($search); ?>"
                </span>
            <?php endif; ?>
            
            <?php if (!empty($quy)): ?>
                <span class="bg-orange-200 text-orange-800 px-3 py-1 rounded-full text-sm">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    Quý: <?php echo htmlspecialchars($quy); ?>
                </span>
            <?php endif; ?>
            
            <?php if (!empty($loaitb)): ?>
                <span class="bg-blue-200 text-blue-800 px-3 py-1 rounded-full text-sm">
                    <i class="fas fa-cogs mr-1"></i>
                    Loại: <?php echo htmlspecialchars($loaitb); ?>
                </span>
            <?php endif; ?>
            
            <?php if (!empty($bophan)): ?>
                <span class="bg-purple-200 text-purple-800 px-3 py-1 rounded-full text-sm">
                    <i class="fas fa-building mr-1"></i>
                    Bộ phận: <?php echo htmlspecialchars($bophan); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <a href="kehoach_thietbi_2026_thongke.php" 
           class="text-blue-600 hover:text-blue-800 font-semibold text-sm whitespace-nowrap">
            <i class="fas fa-times-circle mr-1"></i>Xóa bộ lọc
        </a>
    </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <?php if (!empty($summary['selected_quy'])): ?>
        <!-- Khi chọn quý: 4 trạng thái -->
        <div class="bg-blue-100 border-l-4 border-blue-600 p-3 mb-4 rounded">
            <p class="font-bold text-blue-800">
                <i class="fas fa-filter mr-1"></i> Thống kê theo Quý <?php echo $summary['selected_quy']; ?>
            </p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <div class="text-sm text-gray-600">Tổng thiết bị</div>
                <div class="text-2xl font-bold text-blue-700"><?php echo $summary['total_plans']; ?></div>
            </div>
            
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <div class="text-sm text-gray-600">Đúng hạn</div>
                <div class="text-2xl font-bold text-green-700"><?php echo $summary['da_hoan_thanh']; ?></div>
            </div>
            
            <div class="bg-teal-50 border-l-4 border-teal-500 p-4 rounded">
                <div class="text-sm text-gray-600">Trước hạn</div>
                <div class="text-2xl font-bold text-teal-700"><?php echo $summary['truoc_han']; ?></div>
            </div>
            
            <div class="bg-cyan-50 border-l-4 border-cyan-500 p-4 rounded">
                <div class="text-sm text-gray-600">Sau hạn</div>
                <div class="text-2xl font-bold text-cyan-700"><?php echo $summary['sau_han']; ?></div>
            </div>
            
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="text-sm text-gray-600">Chưa hoàn thành</div>
                <div class="text-2xl font-bold text-red-700"><?php echo $summary['chua_hoan_thanh']; ?></div>
            </div>
        </div>
    <?php else: ?>
        <!-- Khi không chọn tháng: 2 trạng thái -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <div class="text-sm text-gray-600">Tổng thiết bị</div>
                <div class="text-2xl font-bold text-blue-700"><?php echo $summary['total_plans']; ?></div>
                <div class="text-xs text-gray-500 mt-1">Thiết bị có kế hoạch năm 2026</div>
            </div>
            
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <div class="text-sm text-gray-600">Đã kiểm định</div>
                <div class="text-2xl font-bold text-green-700"><?php echo $summary['da_hoan_thanh']; ?></div>
                <div class="text-xs text-gray-500 mt-1">Đã kiểm định trong năm 2026</div>
            </div>
            
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="text-sm text-gray-600">Chưa kiểm định</div>
                <div class="text-2xl font-bold text-red-700"><?php echo $summary['chua_hoan_thanh']; ?></div>
                <div class="text-xs text-gray-500 mt-1">Có kế hoạch nhưng chưa kiểm định</div>
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
                                    <?php echo $summary['tyle_hoan_thanh']; ?>%
                                </span>
                            </div>
                        </div>
                        <div class="overflow-hidden h-6 mb-3 text-xs flex rounded-full bg-gray-200">
                            <div style="width:<?php echo $summary['tyle_hoan_thanh']; ?>%" 
                                 class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-gradient-to-r from-green-400 to-green-600 transition-all duration-500">
                            </div>
                        </div>
                    </div>
                    <div class="text-4xl font-bold text-green-600 mb-2">
                        <?php echo $summary['tyle_hoan_thanh']; ?>%
                    </div>
                    <?php if (!empty($summary['selected_quy'])): ?>
                        <p class="text-sm text-gray-600">Tỷ lệ đã hoàn thành Quý <?php echo $summary['selected_quy']; ?></p>
                    <?php else: ?>
                        <p class="text-sm text-gray-600">Đã kiểm định: 
                            <span class="font-bold"><?php echo $summary['completed_months']; ?></span> / 
                            <span class="font-bold"><?php echo $summary['total_months']; ?></span> tháng
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Tabs -->
    <?php if (!empty($summary['selected_quy'])): ?>
        <!-- Tabs với 4 trạng thái -->
        <div class="border rounded-lg">
            <div class="flex border-b bg-gray-50">
                <button class="tab-button px-4 py-3 font-semibold border-b-2 border-green-500 text-green-700" data-tab="da_hoan_thanh">
                    <i class="fas fa-check-circle mr-1"></i> Đúng hạn (<?php echo $summary['da_hoan_thanh']; ?>)
                </button>
                <button class="tab-button px-4 py-3 font-semibold text-gray-600 hover:bg-gray-100" data-tab="truoc_han">
                    <i class="fas fa-forward mr-1"></i> Trước hạn (<?php echo $summary['truoc_han']; ?>)
                </button>
                <button class="tab-button px-4 py-3 font-semibold text-gray-600 hover:bg-gray-100" data-tab="sau_han">
                    <i class="fas fa-clock mr-1"></i> Sau hạn (<?php echo $summary['sau_han']; ?>)
                </button>
                <button class="tab-button px-4 py-3 font-semibold text-gray-600 hover:bg-gray-100" data-tab="chua_hoan_thanh">
                    <i class="fas fa-times-circle mr-1"></i> Chưa hoàn thành (<?php echo $summary['chua_hoan_thanh']; ?>)
                </button>
            </div>
    <?php else: ?>
        <!-- Tabs với 2 trạng thái -->
        <div class="border rounded-lg">
            <div class="flex border-b bg-gray-50">
                <button class="tab-button px-4 py-3 font-semibold border-b-2 border-green-500 text-green-700" data-tab="da_hoan_thanh">
                    <i class="fas fa-check-circle mr-1"></i> Đã kiểm định (<?php echo $summary['da_hoan_thanh']; ?>)
                </button>
                <button class="tab-button px-4 py-3 font-semibold text-gray-600 hover:bg-gray-100" data-tab="chua_hoan_thanh">
                    <i class="fas fa-times-circle mr-1"></i> Chưa kiểm định (<?php echo $summary['chua_hoan_thanh']; ?>)
                </button>
            </div>
    <?php endif; ?>
            
            <!-- Tab Content -->
            <?php foreach ($statistics as $status => $items): ?>
                <?php 
                // Bỏ qua truoc_han và sau_han nếu không chọn tháng
                if (empty($summary['selected_quy']) && in_array($status, ['truoc_han', 'sau_han'])) {
                    continue;
                }
                ?>
                <div class="tab-content p-4 <?php echo $status === 'da_hoan_thanh' ? '' : 'hidden'; ?>" id="tab_<?php echo $status; ?>">
                    <?php if (empty($items)): ?>
                        <p class="text-center text-gray-500 py-8">Không có thiết bị nào</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="border px-3 py-2 text-left">STT</th>
                                        <th class="border px-3 py-2 text-left">Tên thiết bị</th>
                                        <th class="border px-3 py-2 text-left">Số máy</th>
                                        <th class="border px-3 py-2 text-left">Loại TB</th>
                                        <th class="border px-3 py-2 text-left">bộ phận</th>
                                        <th class="border px-3 py-2 text-left">Đơn vị thực hiện</th>
                                        <th class="border px-3 py-2 text-left">Tháng kế hoạch</th>
                                        <th class="border px-3 py-2 text-left">Tháng đã kiểm định</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $index => $item): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="border px-3 py-2"><?php echo $index + 1; ?></td>
                                            <td class="border px-3 py-2"><?php echo htmlspecialchars($item['tenthietbi']); ?></td>
                                            <td class="border px-3 py-2"><?php echo htmlspecialchars($item['somay'] ?? ''); ?></td>
                                            <td class="border px-3 py-2"><?php echo htmlspecialchars($item['loaitb'] ?? ''); ?></td>
                                            <td class="border px-3 py-2"><?php echo htmlspecialchars($item['bophansh'] ?? ''); ?></td>
                                            <td class="border px-3 py-2"><?php echo htmlspecialchars($item['donvi_thuchien'] ?? ''); ?></td>
                                            <td class="border px-3 py-2">
                                                <?php 
                                                $allPlanned = array_merge(
                                                    $item['planned_months'] ? explode(',', $item['planned_months']) : [],
                                                    $item['planned_months_dot2'] ? explode(',', $item['planned_months_dot2']) : []
                                                );
                                                $allPlanned = array_unique($allPlanned);
                                                sort($allPlanned);
                                                echo implode(', ', $allPlanned);
                                                ?>
                                            </td>
                                            <td class="border px-3 py-2">
                                                <?php 
                                                $inspected = $item['inspected_months'] ? $item['inspected_months'] : '-';
                                                echo htmlspecialchars($inspected);
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Tab switching
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        const tabName = this.dataset.tab;
        
        // Update button styles
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('border-b-2', 'border-green-500', 'text-green-700', 'bg-white');
            btn.classList.add('text-gray-600');
        });
        this.classList.add('border-b-2', 'border-green-500', 'text-green-700', 'bg-white');
        this.classList.remove('text-gray-600');
        
        // Show/hide content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        document.getElementById('tab_' + tabName).classList.remove('hidden');
    });
});

// Pie Chart
const ctxPie = document.getElementById('statusPieChart').getContext('2d');
<?php if (!empty($summary['selected_quy'])): ?>
// 4 states when quarter is selected
new Chart(ctxPie, {
    type: 'pie',
    data: {
        labels: ['Đúng hạn', 'Trước hạn', 'Sau hạn', 'Chưa hoàn thành'],
        datasets: [{
            data: [
                <?php echo $summary['da_hoan_thanh']; ?>,
                <?php echo $summary['truoc_han']; ?>,
                <?php echo $summary['sau_han']; ?>,
                <?php echo $summary['chua_hoan_thanh']; ?>
            ],
            backgroundColor: [
                'rgba(34, 197, 94, 0.8)',  // green
                'rgba(20, 184, 166, 0.8)', // teal
                'rgba(8, 145, 178, 0.8)',  // cyan
                'rgba(239, 68, 68, 0.8)'   // red
            ],
            borderColor: [
                'rgba(34, 197, 94, 1)',
                'rgba(20, 184, 166, 1)',
                'rgba(8, 145, 178, 1)',
                'rgba(239, 68, 68, 1)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
<?php else: ?>
// 2 states when no month selected
new Chart(ctxPie, {
    type: 'pie',
    data: {
        labels: ['Đã kiểm định', 'Chưa kiểm định'],
        datasets: [{
            data: [
                <?php echo $summary['da_hoan_thanh']; ?>,
                <?php echo $summary['chua_hoan_thanh']; ?>
            ],
            backgroundColor: [
                'rgba(34, 197, 94, 0.8)',  // green
                'rgba(239, 68, 68, 0.8)'   // red
            ],
            borderColor: [
                'rgba(34, 197, 94, 1)',
                'rgba(239, 68, 68, 1)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
