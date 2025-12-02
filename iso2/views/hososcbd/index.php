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
    <div class="grid grid-cols-2 md:grid-cols-5 gap-2 md:gap-4 mb-4 md:mb-6">
        <div class="bg-blue-100 rounded p-3 md:p-4 text-center">
            <div class="text-xl md:text-2xl font-bold text-blue-700"><?php echo $stats['total']; ?></div>
            <div class="text-xs md:text-sm text-gray-600">Tổng số</div>
        </div>
        <div class="bg-yellow-100 rounded p-3 md:p-4 text-center">
            <div class="text-xl md:text-2xl font-bold text-yellow-700"><?php echo $stats['chuath']; ?></div>
            <div class="text-xs md:text-sm text-gray-600">Chưa thực hiện</div>
        </div>
        <div class="bg-orange-100 rounded p-3 md:p-4 text-center">
            <div class="text-xl md:text-2xl font-bold text-orange-700"><?php echo $stats['danglam']; ?></div>
            <div class="text-xs md:text-sm text-gray-600">Đang làm</div>
        </div>
        <div class="bg-purple-100 rounded p-3 md:p-4 text-center">
            <div class="text-xl md:text-2xl font-bold text-purple-700"><?php echo $stats['chuabg']; ?></div>
            <div class="text-xs md:text-sm text-gray-600">Chưa bàn giao</div>
        </div>
        <div class="bg-green-100 rounded p-3 md:p-4 text-center">
            <div class="text-xl md:text-2xl font-bold text-green-700"><?php echo $stats['dabg']; ?></div>
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
    <form method="get" class="flex flex-col md:flex-row gap-2 mb-4">
        <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
               placeholder="Tìm phiếu, mã VT, số máy, đơn vị..." 
               class="border rounded px-3 py-2 flex-1 w-full md:min-w-[200px] text-sm md:text-base">
        
        <select name="madv" class="border rounded px-3 py-2 w-full md:w-auto text-sm md:text-base">
            <option value="">Tất cả đơn vị</option>
            <?php foreach ($donViList as $dv): ?>
                <option value="<?php echo htmlspecialchars($dv['madv']); ?>" 
                        <?php echo (isset($_GET['madv']) && $_GET['madv'] === $dv['madv']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($dv['tendv']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select name="trangthai" class="border rounded px-3 py-2 w-full md:w-auto text-sm md:text-base">
            <option value="">Tất cả trạng thái</option>
            <option value="chuath" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'chuath') ? 'selected' : ''; ?>>Chưa thực hiện</option>
            <option value="danglam" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'danglam') ? 'selected' : ''; ?>>Đang làm</option>
            <option value="hoanthanh" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'hoanthanh') ? 'selected' : ''; ?>>Hoàn thành</option>
            <option value="chuabg" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'chuabg') ? 'selected' : ''; ?>>Chưa bàn giao</option>
            <option value="dabg" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'dabg') ? 'selected' : ''; ?>>Đã bàn giao</option>
        </select>
        
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 md:px-4 py-2 rounded text-sm md:text-base w-full md:w-auto">
            <i class="fas fa-search mr-1"></i> Lọc
        </button>
        <a href="hososcbd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-3 md:px-4 py-2 rounded text-sm md:text-base text-center w-full md:w-auto">
            <i class="fas fa-redo mr-1"></i> Xóa lọc
        </a>
        
        <?php if (hasPermission('hososcbd.create')): ?>
        <a href="hososcbd.php?action=create" class="bg-green-600 hover:bg-green-700 text-white px-3 md:px-4 py-2 rounded text-sm md:text-base text-center w-full md:w-auto md:ml-auto">
            <i class="fas fa-plus mr-1"></i> Thêm hồ sơ
        </a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm">Phiếu</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm">Mã VT</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm hidden md:table-cell">Số máy</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm hidden lg:table-cell">Ngày YC</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm hidden lg:table-cell">Đơn vị</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm">Trạng thái</th>
                    <th class="px-2 md:px-4 py-2 border text-center text-xs md:text-sm">Xem/Sửa</th>
                    <th class="px-2 md:px-4 py-2 border text-center text-xs md:text-sm">Chi tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Không có hồ sơ nào</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($items as $item): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm">
                        <strong><?php echo htmlspecialchars($item['phieu']); ?></strong>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm"><?php echo htmlspecialchars($item['mavt']); ?></td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden md:table-cell"><?php echo htmlspecialchars($item['somay']); ?></td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden lg:table-cell">
                        <?php 
                        if ($item['ngayyc'] && $item['ngayyc'] != '0000-00-00') {
                            echo date('d/m/Y', strtotime($item['ngayyc']));
                            if ($item['ngaykt'] && $item['ngaykt'] != '0000-00-00') {
                                echo ' → ' . date('d/m/Y', strtotime($item['ngaykt']));
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
                        if ($item['bg'] == 1) {
                            echo '<span class="inline-block bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">Đã BG</span>';
                        } elseif ($item['ngaykt'] && $item['ngaykt'] != '0000-00-00') {
                            echo '<span class="inline-block bg-purple-100 text-purple-800 text-xs font-bold px-2 py-1 rounded">Hoàn thành</span>';
                        } elseif ($item['ngayth'] && $item['ngayth'] != '0000-00-00') {
                            echo '<span class="inline-block bg-orange-100 text-orange-800 text-xs font-bold px-2 py-1 rounded">Đang làm</span>';
                        } else {
                            echo '<span class="inline-block bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded">Chưa TH</span>';
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
                        <a href="hososcbd_repair_details.php?id=<?php echo $item['stt']; ?><?php echo $filterQuery; ?>" 
                           class="text-orange-600 hover:text-orange-800 mx-1" title="Thông tin sửa chữa">
                            <i class="fas fa-wrench"></i>
                        </a>
                        <a href="hososcbd_handover_details.php?id=<?php echo $item['stt']; ?><?php echo $filterQuery; ?>" 
                           class="text-purple-600 hover:text-purple-800 mx-1" title="Thông tin bàn giao">
                            <i class="fas fa-handshake"></i>
                        </a>
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
