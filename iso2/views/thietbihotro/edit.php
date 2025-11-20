<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <h1 class="text-xl md:text-2xl font-bold mb-4 md:mb-6 flex items-center">
        <i class="fas fa-edit mr-2"></i> Sửa Thiết bị Hỗ trợ
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

    <form method="POST" enctype="multipart/form-data" class="space-y-3 md:space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Tên thiết bị <span class="text-red-500">*</span>
                </label>
                <input type="text" name="tenthietbi" required
                       value="<?php echo isset($_POST['tenthietbi']) ? htmlspecialchars($_POST['tenthietbi']) : htmlspecialchars($device['tenthietbi']); ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2 text-sm md:text-base">
                    Chủ sở hữu <span class="text-red-500">*</span>
                </label>
                <select name="chusohuu" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 text-sm md:text-base">
                    <option value="">-- Chọn chủ sở hữu --</option>
                    <?php 
                    $currentChusohuu = isset($_POST['chusohuu']) ? $_POST['chusohuu'] : $device['chusohuu'];
                    foreach ($chusohuuList as $value => $label): 
                    ?>
                        <option value="<?php echo htmlspecialchars($value); ?>" 
                                <?php echo ($currentChusohuu === $value) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-gray-700 font-semibold mb-2">Tên vật tư</label>
            <textarea name="tenvt" rows="2"
                      class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['tenvt']) ? htmlspecialchars($_POST['tenvt']) : htmlspecialchars($device['tenvt']); ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Serial Number</label>
                <input type="text" name="serialnumber"
                       value="<?php echo isset($_POST['serialnumber']) ? htmlspecialchars($_POST['serialnumber']) : htmlspecialchars($device['serialnumber']); ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Ngày kiểm định</label>
                <input type="date" name="ngaykd" id="ngaykd"
                       value="<?php echo isset($_POST['ngaykd']) ? $_POST['ngaykd'] : $device['ngaykd']; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Hạn kiểm định (tháng)</label>
                <input type="number" name="hankd" id="hankd" min="0"
                       value="<?php echo isset($_POST['hankd']) ? $_POST['hankd'] : $device['hankd']; ?>"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Ngày KĐ tiếp theo <span class="text-xs text-gray-500">(tự động tính)</span></label>
                <input type="date" name="ngaykdtt" id="ngaykdtt" readonly
                       value="<?php echo isset($_POST['ngaykdtt']) ? $_POST['ngaykdtt'] : $device['ngaykdtt']; ?>"
                       class="w-full px-3 py-2 border rounded bg-gray-50 focus:outline-none focus:ring focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <div>
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="cdung" value="1"
                           <?php echo (isset($_POST['cdung']) ? $_POST['cdung'] == 1 : $device['cdung'] == 1) ? 'checked' : ''; ?>
                           class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring focus:ring-blue-200">
                    <span class="ml-2 text-gray-700 font-semibold">TB chuyên dụng của Xưởng</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-7">Đánh dấu nếu là thiết bị chuyên dụng</p>
            </div>

            <div>
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="thly" value="1"
                           <?php echo (isset($_POST['thly']) ? $_POST['thly'] == 1 : $device['thly'] == 1) ? 'checked' : ''; ?>
                           class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring focus:ring-blue-200">
                    <span class="ml-2 text-gray-700 font-semibold">Thanh lý</span>
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-7">Đánh dấu nếu thiết bị đã thanh lý</p>
            </div>
        </div>
        
        <div class="border-t pt-4 mt-2">
            <h3 class="text-base md:text-lg font-semibold mb-3 text-gray-700"><i class="fas fa-folder-open mr-2"></i>Tài liệu đính kèm</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Hồ sơ kỹ thuật</label>
                    <?php if (!empty($device['hosomay'])): ?>
                        <div class="mb-2 p-2 bg-gray-50 rounded text-sm">
                            <i class="fas fa-file mr-2 text-blue-600"></i>
                            <a href="/iso2/uploads/hosomay/<?php echo htmlspecialchars($device['hosomay']); ?>" target="_blank" class="text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($device['hosomay']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="hosomay" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">Chấp nhận: PDF, Word, Excel, Ảnh (Tối đa 5MB). Để trống nếu không đổi file.</p>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Tài liệu kỹ thuật</label>
                    <?php if (!empty($device['tlkt'])): ?>
                        <div class="mb-2 p-2 bg-gray-50 rounded text-sm">
                            <i class="fas fa-file mr-2 text-blue-600"></i>
                            <a href="/iso2/uploads/tlkt/<?php echo htmlspecialchars($device['tlkt']); ?>" target="_blank" class="text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($device['tlkt']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="tlkt" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">Chấp nhận: PDF, Word, Excel, Ảnh (Tối đa 5MB). Để trống nếu không đổi file.</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-2 pt-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 md:px-6 py-2 rounded text-sm md:text-base w-full md:w-auto">
                <i class="fas fa-save mr-1"></i> Cập nhật
            </button>
            <a href="thietbihotro.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 md:px-6 py-2 rounded inline-block text-sm md:text-base text-center w-full md:w-auto">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
        </div>
    </form>
</div>

<script>
// Tự động tính Ngày KĐ tiếp theo = Ngày KĐ + Hạn kiểm định (tháng)
function calculateNextDate() {
    const ngaykd = document.getElementById('ngaykd').value;
    const hankd = parseInt(document.getElementById('hankd').value) || 0;
    const ngaykdttInput = document.getElementById('ngaykdtt');
    
    if (ngaykd && hankd > 0) {
        const date = new Date(ngaykd);
        date.setMonth(date.getMonth() + hankd);
        
        // Format: YYYY-MM-DD
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        
        ngaykdttInput.value = `${year}-${month}-${day}`;
    } else {
        ngaykdttInput.value = '';
    }
}

document.getElementById('ngaykd').addEventListener('change', calculateNextDate);
document.getElementById('hankd').addEventListener('input', calculateNextDate);

// Tính toán ngay khi load trang nếu đã có dữ liệu
window.addEventListener('DOMContentLoaded', calculateNextDate);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
