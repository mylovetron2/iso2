<?php
require_once __DIR__ . '/../layouts/header.php';
$title = 'Tạo mới Project';
?>
<div class="max-w-xl mx-auto bg-white rounded-lg shadow-md p-8 mt-8">
    <h2 class="text-2xl font-bold mb-6 text-center">Tạo mới Project</h2>
    <?php if (!empty($errors)): ?>
        <div class="mb-4 text-red-600">
            <?php foreach ($errors as $err): ?>
                <div><?php echo htmlspecialchars($err); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Tên Project</label>
            <input type="text" name="name" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Mô tả</label>
            <textarea name="description" class="w-full px-3 py-2 border rounded"></textarea>
        </div>
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Trạng thái</label>
            <select name="status" class="w-full px-3 py-2 border rounded">
                <option value="planning">Planning</option>
                <option value="active">Active</option>
                <option value="completed">Completed</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Ngày bắt đầu</label>
            <input type="date" name="start_date" class="w-full px-3 py-2 border rounded">
        </div>
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Ngày kết thúc</label>
            <input type="date" name="end_date" class="w-full px-3 py-2 border rounded">
        </div>
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Ngân sách</label>
            <input type="number" step="0.01" name="budget" class="w-full px-3 py-2 border rounded">
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded">Tạo mới</button>
    </form>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
