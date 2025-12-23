<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Sửa Thiết Bị HC/KĐ';
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-edit mr-2"></i> Sửa Thiết Bị HC/KĐ
    </h1>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Mã vật tư <span class="text-red-500">*</span></label>
                <input type="text" name="mavattu" required 
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo htmlspecialchars($item['mavattu']); ?>">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Tên viết tắt <span class="text-red-500">*</span></label>
                <input type="text" name="tenviettat" required 
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo htmlspecialchars($item['tenviettat']); ?>">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Tên thiết bị <span class="text-red-500">*</span></label>
                <textarea name="tenthietbi" required rows="3"
                          class="w-full border rounded px-3 py-2"><?php echo htmlspecialchars($item['tenthietbi']); ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Số máy <span class="text-red-500">*</span></label>
                <input type="text" name="somay" required 
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo htmlspecialchars($item['somay']); ?>">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Hãng sản xuất</label>
                <input type="text" name="hangsx" 
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo htmlspecialchars($item['hangsx']); ?>">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Bộ phận sử dụng <span class="text-red-500">*</span></label>
                <select name="bophansh" required class="w-full border rounded px-3 py-2">
                    <option value="">-- Chọn bộ phận --</option>
                    <?php foreach ($boPhanList as $bp): ?>
                        <option value="<?php echo htmlspecialchars($bp['madv']); ?>"
                                <?php echo ($item['bophansh'] == $bp['madv']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($bp['tendv']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Chủ sở hữu</label>
                <input type="text" name="chusohuu" 
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo htmlspecialchars($item['chusohuu']); ?>">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Loại thiết bị</label>
                <select name="loaitb" class="w-full border rounded px-3 py-2">
                    <option value=""></option>
                    <option value="1" <?php echo ($item['loaitb'] == '1') ? 'selected' : ''; ?>>Thiết bị theo dõi và đo lường</option>
                    <option value="2" <?php echo ($item['loaitb'] == '2') ? 'selected' : ''; ?>>Máy bắn mìn</option>
                    <option value="3" <?php echo ($item['loaitb'] == '3') ? 'selected' : ''; ?>>Máy kiểm tra kíp mìn</option>
                    <option value="4" <?php echo ($item['loaitb'] == '4') ? 'selected' : ''; ?>>Máy đo độ lệch</option>
                    <option value="5" <?php echo ($item['loaitb'] == '5') ? 'selected' : ''; ?>>Mẫu chuẩn,vật chuẩn</option>
                    <option value="6" <?php echo ($item['loaitb'] == '6') ? 'selected' : ''; ?>>Thiết bị đo lường chuyên dụng</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Ngày kiểm tra/nghiệm thu</label>
                <input type="date" name="ngayktnghiemthu" 
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo $item['ngayktnghiemthu'] ? date('Y-m-d', strtotime($item['ngayktnghiemthu'])) : '1970-01-01'; ?>">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Thời hạn kiểm định (tháng)</label>
                <input type="text" name="thoihankd" 
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo htmlspecialchars($item['thoihankd']); ?>"
                       placeholder="Ví dụ: 12, 24, 36">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">TLKT</label>
                <input type="text" name="tlkt" 
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo htmlspecialchars($item['tlkt']); ?>">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Dần chuẩn</label>
                <select name="danchuan" class="w-full border rounded px-3 py-2">
                    <option value="0" <?php echo ($item['danchuan'] == 0) ? 'selected' : ''; ?>>Không</option>
                    <option value="1" <?php echo ($item['danchuan'] == 1) ? 'selected' : ''; ?>>Có</option>
                </select>
            </div>
        </div>

        <div class="flex gap-2 pt-4">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                <i class="fas fa-save mr-1"></i> Cập nhật
            </button>
            <a href="thietbihckd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
