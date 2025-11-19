<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <h1 class="text-xl md:text-2xl font-bold mb-4 md:mb-6 flex items-center">
        <i class="fas fa-plus-circle mr-2"></i> Thêm Thiết bị Hỗ trợ
    </h1>

    <?php if (!empty($errors)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul class="list-disc list-inside">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-3 md:space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Tên thiết bị <span class="text-red-500">*</span>
                </label>
                <input type="text" name="tenthietbi" required
                       value="<?php echo isset($_POST['tenthietbi']) ? htmlspecialchars($_POST['tenthietbi']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">
                    Chủ sở hữu <span class="text-red-500">*</span>
                </label>
                <input type="text" name="chusohuu" required
                       value="<?php echo isset($_POST['chusohuu']) ? htmlspecialchars($_POST['chusohuu']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-gray-700 font-semibold mb-2">Tên vật tư</label>
            <textarea name="tenvt" rows="2"
                      class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['tenvt']) ? htmlspecialchars($_POST['tenvt']) : ''; ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Serial Number</label>
                <input type="text" name="serialnumber"
                       value="<?php echo isset($_POST['serialnumber']) ? htmlspecialchars($_POST['serialnumber']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Hồ sơ máy</label>
                <input type="text" name="hosomay"
                       value="<?php echo isset($_POST['hosomay']) ? htmlspecialchars($_POST['hosomay']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Ngày kiểm định</label>
                <input type="date" name="ngaykd"
                       value="<?php echo isset($_POST['ngaykd']) ? $_POST['ngaykd'] : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Hạn kiểm định (tháng)</label>
                <input type="number" name="hankd" min="0"
                       value="<?php echo isset($_POST['hankd']) ? $_POST['hankd'] : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Ngày KĐ tiếp theo</label>
                <input type="date" name="ngaykdtt"
                       value="<?php echo isset($_POST['ngaykdtt']) ? $_POST['ngaykdtt'] : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Tỷ lệ kiểm tra</label>
                <input type="text" name="tlkt"
                       value="<?php echo isset($_POST['tlkt']) ? htmlspecialchars($_POST['tlkt']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Công dụng</label>
                <input type="number" name="cdung" min="0"
                       value="<?php echo isset($_POST['cdung']) ? $_POST['cdung'] : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Thủ lý</label>
                <input type="number" name="thly" min="0" max="99"
                       value="<?php echo isset($_POST['thly']) ? $_POST['thly'] : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-2 pt-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 md:px-6 py-2 rounded text-sm md:text-base w-full md:w-auto">
                <i class="fas fa-save mr-1"></i> Lưu
            </button>
            <a href="thietbihotro.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 md:px-6 py-2 rounded inline-block text-sm md:text-base text-center w-full md:w-auto">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
