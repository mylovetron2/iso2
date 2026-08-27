<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Hồ sơ Sửa chữa Bảo dưỡng';
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-folder-open mr-2"></i> Hồ sơ Sửa chữa Bảo dưỡng
    </h1>
    
    <!-- Thống kê -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-2 md:gap-4 mb-4 md:mb-6">
        <div class="bg-blue-100 rounded p-3 md:p-4 text-center" style="display: none;">
            <div class="text-xl md:text-2xl font-bold text-blue-700" id="stat-total">-</div>
            <div class="text-xs md:text-sm text-gray-600">Tổng số</div>
        </div>
        <div class="bg-yellow-100 rounded p-3 md:p-4 text-center">
            <div class="text-xl md:text-2xl font-bold text-yellow-700" id="stat-chuath">-</div>
            <div class="text-xs md:text-sm text-gray-600">Chưa thực hiện</div>
        </div>
        <div class="bg-orange-100 rounded p-3 md:p-4 text-center">
            <div class="text-xl md:text-2xl font-bold text-orange-700" id="stat-danglam">-</div>
            <div class="text-xs md:text-sm text-gray-600">Đang làm</div>
        </div>
        <div class="bg-purple-100 rounded p-3 md:p-4 text-center">
            <div class="text-xl md:text-2xl font-bold text-purple-700" id="stat-chuabg">-</div>
            <div class="text-xs md:text-sm text-gray-600">Chưa bàn giao</div>
        </div>
        <div class="bg-green-100 rounded p-3 md:p-4 text-center" style="display: none;">
            <div class="text-xl md:text-2xl font-bold text-green-700" id="stat-dabg">-</div>
            <div class="text-xs md:text-sm text-gray-600">Đã bàn giao</div>
        </div>
    </div>

    <!-- Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php
            switch ($_GET['success']) {
                case 'created': echo 'Tạo hồ sơ thành công!'; break;
                case 'updated': echo 'Cập nhật hồ sơ thành công!'; break;
                case 'deleted': echo 'Xóa hồ sơ thành công!'; break;
            }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Filter & Search -->
    <form method="get" class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mb-2">
            <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                   placeholder="Tìm phiếu, mã VT, số máy, đơn vị..." 
                   class="border rounded px-3 py-2 text-sm md:text-base">
            
            <select name="madv" class="border rounded px-3 py-2 text-sm md:text-base" onchange="this.form.submit()">
                <option value="">Tất cả đơn vị</option>
                <?php foreach ($donViList as $dv): ?>
                    <option value="<?php echo htmlspecialchars($dv['madv']); ?>" 
                            <?php echo (isset($_GET['madv']) && $_GET['madv'] === $dv['madv']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dv['tendv']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="trangthai" class="border rounded px-3 py-2 text-sm md:text-base" onchange="this.form.submit()">
                <option value="">Tất cả trạng thái</option>
                <option value="chuath" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'chuath') ? 'selected' : ''; ?>>Chưa thực hiện</option>
                <option value="danglam" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'danglam') ? 'selected' : ''; ?>>Đang làm</option>
                <option value="hoanthanh" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'hoanthanh') ? 'selected' : ''; ?>>Hoàn thành</option>
                <option value="chuabg" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'chuabg') ? 'selected' : ''; ?>>Chưa bàn giao</option>
                <option value="dabg" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'dabg') ? 'selected' : ''; ?>>Đã bàn giao</option>
                <option value="TTKTDB" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'TTKTDB') ? 'selected' : ''; ?>>TTKTDB</option>
                <option value="tamdung" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'tamdung') ? 'selected' : ''; ?>>Tạm dừng</option>
            </select>

            <select name="cv" class="border rounded px-3 py-2 text-sm md:text-base" onchange="this.form.submit()">
                <option value="">Tất cả CV</option>
                <option value="SC" <?php echo (isset($_GET['cv']) && $_GET['cv'] === 'SC') ? 'selected' : ''; ?>>SC</option>
                <option value="BD" <?php echo (isset($_GET['cv']) && $_GET['cv'] === 'BD') ? 'selected' : ''; ?>>BD</option>
                <option value="KT" <?php echo (isset($_GET['cv']) && $_GET['cv'] === 'KT') ? 'selected' : ''; ?>>KT</option>
                <option value="BDDK" <?php echo (isset($_GET['cv']) && $_GET['cv'] === 'BDDK') ? 'selected' : ''; ?>>BDDK</option>
            </select>

            <select name="nhomsc" class="border rounded px-3 py-2 text-sm md:text-base" onchange="this.form.submit()">
                <option value="">Tất cả nhóm</option>
                <option value="RDNGA" <?php echo (isset($_GET['nhomsc']) && $_GET['nhomsc'] === 'RDNGA') ? 'selected' : ''; ?>>RDNGA</option>
                <option value="CNC" <?php echo (isset($_GET['nhomsc']) && $_GET['nhomsc'] === 'CNC') ? 'selected' : ''; ?>>CNC</option>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-2">
            <div class="border rounded p-2 bg-gray-50">
                <div class="text-xs font-semibold text-gray-700 mb-1">Ngày YC</div>
                <div class="flex gap-2">
                    <input type="date" name="ngayyc_from" value="<?php echo htmlspecialchars($_GET['ngayyc_from'] ?? ''); ?>" class="border rounded px-2 py-2 text-sm w-full" placeholder="Từ ngày">
                    <input type="date" name="ngayyc_to" value="<?php echo htmlspecialchars($_GET['ngayyc_to'] ?? ''); ?>" class="border rounded px-2 py-2 text-sm w-full" placeholder="Đến ngày">
                </div>
            </div>

            <div class="border rounded p-2 bg-gray-50">
                <div class="text-xs font-semibold text-gray-700 mb-1">Ngày TH</div>
                <div class="flex gap-2">
                    <input type="date" name="ngayth_from" value="<?php echo htmlspecialchars($_GET['ngayth_from'] ?? ''); ?>" class="border rounded px-2 py-2 text-sm w-full" placeholder="Từ ngày">
                    <input type="date" name="ngayth_to" value="<?php echo htmlspecialchars($_GET['ngayth_to'] ?? ''); ?>" class="border rounded px-2 py-2 text-sm w-full" placeholder="Đến ngày">
                </div>
            </div>

            <div class="border rounded p-2 bg-gray-50">
                <div class="text-xs font-semibold text-gray-700 mb-1">Ngày KT</div>
                <div class="flex gap-2">
                    <input type="date" name="ngaykt_from" value="<?php echo htmlspecialchars($_GET['ngaykt_from'] ?? ''); ?>" class="border rounded px-2 py-2 text-sm w-full" placeholder="Từ ngày">
                    <input type="date" name="ngaykt_to" value="<?php echo htmlspecialchars($_GET['ngaykt_to'] ?? ''); ?>" class="border rounded px-2 py-2 text-sm w-full" placeholder="Đến ngày">
                </div>
            </div>
        </div>
        
        <div class="flex flex-wrap gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm md:text-base">
                <i class="fas fa-search mr-1"></i> Lọc
            </button>
            <a href="hososcbd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm md:text-base text-center">
                <i class="fas fa-redo mr-1"></i> Xóa lọc
            </a>
            
            <a href="hososcbd.php?action=exportlistpdf&<?php echo http_build_query([
                'search' => $_GET['search'] ?? '',
                'madv' => $_GET['madv'] ?? '',
                'trangthai' => $_GET['trangthai'] ?? '',
                'cv' => $_GET['cv'] ?? '',
                'nhomsc' => $_GET['nhomsc'] ?? '',
                'ngayyc_from' => $_GET['ngayyc_from'] ?? '',
                'ngayyc_to' => $_GET['ngayyc_to'] ?? '',
                'ngayth_from' => $_GET['ngayth_from'] ?? '',
                'ngayth_to' => $_GET['ngayth_to'] ?? '',
                'ngaykt_from' => $_GET['ngaykt_from'] ?? '',
                'ngaykt_to' => $_GET['ngaykt_to'] ?? '',
            ]); ?>" 
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm md:text-base text-center"
               target="_blank">
                <i class="fas fa-file-pdf mr-1"></i> In PDF
            </a>

            <a href="hososcbd.php?action=exportlistexcel&<?php echo http_build_query([
                'search' => $_GET['search'] ?? '',
                'madv' => $_GET['madv'] ?? '',
                'trangthai' => $_GET['trangthai'] ?? '',
                'cv' => $_GET['cv'] ?? '',
                'nhomsc' => $_GET['nhomsc'] ?? '',
                'ngayyc_from' => $_GET['ngayyc_from'] ?? '',
                'ngayyc_to' => $_GET['ngayyc_to'] ?? '',
                'ngayth_from' => $_GET['ngayth_from'] ?? '',
                'ngayth_to' => $_GET['ngayth_to'] ?? '',
                'ngaykt_from' => $_GET['ngaykt_from'] ?? '',
                'ngaykt_to' => $_GET['ngaykt_to'] ?? '',
            ]); ?>" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm md:text-base text-center"
               target="_blank">
                <i class="fas fa-file-excel mr-1"></i> In Excel
            </a>
            
            <?php if (hasPermission('hososcbd.create')): ?>
            <a href="hososcbd.php?action=create" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm md:text-base text-center ml-auto">
                <i class="fas fa-plus mr-1"></i> Thêm hồ sơ
            </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm">Phiếu</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm">Số hồ sơ</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm">Mã VT</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm hidden md:table-cell">Số máy</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm hidden lg:table-cell">Ngày YC</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm hidden lg:table-cell">Đơn vị</th>
                    <th class="px-2 md:px-4 py-2 border text-center text-xs md:text-sm">CV</th>
                    <th class="px-2 md:px-4 py-2 border text-center text-xs md:text-sm">Định mức</th>
                    <th class="px-2 md:px-4 py-2 border text-center text-xs md:text-sm">BDDK</th>
                    <th class="px-2 md:px-4 py-2 border text-center text-xs md:text-sm hidden xl:table-cell">HC/KĐ</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm">Trạng thái</th>
                    <th class="px-2 md:px-4 py-2 border text-center text-xs md:text-sm">Xem/Sửa</th>
                    <th class="px-2 md:px-4 py-2 border text-center text-xs md:text-sm">Chi tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="12" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Không có hồ sơ nào</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($items as $item): ?>
                <tr class="hover:bg-gray-50" data-stt="<?php echo $item['stt']; ?>">
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm">
                        <a href="/iso2/phieuyeucau.php?action=view&phieu=<?php echo urlencode($item['phieu']); ?>" 
                           class="text-blue-600 hover:text-blue-800 hover:underline font-bold"
                           title="Xem phiếu yêu cầu">
                            <?php echo htmlspecialchars($item['phieu']); ?>
                        </a>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm">
                        <?php if (!empty($item['hoso'])): ?>
                            <a href="/iso2/hososcbd.php?action=view&id=<?php echo $item['stt']; ?>" 
                               class="text-blue-600 hover:text-blue-800 hover:underline font-semibold"
                               title="Xem chi tiết hồ sơ">
                                <?php echo htmlspecialchars($item['hoso']); ?>
                            </a>
                            <?php if (!empty($item['is_tamdung']) && $item['is_tamdung'] == 1): ?>
                                <span class="inline-block ml-2 bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded" 
                                      title="Hồ sơ đang tạm dừng">
                                    <i class="fas fa-pause-circle mr-1"></i>PAUSED
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm">
                        <?php if (!empty($item['thietbi_stt'])): ?>
                            <a href="/iso2/thietbi.php?action=view&id=<?php echo $item['thietbi_stt']; ?>" 
                               class="text-blue-600 hover:text-blue-800 hover:underline" 
                               title="Xem chi tiết thiết bị">
                                <?php echo htmlspecialchars($item['mavt']); ?>
                            </a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($item['mavt']); ?>
                        <?php endif; ?>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden md:table-cell"><?php echo htmlspecialchars($item['somay']); ?></td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden lg:table-cell">
                        <?php 
                        if ($item['ngayyc'] && $item['ngayyc'] != '0000-00-00') {
                            echo '<div class="text-gray-700">' . date('d/m/Y', strtotime($item['ngayyc'])) . '</div>';
                            if ($item['ngayth'] && $item['ngayth'] != '0000-00-00') {
                                echo '<div class="text-orange-500 font-semibold mt-1"><i class="fas fa-play text-xs"></i> ' . date('d/m/Y', strtotime($item['ngayth'])) . '</div>';
                            }
                            if ($item['ngaykt'] && $item['ngaykt'] != '0000-00-00') {
                                echo '<div class="text-green-600 font-semibold mt-1"><i class="fas fa-arrow-right text-xs"></i> ' . date('d/m/Y', strtotime($item['ngaykt'])) . '</div>';
                            }
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden lg:table-cell">
                        <?php echo htmlspecialchars($item['tendv'] ?? $item['madv']); ?>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-center">
                        <?php 
                        $cvValue = $item['cv'] ?? '';
                        $cvDisplay = '';
                        $cvColor = 'bg-gray-100 text-gray-800';
                        
                        switch ($cvValue) {
                            case 'KT':
                                $cvDisplay = 'KT';
                                $cvColor = 'bg-blue-100 text-blue-800';
                                break;
                            case 'BD':
                                $cvDisplay = 'BD';
                                $cvColor = 'bg-green-100 text-green-800';
                                break;
                            case 'SC':
                                $cvDisplay = 'SC';
                                $cvColor = 'bg-red-100 text-red-800';
                                break;
                            case 'BDDK':
                                $cvDisplay = 'BDDK';
                                $cvColor = 'bg-purple-100 text-purple-800';
                                break;
                            default:
                                $cvDisplay = $cvValue ?: '-';
                        }
                        echo '<span class="inline-block ' . $cvColor . ' text-xs font-bold px-2 py-1 rounded">' . htmlspecialchars($cvDisplay) . '</span>';
                        ?>
                    </td>
                    <?php
                    $kpiHourMap = [
                        'kiem_tra' => $item['kpi_kiem_tra_so_gio'] ?? '',
                        'bd_cap_1' => $item['kpi_bd_cap_1_so_gio'] ?? '',
                        'bd_cap_2' => $item['kpi_bd_cap_2_so_gio'] ?? '',
                        'bd_cap_3' => $item['kpi_bd_cap_3_so_gio'] ?? '',
                        'hieu_chuan' => $item['kpi_hieu_chuan_so_gio'] ?? '',
                    ];
                    ?>
                    <td class="px-2 md:px-4 py-2 border text-center text-xs md:text-sm dinhmuc-cell"
                        data-hoso-stt="<?php echo (int)($item['stt'] ?? 0); ?>"
                        data-kpi-stt="<?php echo htmlspecialchars((string)($item['kpi_baoduong_stt'] ?? '')); ?>"
                        data-loai-congviec="<?php echo htmlspecialchars((string)($item['dinh_muc_loai_congviec'] ?? '')); ?>"
                        data-dinhmuc-gio="<?php echo htmlspecialchars((string)($item['dinh_muc_so_gio'] ?? '')); ?>"
                        data-kpi-map='<?php echo json_encode($kpiHourMap, JSON_HEX_TAG | JSON_HEX_AMP); ?>'>
                        <?php
                        $dinhMucSoGio = isset($item['dinh_muc_so_gio']) && $item['dinh_muc_so_gio'] !== null && $item['dinh_muc_so_gio'] !== ''
                            ? (float)$item['dinh_muc_so_gio']
                            : null;
                        $dinhMucLoai = $item['dinh_muc_loai_congviec'] ?? '';
                        if ($dinhMucSoGio !== null) {
                            $labelMap = [
                                'kiem_tra' => 'KT',
                                'bd_cap_1' => 'BD1',
                                'bd_cap_2' => 'BD2',
                                'bd_cap_3' => 'BD3',
                                'hieu_chuan' => 'HC',
                            ];
                            echo '<div class="font-semibold text-blue-700">' . number_format($dinhMucSoGio, 2) . 'h</div>';
                            if ($dinhMucLoai !== '') {
                                echo '<div class="text-[10px] text-gray-500 mt-1">' . htmlspecialchars($labelMap[$dinhMucLoai] ?? strtoupper($dinhMucLoai)) . '</div>';
                            }
                        } else {
                            echo '<span class="text-gray-400">—</span>';
                        }
                        ?>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-center" id="bddk-<?php echo $item['stt']; ?>">
                        <span class="text-gray-300 text-xs"><i class="fas fa-circle-notch fa-spin"></i></span>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-center hidden xl:table-cell" id="hckd-<?php echo $item['stt']; ?>">
                        <span class="text-gray-300 text-xs"><i class="fas fa-circle-notch fa-spin"></i></span>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-center">
                        <?php 
                        $isDaBG = ($item['bg'] == 1);
                        $isHoanThanh = ($item['ngaykt'] && $item['ngaykt'] != '0000-00-00' && !$isDaBG);
                        
                        if ($isDaBG) {
                            echo '<span class="inline-block bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">Đã BG</span>';
                        } elseif ($isHoanThanh) {
                            echo '<span class="inline-block bg-purple-100 text-purple-800 text-xs font-bold px-2 py-1 rounded">Hoàn thành</span>';
                        } elseif ($item['ngayth'] && $item['ngayth'] != '0000-00-00') {
                            echo '<span class="inline-block bg-orange-100 text-orange-800 text-xs font-bold px-2 py-1 rounded">Đang làm</span>';
                        } else {
                            echo '<span class="inline-block bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded">Chưa TH</span>';
                        }
                        
                        // Hiển thị ttktafter cho trạng thái Đã BG hoặc Hoàn thành
                        if (($isDaBG || $isHoanThanh) && !empty($item['ttktafter'])) {
                            $badgeColor = ($item['ttktafter'] === 'TTKTDB') ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800';
                            echo '<div class="mt-1"><span class="inline-block ' . $badgeColor . ' text-xs font-bold px-2 py-1 rounded">' . htmlspecialchars($item['ttktafter']) . '</span></div>';
                        }
                        ?>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-center">
                        <a href="hososcbd.php?action=view&id=<?php echo $item['stt']; ?>" 
                           class="text-blue-600 hover:text-blue-800 mx-1" title="Xem">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php if (hasPermission('hososcbd.edit')): ?>
                        <a href="hososcbd.php?action=edit&id=<?php echo $item['stt']; ?>" 
                           class="text-green-600 hover:text-green-800 mx-1" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('hososcbd.delete')): ?>
                        <form method="POST" action="hososcbd.php?action=delete" 
                              onsubmit="return confirm('Bạn có chắc muốn xóa hồ sơ này?');" 
                              class="inline">
                            <input type="hidden" name="id" value="<?php echo $item['stt']; ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800 mx-1" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-center">
                        <?php if (hasPermission('hososcbd.edit')): ?>
                        <?php
                        // Build URLs with current filter params
                        $currentFilters = [];
                        foreach (['search', 'madv', 'nhomsc', 'trangthai', 'page'] as $key) {
                            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                                $currentFilters[$key] = $_GET[$key];
                            }
                        }
                        $filterQuery = !empty($currentFilters) ? '&' . http_build_query($currentFilters) : '';
                        ?>
                        <div class="flex flex-wrap gap-1 justify-center">
                            <a href="hososcbd_repair_details.php?id=<?php echo $item['stt']; ?><?php echo $filterQuery; ?>" 
                               class="inline-flex items-center bg-orange-500 hover:bg-orange-600 text-white px-2 py-1 rounded text-xs" 
                               title="Thông tin sửa chữa & Thiết bị đo">
                                <i class="fas fa-wrench mr-1"></i>
                                <span class="hidden sm:inline">SC</span>
                            </a>
                            <a href="hososcbd_congviec.php?id=<?php echo $item['stt']; ?><?php echo $filterQuery; ?>" 
                               class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs" 
                               title="Công việc sửa chữa">
                                <i class="fas fa-tasks mr-1"></i>
                                <span class="hidden sm:inline">CV</span>
                            </a>
                            
                            <!-- Nút quản lý tạm dừng (gom tạm dừng/tiếp tục + lịch sử) -->
                            <?php
                            $isTamDung = !empty($item['is_tamdung']) && $item['is_tamdung'] == 1;
                            $btnColor = $isTamDung ? 'bg-green-500 hover:bg-green-600' : 'bg-orange-500 hover:bg-orange-600';
                            $btnIcon = $isTamDung ? 'fa-play-circle' : 'fa-pause-circle';
                            $btnText = $isTamDung ? 'Resume' : 'Pause';
                            ?>
                            <button 
                                onclick="openQuanLyTamDungModal('<?php echo htmlspecialchars($item['hoso'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['mavt'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['somay'], ENT_QUOTES); ?>', <?php echo $isTamDung ? 'true' : 'false'; ?>)" 
                                class="inline-flex items-center <?php echo $btnColor; ?> text-white px-2 py-1 rounded text-xs" 
                                title="Quản lý tạm dừng & lịch sử">
                                <i class="fas <?php echo $btnIcon; ?> mr-1"></i>
                                <span class="hidden sm:inline"><?php echo $btnText; ?></span>
                            </button>
                        </div>
                        <?php endif; ?>
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
                $url = 'hososcbd.php?' . http_build_query($queryParams);
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
                $url = 'hososcbd.php?' . http_build_query($queryParams);
            ?>
                <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">1</a>
                <?php if ($start > 2): ?>
                    <span class="px-3 py-2">...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start; $i <= $end; $i++):
                $queryParams['page'] = $i;
                $url = 'hososcbd.php?' . http_build_query($queryParams);
                $active = ($page === $i);
            ?>
                <a href="<?php echo $url; ?>" 
                   class="px-3 py-2 rounded <?php echo $active ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php
            if ($end < $totalPages):
                if ($end < $totalPages - 1):
            ?>
                    <span class="px-3 py-2">...</span>
                <?php endif; ?>
                <?php
                $queryParams['page'] = $totalPages;
                $url = 'hososcbd.php?' . http_build_query($queryParams);
                ?>
                <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300"><?php echo $totalPages; ?></a>
            <?php endif; ?>
            
            <?php
            if ($page < $totalPages):
                $queryParams['page'] = $page + 1;
                $url = 'hososcbd.php?' . http_build_query($queryParams);
            ?>
                <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
    
    <div class="mt-4 text-sm text-gray-600 text-center">
        Hiển thị <?php echo count($items); ?> / <?php echo $total; ?> hồ sơ
    </div>
