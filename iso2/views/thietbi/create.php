<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Thêm Thiết Bị';
require_once __DIR__ . '/../layouts/header.php'; 
$prefill = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : ($copySourceData ?? []);
$copyDeviceList = $copyDeviceList ?? [];
?>
<div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <h1 class="text-xl md:text-2xl font-bold mb-4 md:mb-6 flex items-center">
        <i class="fas fa-plus-circle mr-2"></i> Thêm Thiết Bị
    </h1>

    <?php if (!empty($copyId) && !empty($copySourceData)): ?>
    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3 md:p-4 mb-4">
        <div class="flex items-center gap-2 text-indigo-700 font-semibold">
            <i class="fas fa-copy"></i>
            <span>Đang tự động copy dữ liệu từ thiết bị STT <?php echo (int)$copyId; ?></span>
        </div>
        <div class="mt-1 text-sm text-gray-700">
            <?php echo htmlspecialchars((string)($copySourceData['mavt'] ?? '')) . ' - ' . htmlspecialchars((string)($copySourceData['somay'] ?? '')) . ' - ' . htmlspecialchars((string)($copySourceData['model'] ?? '')); ?>
        </div>
    </div>
    <?php endif; ?>

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
        <input type="hidden" name="copy_from" value="<?php echo isset($_GET['copy_from']) ? htmlspecialchars((string)$_GET['copy_from']) : ''; ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Mã vật tư <span class="text-red-500">*</span>
                </label>
                <input type="text" name="mavt" required
                       value="<?php echo isset($prefill['mavt']) ? htmlspecialchars((string)$prefill['mavt']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Tên vật tư <span class="text-red-500">*</span>
                </label>
                <input type="text" name="tenvt" required
                       value="<?php echo isset($prefill['tenvt']) ? htmlspecialchars((string)$prefill['tenvt']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Số máy <span class="text-red-500">*</span>
                </label>
                <input type="text" name="somay" required
                       value="<?php echo isset($prefill['somay']) ? htmlspecialchars((string)$prefill['somay']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Model <span class="text-red-500">*</span>
                </label>
                <input type="text" name="model"
                       value="<?php echo isset($prefill['model']) ? htmlspecialchars((string)$prefill['model']) : ''; ?>"
                       required
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Hộp máy
                </label>
                <input type="text" name="homay"
                       value="<?php echo isset($prefill['homay']) ? htmlspecialchars((string)$prefill['homay']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Điện áp
                </label>
                <input type="text" name="dienap"
                       value="<?php echo isset($prefill['dienap']) ? htmlspecialchars((string)$prefill['dienap']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Đơn vị <span class="text-red-500">*</span>
                </label>
                <select name="madv" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 text-sm md:text-base">
                    <option value="">-- Chọn đơn vị --</option>
                    <?php foreach ($donViList as $dv): ?>
                        <option value="<?php echo htmlspecialchars((string)$dv['madv']); ?>" 
                                <?php echo (isset($prefill['madv']) && (string)$prefill['madv'] === (string)$dv['madv']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string)$dv['tendv']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Loại dầu</label>
                <input type="text" name="loaidau"
                       value="<?php echo isset($prefill['loaidau']) ? htmlspecialchars((string)$prefill['loaidau']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Mức dầu</label>
                <input type="text" name="mucdau"
                       value="<?php echo isset($prefill['mucdau']) ? htmlspecialchars((string)$prefill['mucdau']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Thời gian BD (ngày)</label>
                <input type="number" name="bdtime" min="0"
                       value="<?php echo isset($prefill['bdtime']) ? htmlspecialchars((string)$prefill['bdtime']) : '0'; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Ngày KTSD</label>
                <input type="date" name="ngayktsd"
                       value="<?php echo isset($prefill['ngayktsd']) ? htmlspecialchars((string)$prefill['ngayktsd']) : '1970-01-01'; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">
                    Mã máy
                    <span class="text-sm text-gray-500 font-normal">(Tự động: mavt-model-somay)</span>
                </label>
                <input type="text" name="mamay"
                       value="<?php echo isset($prefill['mamay']) ? htmlspecialchars((string)$prefill['mamay']) : ''; ?>"
                       placeholder="VD: TP7-38-452 (hoặc để trống để tự động tạo)"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-gray-700 font-semibold mb-2">Thông tin cơ bản</label>
            <textarea name="thongtincb" rows="2"
                      class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($prefill['thongtincb']) ? htmlspecialchars((string)$prefill['thongtincb']) : ''; ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Tài liệu KT</label>
                <input type="text" name="tlkt"
                       value="<?php echo isset($prefill['tlkt']) ? htmlspecialchars((string)$prefill['tlkt']) : ''; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Hồ sơ máy</label>
                <input type="text" name="hosomay"
                       value="<?php echo isset($prefill['hosomay']) ? htmlspecialchars((string)$prefill['hosomay']) : ''; ?>"
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
