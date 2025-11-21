<?php
declare(strict_types=1);
$title = 'Sửa Đơn vị';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-4 md:p-6">
        <h2 class="text-xl md:text-2xl font-bold mb-6">
            <i class="fas fa-edit mr-2"></i>Sửa Đơn vị Khách hàng
        </h2>

        <?php if (isset($error)): ?>
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">
                    Mã đơn vị <span class="text-red-500">*</span>
                </label>
                <input type="text" name="madv" required
                       value="<?php echo htmlspecialchars($data['madv'] ?? $item['madv']); ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Nhập mã đơn vị">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">
                    Tên đơn vị <span class="text-red-500">*</span>
                </label>
                <input type="text" name="tendv" required
                       value="<?php echo htmlspecialchars($data['tendv'] ?? $item['tendv']); ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Nhập tên đơn vị">
            </div>

            <div class="flex space-x-4 pt-4">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                    <i class="fas fa-save mr-2"></i>Cập nhật
                </button>
                <a href="/iso2/donvi.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Quay lại
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
