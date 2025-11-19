<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<div class="max-w-lg mx-auto bg-white rounded-lg shadow-md p-4 md:p-6 mt-4 md:mt-8">
    <h1 class="text-xl md:text-2xl font-bold mb-4">Thêm tiến độ công việc</h1>
    <form method="POST" action="tiendocongviec2.php?action=store">
        <div class="mb-3 md:mb-4">
            <label class="block mb-1 font-semibold text-sm md:text-base">Tên công việc</label>
            <input type="text" name="ten_cong_viec" class="w-full px-3 py-2 border rounded text-sm md:text-base" required>
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Trạng thái</label>
            <input type="text" name="trang_thai" class="w-full px-3 py-2 border rounded">
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Deadline</label>
            <input type="date" name="deadline" class="w-full px-3 py-2 border rounded">
        </div>
        <div class="flex flex-col md:flex-row gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm md:text-base w-full md:w-auto">Lưu</button>
            <a href="tiendocongviec2.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-center text-sm md:text-base w-full md:w-auto inline-block">Quay lại</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