</div>

<?php 
// Include modals for pause/resume functionality
require_once __DIR__ . '/partials/tamdung_modals.php'; 
?>

<?php
// Dữ liệu cho AJAX lazy-load cột BDDK & HC/KĐ
$_bddkMeta = [];
foreach ($items as $_item) {
    $_bddkMeta[(int)$_item['stt']] = [
        'thietbi_stt' => (int)($_item['thietbi_stt'] ?? 0),
        'mavt'        => $_item['mavt'] ?? '',
        'ngayyc'      => $_item['ngayyc'] ?? '',
    ];
}
?>
<script>
(function () {
    const metaMap = <?php echo json_encode($_bddkMeta, JSON_HEX_TAG | JSON_HEX_AMP); ?>;

    if (!Object.keys(metaMap).length) return;

    const items = Object.entries(metaMap).map(([stt, d]) => ({
        stt:         parseInt(stt),
        thietbi_stt: d.thietbi_stt,
        ngayyc:      d.ngayyc,
    }));

    function renderBddk(stt, data, meta) {
        const cell = document.getElementById('bddk-' + stt);
        if (!cell) return;
        const quarters = data.bddk_quarters || [];
        if (!meta.thietbi_stt || !quarters.length) {
            cell.innerHTML = '<span class="text-gray-400 text-xs">-</span>';
            return;
        }
        let html = '<a href="/iso2/kehoachbaoduongdinhky.php?thietbi_id=' + meta.thietbi_stt
                 + '" class="inline-flex flex-wrap gap-1" title="Xem kế hoạch bảo dưỡng định kỳ">';
        for (const q of quarters) {
            const name = q.quarter.replace('Q', 'Quý ');
            if (q.completed) {
                html += '<span class="inline-flex items-center bg-green-600 text-white text-xs font-bold px-2 py-1 rounded">'
                      + '<i class="fas fa-check mr-1"></i>' + name + '</span>';
            } else {
                html += '<span class="inline-flex items-center bg-gray-300 text-gray-700 text-xs font-bold px-2 py-1 rounded">'
                      + name + '</span>';
            }
        }
        html += '</a>';
        cell.innerHTML = html;
    }

    function renderHckd(stt, data, meta) {
        const cell = document.getElementById('hckd-' + stt);
        if (!cell) return;
        const planned   = data.planned_months      ? data.planned_months.split(',').map(m => parseInt(m)).filter(Boolean) : [];
        const dot2      = data.planned_months_dot2 ? data.planned_months_dot2.split(',').map(m => parseInt(m)).filter(Boolean) : [];
        const inspected = data.inspected_months    ? data.inspected_months.split(',').map(m => parseInt(m)).filter(Boolean) : [];
        const kehoach   = [...new Set([...planned, ...dot2.filter(m => !planned.includes(m))])];

        let hckdLink = '';
        if (data.thckd_stt) {
            hckdLink = '/iso2/bangcanhbao.php?action=formhoso&mavattu='
                     + encodeURIComponent(data.thckd_mavattu || '') + '&stt=' + data.thckd_stt;
        } else if (meta.mavt) {
            hckdLink = '/iso2/bangcanhbao.php?action=formhoso&mavattu=' + encodeURIComponent(meta.mavt);
        }

        if (!kehoach.length && !inspected.length) {
            cell.innerHTML = '<span class="text-gray-400">-</span>';
            return;
        }

        const all = [...new Set([...kehoach, ...inspected])].sort((a, b) => a - b);
        let badges = '<div class="inline-flex flex-wrap gap-1">';
        for (const m of all) {
            if (inspected.includes(m)) {
                badges += '<span class="inline-flex items-center bg-green-600 text-white text-xs font-bold px-2 py-1 rounded">'
                        + '<i class="fas fa-check mr-1"></i>T' + m + '</span>';
            } else {
                badges += '<span class="inline-flex items-center bg-gray-300 text-gray-700 text-xs font-bold px-2 py-1 rounded">T' + m + '</span>';
            }
        }
        badges += '</div>';
        cell.innerHTML = hckdLink
            ? '<a href="' + hckdLink + '" title="Nhập hồ sơ HC/KĐ" class="block">' + badges + '</a>'
            : badges;
    }

    function showError() {
        for (const stt of Object.keys(metaMap)) {
            const bc = document.getElementById('bddk-' + stt);
            const hc = document.getElementById('hckd-' + stt);
            if (bc) bc.innerHTML = '<span class="text-gray-400 text-xs">-</span>';
            if (hc) hc.innerHTML = '<span class="text-gray-400 text-xs">-</span>';
        }
    }

    function getKpiHourMap(cell) {
        const raw = cell.dataset.kpiMap || '{}';
        try {
            return JSON.parse(raw) || {};
        } catch (e) {
            return {};
        }
    }

    document.addEventListener('click', function (event) {
        const cell = event.target.closest('.dinhmuc-cell');
        if (!cell) return;
        if (event.target.closest('button, select, input')) return;

        if (cell.classList.contains('editing')) return;

        const stt = cell.dataset.hosoStt || '';
        const kpiStt = cell.dataset.kpiStt || '';
        const loai = cell.dataset.loaiCongviec || 'bd_cap_1';
        const gio = cell.dataset.dinhmucGio || '';
        const kpiMap = getKpiHourMap(cell);

        const loaiOptions = [
            ['kiem_tra', 'KT'],
            ['bd_cap_1', 'BD cấp 1'],
            ['bd_cap_2', 'BD cấp 2'],
            ['bd_cap_3', 'BD cấp 3'],
            ['hieu_chuan', 'Hiệu chuẩn'],
        ];

        const selectHtml = loaiOptions.map(([value, label]) => {
            const selected = value === loai ? 'selected' : '';
            return '<option value="' + value + '" ' + selected + '>' + label + '</option>';
        }).join('');

        const defaultHour = (kpiMap[loai] !== undefined && kpiMap[loai] !== null && kpiMap[loai] !== '') ? kpiMap[loai] : gio;

        cell.classList.add('editing');
        cell.innerHTML = '<div class="space-y-2">'
            + '<select name="loai_congviec" class="border rounded px-1 py-1 text-[10px] w-full">' + selectHtml + '</select>'
            + '<input type="number" min="0" step="0.01" name="dinh_muc_gio_thu_cong" value="' + (defaultHour || '') + '" data-auto-sync="1" placeholder="Giờ" class="border rounded px-1 py-1 text-[10px] w-full" />'
            + '<div class="flex gap-1 justify-center">'
            + '<button type="button" class="dinhmuc-save-btn bg-blue-600 text-white px-2 py-1 rounded text-[10px]">Lưu</button>'
            + '<button type="button" class="dinhmuc-cancel-btn bg-gray-300 text-gray-700 px-2 py-1 rounded text-[10px]">Hủy</button>'
            + '</div>'
            + '</div>';

        cell.dataset.kpiStt = kpiStt;
        cell.dataset.hosoStt = stt;
    });

    document.addEventListener('change', function (event) {
        const select = event.target.closest('select[name="loai_congviec"]');
        if (!select) return;

        const cell = select.closest('.dinhmuc-cell');
        if (!cell) return;

        const kpiMap = getKpiHourMap(cell);
        const input = cell.querySelector('input[name="dinh_muc_gio_thu_cong"]');
        if (!input) return;

        const value = kpiMap[select.value];
        const nextValue = (value !== undefined && value !== null && value !== '') ? value : '';
        input.value = nextValue;
        input.dataset.autoSync = '1';
    });

    document.addEventListener('input', function (event) {
        const input = event.target.closest('input[name="dinh_muc_gio_thu_cong"]');
        if (!input) return;
        input.dataset.autoSync = '0';
    });

    document.addEventListener('click', function (event) {
        const btn = event.target.closest('.dinhmuc-save-btn');
        if (!btn) return;

        const cell = btn.closest('.dinhmuc-cell');
        if (!cell) return;

        const stt = cell.dataset.hosoStt || '';
        const select = cell.querySelector('select[name="loai_congviec"]');
        const input = cell.querySelector('input[name="dinh_muc_gio_thu_cong"]');
        const form = new URLSearchParams();
        form.append('id', stt);
        form.append('dinhmuc_action', '1');
        form.append('kpi_baoduong_stt', cell.dataset.kpiStt || '');
        form.append('loai_congviec', select.value);

        const autoSync = input.dataset.autoSync === '1';
        if (autoSync) {
            form.append('dinh_muc_gio_thu_cong', '');
        } else {
            form.append('dinh_muc_gio_thu_cong', input.value.trim());
        }

        fetch('hososcbd_repair_details.php?id=' + encodeURIComponent(stt), {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: form.toString()
        }).then(function() {
            window.location.reload();
        }).catch(function() {
            window.location.reload();
        });
    });

    document.addEventListener('click', function (event) {
        const btn = event.target.closest('.dinhmuc-cancel-btn');
        if (!btn) return;

        const cell = btn.closest('.dinhmuc-cell');
        if (!cell) return;
        window.location.reload();
    });

    fetch('/iso2/hososcbd.php?action=ajax_bddk_hckd', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ items })
    })
    .then(function(r) { return r.ok ? r.json() : Promise.reject(r.status); })
    .then(function(data) {
        for (const [stt, d] of Object.entries(data)) {
            const meta = metaMap[stt] || {};
            renderBddk(stt, d, meta);
            renderHckd(stt, d, meta);
        }
        // Các stt không có dữ liệu vẫn cần xóa spinner
        for (const stt of Object.keys(metaMap)) {
            if (!data[stt]) {
                renderBddk(stt, {bddk_quarters: []}, metaMap[stt]);
                renderHckd(stt, {}, metaMap[stt]);
            }
        }
    })
    .catch(showError);

    // --- Thống kê (AJAX lazy-load) ---
    const nhomscParam = <?php echo json_encode($_GET['nhomsc'] ?? ''); ?>;
    fetch('/iso2/hososcbd.php?action=ajax_stats' + (nhomscParam ? '&nhomsc=' + encodeURIComponent(nhomscParam) : ''))
    .then(function(r) { return r.ok ? r.json() : Promise.reject(r.status); })
    .then(function(s) {
        ['total','chuath','danglam','chuabg','dabg'].forEach(function(k) {
            const el = document.getElementById('stat-' + k);
            if (el && s[k] !== undefined) el.textContent = s[k];
        });
    })
    .catch(function() {
        ['total','chuath','danglam','chuabg','dabg'].forEach(function(k) {
            const el = document.getElementById('stat-' + k);
            if (el) el.textContent = '?';
        });
    });
}());
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
