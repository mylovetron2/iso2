<?php 
$title = 'Thêm Mỏ Mới';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-6 flex items-center">
            <i class="fas fa-plus-circle mr-2 text-blue-600"></i>Thêm Mỏ Mới
        </h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="mo.php?action=create">
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">
                    Mã Mỏ <span class="text-red-500">*</span>
                </label>
                <input type="text" name="mamo" required 
                       class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Ví dụ: M001">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">
                    Tên Mỏ <span class="text-red-500">*</span>
                </label>
                <textarea name="tenmo" required rows="3"
                          class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Nhập tên mỏ"></textarea>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                    <i class="fas fa-save mr-2"></i>Lưu
                </button>
                <a href="mo.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                    <i class="fas fa-times mr-2"></i>Hủy
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
