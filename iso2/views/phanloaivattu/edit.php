<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Sửa Phân loại Vật Tư';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="bg-white rounded-lg shadow-md p-6 max-w-3xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">
        <i class="fas fa-edit mr-2"></i> Sửa Phân loại Vật Tư Thanh Lý
    </h1>

    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($item)): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            Không tìm thấy phân loại này hoặc có lỗi xảy ra.
            <a href="phanloaivattu.php" class="underline">Quay lại danh sách</a>
        </div>
    <?php else: ?>

    <?php if (isset($item['so_luong_vattu']) && $item['so_luong_vattu'] > 0): ?>
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-info-circle"></i> 
            Phân loại này đang được sử dụng bởi <strong><?php echo $item['so_luong_vattu']; ?> vật tư</strong>.
        </div>
    <?php endif; ?>

    <form method="POST" action="phanloaivattu.php?action=edit&id=<?php echo $item['id']; ?>">
        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block font-medium mb-2">
                    Mã phân loại <span class="text-red-500">*</span>
                    <span class="text-sm text-gray-500">(Viết hoa, không dấu, dùng _ thay khoảng trắng)</span>
                </label>
                <input type="text" name="ma_phanloai" required 
                       value="<?php echo htmlspecialchars($item['ma_phanloai']); ?>"
                       class="w-full border rounded px-3 py-2 uppercase" 
                       pattern="[A-Z0-9_]+"
                       title="Chỉ dùng chữ in hoa, số và dấu gạch dưới">
            </div>

            <div>
                <label class="block font-medium mb-2">
                    Tên phân loại <span class="text-red-500">*</span>
                </label>
                <input type="text" name="ten_phanloai" required 
                       value="<?php echo htmlspecialchars($item['ten_phanloai']); ?>"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block font-medium mb-2">
                    Màu hiển thị (Tailwind CSS class)
                    <span class="text-sm text-gray-500">(Tùy chọn)</span>
                </label>
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <button type="button" onclick="document.getElementsByName('mau_sac')[0].value='bg-blue-100 text-blue-800'" 
                            class="bg-blue-100 text-blue-800 px-3 py-2 rounded text-sm font-semibold">
                        Xanh dương
                    </button>
                    <button type="button" onclick="document.getElementsByName('mau_sac')[0].value='bg-purple-100 text-purple-800'" 
                            class="bg-purple-100 text-purple-800 px-3 py-2 rounded text-sm font-semibold">
                        Tím
                    </button>
                    <button type="button" onclick="document.getElementsByName('mau_sac')[0].value='bg-green-100 text-green-800'" 
                            class="bg-green-100 text-green-800 px-3 py-2 rounded text-sm font-semibold">
                        Xanh lá
                    </button>
                    <button type="button" onclick="document.getElementsByName('mau_sac')[0].value='bg-orange-100 text-orange-800'" 
                            class="bg-orange-100 text-orange-800 px-3 py-2 rounded text-sm font-semibold">
                        Cam
                    </button>
                    <button type="button" onclick="document.getElementsByName('mau_sac')[0].value='bg-red-100 text-red-800'" 
                            class="bg-red-100 text-red-800 px-3 py-2 rounded text-sm font-semibold">
                        Đỏ
                    </button>
                    <button type="button" onclick="document.getElementsByName('mau_sac')[0].value='bg-gray-100 text-gray-800'" 
                            class="bg-gray-100 text-gray-800 px-3 py-2 rounded text-sm font-semibold">
                        Xám
                    </button>
                </div>
                <input type="text" name="mau_sac" 
                       value="<?php echo htmlspecialchars($item['mau_sac'] ?? ''); ?>"
                       class="w-full border rounded px-3 py-2" 
                       placeholder="VD: bg-blue-100 text-blue-800">
                <?php if (!empty($item['mau_sac'])): ?>
                <div class="mt-2">
                    <span class="text-sm text-gray-600">Xem trước: </span>
                    <span class="px-3 py-1 rounded text-sm font-semibold <?php echo htmlspecialchars($item['mau_sac']); ?>">
                        Mẫu màu
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <div>
                <label class="block font-medium mb-2">
                    Thứ tự sắp xếp
                    <span class="text-sm text-gray-500">(Số nhỏ hiển thị trước)</span>
                </label>
                <input type="number" name="thu_tu" 
                       value="<?php echo $item['thu_tu']; ?>"
                       class="w-full border rounded px-3 py-2" 
                       min="0">
            </div>

            <div>
                <label class="block font-medium mb-2">Mô tả</label>
                <textarea name="mo_ta" rows="3"
                          class="w-full border rounded px-3 py-2"><?php echo htmlspecialchars($item['mo_ta'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                <i class="fas fa-save mr-1"></i> Cập nhật
            </button>
            <a href="phanloaivattu.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
        </div>
    </form>
    
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
