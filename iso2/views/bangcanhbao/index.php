<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Bảng Cảnh Báo HC/KĐ';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-exclamation-triangle mr-2 text-yellow-500"></i> 
        Bảng Cảnh Báo Hiệu Chuẩn/Kiểm Định
    </h1>

    <!-- Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php
            switch ($_GET['success']) {
                case '1':
                    echo 'Lưu hồ sơ hiệu chuẩn thành công!';
                    break;
                case '2':
                    echo 'Cập nhật kết quả kiểm tra thành công!';
                    break;
            }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Filter Month/Year -->
    <form method="get" class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
            <div>
                <label class="block text-sm font-medium mb-1">Tháng</label>
                <select name="month" class="border rounded px-3 py-2 w-full">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $month == $m ? 'selected' : ''; ?>>
                            Tháng <?php echo $m; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Năm</label>
                <select name="year" class="border rounded px-3 py-2 w-full">
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full">
                    <i class="fas fa-search mr-1"></i> Xem
                </button>
            </div>
            
            <div>
                <a href="bangcanhbao.php?action=phieuyc&month=<?php echo $month; ?>&year=<?php echo $year; ?>" 
                   class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-block text-center w-full">
                    <i class="fas fa-file-alt mr-1"></i> Phiếu Yêu Cầu
                </a>
            </div>
        </div>
    </form>

    <!-- Search Form -->
    <form method="get" class="mb-6">
        <input type="hidden" name="month" value="<?php echo $month; ?>">
        <input type="hidden" name="year" value="<?php echo $year; ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Trạng Thái</label>
                <select name="status" class="border rounded px-3 py-2 w-full">
                    <option value="all" <?php echo (!isset($_GET['status']) || $_GET['status'] === 'all') ? 'selected' : ''; ?>>
                        Tất cả
                    </option>
                    <option value="not_calibrated" <?php echo (isset($_GET['status']) && $_GET['status'] === 'not_calibrated') ? 'selected' : ''; ?>>
                        Chưa hiệu chuẩn
                    </option>
                    <option value="calibrated" <?php echo (isset($_GET['status']) && $_GET['status'] === 'calibrated') ? 'selected' : ''; ?>>
                        Đã HC
                    </option>
                    <option value="calibrated_broken" <?php echo (isset($_GET['status']) && $_GET['status'] === 'calibrated_broken') ? 'selected' : ''; ?>>
                        Đã HC - Hỏng
                    </option>
                </select>
            </div>
            
            <div class="md:col-span-3">
                <label class="block text-sm font-medium mb-1">Loại Tìm Kiếm</label>
                <select name="search_type" class="border rounded px-3 py-2 w-full">
                    <option value="all" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] === 'all') ? 'selected' : ''; ?>>
                        Tất cả
                    </option>
                    <option value="device" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] === 'device') ? 'selected' : ''; ?>>
                        Tên thiết bị
                    </option>
                    <option value="code" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] === 'code') ? 'selected' : ''; ?>>
                        Số máy/Mã vật tư
                    </option>
                    <option value="owner" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] === 'owner') ? 'selected' : ''; ?>>
                        Chủ sở hữu
                    </option>
                </select>
            </div>
            
            <div class="md:col-span-4">
                <label class="block text-sm font-medium mb-1">Từ Khóa</label>
                <input type="text" 
                       name="search" 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                       placeholder="Nhập từ khóa tìm kiếm..." 
                       class="border rounded px-3 py-2 w-full">
            </div>
            
            <div class="md:col-span-2">
                <button type="submit" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded w-full">
                    <i class="fas fa-search mr-1"></i> Tìm
                </button>
            </div>
            
            <div class="md:col-span-1">
                <a href="bangcanhbao.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>" 
                   class="bg-gray-400 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-block text-center w-full" 
                   title="Xóa bộ lọc">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </div>
        
        <?php if ((isset($_GET['search']) && !empty($_GET['search'])) || (isset($_GET['status']) && $_GET['status'] !== 'all')): ?>
            <div class="mt-2 text-sm text-gray-600">
                <i class="fas fa-info-circle mr-1"></i>
                <?php if (isset($_GET['status']) && $_GET['status'] !== 'all'): ?>
                    Trạng thái: <strong>
                        <?php 
                        $statusLabels = [
                            'not_calibrated' => 'Chưa hiệu chuẩn',
                            'calibrated' => 'Đã HC',
                            'calibrated_broken' => 'Đã HC - Hỏng'
                        ];
                        echo $statusLabels[$_GET['status']] ?? 'Tất cả';
                        ?>
                    </strong>
                <?php endif; ?>
                <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <?php if (isset($_GET['status']) && $_GET['status'] !== 'all'): ?> | <?php endif; ?>
                    Tìm kiếm: <strong><?php echo htmlspecialchars($_GET['search']); ?></strong>
                    <?php if (isset($_GET['search_type']) && $_GET['search_type'] !== 'all'): ?>
                        trong <strong>
                            <?php 
                            $typeLabels = [
                                'device' => 'Tên thiết bị',
                                'code' => 'Số máy/Mã vật tư',
                                'owner' => 'Chủ sở hữu'
                            ];
                            echo $typeLabels[$_GET['search_type']] ?? 'Tất cả';
                            ?>
                        </strong>
                    <?php endif; ?>
                <?php endif; ?>
                (<?php echo $total; ?> kết quả)
            </div>
        <?php endif; ?>
    </form>

    <!-- Legend -->
    <div class="mb-4 p-3 bg-gray-50 rounded border">
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center">
                <div class="w-6 h-6 border mr-2" style="background-color: #FFFFFF;"></div>
                <span>Chưa hiệu chuẩn</span>
            </div>
            <div class="flex items-center">
                <div class="w-6 h-6 border mr-2" style="background-color: #A0FFFF;"></div>
                <span>Đã HC - Tốt</span>
            </div>
            <div class="flex items-center">
                <div class="w-6 h-6 border mr-2" style="background-color: #FFA0A0;"></div>
                <span>Đã HC - Hỏng</span>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full table-auto border-collapse border">
            <thead class="bg-gray-200">
                <tr>
                    <th class="border px-2 py-2 text-xs md:text-sm">STT</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Số Hồ Sơ</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Tên Máy</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Số Máy</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Công Việc</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Ngày Thực Hiện</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Nhân Viên</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Nơi TH</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Chủ Sở Hữu</th>
                    <th class="border px-2 py-2 text-xs md:text-sm">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr>
                        <td colspan="10" class="border px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Không có dữ liệu kế hoạch cho tháng <?php echo $month; ?>/<?php echo $year; ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $sttDisplay = isset($offset) ? $offset + 1 : 1;
                    foreach ($data as $row): 
                        // Xác định màu nền
                        $bgColor = '#FFFFFF'; // Mặc định trắng
                        if (!empty($row['ngayhc'])) {
                            if ($row['ttkt'] === 'Tốt') {
                                $bgColor = '#A0FFFF'; // Xanh
                            } elseif ($row['ttkt'] === 'Hỏng') {
                                $bgColor = '#FFA0A0'; // Đỏ
                            }
                        }
                    ?>
                    <tr style="background-color: <?php echo $bgColor; ?>;">
                        <td class="border px-2 py-2 text-center text-xs md:text-sm">
                            <?php echo $sttDisplay++; ?>
                        </td>
                        <td class="border px-2 py-2 text-xs md:text-sm">
                            <?php echo htmlspecialchars($row['sohs'] ?? ''); ?>
                        </td>
                        <td class="border px-2 py-2 text-xs md:text-sm">
                            <a href="bangcanhbao.php?action=formhoso&mavattu=<?php echo urlencode($row['mavattu'] ?? ''); ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>" 
                               class="text-blue-600 hover:text-blue-800 font-medium">
                                <?php echo htmlspecialchars($row['tenviettat'] ?? $row['tenthietbi']); ?>
                            </a>
                        </td>
                        <td class="border px-2 py-2 text-xs md:text-sm">
                            <?php echo htmlspecialchars($row['somay'] ?? ''); ?>
                        </td>
                        <td class="border px-2 py-2 text-center text-xs md:text-sm">
                            <?php 
                            // Hiển thị công việc từ kế hoạch hoặc từ hồ sơ
                            echo htmlspecialchars($row['loaitb'] ?? 'HC'); 
                            ?>
                        </td>
                        <td class="border px-2 py-2 text-center text-xs md:text-sm">
                            <?php 
                            if (!empty($row['ngayhc'])) {
                                echo date('d/m/Y', strtotime($row['ngayhc']));
                            }
                            ?>
                        </td>
                        <td class="border px-2 py-2 text-xs md:text-sm">
                            <?php echo htmlspecialchars($row['nhanvien'] ?? ''); ?>
                        </td>
                        <td class="border px-2 py-2 text-xs md:text-sm">
                            <?php echo htmlspecialchars($row['noithuchien'] ?? ''); ?>
                        </td>
                        <td class="border px-2 py-2 text-xs md:text-sm">
                            <?php echo htmlspecialchars($row['chusohuu'] ?? ''); ?>
                        </td>
                        <td class="border px-2 py-2 text-center text-xs md:text-sm">
                            <a href="bangcanhbao.php?action=formhoso&mavattu=<?php echo urlencode($row['mavattu'] ?? ''); ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>" 
                               class="text-blue-600 hover:text-blue-800" title="Nhập hồ sơ">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="mt-4 flex justify-center">
            <nav class="inline-flex rounded-md shadow">
                <?php 
                // Xây dựng query string cho pagination
                $paginationParams = ['month' => $month, 'year' => $year];
                
                // Chỉ thêm search params nếu có giá trị
                if (isset($_GET['search']) && trim($_GET['search']) !== '') {
                    $paginationParams['search'] = $_GET['search'];
                    $paginationParams['search_type'] = isset($_GET['search_type']) ? $_GET['search_type'] : 'all';
                }
                
                // Thêm status filter nếu khác 'all'
                if (isset($_GET['status']) && $_GET['status'] !== 'all') {
                    $paginationParams['status'] = $_GET['status'];
                }
                
                $queryString = http_build_query($paginationParams);
                
                for ($p = 1; $p <= $totalPages; $p++): 
                ?>
                    <a href="?<?php echo $queryString; ?>&page=<?php echo $p; ?>" 
                       class="px-3 py-2 border <?php echo $p == $page ? 'bg-blue-500 text-white' : 'bg-white text-blue-600 hover:bg-blue-50'; ?> 
                              <?php echo $p == 1 ? 'rounded-l-md' : ''; ?> 
                              <?php echo $p == $totalPages ? 'rounded-r-md' : ''; ?>">
                        <?php echo $p; ?>
                    </a>
                <?php endfor; ?>
            </nav>
        </div>
    <?php endif; ?>

    <!-- Summary -->
    <div class="mt-4 text-sm text-gray-600">
        <p>Hiển thị <?php echo count($data); ?> / <?php echo $total; ?> thiết bị</p>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
