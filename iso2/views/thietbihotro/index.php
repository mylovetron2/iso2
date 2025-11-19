<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-tools mr-2"></i> Quản lý Thiết bị Hỗ trợ
    </h1>
    
    <!-- Thống kê -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-100 rounded p-4 text-center">
            <div class="text-2xl font-bold text-blue-700"><?php echo $stats['total']; ?></div>
            <div class="text-gray-600">Tổng số thiết bị</div>
        </div>
        <div class="bg-green-100 rounded p-4 text-center">
            <div class="text-2xl font-bold text-green-700"><?php echo $stats['conhan']; ?></div>
            <div class="text-gray-600">Còn hạn KĐ</div>
        </div>
        <div class="bg-yellow-100 rounded p-4 text-center">
            <div class="text-2xl font-bold text-yellow-700"><?php echo $stats['saphethan']; ?></div>
            <div class="text-gray-600">Sắp hết hạn (30 ngày)</div>
        </div>
        <div class="bg-red-100 rounded p-4 text-center">
            <div class="text-2xl font-bold text-red-700"><?php echo $stats['hethan']; ?></div>
            <div class="text-gray-600">Hết hạn KĐ</div>
        </div>
    </div>

    <!-- Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Filter & Search -->
    <form method="get" class="flex flex-wrap gap-2 mb-4">
        <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
               placeholder="Tìm kiếm tên thiết bị, serial..." 
               class="border rounded px-3 py-2 flex-1 min-w-[200px]">
        
        <select name="chusohuu" class="border rounded px-3 py-2">
            <option value="">Tất cả chủ sở hữu</option>
            <?php foreach ($chusohuuList as $owner): ?>
                <option value="<?php echo htmlspecialchars($owner); ?>" 
                        <?php echo (isset($_GET['chusohuu']) && $_GET['chusohuu'] === $owner) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($owner); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            <i class="fas fa-search mr-1"></i> Lọc
        </button>
        <a href="thietbihotro.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
            <i class="fas fa-redo mr-1"></i> Xóa lọc
        </a>
        
        <?php if (hasPermission(PERMISSION_PROJECT_CREATE)): ?>
        <a href="thietbihotro.php?action=create" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded ml-auto">
            <i class="fas fa-plus mr-1"></i> Thêm thiết bị
        </a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 border text-left">STT</th>
                    <th class="px-4 py-2 border text-left">Tên thiết bị</th>
                    <th class="px-4 py-2 border text-left">Tên vật tư</th>
                    <th class="px-4 py-2 border text-left">Chủ sở hữu</th>
                    <th class="px-4 py-2 border text-left">Serial Number</th>
                    <th class="px-4 py-2 border text-left">Ngày KĐ</th>
                    <th class="px-4 py-2 border text-left">Hạn KĐ (tháng)</th>
                    <th class="px-4 py-2 border text-left">Ngày KĐ tiếp theo</th>
                    <th class="px-4 py-2 border text-left">Trạng thái</th>
                    <th class="px-4 py-2 border text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($devices)): ?>
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Không có thiết bị nào</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($devices as $device): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border"><?php echo $device['stt']; ?></td>
                    <td class="px-4 py-2 border">
                        <strong><?php echo htmlspecialchars($device['tenthietbi']); ?></strong>
                    </td>
                    <td class="px-4 py-2 border"><?php echo htmlspecialchars($device['tenvt']); ?></td>
                    <td class="px-4 py-2 border"><?php echo htmlspecialchars($device['chusohuu']); ?></td>
                    <td class="px-4 py-2 border">
                        <code class="bg-gray-100 px-2 py-1 rounded text-sm">
                            <?php echo htmlspecialchars($device['serialnumber']); ?>
                        </code>
                    </td>
                    <td class="px-4 py-2 border">
                        <?php echo $device['ngaykd'] ? date('d/m/Y', strtotime($device['ngaykd'])) : '-'; ?>
                    </td>
                    <td class="px-4 py-2 border text-center"><?php echo $device['hankd']; ?></td>
                    <td class="px-4 py-2 border">
                        <?php 
                        if ($device['ngaykdtt']) {
                            $ngaykdtt = strtotime($device['ngaykdtt']);
                            $today = strtotime('today');
                            $diff = ($ngaykdtt - $today) / 86400;
                            
                            echo date('d/m/Y', $ngaykdtt);
                            
                            if ($diff < 0) {
                                echo '<br><span class="text-xs text-red-600 font-semibold">Đã quá hạn</span>';
                            } elseif ($diff <= 30) {
                                echo '<br><span class="text-xs text-yellow-600 font-semibold">Còn ' . round($diff) . ' ngày</span>';
                            }
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td class="px-4 py-2 border text-center">
                        <?php 
                        if ($device['ngaykdtt']) {
                            $ngaykdtt = strtotime($device['ngaykdtt']);
                            $today = strtotime('today');
                            
                            if ($ngaykdtt < $today) {
                                echo '<span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded">Hết hạn</span>';
                            } elseif (($ngaykdtt - $today) / 86400 <= 30) {
                                echo '<span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">Sắp hết hạn</span>';
                            } else {
                                echo '<span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Còn hạn</span>';
                            }
                        } else {
                            echo '<span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">Chưa có</span>';
                        }
                        ?>
                    </td>
                    <td class="px-4 py-2 border text-center whitespace-nowrap">
                        <a href="thietbihotro.php?action=view&id=<?php echo $device['stt']; ?>" 
                           class="text-blue-600 hover:text-blue-800 mx-1" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php if (hasPermission(PERMISSION_PROJECT_EDIT)): ?>
                        <a href="thietbihotro.php?action=edit&id=<?php echo $device['stt']; ?>" 
                           class="text-green-600 hover:text-green-800 mx-1" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission(PERMISSION_PROJECT_DELETE)): ?>
                        <a href="thietbihotro.php?action=delete&id=<?php echo $device['stt']; ?>" 
                           class="text-red-600 hover:text-red-800 mx-1" title="Xóa"
                           onclick="return confirm('Bạn có chắc muốn xóa thiết bị này?')">
                            <i class="fas fa-trash"></i>
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
    <div class="flex justify-center mt-6 gap-2">
        <?php
        $currentParams = $_GET;
        for ($i = 1; $i <= $totalPages; $i++):
            $currentParams['page'] = $i;
            $queryString = http_build_query($currentParams);
        ?>
        <a href="?<?php echo $queryString; ?>" 
           class="px-4 py-2 rounded <?php echo ($page == $i) ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    
    <div class="mt-4 text-sm text-gray-600 text-center">
        Hiển thị <?php echo count($devices); ?> / <?php echo $total; ?> thiết bị
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
