<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
require_once __DIR__ . '/../../includes/permissions.php';
$title = 'Kế hoạch Bảo dưỡng thiết bị định kỳ';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-tools mr-2 text-blue-600"></i> Kế hoạch Bảo dưỡng thiết bị định kỳ
        </h1>
        <div class="flex gap-2">
            <a href="kehoachbaoduongdinhky.php?action=thongke&nam=<?php echo $nam; ?>" 
               class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-chart-bar mr-1"></i> Thống kê
            </a>
            
            <?php if (false && hasPermission('kehoachbaoduong.create')): ?>
            <a href="kehoachbaoduongdinhky.php?action=import" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-file-import mr-1"></i> Import Excel
            </a>
            <?php endif; ?>
            
            <?php if (!empty($items)): ?>
            <?php 
            $exportUrl = "kehoachbaoduongdinhky.php?action=exportExcel&nam=" . $nam;
            if (!empty($search)) $exportUrl .= "&search=" . urlencode($search);
            if (isset($qui) && $qui > 0) $exportUrl .= "&qui=" . $qui;            if (!empty($nhomsc)) $exportUrl .= "&nhomsc=" . urlencode($nhomsc);            if (!empty($trangthai)) $exportUrl .= "&trangthai=" . urlencode($trangthai);
            if (!empty($sapxep)) $exportUrl .= "&sapxep=" . urlencode($sapxep);
            if (!empty($thietbi_id_filter)) $exportUrl .= "&thietbi_id_filter=" . urlencode($thietbi_id_filter);
            ?>
            <a href="<?php echo $exportUrl; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-file-excel mr-1"></i> Xuất Excel
            </a>
            <?php endif; ?>
            
            <?php if (false): ?>
            <a href="download_template_bao_duong.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-download mr-1"></i> Tải mẫu Excel
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6">
        <?php if (isset($_GET['thietbi_id']) && $_GET['thietbi_id'] > 0): ?>
            <input type="hidden" name="thietbi_id" value="<?php echo (int)$_GET['thietbi_id']; ?>">
            <div class="mb-4 p-3 bg-teal-50 border border-teal-300 rounded flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-filter text-teal-600 mr-2"></i>
                    <span class="text-teal-800 font-semibold">Đang lọc theo thiết bị ID: <?php echo (int)$_GET['thietbi_id']; ?></span>
                </div>
                <a href="kehoachbaoduongdinhky.php?nam=<?php echo $nam; ?>" 
                   class="text-teal-600 hover:text-teal-800 font-semibold">
                    <i class="fas fa-times mr-1"></i>Xóa bộ lọc
                </a>
            </div>
        <?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-8 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Năm:</label>
                <select name="nam" class="w-full border rounded px-3 py-2" onchange="this.form.submit()">
                    <option value="2026" <?php echo $nam == 2026 ? 'selected' : ''; ?>>2026<?php echo in_array(2026, $years) ? ' (có dữ liệu)' : ''; ?></option>
                    <option value="2027" <?php echo $nam == 2027 ? 'selected' : ''; ?>>2027<?php echo in_array(2027, $years) ? ' (có dữ liệu)' : ''; ?></option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Quý:</label>
                <select name="qui" class="w-full border rounded px-3 py-2" onchange="this.form.submit()">
                    <option value="0" <?php echo !isset($qui) || $qui == 0 ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="1" <?php echo isset($qui) && $qui == 1 ? 'selected' : ''; ?>>Quý 1</option>
                    <option value="2" <?php echo isset($qui) && $qui == 2 ? 'selected' : ''; ?>>Quý 2</option>
                    <option value="3" <?php echo isset($qui) && $qui == 3 ? 'selected' : ''; ?>>Quý 3</option>
                    <option value="4" <?php echo isset($qui) && $qui == 4 ? 'selected' : ''; ?>>Quý 4</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Nhóm máy:</label>
                <select name="nhomsc" class="w-full border rounded px-3 py-2" onchange="this.form.submit()">
                    <option value="" <?php echo !isset($nhomsc) || $nhomsc == '' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="RDNGA" <?php echo isset($nhomsc) && $nhomsc == 'RDNGA' ? 'selected' : ''; ?>>RDNGA</option>
                    <option value="CNC" <?php echo isset($nhomsc) && $nhomsc == 'CNC' ? 'selected' : ''; ?>>CNC</option>
                    <option value="KTKT" <?php echo isset($nhomsc) && $nhomsc == 'KTKT' ? 'selected' : ''; ?>>KTKT</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Trạng thái:</label>
                <select name="trangthai" class="w-full border rounded px-3 py-2" onchange="this.form.submit()">
                    <option value="" <?php echo !isset($trangthai) || $trangthai == '' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="hoantat" <?php echo isset($trangthai) && $trangthai == 'hoantat' ? 'selected' : ''; ?>>Đã hoàn thành</option>
                    <option value="chuahoantat" <?php echo isset($trangthai) && $trangthai == 'chuahoantat' ? 'selected' : ''; ?>>Chưa hoàn thành</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Thiết bị ID:</label>
                <select name="thietbi_id_filter" class="w-full border rounded px-3 py-2" onchange="this.form.submit()">
                    <option value="" <?php echo !isset($thietbi_id_filter) || $thietbi_id_filter == '' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="null" <?php echo isset($thietbi_id_filter) && $thietbi_id_filter == 'null' ? 'selected' : ''; ?>>Chưa có ID</option>
                    <option value="notnull" <?php echo isset($thietbi_id_filter) && $thietbi_id_filter == 'notnull' ? 'selected' : ''; ?>>Đã có ID</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Sắp xếp theo:</label>
                <select name="sapxep" class="w-full border rounded px-3 py-2" onchange="this.form.submit()">
                    <option value="" <?php echo !isset($sapxep) || $sapxep == '' ? 'selected' : ''; ?>>Mặc định</option>
                    <option value="qui_tangdan" <?php echo isset($sapxep) && $sapxep == 'qui_tangdan' ? 'selected' : ''; ?>>Quý tăng dần</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Tìm kiếm:</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Tên thiết bị hoặc số S/N..." class="w-full border rounded px-3 py-2">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mr-2">
                    <i class="fas fa-search mr-1"></i> Tìm
                </button>
                <a href="kehoachbaoduongdinhky.php?nam=<?php echo $nam; ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-redo mr-1"></i> Reset
                </a>
            </div>
        </div>
    </form>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-600 font-medium">Tổng thiết bị</p>
                    <p class="text-2xl font-bold text-blue-800"><?php echo number_format($total); ?></p>
                </div>
                <i class="fas fa-tools text-3xl text-blue-300"></i>
            </div>
        </div>
        
        <?php
        $qui1Count = count(array_filter($items, fn($i) => !empty($i['qui_1'])));
        $qui2Count = count(array_filter($items, fn($i) => !empty($i['qui_2'])));
        $qui3Count = count(array_filter($items, fn($i) => !empty($i['qui_3'])));
        $qui4Count = count(array_filter($items, fn($i) => !empty($i['qui_4'])));
        ?>
        
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-600 font-medium">Quí 1</p>
                    <p class="text-2xl font-bold text-green-800"><?php echo $qui1Count; ?></p>
                </div>
                <i class="fas fa-calendar-check text-3xl text-green-300"></i>
            </div>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-yellow-600 font-medium">Quí 2</p>
                    <p class="text-2xl font-bold text-yellow-800"><?php echo $qui2Count; ?></p>
                </div>
                <i class="fas fa-calendar-check text-3xl text-yellow-300"></i>
            </div>
        </div>
        
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-orange-600 font-medium">Quí 3 & 4</p>
                    <p class="text-2xl font-bold text-orange-800"><?php echo ($qui3Count + $qui4Count); ?></p>
                </div>
                <i class="fas fa-calendar-check text-3xl text-orange-300"></i>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID Thiết bị</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên thiết bị</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số S/N</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhóm SC</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase border-l border-r border-gray-300">Quí 1</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase border-r border-gray-300">Quí 2</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase border-r border-gray-300">Quí 3</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase border-r border-gray-300">Quí 4</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase border-l border-r border-gray-300">Đơn vị làm chính</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase border-r border-gray-300">Đơn vị làm phụ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ghi chú</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase bg-blue-50">Trạng thái hoàn thành</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $index => $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm"><?php echo $index + 1; ?></td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-700">
                                    <?php echo htmlspecialchars($item['id']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php if (!empty($item['thietbi_id'])): ?>
                                    <a href="/iso2/thietbi.php?action=view&id=<?php echo $item['thietbi_id']; ?>" 
                                       class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors duration-150"
                                       title="Xem chi tiết thiết bị">
                                        <?php echo htmlspecialchars($item['thietbi_id']); ?>
                                    </a>
                                <?php else: ?>
                                    <?php if (hasPermission('kehoachbaoduong.edit')): ?>
                                        <input type="number" 
                                               class="thietbi-id-input w-20 border border-gray-300 rounded px-2 py-1 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                                               data-kehoach-id="<?php echo $item['id']; ?>"
                                               placeholder="Nhập ID"
                                               title="Nhập ID thiết bị">
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-900">
                                    <?php echo htmlspecialchars($item['ten_thietbi']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-blue-700 text-sm">
                                    <?php echo htmlspecialchars($item['so_serial']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <?php echo htmlspecialchars($item['nhomsc'] ?? ''); ?>
                            </td>
                            <td class="px-4 py-3 text-center border-l border-r border-gray-300 <?php echo strtoupper(trim($item['qui_1'] ?? '')) === 'TO' ? 'bg-green-100' : ''; ?>">
                                <?php if (!empty($item['qui_1_hoantat'])): ?>
                                    <i class="fas fa-check text-blue-600 text-lg"></i>
                                <?php elseif (!empty($item['qui_1'])): ?>
                                    <?php 
                                    $isTOQ1 = strtoupper(trim($item['qui_1'])) === 'TO';
                                    ?>
                                    <?php if (!$isTOQ1): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-800">
                                            <?php echo htmlspecialchars($item['qui_1']); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-300">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center border-r border-gray-300 <?php echo strtoupper(trim($item['qui_2'] ?? '')) === 'TO' ? 'bg-green-100' : ''; ?>">
                                <?php if (!empty($item['qui_2_hoantat'])): ?>
                                    <i class="fas fa-check text-blue-600 text-lg"></i>
                                <?php elseif (!empty($item['qui_2'])): ?>
                                    <?php 
                                    $isTOQ2 = strtoupper(trim($item['qui_2'])) === 'TO';
                                    ?>
                                    <?php if (!$isTOQ2): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-800">
                                            <?php echo htmlspecialchars($item['qui_2']); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-300">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center border-r border-gray-300 <?php echo strtoupper(trim($item['qui_3'] ?? '')) === 'TO' ? 'bg-green-100' : ''; ?>">
                                <?php if (!empty($item['qui_3_hoantat'])): ?>
                                    <i class="fas fa-check text-blue-600 text-lg"></i>
                                <?php elseif (!empty($item['qui_3'])): ?>
                                    <?php 
                                    $isTOQ3 = strtoupper(trim($item['qui_3'])) === 'TO';
                                    ?>
                                    <?php if (!$isTOQ3): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-800">
                                            <?php echo htmlspecialchars($item['qui_3']); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-300">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center border-r border-gray-300 <?php echo strtoupper(trim($item['qui_4'] ?? '')) === 'TO' ? 'bg-green-100' : ''; ?>">
                                <?php if (!empty($item['qui_4_hoantat'])): ?>
                                    <i class="fas fa-check text-blue-600 text-lg"></i>
                                <?php elseif (!empty($item['qui_4'])): ?>
                                    <?php 
                                    $isTOQ4 = strtoupper(trim($item['qui_4'])) === 'TO';
                                    ?>
                                    <?php if (!$isTOQ4): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-800">
                                            <?php echo htmlspecialchars($item['qui_4']); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-300">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 border-l border-r border-gray-300">
                                <?php if (hasPermission('kehoachbaoduong.edit')): ?>
                                    <div class="donvi-chinh-container" data-kehoach-id="<?php echo $item['id']; ?>">
                                        <!-- Chế độ xem (mặc định) -->
                                        <div class="donvi-chinh-view flex items-center justify-between">
                                            <span class="donvi-chinh-text">
                                                <?php 
                                                $donviChinh = $item['donvi_lam_chinh'] ?? '';
                                                if (!empty($donviChinh)) {
                                                    $colorClass = '';
                                                    if ($donviChinh === 'KTKT') {
                                                        $colorClass = 'bg-blue-100 text-blue-800 px-2 py-1 rounded';
                                                    } elseif ($donviChinh === 'ĐVLTH') {
                                                        $colorClass = 'bg-green-100 text-green-800 px-2 py-1 rounded';
                                                    } elseif ($donviChinh === 'CNM') {
                                                        $colorClass = 'bg-orange-100 text-orange-800 px-2 py-1 rounded';
                                                    } elseif ($donviChinh === 'RDNGA') {
                                                        $colorClass = 'bg-purple-100 text-purple-800 px-2 py-1 rounded';
                                                    }
                                                    echo '<span class="' . $colorClass . '">' . htmlspecialchars($donviChinh) . '</span>';
                                                } else {
                                                    echo '<span class="text-gray-400">--</span>';
                                                }
                                                ?>
                                            </span>
                                            <button type="button" class="donvi-chinh-edit-btn ml-2 text-blue-600 hover:text-blue-800" title="Sửa">
                                                <i class="fas fa-edit text-sm"></i>
                                            </button>
                                        </div>
                                        <!-- Chế độ sửa (ẩn) -->
                                        <div class="donvi-chinh-edit hidden">
                                            <select class="donvi-chinh-select w-full border border-gray-300 rounded px-2 py-1 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                                <option value="" <?php echo empty($item['donvi_lam_chinh']) ? 'selected' : ''; ?>>--</option>
                                                <option value="KTKT" class="bg-blue-50" <?php echo ($item['donvi_lam_chinh'] ?? '') === 'KTKT' ? 'selected' : ''; ?>>⬢ KTKT</option>
                                                <option value="ĐVLTH" class="bg-green-50" <?php echo ($item['donvi_lam_chinh'] ?? '') === 'ĐVLTH' ? 'selected' : ''; ?>>⬢ ĐVLTH</option>
                                                <option value="CNM" class="bg-orange-50" <?php echo ($item['donvi_lam_chinh'] ?? '') === 'CNM' ? 'selected' : ''; ?>>⬢ CNM</option>
                                                <option value="RDNGA" class="bg-purple-50" <?php echo ($item['donvi_lam_chinh'] ?? '') === 'RDNGA' ? 'selected' : ''; ?>>⬢ RDNGA</option>
                                            </select>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php 
                                    $donviChinh = $item['donvi_lam_chinh'] ?? '';
                                    if (!empty($donviChinh)) {
                                        $colorClass = '';
                                        if ($donviChinh === 'KTKT') {
                                            $colorClass = 'bg-blue-100 text-blue-800 px-2 py-1 rounded';
                                        } elseif ($donviChinh === 'ĐVLTH') {
                                            $colorClass = 'bg-green-100 text-green-800 px-2 py-1 rounded';
                                        } elseif ($donviChinh === 'CNM') {
                                            $colorClass = 'bg-orange-100 text-orange-800 px-2 py-1 rounded';
                                        } elseif ($donviChinh === 'RDNGA') {
                                            $colorClass = 'bg-purple-100 text-purple-800 px-2 py-1 rounded';
                                        }
                                        echo '<span class="' . $colorClass . '">' . htmlspecialchars($donviChinh) . '</span>';
                                    }
                                    ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 border-r border-gray-300">
                                <?php if (hasPermission('kehoachbaoduong.edit')): ?>
                                    <div class="donvi-phu-container" data-kehoach-id="<?php echo $item['id']; ?>">
                                        <!-- Chế độ xem (mặc định) -->
                                        <div class="donvi-phu-view flex items-center justify-between">
                                            <span class="donvi-phu-text">
                                                <?php 
                                                $donviPhu = $item['donvi_lam_phu'] ?? '';
                                                if (!empty($donviPhu)) {
                                                    $colorClass = '';
                                                    if ($donviPhu === 'KTKT') {
                                                        $colorClass = 'bg-blue-100 text-blue-800 px-2 py-1 rounded';
                                                    } elseif ($donviPhu === 'ĐVLTH') {
                                                        $colorClass = 'bg-green-100 text-green-800 px-2 py-1 rounded';
                                                    } elseif ($donviPhu === 'CNM') {
                                                        $colorClass = 'bg-orange-100 text-orange-800 px-2 py-1 rounded';
                                                    } elseif ($donviPhu === 'RDNGA') {
                                                        $colorClass = 'bg-purple-100 text-purple-800 px-2 py-1 rounded';
                                                    }
                                                    echo '<span class="' . $colorClass . '">' . htmlspecialchars($donviPhu) . '</span>';
                                                } else {
                                                    echo '<span class="text-gray-400">--</span>';
                                                }
                                                ?>
                                            </span>
                                            <button type="button" class="donvi-phu-edit-btn ml-2 text-blue-600 hover:text-blue-800" title="Sửa">
                                                <i class="fas fa-edit text-sm"></i>
                                            </button>
                                        </div>
                                        <!-- Chế độ sửa (ẩn) -->
                                        <div class="donvi-phu-edit hidden">
                                            <select class="donvi-phu-select w-full border border-gray-300 rounded px-2 py-1 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                                <option value="" <?php echo empty($item['donvi_lam_phu']) ? 'selected' : ''; ?>>--</option>
                                                <option value="KTKT" class="bg-blue-50" <?php echo ($item['donvi_lam_phu'] ?? '') === 'KTKT' ? 'selected' : ''; ?>>⬢ KTKT</option>
                                                <option value="ĐVLTH" class="bg-green-50" <?php echo ($item['donvi_lam_phu'] ?? '') === 'ĐVLTH' ? 'selected' : ''; ?>>⬢ ĐVLTH</option>
                                                <option value="CNM" class="bg-orange-50" <?php echo ($item['donvi_lam_phu'] ?? '') === 'CNM' ? 'selected' : ''; ?>>⬢ CNM</option>
                                                <option value="RDNGA" class="bg-purple-50" <?php echo ($item['donvi_lam_phu'] ?? '') === 'RDNGA' ? 'selected' : ''; ?>>⬢ RDNGA</option>
                                            </select>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php 
                                    $donviPhu = $item['donvi_lam_phu'] ?? '';
                                    if (!empty($donviPhu)) {
                                        $colorClass = '';
                                        if ($donviPhu === 'KTKT') {
                                            $colorClass = 'bg-blue-100 text-blue-800 px-2 py-1 rounded';
                                        } elseif ($donviPhu === 'ĐVLTH') {
                                            $colorClass = 'bg-green-100 text-green-800 px-2 py-1 rounded';
                                        } elseif ($donviPhu === 'CNM') {
                                            $colorClass = 'bg-orange-100 text-orange-800 px-2 py-1 rounded';
                                        } elseif ($donviPhu === 'RDNGA') {
                                            $colorClass = 'bg-purple-100 text-purple-800 px-2 py-1 rounded';
                                        }
                                        echo '<span class="' . $colorClass . '">' . htmlspecialchars($donviPhu) . '</span>';
                                    }
                                    ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <?php echo htmlspecialchars($item['ghi_chu']); ?>
                            </td>
                            <td class="px-4 py-3 text-center bg-blue-50">
                                <?php if (hasPermission('kehoachbaoduong.edit')): ?>
                                    <?php
                                    // Xác định quý nào đã hoàn thành (chỉ 1 quý duy nhất)
                                    $selectedQui = '0';
                                    if (!empty($item['qui_1_hoantat']) && empty($item['qui_2_hoantat']) && empty($item['qui_3_hoantat']) && empty($item['qui_4_hoantat'])) {
                                        $selectedQui = '1';
                                    } elseif (empty($item['qui_1_hoantat']) && !empty($item['qui_2_hoantat']) && empty($item['qui_3_hoantat']) && empty($item['qui_4_hoantat'])) {
                                        $selectedQui = '2';
                                    } elseif (empty($item['qui_1_hoantat']) && empty($item['qui_2_hoantat']) && !empty($item['qui_3_hoantat']) && empty($item['qui_4_hoantat'])) {
                                        $selectedQui = '3';
                                    } elseif (empty($item['qui_1_hoantat']) && empty($item['qui_2_hoantat']) && empty($item['qui_3_hoantat']) && !empty($item['qui_4_hoantat'])) {
                                        $selectedQui = '4';
                                    }
                                    ?>
                                    <select class="hoantat-select border border-gray-300 rounded px-2 py-1 text-sm" 
                                            data-id="<?php echo $item['id']; ?>">
                                        <option value="0" <?php echo $selectedQui === '0' ? 'selected' : ''; ?>>Chưa hoàn thành</option>
                                        <option value="1" <?php echo $selectedQui === '1' ? 'selected' : ''; ?>>Quý 1</option>
                                        <option value="2" <?php echo $selectedQui === '2' ? 'selected' : ''; ?>>Quý 2</option>
                                        <option value="3" <?php echo $selectedQui === '3' ? 'selected' : ''; ?>>Quý 3</option>
                                        <option value="4" <?php echo $selectedQui === '4' ? 'selected' : ''; ?>>Quý 4</option>
                                    </select>
                                <?php else: ?>
                                    <?php
                                    // Hiển thị các quý đã hoàn thành
                                    $completed = [];
                                    if (!empty($item['qui_1_hoantat'])) $completed[] = 'Q1';
                                    if (!empty($item['qui_2_hoantat'])) $completed[] = 'Q2';
                                    if (!empty($item['qui_3_hoantat'])) $completed[] = 'Q3';
                                    if (!empty($item['qui_4_hoantat'])) $completed[] = 'Q4';
                                    ?>
                                    <?php if (!empty($completed)): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-600 text-white">
                                            <i class="fas fa-check mr-1"></i>
                                            <?php echo implode(', ', $completed); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">Chưa hoàn thành</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>Chưa có kế hoạch bảo dưỡng cho năm <?php echo $nam; ?></p>
                                <?php if (hasPermission('kehoachbaoduong.create')): ?>
                                <p class="mt-2">
                                    <a href="kehoachbaoduongdinhky.php?action=import" class="text-blue-600 hover:underline">
                                        <i class="fas fa-plus mr-1"></i> Import dữ liệu từ Excel
                                    </a>
                                </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete button -->
    <?php if (!empty($items) && hasPermission('kehoachbaoduong.delete') && $nam != 2026): ?>
    <div class="mt-4 text-right">
        <button onclick="confirmDelete(<?php echo $nam; ?>)" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
            <i class="fas fa-trash mr-1"></i> Xóa toàn bộ kế hoạch năm <?php echo $nam; ?>
        </button>
    </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(nam) {
    if (confirm('Bạn có chắc chắn muốn xóa toàn bộ kế hoạch bảo dưỡng năm ' + nam + '?\n\nHành động này không thể hoàn tác!')) {
        window.location.href = 'kehoachbaoduongdinhky.php?action=delete&nam=' + nam;
    }
}

// Xử lý dropdown hoàn thành (chọn 1 quý hoặc không có quý nào)
document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('.hoantat-select');
    
    selects.forEach(select => {
        select.addEventListener('change', function() {
            const id = this.dataset.id;
            const selectedQui = this.value; // '0', '1', '2', '3', hoặc '4'
            
            // Disable select và hiển thị loading
            this.disabled = true;
            const originalBg = this.style.backgroundColor;
            this.style.backgroundColor = '#e5e7eb';
            
            // Chuẩn bị dữ liệu: chỉ quý được chọn = 1, các quý khác = 0
            const quarters = {
                1: selectedQui === '1' ? 1 : 0,
                2: selectedQui === '2' ? 1 : 0,
                3: selectedQui === '3' ? 1 : 0,
                4: selectedQui === '4' ? 1 : 0
            };
            
            // Gửi AJAX request để cập nhật tất cả quý
            fetch('kehoachbaoduongdinhky.php?action=updateMultipleHoanTat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: parseInt(id),
                    quarters: quarters
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload trang để cập nhật màu sắc của ô quý
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi cập nhật trạng thái');
            })
            .finally(() => {
                this.disabled = false;
                this.style.backgroundColor = originalBg;
            });
        });
    });
    
    // Xử lý nhập ID thiết bị
    const thietbiInputs = document.querySelectorAll('.thietbi-id-input');
    
    thietbiInputs.forEach(input => {
        // Lưu khi người dùng nhấn Enter hoặc blur (rời khỏi input)
        input.addEventListener('blur', function() {
            saveThietbiId(this);
        });
        
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveThietbiId(this);
            }
        });
    });
    
    function saveThietbiId(inputElement) {
        const kehoachId = inputElement.dataset.kehoachId;
        const thietbiId = inputElement.value.trim();
        
        // Nếu rỗng thì không làm gì
        if (!thietbiId) {
            return;
        }
        
        // Validate là số
        if (!/^\d+$/.test(thietbiId)) {
            alert('ID thiết bị phải là số nguyên');
            inputElement.value = '';
            return;
        }
        
        // Disable input và hiển thị loading
        inputElement.disabled = true;
        const originalBg = inputElement.style.backgroundColor;
        inputElement.style.backgroundColor = '#fef3c7';
        
        // Gửi AJAX request
        fetch('kehoachbaoduongdinhky.php?action=updateThietbiId', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                kehoach_id: parseInt(kehoachId),
                thietbi_id: parseInt(thietbiId)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload trang để hiển thị link thiết bị
                location.reload();
            } else {
                alert('Lỗi: ' + data.message);
                inputElement.value = '';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi cập nhật ID thiết bị');
            inputElement.value = '';
        })
        .finally(() => {
            inputElement.disabled = false;
            inputElement.style.backgroundColor = originalBg;
        });
    }
    
    // Xử lý nút sửa Đơn vị làm chính
    document.querySelectorAll('.donvi-chinh-edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const container = this.closest('.donvi-chinh-container');
            const viewDiv = container.querySelector('.donvi-chinh-view');
            const editDiv = container.querySelector('.donvi-chinh-edit');
            
            // Ẩn view, hiện edit
            viewDiv.classList.add('hidden');
            editDiv.classList.remove('hidden');
            
            // Focus vào dropdown
            const select = editDiv.querySelector('.donvi-chinh-select');
            select.focus();
        });
    });
    
    // Xử lý nút sửa Đơn vị làm phụ
    document.querySelectorAll('.donvi-phu-edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const container = this.closest('.donvi-phu-container');
            const viewDiv = container.querySelector('.donvi-phu-view');
            const editDiv = container.querySelector('.donvi-phu-edit');
            
            // Ẩn view, hiện edit
            viewDiv.classList.add('hidden');
            editDiv.classList.remove('hidden');
            
            // Focus vào dropdown
            const select = editDiv.querySelector('.donvi-phu-select');
            select.focus();
        });
    });
    
    // Xử lý dropdown đơn vị làm chính
    document.querySelectorAll('.donvi-chinh-select').forEach(select => {
        select.addEventListener('change', function() {
            const container = this.closest('.donvi-chinh-container');
            saveDonViFields(container);
        });
        
        // Ấn Escape để hủy
        select.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const container = this.closest('.donvi-chinh-container');
                cancelDonViEdit(container, 'chinh');
            }
        });
        
        // Click ra ngoài (blur) để quay lại view mode
        select.addEventListener('blur', function() {
            setTimeout(() => {
                const container = this.closest('.donvi-chinh-container');
                if (container) {
                    const editDiv = container.querySelector('.donvi-chinh-edit');
                    if (!editDiv.classList.contains('hidden')) {
                        cancelDonViEdit(container, 'chinh');
                    }
                }
            }, 200); // Delay nhỏ để change event có thể fire trước
        });
    });
    
    // Xử lý dropdown đơn vị làm phụ
    document.querySelectorAll('.donvi-phu-select').forEach(select => {
        select.addEventListener('change', function() {
            const container = this.closest('.donvi-phu-container');
            saveDonViFields(container);
        });
        
        // Ấn Escape để hủy
        select.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const container = this.closest('.donvi-phu-container');
                cancelDonViEdit(container, 'phu');
            }
        });
        
        // Click ra ngoài (blur) để quay lại view mode
        select.addEventListener('blur', function() {
            setTimeout(() => {
                const container = this.closest('.donvi-phu-container');
                if (container) {
                    const editDiv = container.querySelector('.donvi-phu-edit');
                    if (!editDiv.classList.contains('hidden')) {
                        cancelDonViEdit(container, 'phu');
                    }
                }
            }, 200); // Delay nhỏ để change event có thể fire trước
        });
    });
    
    function cancelDonViEdit(container, type) {
        const viewDiv = container.querySelector(`.donvi-${type}-view`);
        const editDiv = container.querySelector(`.donvi-${type}-edit`);
        
        // Hiển view, ẩn edit
        viewDiv.classList.remove('hidden');
        editDiv.classList.add('hidden');
    }
    
    function saveDonViFields(triggerContainer) {
        const row = triggerContainer.closest('tr');
        const donviChinhContainer = row.querySelector('.donvi-chinh-container');
        const donviPhuContainer = row.querySelector('.donvi-phu-container');
        
        const kehoachId = triggerContainer.dataset.kehoachId;
        const donviLamChinh = donviChinhContainer ? donviChinhContainer.querySelector('.donvi-chinh-select').value : '';
        const donviLamPhu = donviPhuContainer ? donviPhuContainer.querySelector('.donvi-phu-select').value : '';
        
        // Disable selects và hiển thị loading
        const allSelects = row.querySelectorAll('.donvi-chinh-select, .donvi-phu-select');
        const allEditDivs = row.querySelectorAll('.donvi-chinh-edit, .donvi-phu-edit');
        
        allSelects.forEach(sel => {
            sel.disabled = true;
            sel.style.backgroundColor = '#fef3c7';
        });
        
        // Thêm text "Đang lưu..."
        allEditDivs.forEach(div => {
            const existingMsg = div.querySelector('.saving-message');
            if (!existingMsg) {
                const savingMsg = document.createElement('div');
                savingMsg.className = 'saving-message text-xs text-blue-600 mt-1';
                savingMsg.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Đang lưu...';
                div.appendChild(savingMsg);
            }
        });
        
        // Gửi AJAX request
        fetch('kehoachbaoduongdinhky.php?action=updateDonViFields', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                kehoach_id: parseInt(kehoachId),
                donvi_lam_chinh: donviLamChinh,
                donvi_lam_phu: donviLamPhu
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cập nhật text hiển thị
                if (donviChinhContainer) {
                    const textSpan = donviChinhContainer.querySelector('.donvi-chinh-text');
                    let htmlContent = '';
                    if (donviLamChinh) {
                        let colorClass = '';
                        if (donviLamChinh === 'KTKT') {
                            colorClass = 'bg-blue-100 text-blue-800 px-2 py-1 rounded';
                        } else if (donviLamChinh === 'ĐVLTH') {
                            colorClass = 'bg-green-100 text-green-800 px-2 py-1 rounded';
                        } else if (donviLamChinh === 'CNM') {
                            colorClass = 'bg-orange-100 text-orange-800 px-2 py-1 rounded';
                        } else if (donviLamChinh === 'RDNGA') {
                            colorClass = 'bg-purple-100 text-purple-800 px-2 py-1 rounded';
                        }
                        htmlContent = '<span class="' + colorClass + '">' + donviLamChinh + '</span>';
                    } else {
                        htmlContent = '<span class="text-gray-400">--</span>';
                    }
                    textSpan.innerHTML = htmlContent;
                    
                    // Chuyển về chế độ xem
                    donviChinhContainer.querySelector('.donvi-chinh-view').classList.remove('hidden');
                    donviChinhContainer.querySelector('.donvi-chinh-edit').classList.add('hidden');
                }
                
                if (donviPhuContainer) {
                    const textSpan = donviPhuContainer.querySelector('.donvi-phu-text');
                    let htmlContent = '';
                    if (donviLamPhu) {
                        let colorClass = '';
                        if (donviLamPhu === 'KTKT') {
                            colorClass = 'bg-blue-100 text-blue-800 px-2 py-1 rounded';
                        } else if (donviLamPhu === 'ĐVLTH') {
                            colorClass = 'bg-green-100 text-green-800 px-2 py-1 rounded';
                        } else if (donviLamPhu === 'CNM') {
                            colorClass = 'bg-orange-100 text-orange-800 px-2 py-1 rounded';
                        } else if (donviLamPhu === 'RDNGA') {
                            colorClass = 'bg-purple-100 text-purple-800 px-2 py-1 rounded';
                        }
                        htmlContent = '<span class="' + colorClass + '">' + donviLamPhu + '</span>';
                    } else {
                        htmlContent = '<span class="text-gray-400">--</span>';
                    }
                    textSpan.innerHTML = htmlContent;
                    
                    // Chuyển về chế độ xem
                    donviPhuContainer.querySelector('.donvi-phu-view').classList.remove('hidden');
                    donviPhuContainer.querySelector('.donvi-phu-edit').classList.add('hidden');
                }
                
                // Hiệu ứng xanh
                allSelects.forEach(sel => sel.style.backgroundColor = '#d1fae5');
                setTimeout(() => {
                    allSelects.forEach(sel => sel.style.backgroundColor = '');
                }, 500);
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi cập nhật đơn vị');
        })
        .finally(() => {
            // Xóa message "Đang lưu..."
            allEditDivs.forEach(div => {
                const savingMsg = div.querySelector('.saving-message');
                if (savingMsg) {
                    savingMsg.remove();
                }
            });
            
            allSelects.forEach(sel => sel.disabled = false);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
