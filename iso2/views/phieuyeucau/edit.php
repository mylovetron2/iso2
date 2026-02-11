<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Sửa Phiếu ' . htmlspecialchars($detail['summary']['phieu']);
require_once __DIR__ . '/../layouts/header.php'; 

$summary = $detail['summary'];
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-edit mr-2"></i> Sửa Phiếu: <?php echo htmlspecialchars($summary['phieu']); ?>
        </h1>
        <a href="phieuyeucau.php?action=view&phieu=<?php echo urlencode($summary['phieu']); ?>" 
           class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="bg-yellow-50 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-4">
        <i class="fas fa-info-circle mr-1"></i> 
        <strong>Lưu ý:</strong> Sửa thông tin chung sẽ áp dụng cho tất cả <?php echo $summary['so_thietbi']; ?> thiết bị trong phiếu này.
        Để sửa thông tin riêng của từng thiết bị, vui lòng truy cập trang chi tiết từng thiết bị.
    </div>

    <form method="post" class="space-y-6">
        <!-- Thông tin chung phiếu -->
        <div class="bg-gray-50 rounded-lg p-4">
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                <i class="fas fa-file-alt mr-2"></i> Thông tin phiếu
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold mb-1">Số phiếu</label>
                    <input type="text" value="<?php echo htmlspecialchars($summary['phieu']); ?>" 
                           class="w-full border rounded px-3 py-2 bg-gray-100" disabled>
                    <p class="text-xs text-gray-600 mt-1">Không thể thay đổi số phiếu</p>
                </div>
                
                <div>
                    <label class="block font-semibold mb-1">
                        Ngày yêu cầu <span class="text-red-600">*</span>
                    </label>
                    <input type="date" name="ngayyc" 
                           value="<?php echo isset($_POST['ngayyc']) ? htmlspecialchars($_POST['ngayyc']) : $summary['ngayyc']; ?>" 
                           class="w-full border rounded px-3 py-2" required>
                </div>
                
                <div>
                    <label class="block font-semibold mb-1">
                        Đơn vị <span class="text-red-600">*</span>
                    </label>
                    <select name="madv" class="w-full border rounded px-3 py-2" required>
                        <option value="">-- Chọn đơn vị --</option>
                        <?php foreach ($donViList as $dv): ?>
                            <option value="<?php echo htmlspecialchars($dv['madv']); ?>"
                                    <?php 
                                    $selectedMadv = isset($_POST['madv']) ? $_POST['madv'] : $summary['madv'];
                                    echo ($selectedMadv === $dv['madv']) ? 'selected' : ''; 
                                    ?>>
                                <?php echo htmlspecialchars($dv['tendv']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block font-semibold mb-1">Nhóm sửa chữa</label>
                    <select name="nhomsc" class="w-full border rounded px-3 py-2">
                        <option value="">-- Chọn nhóm --</option>
                        <?php 
                        $selectedNhom = isset($_POST['nhomsc']) ? $_POST['nhomsc'] : $summary['nhomsc'];
                        $nhomOptions = ['CNC', 'RDNGA'];
                        foreach ($nhomOptions as $nhom):
                        ?>
                            <option value="<?php echo $nhom; ?>" <?php echo ($selectedNhom === $nhom) ? 'selected' : ''; ?>>
                                <?php echo $nhom; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block font-semibold mb-1">Người yêu cầu</label>
                    <input type="text" name="ngyeucau" 
                           value="<?php echo isset($_POST['ngyeucau']) ? htmlspecialchars($_POST['ngyeucau']) : htmlspecialchars($summary['ngyeucau']); ?>" 
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block font-semibold mb-1">Người nhận yêu cầu</label>
                    <input type="text" name="ngnhyeucau" 
                           value="<?php echo isset($_POST['ngnhyeucau']) ? htmlspecialchars($_POST['ngnhyeucau']) : htmlspecialchars($summary['ngnhyeucau']); ?>" 
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block font-semibold mb-1">Điện thoại</label>
                    <input type="text" name="dienthoai" 
                           value="<?php echo isset($_POST['dienthoai']) ? htmlspecialchars($_POST['dienthoai']) : htmlspecialchars($summary['dienthoai']); ?>" 
                           class="w-full border rounded px-3 py-2">
                </div>
            </div>
            
            <div class="mt-4">
                <label class="block font-semibold mb-1">Công việc yêu cầu</label>
                <textarea name="cv" rows="3" class="w-full border rounded px-3 py-2"><?php echo isset($_POST['cv']) ? htmlspecialchars($_POST['cv']) : htmlspecialchars($summary['cv']); ?></textarea>
            </div>
            
            <div class="mt-4">
                <label class="block font-semibold mb-1">Yêu cầu thêm từ khách hàng</label>
                <textarea name="ycthemkh" rows="3" class="w-full border rounded px-3 py-2"><?php echo isset($_POST['ycthemkh']) ? htmlspecialchars($_POST['ycthemkh']) : htmlspecialchars($summary['ycthemkh']); ?></textarea>
            </div>
        </div>

        <!-- Submit buttons -->
        <div class="flex gap-2">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                <i class="fas fa-save mr-1"></i> Lưu thay đổi
            </button>
            <a href="phieuyeucau.php?action=view&phieu=<?php echo urlencode($summary['phieu']); ?>" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded text-center">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
