<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Quản lý Phân loại Vật Tư Thanh Lý';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-tags mr-2"></i> Quản lý Phân loại Vật Tư Thanh Lý
    </h1>

    <!-- Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php
            switch ($_GET['success']) {
                case 'created': echo 'Tạo phân loại thành công!'; break;
                case 'updated': echo 'Cập nhật phân loại thành công!'; break;
                case 'deleted': echo 'Xóa phân loại thành công!'; break;
            }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="mb-4">
        <?php if (hasPermission('vattu.create') || hasPermission('phanloai_vattu.create')): ?>
        <a href="phanloaivattu.php?action=create" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
            <i class="fas fa-plus mr-1"></i> Thêm phân loại
        </a>
        <?php endif; ?>
        
        <a href="vattuthanhly.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded ml-2">
            <i class="fas fa-boxes mr-1"></i> Quay lại quản lý vật tư
        </a>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 border text-center">STT</th>
                    <th class="px-4 py-2 border text-left">Mã phân loại</th>
                    <th class="px-4 py-2 border text-left">Tên phân loại</th>
                    <th class="px-4 py-2 border text-center">Màu hiển thị</th>
                    <th class="px-4 py-2 border text-left">Mô tả</th>
                    <th class="px-4 py-2 border text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Chưa có phân loại nào</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php $stt = 1; foreach ($items as $item): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border text-center"><?php echo $stt++; ?></td>
                    <td class="px-4 py-2 border">
                        <code class="bg-gray-100 px-2 py-1 rounded font-semibold">
                            <?php echo htmlspecialchars($item['ma_phanloai']); ?>
                        </code>
                    </td>
                    <td class="px-4 py-2 border font-semibold">
                        <?php echo htmlspecialchars($item['ten_phanloai']); ?>
                    </td>
                    <td class="px-4 py-2 border text-center">
                        <?php if (!empty($item['mau_sac'])): ?>
                            <span class="px-3 py-1 rounded text-sm font-semibold <?php echo htmlspecialchars($item['mau_sac']); ?>">
                                Mẫu
                            </span>
                            <div class="text-xs text-gray-500 mt-1">
                                <?php echo htmlspecialchars($item['mau_sac']); ?>
                            </div>
                        <?php else: ?>
                            <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2 border text-sm text-gray-600">
                        <?php echo htmlspecialchars($item['mo_ta'] ?? '-'); ?>
                    </td>
                    <td class="px-4 py-2 border text-center">
                        <div class="flex gap-2 justify-center">
                            <?php if (hasPermission('vattu.edit') || hasPermission('phanloai_vattu.edit')): ?>
                            <a href="phanloaivattu.php?action=edit&id=<?php echo $item['id']; ?>" 
                               class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('vattu.delete') || hasPermission('phanloai_vattu.delete')): ?>
                            <form method="POST" action="phanloaivattu.php?action=delete" 
                                  onsubmit="return confirm('<?php echo $item['so_luong_vattu'] > 0 ? 'CẢNH BÁO: Phân loại này đang được sử dụng bởi ' . $item['so_luong_vattu'] . ' vật tư! ' : ''; ?>Bạn có chắc chắn muốn xóa?');" 
                                  class="inline">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" 
                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm"
                                        <?php echo $item['so_luong_vattu'] > 0 ? 'disabled title="Không thể xóa vì đang có vật tư sử dụng"' : ''; ?>>
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
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
