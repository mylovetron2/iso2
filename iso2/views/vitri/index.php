<?php 
$title = 'Quản Lý Vị Trí';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold flex items-center">
            <i class="fas fa-map-marker-alt mr-3 text-blue-600"></i>Quản Lý Vị Trí
        </h1>
        <?php if (hasPermission('vitri.create')): ?>
        <a href="vitri.php?action=create" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded flex items-center">
            <i class="fas fa-plus mr-2"></i>Thêm Vị Trí Mới
        </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
        <form method="GET" action="vitri.php" class="flex gap-4">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Tìm theo mã vị trí hoặc tên vị trí..." 
                   class="flex-1 px-4 py-2 border rounded">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                <i class="fas fa-search mr-2"></i>Tìm kiếm
            </button>
            <?php if ($search !== ''): ?>
            <a href="vitri.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                <i class="fas fa-times mr-2"></i>Xóa lọc
            </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-4 border text-left font-semibold">Mã Vị Trí</th>
                    <th class="px-6 py-4 border text-left font-semibold">Tên Vị Trí</th>
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
                    <td class="px-6 py-4 border font-semibold text-blue-600"><?php echo htmlspecialchars((string)$item['mavitri']); ?></td>
                    <td class="px-6 py-4 border"><?php echo htmlspecialchars((string)$item['tenvitri']); ?></td>
                    <td class="px-6 py-4 border text-center">
                        <?php if (hasPermission('vitri.edit')): ?>
                        <a href="vitri.php?action=edit&id=<?php echo (int)$item['stt']; ?>" 
                           class="text-yellow-600 hover:text-yellow-800 mx-1" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (hasPermission('vitri.delete')): ?>
                        <button type="button"
                                data-id="<?php echo (int)$item['stt']; ?>"
                                data-code="<?php echo htmlspecialchars((string)$item['mavitri'], ENT_QUOTES); ?>"
                                onclick="deleteVitri(this.dataset.id, this.dataset.code)"
                                class="text-red-600 hover:text-red-800 mx-1" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="mt-6">
        <nav class="flex justify-center gap-2">
            <?php 
            $queryParams = ['search' => $search];
            for ($i = 1; $i <= $totalPages; $i++):
                $queryParams['page'] = $i;
                $url = 'vitri.php?' . http_build_query($queryParams);
                $active = ($i == $page) ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 hover:bg-blue-50';
            ?>
                <a href="<?php echo htmlspecialchars($url); ?>" class="<?php echo $active; ?> px-4 py-2 border rounded">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<form id="deleteVitriForm" method="POST" action="vitri.php?action=delete" class="hidden">
    <input type="hidden" name="id" id="deleteVitriId">
</form>

<script>
function deleteVitri(id, code) {
    if (!confirm('Bạn có chắc muốn xóa vị trí ' + code + '?')) {
        return;
    }
    document.getElementById('deleteVitriId').value = id;
    document.getElementById('deleteVitriForm').submit();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
