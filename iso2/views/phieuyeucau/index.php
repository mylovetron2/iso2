<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Quản lý Số Phiếu Yêu Cầu';
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-file-alt mr-2"></i> Quản lý Số Phiếu Yêu Cầu
    </h1>

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

    <!-- Filter & Search -->
    <form method="get" class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 mb-2">
            <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                   placeholder="Tìm số phiếu, đơn vị, người yêu cầu..." 
                   class="border rounded px-3 py-2 text-sm md:text-base">
            
            <select name="madv" class="border rounded px-3 py-2 text-sm md:text-base">
                <option value="">Tất cả đơn vị</option>
                <?php foreach ($donViList as $dv): ?>
                    <option value="<?php echo htmlspecialchars($dv['madv']); ?>" 
                            <?php echo (isset($_GET['madv']) && $_GET['madv'] === $dv['madv']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dv['tendv']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="trangthai" class="border rounded px-3 py-2 text-sm md:text-base">
                <option value="">Tất cả trạng thái</option>
                <option value="chuath" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'chuath') ? 'selected' : ''; ?>>Chưa thực hiện</option>
                <option value="danglam" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'danglam') ? 'selected' : ''; ?>>Đang làm</option>
                <option value="hoanthanh" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'hoanthanh') ? 'selected' : ''; ?>>Hoàn thành</option>
                <option value="chuabg" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'chuabg') ? 'selected' : ''; ?>>Chưa bàn giao</option>
                <option value="dabg" <?php echo (isset($_GET['trangthai']) && $_GET['trangthai'] === 'dabg') ? 'selected' : ''; ?>>Đã bàn giao</option>
            </select>
            
            <input type="date" name="from_date" value="<?php echo $_GET['from_date'] ?? ''; ?>" 
                   placeholder="Từ ngày" 
                   class="border rounded px-3 py-2 text-sm md:text-base">
            
            <input type="date" name="to_date" value="<?php echo $_GET['to_date'] ?? ''; ?>" 
                   placeholder="Đến ngày" 
                   class="border rounded px-3 py-2 text-sm md:text-base">
            
            <select name="nhomsc" class="border rounded px-3 py-2 text-sm md:text-base">
                <option value="">Tất cả nhóm</option>
                <option value="CNC" <?php echo (isset($_GET['nhomsc']) && $_GET['nhomsc'] === 'CNC') ? 'selected' : ''; ?>>CNC</option>
                <option value="RDNGA" <?php echo (isset($_GET['nhomsc']) && $_GET['nhomsc'] === 'RDNGA') ? 'selected' : ''; ?>>RDNGA</option>
            </select>
        </div>
        
        <div class="flex flex-wrap gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm md:text-base">
                <i class="fas fa-search mr-1"></i> Lọc
            </button>
            <a href="phieuyeucau.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm md:text-base text-center">
                <i class="fas fa-redo mr-1"></i> Xóa lọc
            </a>
            
            <?php if (hasPermission('phieuyeucau.create')): ?>
            <a href="phieuyeucau.php?action=create" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm md:text-base text-center ml-auto">
                <i class="fas fa-plus mr-1"></i> Tạo phiếu mới
            </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-2 md:px-4 py-2 border">STT</th>
                    <th class="px-2 md:px-4 py-2 border">Số phiếu</th>
                    <th class="px-2 md:px-4 py-2 border">Ngày YC</th>
                    <th class="px-2 md:px-4 py-2 border hidden md:table-cell">Đơn vị</th>
                    <th class="px-2 md:px-4 py-2 border hidden lg:table-cell">Người YC</th>
                    <th class="px-2 md:px-4 py-2 border">Thiết bị</th>
                    <th class="px-2 md:px-4 py-2 border">Trạng thái</th>
                    <th class="px-2 md:px-4 py-2 border">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($phieuList)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Không có phiếu nào</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $offset = ($page - 1) * 20;
                    foreach ($phieuList as $index => $phieu): 
                    ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 md:px-4 py-2 border text-center"><?php echo $offset + $index + 1; ?></td>
                            <td class="px-2 md:px-4 py-2 border">
                                <a href="phieuyeucau.php?action=view&phieu=<?php echo urlencode($phieu['phieu']); ?>" 
                                   class="text-blue-600 hover:underline font-semibold">
                                    <?php echo htmlspecialchars($phieu['phieu']); ?>
                                </a>
                                <?php if ($phieu['nhomsc']): ?>
                                    <span class="ml-1 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded"><?php echo htmlspecialchars($phieu['nhomsc']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-2 md:px-4 py-2 border text-center">
                                <?php echo date('d/m/Y', strtotime($phieu['ngayyc'])); ?>
                            </td>
                            <td class="px-2 md:px-4 py-2 border hidden md:table-cell">
                                <div class="text-xs text-gray-600"><?php echo htmlspecialchars($phieu['madv']); ?></div>
                                <div><?php echo htmlspecialchars($phieu['tendv']); ?></div>
                            </td>
                            <td class="px-2 md:px-4 py-2 border hidden lg:table-cell">
                                <?php echo htmlspecialchars($phieu['ngyeucau']); ?>
                            </td>
                            <td class="px-2 md:px-4 py-2 border text-center">
                                <div class="font-bold text-blue-700"><?php echo $phieu['so_thietbi']; ?></div>
                                <div class="text-xs space-y-1 mt-1">
                                    <?php if ($phieu['tb_chuath'] > 0): ?>
                                        <div class="text-yellow-600">
                                            <i class="fas fa-clock"></i> <?php echo $phieu['tb_chuath']; ?> chưa TH
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($phieu['tb_danglam'] > 0): ?>
                                        <div class="text-orange-600">
                                            <i class="fas fa-wrench"></i> <?php echo $phieu['tb_danglam']; ?> đang làm
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($phieu['tb_hoanthanh'] > 0): ?>
                                        <div class="text-purple-600">
                                            <i class="fas fa-check"></i> <?php echo $phieu['tb_hoanthanh']; ?> chưa BG
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($phieu['tb_dabg'] > 0): ?>
                                        <div class="text-green-600">
                                            <i class="fas fa-check-double"></i> <?php echo $phieu['tb_dabg']; ?> đã BG
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-2 md:px-4 py-2 border text-center">
                                <?php 
                                // Xác định trạng thái tổng thể của phiếu
                                if ($phieu['tb_dabg'] == $phieu['so_thietbi']) {
                                    echo '<span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Hoàn thành</span>';
                                } elseif ($phieu['tb_hoanthanh'] > 0) {
                                    echo '<span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs">Chờ BG</span>';
                                } elseif ($phieu['tb_danglam'] > 0) {
                                    echo '<span class="bg-orange-100 text-orange-800 px-2 py-1 rounded text-xs">Đang xử lý</span>';
                                } else {
                                    echo '<span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">Chưa TH</span>';
                                }
                                ?>
                            </td>
                            <td class="px-2 md:px-4 py-2 border text-center">
                                <div class="flex justify-center gap-1">
                                    <a href="phieuyeucau.php?action=view&phieu=<?php echo urlencode($phieu['phieu']); ?>" 
                                       class="text-blue-600 hover:text-blue-800" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if (hasPermission('phieuyeucau.edit')): ?>
                                    <a href="phieuyeucau.php?action=edit&phieu=<?php echo urlencode($phieu['phieu']); ?>" 
                                       class="text-orange-600 hover:text-orange-800" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('phieuyeucau.delete') && $phieu['tb_chuath'] == $phieu['so_thietbi']): ?>
                                    <form method="post" action="phieuyeucau.php?action=delete" class="inline" 
                                          onsubmit="return confirm('Xác nhận xóa phiếu <?php echo htmlspecialchars($phieu['phieu']); ?>?');">
                                        <input type="hidden" name="phieu" value="<?php echo htmlspecialchars($phieu['phieu']); ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
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
        <nav class="flex gap-1">
            <?php 
            $currentParams = $_GET;
            
            // Previous
            if ($page > 1):
                $currentParams['page'] = $page - 1;
            ?>
                <a href="?<?php echo http_build_query($currentParams); ?>" 
                   class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
                    &laquo; Trước
                </a>
            <?php endif; ?>
            
            <?php 
            // Page numbers
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            
            for ($i = $start; $i <= $end; $i++):
                $currentParams['page'] = $i;
            ?>
                <a href="?<?php echo http_build_query($currentParams); ?>" 
                   class="px-3 py-2 <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?> rounded">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php 
            // Next
            if ($page < $totalPages):
                $currentParams['page'] = $page + 1;
            ?>
                <a href="?<?php echo http_build_query($currentParams); ?>" 
                   class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
                    Sau &raquo;
                </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
