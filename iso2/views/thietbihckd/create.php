<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Thêm Thiết Bị HC/KĐ';
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-plus-circle mr-2"></i> Thêm Thiết Bị HC/KĐ
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
                       value="<?php echo isset($_POST['mavattu']) ? htmlspecialchars($_POST['mavattu']) : ''; ?>">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Tên viết tắt <span class="text-red-500">*</span></label>
                <input type="text" name="tenviettat" required 
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo isset($_POST['tenviettat']) ? htmlspecialchars($_POST['tenviettat']) : ''; ?>">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Tên thiết bị <span class="text-red-500">*</span></label>
                <textarea name="tenthietbi" required rows="3"
                          class="w-full border rounded px-3 py-2"><?php echo isset($_POST['tenthietbi']) ? htmlspecialchars($_POST['tenthietbi']) : ''; ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Số máy <span class="text-red-500">*</span></label>
                <input type="text" name="somay" required 
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo isset($_POST['somay']) ? htmlspecialchars($_POST['somay']) : ''; ?>">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Hãng sản xuất</label>
                <input type="text" name="hangsx" 
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo isset($_POST['hangsx']) ? htmlspecialchars($_POST['hangsx']) : ''; ?>">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Bộ phận sử dụng <span class="text-red-500">*</span></label>
                <input type="text" name="bophansh" required list="bophan-list"
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo isset($_POST['bophansh']) ? htmlspecialchars($_POST['bophansh']) : ''; ?>">
                <datalist id="bophan-list">
                    <?php foreach ($boPhanList as $bp): ?>
                        <option value="<?php echo htmlspecialchars($bp); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Chủ sở hữu</label>
                <input type="text" name="chusohuu" 
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo isset($_POST['chusohuu']) ? htmlspecialchars($_POST['chusohuu']) : ''; ?>">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Loại thiết bị</label>
                <input type="text" name="loaitb" list="loaitb-list"
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo isset($_POST['loaitb']) ? htmlspecialchars($_POST['loaitb']) : ''; ?>">
                <datalist id="loaitb-list">
                    <?php foreach ($loaiTBList as $lt): ?>
                        <option value="<?php echo htmlspecialchars($lt); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Ngày kiểm tra/nghiệm thu</label>
                <input type="date" name="ngayktnghiemthu" 
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo isset($_POST['ngayktnghiemthu']) ? htmlspecialchars($_POST['ngayktnghiemthu']) : ''; ?>">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Thời hạn kiểm định (tháng)</label>
                <input type="text" name="thoihankd" 
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo isset($_POST['thoihankd']) ? htmlspecialchars($_POST['thoihankd']) : ''; ?>"
                       placeholder="Ví dụ: 12, 24, 36">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">TLKT</label>
                <input type="text" name="tlkt" 
                       class="w-full border rounded px-3 py-2" 
                       value="<?php echo isset($_POST['tlkt']) ? htmlspecialchars($_POST['tlkt']) : ''; ?>">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Dần chuẩn</label>
                <select name="danchuan" class="w-full border rounded px-3 py-2">
                    <option value="0" <?php echo (isset($_POST['danchuan']) && $_POST['danchuan'] == '0') ? 'selected' : ''; ?>>Không</option>
                    <option value="1" <?php echo (isset($_POST['danchuan']) && $_POST['danchuan'] == '1') ? 'selected' : ''; ?>>Có</option>
                </select>
            </div>
        </div>

        <div class="flex gap-2 pt-4">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                <i class="fas fa-save mr-1"></i> Lưu
            </button>
            <a href="thietbihckd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
