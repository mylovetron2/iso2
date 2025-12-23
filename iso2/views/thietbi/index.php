<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Quản lý Thiết Bị';
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-cogs mr-2"></i> Quản lý Thiết Bị
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

    <!-- Filter & Search -->
    <form method="get" class="flex flex-col md:flex-row gap-2 mb-4">
        <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
               placeholder="Tìm kiếm mã VT, tên VT, số máy, model..." 
               class="border rounded px-3 py-2 flex-1 w-full md:min-w-[200px] text-sm md:text-base">
        
        <select name="madv" class="border rounded px-3 py-2 w-full md:w-auto text-sm md:text-base">
            <option value="">Tất cả đơn vị</option>
            <?php foreach ($donViList as $dv): ?>
                <option value="<?php echo htmlspecialchars($dv['madv']); ?>" 
                        <?php echo (isset($_GET['madv']) && $_GET['madv'] == $dv['madv']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($dv['tendv']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 md:px-4 py-2 rounded text-sm md:text-base w-full md:w-auto">
            <i class="fas fa-search mr-1"></i> Lọc
        </button>
        <a href="thietbi.php" class="bg-gray-500 hover:bg-gray-600 text-white px-3 md:px-4 py-2 rounded text-sm md:text-base text-center w-full md:w-auto">
            <i class="fas fa-redo mr-1"></i> Xóa lọc
        </a>
        
        <?php if (hasPermission('thietbi.create')): ?>
        <a href="thietbi.php?action=create" class="bg-green-600 hover:bg-green-700 text-white px-3 md:px-4 py-2 rounded text-sm md:text-base text-center w-full md:w-auto md:ml-auto">
            <i class="fas fa-plus mr-1"></i> Thêm thiết bị
        </a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm">STT</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm">Mã VT</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm">Tên VT</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm hidden md:table-cell">Số máy</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm hidden lg:table-cell">Model</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm hidden lg:table-cell">Hộp máy</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm">Đơn vị</th>
                    <th class="px-2 md:px-4 py-2 border text-left text-xs md:text-sm hidden xl:table-cell">Lịch sử sửa chữa</th>
                    <th class="px-2 md:px-4 py-2 border text-center text-xs md:text-sm">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Không có thiết bị nào</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($items as $item): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm"><?php echo $item['stt']; ?></td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm">
                        <code class="bg-gray-100 px-2 py-1 rounded text-sm md:text-base font-semibold">
                            <?php echo htmlspecialchars($item['mavt']); ?>
                        </code>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm">
                        <strong><?php echo htmlspecialchars($item['tenvt']); ?></strong>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden md:table-cell"><?php echo htmlspecialchars($item['somay']); ?></td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden lg:table-cell"><?php echo htmlspecialchars($item['model']); ?></td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden lg:table-cell"><?php echo htmlspecialchars($item['homay']); ?></td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm"><?php echo htmlspecialchars($item['madv']); ?></td>
                    <td class="px-2 md:px-4 py-2 border text-xs md:text-sm hidden xl:table-cell">
                        <?php if (!empty($item['lichsu_suachua'])): ?>
                            <div class="max-h-32 overflow-y-auto">
                                <?php foreach ($item['lichsu_suachua'] as $ls): ?>
                                    <div class="mb-2 pb-2 border-b border-gray-200 last:border-0">
                                        <div class="text-xs text-blue-600 font-semibold">
                                            <?php echo $ls['ngaykt'] ? date('d/m/Y', strtotime($ls['ngaykt'])) : ''; ?>
                                        </div>
                                        <?php if (!empty($ls['honghoc'])): ?>
                                            <div class="text-xs text-red-600 mt-1">
                                                <strong>Hỏng:</strong> <?php $text = strip_tags($ls['honghoc']); $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8'); echo mb_substr($text, 0, 80, 'UTF-8'); ?><?php echo mb_strlen($text, 'UTF-8') > 80 ? '...' : ''; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($ls['khacphuc'])): ?>
                                            <div class="text-xs text-green-600 mt-1">
                                                <strong>Khắc phục:</strong> <?php $text = strip_tags($ls['khacphuc']); $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8'); echo mb_substr($text, 0, 80, 'UTF-8'); ?><?php echo mb_strlen($text, 'UTF-8') > 80 ? '...' : ''; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-gray-400 text-xs">Chưa có lịch sử</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-2 md:px-4 py-2 border text-center">
                        <?php if (hasPermission('thietbi.edit')): ?>
                        <a href="thietbi.php?action=edit&id=<?php echo $item['stt']; ?>" 
                           class="text-green-600 hover:text-green-800 mx-1" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('thietbi.delete')): ?>
                        <form method="POST" action="thietbi.php?action=delete" 
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
                $url = 'thietbi.php?' . http_build_query($queryParams);
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
                $url = 'thietbi.php?' . http_build_query($queryParams);
            ?>
                <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">1</a>
                <?php if ($start > 2): ?>
                    <span class="px-3 py-2">...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start; $i <= $end; $i++):
                $queryParams['page'] = $i;
                $url = 'thietbi.php?' . http_build_query($queryParams);
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
                $url = 'thietbi.php?' . http_build_query($queryParams);
                ?>
                <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300"><?php echo $totalPages; ?></a>
            <?php endif; ?>
            
            <?php
            // Next button
            if ($page < $totalPages):
                $queryParams['page'] = $page + 1;
                $url = 'thietbi.php?' . http_build_query($queryParams);
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
