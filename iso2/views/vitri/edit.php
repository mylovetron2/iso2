<?php 
$title = 'Sửa Vị Trí';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-6 flex items-center">
            <i class="fas fa-edit mr-2 text-yellow-600"></i>Sửa Vị Trí
        </h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="vitri.php?action=edit&id=<?php echo         (int)$item['stt']; ?>">
            <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">
                            Mã Vị Trí <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="mavitri" required
                               value="<?php echo htmlspecialchars((string)$item['mavitri']); ?>"
                               class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Ví dụ: VT001">
                    </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">
                    Tên Vị Trí <span class="text-red-500">*</span>
                </label>
                <input type="text" name="tenvitri" required
                       value="<?php echo htmlspecialchars((string)$item['tenvitri']); ?>"
                       class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Nhập tên vị trí">
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                    <i class="fas fa-save mr-2"></i>Cập nhật
                </button>
                <a href="vitri.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                    <i class="fas fa-times mr-2"></i>Hủy
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
