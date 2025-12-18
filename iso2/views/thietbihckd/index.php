<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Quản lý Thiết Bị HC/KĐ';
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-tools mr-2"></i> Quản lý Thiết Bị Hiệu Chuẩn/Kiểm Định
    </h1>

    <!-- Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php
            switch ($_GET['success']) {
                case 'created':
                    echo 'Tạo thiết bị thành công!';
                    break;
                case 'updated':
                    echo 'Cập nhật thiết bị thành công!';
                    break;
                case 'deleted':
                    echo 'Xóa thiết bị thành công!';
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
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Filter & Search -->
    <form method="get" class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
            <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                   placeholder="Tìm kiếm mã VT, tên thiết bị, số máy..." 
                   class="border rounded px-3 py-2 text-sm md:text-base">
            
            <select name="bophansh" class="border rounded px-3 py-2 text-sm md:text-base">
                <option value="">Tất cả bộ phận</option>
                <?php foreach ($boPhanList as $bp): ?>
                    <option value="<?php echo htmlspecialchars($bp); ?>" 
                            <?php echo (isset($_GET['bophansh']) && $_GET['bophansh'] == $bp) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($bp); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="loaitb" class="border rounded px-3 py-2 text-sm md:text-base">
                <option value="">Tất cả loại thiết bị</option>
                <?php foreach ($loaiTBList as $lt): ?>
                    <option value="<?php echo htmlspecialchars($lt); ?>" 
                            <?php echo (isset($_GET['loaitb']) && $_GET['loaitb'] == $lt) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($lt); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="filter" class="border rounded px-3 py-2 text-sm md:text-base">
                <option value="">Tất cả trạng thái</option>
                <option value="saphethan" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'saphethan') ? 'selected' : ''; ?>>
                    Sắp hết hạn (30 ngày)
                </option>
                <option value="dahethan" <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'dahethan') ? 'selected' : ''; ?>>
                    Đã hết hạn
                </option>
            </select>
        </div>
        
        <div class="flex flex-wrap gap-2 mt-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm md:text-base">
                <i class="fas fa-search mr-1"></i> Lọc
            </button>
            <a href="thietbihckd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm md:text-base">
                <i class="fas fa-redo mr-1"></i> Xóa lọc
            </a>
            
            <?php if (hasPermission('thietbi.create')): ?>
            <a href="thietbihckd.php?action=create" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm md:text-base ml-auto">
                <i class="fas fa-plus mr-1"></i> Thêm thiết bị
            </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm">STT</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm">Mã VT</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm">Tên viết tắt</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm hidden md:table-cell">Tên thiết bị</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm">Số máy</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm hidden lg:table-cell">Hãng SX</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm hidden lg:table-cell">Bộ phận</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm hidden xl:table-cell">Thời hạn</th>
                    <th class="px-2 md:px-4 py-2 border text-center text-xs md:text-sm">Trạng thái</th>
                    <th class="px-2 md:px-4 py-2 border text-center text-xs md:text-sm">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Không có thiết bị nào</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($items as $item): 
                    // Calculate expiry status
                    $status = '';
                    $statusClass = '';
                    if (!empty($item['ngayktnghiemthu']) && !empty($item['thoihankd'])) {
                        $ngayKT = new DateTime($item['ngayktnghiemthu']);
                        $ngayHetHan = clone $ngayKT;
                        $ngayHetHan->modify('+' . (int)$item['thoihankd'] . ' months');
                        $today = new DateTime();
                        $diff = $today->diff($ngayHetHan);
                        
                        if ($ngayHetHan < $today) {
                            $status = 'Hết hạn';
                            $statusClass = 'bg-red-100 text-red-800';
                        } elseif ($diff->days <= 30) {
                            $status = 'Sắp hết hạn';
                            $statusClass = 'bg-yellow-100 text-yellow-800';
                        } else {
                            $status = 'Còn hạn';
                            $statusClass = 'bg-green-100 text-green-800';
                        }
                    }
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm"><?php echo $item['stt']; ?></td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm">
                        <code class="bg-gray-100 px-2 py-1 rounded text-sm md:text-base font-semibold">
                            <?php echo htmlspecialchars($item['mavattu']); ?>
                        </code>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm">
                        <strong><?php echo htmlspecialchars($item['tenviettat']); ?></strong>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden md:table-cell" title="<?php echo htmlspecialchars($item['tenthietbi']); ?>">
                        <?php echo htmlspecialchars(mb_substr($item['tenthietbi'], 0, 50)) . (mb_strlen($item['tenthietbi']) > 50 ? '...' : ''); ?>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm"><?php echo htmlspecialchars($item['somay']); ?></td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden lg:table-cell"><?php echo htmlspecialchars($item['hangsx']); ?></td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden lg:table-cell"><?php echo htmlspecialchars($item['bophansh']); ?></td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden xl:table-cell">
                        <?php echo $item['thoihankd'] ? $item['thoihankd'] . ' tháng' : '-'; ?>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-center">
                        <?php if ($status): ?>
                            <span class="px-2 py-1 rounded text-xs <?php echo $statusClass; ?>">
                                <?php echo $status; ?>
                            </span>
                        <?php else: ?>
                            <span class="text-gray-400 text-xs">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-2 py-2 border text-center">
                        <?php if (hasPermission('thietbi.edit')): ?>
                        <a href="thietbihckd.php?action=edit&id=<?php echo $item['stt']; ?>" 
                           class="text-green-600 hover:text-green-800 mx-1" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('thietbi.delete')): ?>
                        <form method="POST" action="thietbihckd.php?action=delete" 
                              onsubmit="return confirm('Bạn có chắc muốn xóa thiết bị này?');" 
                              class="inline">
                            <input type="hidden" name="id" value="<?php echo $item['stt']; ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800 mx-1" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
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
            
            // Previous button
            if ($page > 1):
                $queryParams['page'] = $page - 1;
                $url = 'thietbihckd.php?' . http_build_query($queryParams);
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
                $url = 'thietbihckd.php?' . http_build_query($queryParams);
            ?>
                <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">1</a>
                <?php if ($start > 2): ?>
                    <span class="px-3 py-2">...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start; $i <= $end; $i++):
                $queryParams['page'] = $i;
                $url = 'thietbihckd.php?' . http_build_query($queryParams);
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
                $url = 'thietbihckd.php?' . http_build_query($queryParams);
                ?>
                <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300"><?php echo $totalPages; ?></a>
            <?php endif; ?>
            
            <?php
            // Next button
            if ($page < $totalPages):
                $queryParams['page'] = $page + 1;
                $url = 'thietbihckd.php?' . http_build_query($queryParams);
            ?>
                <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
    
    <div class="mt-4 text-sm text-gray-600 text-center">
        Hiển thị <?php echo count($items); ?> / <?php echo $total; ?> thiết bị
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
