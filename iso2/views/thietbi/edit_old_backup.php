<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Sửa Thiết Bị';
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <h1 class="text-xl md:text-2xl font-bold mb-4 md:mb-6 flex items-center">
        <i class="fas fa-edit mr-2"></i> Sửa Thiết Bị
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

        <form method="POST" id="editForm" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Mã vật tư <span class="text-red-500">*</span></label>
                    <input type="text" name="mavt" required value="<?php echo htmlspecialchars($data['mavt'] ?? $item['mavt']); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Số máy <span class="text-red-500">*</span></label>
                    <input type="text" name="somay" required value="<?php echo htmlspecialchars($data['somay'] ?? $item['somay']); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Model <span class="text-red-500">*</span></label>
                    <input type="text" name="model" required value="<?php echo htmlspecialchars($data['model'] ?? $item['model'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Đơn vị <span class="text-red-500">*</span></label>
                    <select name="madv" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Chọn đơn vị --</option>
                        <?php 
                        $selectedMadv = $data['madv'] ?? $item['madv'];
                        foreach ($donViList as $dv): 
                        ?>
                            <option value="<?php echo htmlspecialchars($dv['madv']); ?>"
                                    <?php echo $selectedMadv === $dv['madv'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dv['tendv']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Tên vật tư <span class="text-red-500">*</span></label>
                <textarea name="tenvt" required rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($data['tenvt'] ?? $item['tenvt']); ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Mã máy <span class="text-red-500">*</span></label>
                    <input type="text" name="mamay" required value="<?php echo htmlspecialchars($data['mamay'] ?? $item['mamay']); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Hộp máy <span class="text-red-500">*</span></label>
                    <input type="text" name="homay" required value="<?php echo htmlspecialchars($data['homay'] ?? $item['homay'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Điện áp <span class="text-red-500">*</span></label>
                    <input type="text" name="dienap" required value="<?php echo htmlspecialchars($data['dienap'] ?? $item['dienap'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Loại dầu</label>
                    <input type="text" name="loaidau" value="<?php echo htmlspecialchars($data['loaidau'] ?? $item['loaidau'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Mức dầu <span class="text-red-500">*</span></label>
                    <input type="text" name="mucdau" required value="<?php echo htmlspecialchars($data['mucdau'] ?? $item['mucdau'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Thời gian BD (ngày)</label>
                    <input type="number" name="bdtime" min="0" value="<?php echo htmlspecialchars($data['bdtime'] ?? $item['bdtime'] ?? '0'); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Ngày KTSD</label>
                    <input type="date" name="ngayktsd" value="<?php echo htmlspecialchars($data['ngayktsd'] ?? $item['ngayktsd'] ?? date('Y-m-d')); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Tài liệu KT</label>
                    <input type="text" name="tlkt" value="<?php echo htmlspecialchars($data['tlkt'] ?? $item['tlkt'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Thông tin cơ bản</label>
                <textarea name="thongtincb" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($data['thongtincb'] ?? $item['thongtincb'] ?? ''); ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Hồ sơ máy</label>
                <input type="text" name="hosomay" value="<?php echo htmlspecialchars($data['hosomay'] ?? $item['hosomay'] ?? ''); ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Action buttons - visible inline -->
            <div class="mt-8 pt-6 border-t-4 border-green-600 bg-gray-50 p-6 rounded-lg">
                <div class="flex flex-col sm:flex-row gap-4">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-10 py-4 rounded-lg font-bold text-xl shadow-2xl transition-all hover:shadow-xl flex items-center justify-center">
                        <i class="fas fa-check-circle mr-3 text-2xl"></i>CẬP NHẬT THIẾT BỊ
                    </button>
                    <a href="/iso2/thietbi.php" class="bg-gray-500 hover:bg-gray-600 text-white px-10 py-4 rounded-lg font-bold text-xl shadow-2xl transition-all hover:shadow-xl flex items-center justify-center">
                        <i class="fas fa-times-circle mr-3 text-2xl"></i>HỦY BỎ
                    </a>
                </div>
                <p class="text-center text-sm text-gray-600 mt-3">
                    <i class="fas fa-info-circle mr-1"></i>Nhấn nút "CẬP NHẬT THIẾT BỊ" để lưu thay đổi
                </p>
            </div>
        </form>
    </div>
</div>

<!-- Fixed bottom action bar for better visibility -->
<div class="fixed bottom-0 left-0 right-0 bg-white border-t-4 border-green-600 shadow-2xl p-4 z-50 lg:hidden">
    <div class="max-w-4xl mx-auto flex gap-3">
        <button type="submit" form="editForm" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-bold shadow-lg">
            <i class="fas fa-check-circle mr-2"></i>CẬP NHẬT
        </button>
        <a href="/iso2/thietbi.php" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-bold shadow-lg text-center">
            <i class="fas fa-times mr-2"></i>HỦY
        </a>
    </div>
</div>

<!-- Add padding to body when fixed bar is visible -->
<div class="h-20 lg:hidden"></div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
