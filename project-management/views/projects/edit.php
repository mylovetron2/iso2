<?php
require_once __DIR__ . '/../layouts/header.php';
$title = 'Chỉnh sửa Project';
?>
<div class="max-w-xl mx-auto bg-white rounded-lg shadow-md p-8 mt-8">
    <h2 class="text-2xl font-bold mb-6 text-center">Chỉnh sửa Project</h2>
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
            <input type="text" name="name" class="w-full px-3 py-2 border rounded" value="<?php echo htmlspecialchars($project['name']); ?>" required>
        </div>
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Mô tả</label>
            <textarea name="description" class="w-full px-3 py-2 border rounded"><?php echo htmlspecialchars($project['description']); ?></textarea>
        </div>
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Trạng thái</label>
            <select name="status" class="w-full px-3 py-2 border rounded">
                <option value="planning" <?php if($project['status']==='planning') echo 'selected'; ?>>Planning</option>
                <option value="active" <?php if($project['status']==='active') echo 'selected'; ?>>Active</option>
                <option value="completed" <?php if($project['status']==='completed') echo 'selected'; ?>>Completed</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Ngày bắt đầu</label>
            <input type="date" name="start_date" class="w-full px-3 py-2 border rounded" value="<?php echo htmlspecialchars($project['start_date']); ?>">
        </div>
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Ngày kết thúc</label>
            <input type="date" name="end_date" class="w-full px-3 py-2 border rounded" value="<?php echo htmlspecialchars($project['end_date']); ?>">
        </div>
        <div class="mb-4">
            <label class="block mb-2 font-semibold">Ngân sách</label>
            <input type="number" step="0.01" name="budget" class="w-full px-3 py-2 border rounded" value="<?php echo htmlspecialchars($project['budget']); ?>">
        </div>
        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded">Cập nhật</button>
    </form>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
