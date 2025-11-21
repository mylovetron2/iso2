<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Thêm Thiết Bị';
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <h1 class="text-xl md:text-2xl font-bold mb-4 md:mb-6 flex items-center">
        <i class="fas fa-plus-circle mr-2"></i> Thêm Thiết Bị
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

    <?php if (isset($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-3 md:space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Mã vật tư <span class="text-red-500">*</span>
                </label>
                <input type="text" name="mavt" required
                       value="<?php echo isset($_POST['mavt']) ? htmlspecialchars($_POST['mavt']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Tên vật tư <span class="text-red-500">*</span>
                </label>
                <input type="text" name="tenvt" required
                       value="<?php echo isset($_POST['tenvt']) ? htmlspecialchars($_POST['tenvt']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Số máy <span class="text-red-500">*</span>
                </label>
                <input type="text" name="somay" required
                       value="<?php echo isset($_POST['somay']) ? htmlspecialchars($_POST['somay']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Model <span class="text-red-500">*</span>
                </label>
                <input type="text" name="model" required
                       value="<?php echo isset($_POST['model']) ? htmlspecialchars($_POST['model']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Hộp máy <span class="text-red-500">*</span>
                </label>
                <input type="text" name="homay" required
                       value="<?php echo isset($_POST['homay']) ? htmlspecialchars($_POST['homay']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Điện áp <span class="text-red-500">*</span>
                </label>
                <input type="text" name="dienap" required
                       value="<?php echo isset($_POST['dienap']) ? htmlspecialchars($_POST['dienap']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Đơn vị <span class="text-red-500">*</span>
                </label>
                <select name="madv" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 text-sm md:text-base">
                    <option value="">-- Chọn đơn vị --</option>
                    <?php foreach ($donViList as $dv): ?>
                        <option value="<?php echo htmlspecialchars($dv['madv']); ?>" 
                                <?php echo (isset($_POST['madv']) && $_POST['madv'] === $dv['madv']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dv['tendv']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Loại dầu</label>
                <input type="text" name="loaidau"
                       value="<?php echo isset($_POST['loaidau']) ? htmlspecialchars($_POST['loaidau']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Mức dầu <span class="text-red-500">*</span></label>
                <input type="text" name="mucdau" required
                       value="<?php echo isset($_POST['mucdau']) ? htmlspecialchars($_POST['mucdau']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Thời gian BD (ngày)</label>
                <input type="number" name="bdtime" min="0"
                       value="<?php echo isset($_POST['bdtime']) ? $_POST['bdtime'] : '0'; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Ngày KTSD</label>
                <input type="date" name="ngayktsd"
                       value="<?php echo isset($_POST['ngayktsd']) ? $_POST['ngayktsd'] : date('Y-m-d'); ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Mã máy <span class="text-red-500">*</span></label>
                <input type="text" name="mamay" required
                       value="<?php echo isset($_POST['mamay']) ? htmlspecialchars($_POST['mamay']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-gray-700 font-semibold mb-2">Thông tin cơ bản</label>
            <textarea name="thongtincb" rows="2"
                      class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['thongtincb']) ? htmlspecialchars($_POST['thongtincb']) : ''; ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Tài liệu KT</label>
                <input type="text" name="tlkt"
                       value="<?php echo isset($_POST['tlkt']) ? htmlspecialchars($_POST['tlkt']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Hồ sơ máy</label>
                <input type="text" name="hosomay"
                       value="<?php echo isset($_POST['hosomay']) ? htmlspecialchars($_POST['hosomay']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-2 pt-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 md:px-6 py-2 rounded text-sm md:text-base w-full md:w-auto">
                <i class="fas fa-save mr-1"></i> Lưu
            </button>
            <a href="thietbi.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 md:px-6 py-2 rounded inline-block text-sm md:text-base text-center w-full md:w-auto">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
        </div>
    </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
