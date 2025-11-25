<?php 
$title = 'Quản Lý Lô';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold flex items-center">
            <i class="fas fa-box mr-3 text-blue-600"></i>Quản Lý Lô
        </h1>
        <a href="lo.php?action=create" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded flex items-center">
            <i class="fas fa-plus mr-2"></i>Thêm Lô Mới
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-check-circle mr-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Search form -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
        <form method="GET" action="lo.php" class="flex gap-4">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Tìm theo mã lô hoặc tên lô..." 
                   class="flex-1 px-4 py-2 border rounded">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                <i class="fas fa-search mr-2"></i>Tìm kiếm
            </button>
            <?php if ($search): ?>
            <a href="lo.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                <i class="fas fa-times mr-2"></i>Xóa lọc
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full border">
            <thead class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                <tr>
                    <th class="px-6 py-4 border text-left font-semibold">Mã Lô</th>
                    <th class="px-6 py-4 border text-left font-semibold">Tên Lô</th>
                    <th class="px-6 py-4 border text-center font-semibold">Thao Tác</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="3" class="px-6 py-8 border text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2 block"></i>
                        Không có dữ liệu
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($items as $item): ?>
                <tr class="hover:bg-blue-50 transition-colors">
                    <td class="px-6 py-4 border font-semibold text-blue-600"><?php echo htmlspecialchars($item['malo']); ?></td>
                    <td class="px-6 py-4 border"><?php echo htmlspecialchars($item['tenlo']); ?></td>
                    <td class="px-6 py-4 border text-center">
                        <a href="lo.php?action=edit&id=<?php echo $item['stt']; ?>" 
                           class="text-yellow-600 hover:text-yellow-800 mx-1" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="deleteLo(<?php echo $item['stt']; ?>, '<?php echo htmlspecialchars($item['malo']); ?>')" 
                                class="text-red-600 hover:text-red-800 mx-1" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mt-6">
        <nav class="flex justify-center gap-2">
            <?php 
            $queryParams = ['search' => $search];
            for ($i = 1; $i <= $totalPages; $i++):
                $queryParams['page'] = $i;
                $url = 'lo.php?' . http_build_query($queryParams);
                $active = ($i == $page) ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 hover:bg-blue-50';
            ?>
                <a href="<?php echo $url; ?>" class="<?php echo $active; ?> px-4 py-2 border rounded">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
function deleteLo(id, malo) {
    if (!confirm('Bạn có chắc muốn xóa lô ' + malo + '?')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'lo.php?action=delete';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'id';
    input.value = id;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
