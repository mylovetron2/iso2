<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Phiếu Kiểm Tra HC/KĐ';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-clipboard-check mr-2"></i> 
        Phiếu Kiểm Tra Sau Hiệu Chuẩn
    </h1>

    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (!$hoSo): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            <p>Không tìm thấy hồ sơ hiệu chuẩn. Vui lòng nhập hồ sơ trước khi kiểm tra.</p>
            <a href="bangcanhbao.php" class="text-blue-600 hover:text-blue-800 font-medium">
                ← Quay lại Bảng Cảnh Báo
            </a>
        </div>
    <?php else: ?>
        <!-- Thông tin thiết bị -->
        <div class="bg-gray-50 border rounded p-4 mb-6">
            <h2 class="font-bold text-lg mb-3">Thông Tin Thiết Bị</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="font-medium">Tên thiết bị:</span>
                    <span class="ml-2"><?php echo htmlspecialchars($thietBi['tenviettat'] ?? $thietBi['tenthietbi'] ?? ''); ?></span>
                </div>
                <div>
                    <span class="font-medium">Số máy:</span>
                    <span class="ml-2"><?php echo htmlspecialchars($thietBi['somay'] ?? ''); ?></span>
                </div>
                <div>
                    <span class="font-medium">Số hồ sơ:</span>
                    <span class="ml-2"><?php echo htmlspecialchars($hoSo['sohs'] ?? ''); ?></span>
                </div>
                <div>
                    <span class="font-medium">Ngày HC:</span>
                    <span class="ml-2">
                        <?php echo $hoSo['ngayhc'] ? date('d/m/Y', strtotime($hoSo['ngayhc'])) : ''; ?>
                    </span>
                </div>
                <div>
                    <span class="font-medium">Người HC:</span>
                    <span class="ml-2"><?php echo htmlspecialchars($hoSo['nhanvien'] ?? ''); ?></span>
                </div>
                <div>
                    <span class="font-medium">Nơi thực hiện:</span>
                    <span class="ml-2"><?php echo htmlspecialchars($hoSo['noithuchien'] ?? ''); ?></span>
                </div>
            </div>
        </div>

        <!-- Form Kiểm Tra -->
        <form method="post" action="bangcanhbao.php?action=savekt" class="space-y-4">
            <input type="hidden" name="stt" value="<?php echo $hoSo['stt']; ?>">

            <!-- Tình Trạng Kiểm Tra -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Tình Trạng Kiểm Tra <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-6">
                    <label class="flex items-center">
                        <input type="radio" name="ttkt" value="Tốt" 
                               <?php echo (empty($hoSo['ttkt']) || $hoSo['ttkt'] === 'Tốt') ? 'checked' : ''; ?>
                               class="mr-2">
                        <span class="text-green-600 font-medium">
                            <i class="fas fa-check-circle mr-1"></i> Tốt
                        </span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="ttkt" value="Hỏng" 
                               <?php echo ($hoSo['ttkt'] === 'Hỏng') ? 'checked' : ''; ?>
                               class="mr-2">
                        <span class="text-red-600 font-medium">
                            <i class="fas fa-times-circle mr-1"></i> Hỏng
                        </span>
                    </label>
                </div>
            </div>

            <!-- Phương Pháp Chuẩn -->
            <div>
                <label class="block text-sm font-medium mb-2">Phương Pháp Chuẩn</label>
                <div class="flex gap-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="danchuan" value="on" 
                               <?php echo ($hoSo['danchuan'] === 'on') ? 'checked' : ''; ?>
                               class="mr-2">
                        Dẫn chuẩn
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="mauchuan" value="on" 
                               <?php echo ($hoSo['mauchuan'] === 'on') ? 'checked' : ''; ?>
                               class="mr-2">
                        Chuẩn qua mẫu chuẩn
                    </label>
                </div>
            </div>

            <!-- Thiết Bị Dẫn Chuẩn -->
            <div>
                <label class="block text-sm font-medium mb-2">Thiết Bị Dẫn Chuẩn Sử Dụng</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <select name="thietbidc<?php echo $i; ?>" class="border rounded px-3 py-2 text-sm">
                            <option value="">-- Chọn thiết bị dẫn chuẩn <?php echo $i; ?> --</option>
                            <?php if (!empty($danhChuanList)): ?>
                                <?php foreach ($danhChuanList as $dc): ?>
                                    <option value="<?php echo htmlspecialchars($dc['mavattu']); ?>"
                                            <?php echo (isset($hoSo["thietbidc$i"]) && $hoSo["thietbidc$i"] === $dc['mavattu']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dc['tenviettat'] . ' - ' . $dc['somay']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    <?php endfor; ?>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    * Chỉ chọn các thiết bị dẫn chuẩn đã được hiệu chuẩn và còn hiệu lực
                </p>
            </div>

            <!-- Ghi Chú -->
            <div>
                <label class="block text-sm font-medium mb-2">Ghi Chú Kiểm Tra</label>
                <textarea name="ghichu_kt" rows="3" class="border rounded px-3 py-2 w-full" 
                          placeholder="Nhập ghi chú về kết quả kiểm tra (nếu có)..."><?php echo htmlspecialchars($hoSo['ghichu'] ?? ''); ?></textarea>
            </div>

            <!-- Kết Quả Trước Đó -->
            <?php if (!empty($hoSo['ngayhc'])): ?>
                <div class="bg-blue-50 border border-blue-200 rounded p-4">
                    <h3 class="font-medium mb-2">
                        <i class="fas fa-history mr-1"></i> Thông Tin Hiện Tại
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="font-medium">Tình trạng:</span>
                            <span class="ml-2 
                                <?php echo ($hoSo['ttkt'] === 'Tốt') ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo htmlspecialchars($hoSo['ttkt'] ?? 'Chưa xác định'); ?>
                            </span>
                        </div>
                        <div>
                            <span class="font-medium">Công việc:</span>
                            <span class="ml-2"><?php echo htmlspecialchars($hoSo['congviec'] ?? ''); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Buttons -->
            <div class="flex gap-2 pt-4 border-t">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    <i class="fas fa-save mr-1"></i> Lưu Kết Quả
                </button>
                <a href="bangcanhbao.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded inline-block">
                    <i class="fas fa-arrow-left mr-1"></i> Quay Lại
                </a>
                <button type="button" onclick="window.print()" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-6 rounded ml-auto">
                    <i class="fas fa-print mr-1"></i> In Phiếu
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<style>
@media print {
    .no-print, button, form button, a {
        display: none !important;
    }
    body {
        font-size: 12px;
    }
    .bg-white {
        box-shadow: none;
    }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
