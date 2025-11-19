<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="max-w-lg mx-auto bg-white rounded-lg shadow-md p-6 mt-8">
    <h1 class="text-2xl font-bold mb-4">Sửa tiến độ công việc</h1>
    <form method="POST" action="tiendocongviec2.php?action=update&id=<?php echo $item['id']; ?>">
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Tên công việc</label>
            <input type="text" name="ten_cong_viec" class="w-full px-3 py-2 border rounded" value="<?php echo htmlspecialchars($item['ten_cong_viec']); ?>" required>
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Trạng thái</label>
            <input type="text" name="trang_thai" class="w-full px-3 py-2 border rounded" value="<?php echo htmlspecialchars($item['trang_thai']); ?>">
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Deadline</label>
            <input type="date" name="deadline" class="w-full px-3 py-2 border rounded" value="<?php echo htmlspecialchars($item['deadline']); ?>">
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Cập nhật</button>
    <a href="tiendocongviec2.php" class="ml-4 text-gray-600 hover:underline">Quay lại</a>
    </form>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
