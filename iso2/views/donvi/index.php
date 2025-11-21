<?php
declare(strict_types=1);
$title = 'Danh sách Đơn vị';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <h2 class="text-xl md:text-2xl font-bold mb-4 md:mb-0">
                <i class="fas fa-building mr-2"></i>Danh sách Đơn vị Khách hàng
            </h2>
            <?php if (hasPermission('donvi.create')): ?>
                <a href="/iso2/donvi.php?action=create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Thêm Đơn vị
                </a>
            <?php endif; ?>
        </div>

        <!-- Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded">
                <i class="fas fa-check-circle mr-2"></i>
                <?php
                switch ($_GET['success']) {
                    case 'created':
                        echo 'Tạo đơn vị thành công!';
                        break;
                    case 'updated':
                        echo 'Cập nhật đơn vị thành công!';
                        break;
                    case 'deleted':
                        echo 'Xóa đơn vị thành công!';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php
                switch ($_GET['error']) {
                    case 'permission_denied':
                        echo 'Bạn không có quyền thực hiện thao tác này!';
                        break;
                    case 'invalid':
                        echo 'ID không hợp lệ!';
                        break;
                    case 'notfound':
                        echo 'Không tìm thấy đơn vị!';
                        break;
                    case 'delete_failed':
                        echo 'Xóa đơn vị thất bại!';
                        break;
                    default:
                        echo 'Có lỗi xảy ra!';
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Search -->
        <form method="GET" class="mb-6">
            <div class="flex gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                           placeholder="Tìm kiếm mã đơn vị, tên đơn vị..." 
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    <i class="fas fa-search mr-2"></i>Tìm kiếm
                </button>
                <a href="/iso2/donvi.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded inline-flex items-center">
                    <i class="fas fa-redo mr-2"></i>Làm mới
                </a>
            </div>
        </form>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn vị</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên đơn vị</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>Không có dữ liệu</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm"><?php echo $item['stt']; ?></td>
                                <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($item['madv']); ?></td>
                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($item['tendv']); ?></td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <div class="flex justify-center space-x-2">
                                        <?php if (hasPermission('donvi.edit')): ?>
                                            <a href="/iso2/donvi.php?action=edit&id=<?php echo $item['stt']; ?>" 
                                               class="text-blue-600 hover:text-blue-800" title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('donvi.delete')): ?>
                                            <form method="POST" action="/iso2/donvi.php?action=delete" 
                                                  onsubmit="return confirm('Bạn có chắc muốn xóa đơn vị này?');" 
                                                  class="inline">
                                                <input type="hidden" name="id" value="<?php echo $item['stt']; ?>">
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
            <div class="mt-6 flex justify-center">
                <nav class="flex space-x-2">
                    <?php
                    $queryParams = $_GET;
                    
                    // Previous button
                    if ($page > 1):
                        $queryParams['page'] = $page - 1;
                        $url = '/iso2/donvi.php?' . http_build_query($queryParams);
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
                        $url = '/iso2/donvi.php?' . http_build_query($queryParams);
                    ?>
                        <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">1</a>
                        <?php if ($start > 2): ?>
                            <span class="px-3 py-2">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start; $i <= $end; $i++):
                        $queryParams['page'] = $i;
                        $url = '/iso2/donvi.php?' . http_build_query($queryParams);
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
                        $url = '/iso2/donvi.php?' . http_build_query($queryParams);
                        ?>
                        <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300"><?php echo $totalPages; ?></a>
                    <?php endif; ?>
                    
                    <?php
                    // Next button
                    if ($page < $totalPages):
                        $queryParams['page'] = $page + 1;
                        $url = '/iso2/donvi.php?' . http_build_query($queryParams);
                    ?>
                        <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>

        <!-- Summary -->
        <div class="mt-4 text-sm text-gray-600 text-center">
            Tổng số: <strong><?php echo $total; ?></strong> đơn vị
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
