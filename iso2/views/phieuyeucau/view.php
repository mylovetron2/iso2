<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Chi tiết Phiếu ' . htmlspecialchars($detail['summary']['phieu']);
require_once __DIR__ . '/../layouts/header.php'; 

$summary = $detail['summary'];
$devices = $detail['devices'];
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-file-alt mr-2"></i> 
            Chi tiết Phiếu: <?php echo htmlspecialchars($summary['phieu']); ?>
        </h1>
        <div class="flex gap-2">
            <?php /* Hidden PDF export button
            <a href="phieuyeucau.php?action=exportpdf&phieu=<?php echo urlencode($summary['phieu']); ?>" 
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded"
               target="_blank"
               title="In PDF">
                <i class="fas fa-file-pdf mr-1"></i> In PDF
            </a>
            */ ?>
            <a href="phieuyeucau.php?action=exportword&phieu=<?php echo urlencode($summary['phieu']); ?>" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"
               target="_blank"
               title="In phiếu YC">
                <i class="fas fa-file-word mr-1"></i> In phiếu YC
            </a>
            <a href="phieuyeucau.php?action=exportworddetail&phieu=<?php echo urlencode($summary['phieu']); ?>" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded"
               target="_blank"
               title="In danh sách thiết bị chi tiết">
                <i class="fas fa-file-word mr-1"></i> In chi tiết
            </a>
            <a href="phieuyeucau.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>
    </div>

    <!-- Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Thông tin tổng hợp -->
    <div class="bg-gray-50 rounded-lg p-4 mb-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center">
            <i class="fas fa-info-circle mr-2"></i> Thông tin phiếu
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="font-semibold text-gray-700">Số phiếu:</label>
                <div class="text-lg text-blue-700 font-bold"><?php echo htmlspecialchars($summary['phieu']); ?></div>
            </div>
            
            <div>
                <label class="font-semibold text-gray-700">Ngày yêu cầu:</label>
                <div><?php echo date('d/m/Y', strtotime($summary['ngayyc'])); ?></div>
            </div>
            
            <div>
                <label class="font-semibold text-gray-700">Đơn vị:</label>
                <div><?php echo htmlspecialchars($summary['tendv']); ?> (<?php echo htmlspecialchars($summary['madv']); ?>)</div>
            </div>
            
            <?php if ($summary['nhomsc']): ?>
            <div>
                <label class="font-semibold text-gray-700">Nhóm SC:</label>
                <div><span class="bg-blue-100 text-blue-800 px-2 py-1 rounded"><?php echo htmlspecialchars($summary['nhomsc']); ?></span></div>
            </div>
            <?php endif; ?>
            
            <?php if ($summary['ngyeucau']): ?>
            <div>
                <label class="font-semibold text-gray-700">Người yêu cầu:</label>
                <div><?php echo htmlspecialchars($summary['ngyeucau']); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($summary['ngnhyeucau']): ?>
            <div>
                <label class="font-semibold text-gray-700">Người nhận yêu cầu:</label>
                <div><?php echo htmlspecialchars($summary['ngnhyeucau']); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($summary['dienthoai']): ?>
            <div>
                <label class="font-semibold text-gray-700">Điện thoại:</label>
                <div><?php echo htmlspecialchars($summary['dienthoai']); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($summary['cv']): ?>
        <div class="mt-4">
            <label class="font-semibold text-gray-700">Công việc yêu cầu:</label>
            <div class="bg-white rounded p-3 mt-1"><?php echo nl2br(htmlspecialchars($summary['cv'])); ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($summary['ycthemkh']): ?>
        <div class="mt-4">
            <label class="font-semibold text-gray-700">Yêu cầu thêm từ khách hàng:</label>
            <div class="bg-white rounded p-3 mt-1"><?php echo nl2br(htmlspecialchars($summary['ycthemkh'])); ?></div>
        </div>
        <?php endif; ?>
        
        <!-- Thống kê thiết bị -->
        <div class="mt-4 pt-4 border-t">
            <label class="font-semibold text-gray-700">Thống kê thiết bị:</label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2">
                <div class="bg-blue-100 rounded p-2 text-center">
                    <div class="text-xl font-bold text-blue-700"><?php echo $summary['so_thietbi']; ?></div>
                    <div class="text-xs text-gray-600">Tổng số</div>
                </div>
                <div class="bg-yellow-100 rounded p-2 text-center">
                    <div class="text-xl font-bold text-yellow-700"><?php echo $summary['tb_chuath']; ?></div>
                    <div class="text-xs text-gray-600">Chưa TH</div>
                </div>
                <div class="bg-orange-100 rounded p-2 text-center">
                    <div class="text-xl font-bold text-orange-700"><?php echo $summary['tb_danglam']; ?></div>
                    <div class="text-xs text-gray-600">Đang làm</div>
                </div>
                <div class="bg-purple-100 rounded p-2 text-center">
                    <div class="text-xl font-bold text-purple-700"><?php echo $summary['tb_hoanthanh']; ?></div>
                    <div class="text-xs text-gray-600">Chưa BG</div>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="mt-4 pt-4 border-t flex gap-2">
            <?php if (hasPermission('phieuyeucau.edit')): ?>
            <a href="phieuyeucau.php?action=edit&phieu=<?php echo urlencode($summary['phieu']); ?>" 
               class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded">
                <i class="fas fa-edit mr-1"></i> Sửa thông tin phiếu
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('hososcbd.create')): ?>
            <a href="hososcbd.php?action=create&phieu=<?php echo urlencode($summary['phieu']); ?>" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                <i class="fas fa-plus mr-1"></i> Thêm thiết bị vào phiếu
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Danh sách thiết bị -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold mb-4 flex items-center">
            <i class="fas fa-list mr-2"></i> Danh sách thiết bị (<?php echo count($devices); ?>)
        </h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-2 md:px-4 py-2 border">STT</th>
                        <th class="px-2 md:px-4 py-2 border">Mã QL</th>
                        <th class="px-2 md:px-4 py-2 border">Mã VT</th>
                        <th class="px-2 md:px-4 py-2 border">Tên thiết bị</th>
                        <th class="px-2 md:px-4 py-2 border hidden md:table-cell">Số máy</th>
                        <th class="px-2 md:px-4 py-2 border hidden lg:table-cell">Model</th>
                        <th class="px-2 md:px-4 py-2 border hidden xl:table-cell">BDDK</th>
                        <th class="px-2 md:px-4 py-2 border hidden xl:table-cell">HC/KĐ</th>
                        <th class="px-2 md:px-4 py-2 border">Trạng thái</th>
                        <th class="px-2 md:px-4 py-2 border">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($devices as $index => $device): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 md:px-4 py-2 border text-center"><?php echo $index + 1; ?></td>
                            <td class="px-2 md:px-4 py-2 border">
                                <a href="hososcbd.php?action=view&id=<?php echo $device['stt']; ?>" 
                                   class="text-blue-600 hover:underline">
                                    <?php echo htmlspecialchars($device['maql']); ?>
                                </a>
                            </td>
                            <td class="px-2 md:px-4 py-2 border"><?php echo htmlspecialchars($device['mavt']); ?></td>
                            <td class="px-2 md:px-4 py-2 border"><?php echo htmlspecialchars($device['tenvt'] ?? ''); ?></td>
                            <td class="px-2 md:px-4 py-2 border hidden md:table-cell"><?php echo htmlspecialchars($device['somay']); ?></td>
                            <td class="px-2 md:px-4 py-2 border hidden lg:table-cell"><?php echo htmlspecialchars($device['model']); ?></td>
                            <td class="px-2 md:px-4 py-2 border text-center hidden xl:table-cell">
                                <?php if (!empty($device['bddk_quarters'])): ?>
                                    <a href="/iso2/kehoachbaoduongdinhky.php?thietbi_id=<?php echo $device['thietbi_stt']; ?>" 
                                       class="inline-flex flex-wrap gap-1"
                                       title="Xem kế hoạch bảo dưỡng định kỳ">
                                        <?php 
                                        foreach ($device['bddk_quarters'] as $qData) {
                                            $quarterName = str_replace('Q', 'Quý ', $qData['quarter']);
                                            $isCompleted = $qData['completed'];
                                            
                                            if ($isCompleted) {
                                                // Đã hoàn thành: màu xanh đậm + dấu tích
                                                echo '<span class="inline-flex items-center bg-green-600 text-white text-xs font-bold px-2 py-1 rounded">';
                                                echo '<i class="fas fa-check mr-1"></i>' . htmlspecialchars($quarterName);
                                                echo '</span>';
                                            } else {
                                                // Chưa hoàn thành: màu xám
                                                echo '<span class="inline-flex items-center bg-gray-300 text-gray-700 text-xs font-bold px-2 py-1 rounded">';
                                                echo htmlspecialchars($quarterName);
                                                echo '</span>';
                                            }
                                        }
                                        ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="px-2 md:px-4 py-2 border text-center hidden xl:table-cell">
                                <?php
                                // Xử lý HC/KĐ (Kế hoạch kiểm định)
                                // Kế hoạch (lấy từ planned_months CSV)
                                $kehoachParts = [];
                                if (!empty($device['planned_months'])) {
                                    $plannedMonths = explode(',', $device['planned_months']);
                                    foreach ($plannedMonths as $month) {
                                        $month = trim($month);
                                        if ($month !== '') {
                                            $kehoachParts[] = (int)$month;
                                        }
                                    }
                                }
                                // Bổ sung đợt 2 nếu có
                                if (!empty($device['planned_months_dot2'])) {
                                    $plannedMonthsDot2 = explode(',', $device['planned_months_dot2']);
                                    foreach ($plannedMonthsDot2 as $month) {
                                        $month = trim($month);
                                        if ($month !== '' && !in_array((int)$month, $kehoachParts)) {
                                            $kehoachParts[] = (int)$month;
                                        }
                                    }
                                }
                                
                                // Thực hiện
                                $thuchienParts = [];
                                if (!empty($device['inspected_months'])) {
                                    $inspectedMonths = explode(',', $device['inspected_months']);
                                    foreach ($inspectedMonths as $month) {
                                        $month = trim($month);
                                        if ($month !== '') {
                                            $thuchienParts[] = (int)$month;
                                        }
                                    }
                                }
                                
                                // Hiển thị dạng badge giống BDDK
                                if (!empty($kehoachParts) || !empty($thuchienParts)):
                                    echo '<div class="inline-flex flex-wrap gap-1">';
                                    
                                    // Lấy tất cả tháng unique
                                    $allMonths = array_unique(array_merge($kehoachParts, $thuchienParts));
                                    sort($allMonths);
                                    
                                    foreach ($allMonths as $month) {
                                        $monthName = 'T' . $month;
                                        $isCompleted = in_array($month, $thuchienParts);
                                        
                                        if ($isCompleted) {
                                            // Đã thực hiện: màu xanh đậm + dấu tích
                                            echo '<span class="inline-flex items-center bg-green-600 text-white text-xs font-bold px-2 py-1 rounded">';
                                            echo '<i class="fas fa-check mr-1"></i>' . htmlspecialchars($monthName);
                                            echo '</span>';
                                        } else {
                                            // Chỉ kế hoạch: màu xám
                                            echo '<span class="inline-flex items-center bg-gray-300 text-gray-700 text-xs font-bold px-2 py-1 rounded">';
                                            echo htmlspecialchars($monthName);
                                            echo '</span>';
                                        }
                                    }
                                    
                                    echo '</div>';
                                else:
                                    echo '<span class="text-gray-400">-</span>';
                                endif;
                                ?>
                            </td>
                            <td class="px-2 md:px-4 py-2 border text-center">
                                <?php
                                // Xác định trạng thái
                                if ($device['bg'] == 1) {
                                    echo '<span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs"><i class="fas fa-check-double"></i> Đã BG</span>';
                                } elseif (!empty($device['ngaykt']) && $device['ngaykt'] !== '0000-00-00') {
                                    echo '<span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs"><i class="fas fa-check"></i> Chưa BG</span>';
                                } elseif (!empty($device['ngayth']) && $device['ngayth'] !== '0000-00-00') {
                                    echo '<span class="bg-orange-100 text-orange-800 px-2 py-1 rounded text-xs"><i class="fas fa-wrench"></i> Đang làm</span>';
                                } else {
                                    echo '<span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs"><i class="fas fa-clock"></i> Chưa TH</span>';
                                }
                                ?>
                                
                                <!-- Ngày -->
                                <div class="text-xs text-gray-600 mt-1">
                                    <?php if (!empty($device['ngaykt']) && $device['ngaykt'] !== '0000-00-00'): ?>
                                        KT: <?php echo date('d/m/Y', strtotime($device['ngaykt'])); ?>
                                    <?php elseif (!empty($device['ngayth']) && $device['ngayth'] !== '0000-00-00'): ?>
                                        TH: <?php echo date('d/m/Y', strtotime($device['ngayth'])); ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-2 md:px-4 py-2 border text-center">
                                <div class="flex justify-center gap-1">
                                    <a href="hososcbd.php?action=view&id=<?php echo $device['stt']; ?>" 
                                       class="text-blue-600 hover:text-blue-800" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if (hasPermission('hososcbd.edit')): ?>
                                    <a href="hososcbd.php?action=edit&id=<?php echo $device['stt']; ?>" 
                                       class="text-orange-600 hover:text-orange-800" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
