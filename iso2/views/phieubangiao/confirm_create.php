<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Xác Nhận Tạo Phiếu Bàn Giao';
require_once __DIR__ . '/../layouts/header.php'; 

$phieuCount = count($groupedByPhieu);
?>
<div class="max-w-7xl mx-auto bg-white rounded-lg shadow-md p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-check-circle mr-2 text-green-600"></i> Bước 2: Xác Nhận & Tạo Phiếu Bàn Giao
        </h1>
        <a href="phieubangiao.php?action=select" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
    </div>

    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
        <div class="flex items-center">
            <i class="fas fa-info-circle text-yellow-600 text-2xl mr-3"></i>
            <div>
                <p class="font-semibold text-yellow-800">Hệ thống sẽ tạo <?php echo $phieuCount; ?> phiếu bàn giao</p>
                <p class="text-sm text-yellow-700">Vì các thiết bị thuộc <?php echo $phieuCount; ?> phiếu yêu cầu khác nhau</p>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-times-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <?php $index = 0; foreach ($groupedByPhieu as $phieuyc => $devices): $index++; ?>
        <div class="border-2 border-gray-200 rounded-lg p-6 bg-gray-50">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold flex items-center">
                    <i class="fas fa-file-alt mr-2 text-blue-600"></i>
                    Phiếu Bàn Giao #<?php echo $index; ?> - Phiếu YC: <?php echo htmlspecialchars($phieuyc); ?>
                </h2>
                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded font-semibold">
                    <?php echo count($devices); ?> thiết bị
                </span>
            </div>

            <!-- Thông tin phiếu -->
            <div class="bg-white p-4 rounded-lg mb-4">
                <h3 class="font-semibold mb-3 text-gray-700 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>Thông Tin Phiếu
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            Số phiếu <span class="text-sm text-gray-500">(Tự động)</span>
                        </label>
                        <input type="text" value="<?php echo $this->model->getNextSoPhieu(); ?>" readonly
                               class="w-full px-3 py-2 border rounded bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            Ngày bàn giao <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="ngaybg_<?php echo htmlspecialchars($phieuyc); ?>" 
                               value="<?php echo date('Y-m-d'); ?>" required
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            Người giao <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nguoigiao_<?php echo htmlspecialchars($phieuyc); ?>" required
                               placeholder="Họ tên người giao thiết bị"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            Người nhận <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nguoinhan_<?php echo htmlspecialchars($phieuyc); ?>" required
                               placeholder="Họ tên người nhận thiết bị"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            Đơn vị giao <span class="text-sm text-gray-500">(Tự động)</span>
                        </label>
                        <input type="text" value="<?php echo htmlspecialchars($devices[0]['madv'] . ' - ' . ($devices[0]['tendv'] ?? '')); ?>" readonly
                               class="w-full px-3 py-2 border rounded bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            Đơn vị nhận <span class="text-red-500">*</span>
                        </label>
                        <select name="donvinhan_<?php echo htmlspecialchars($phieuyc); ?>" required
                                class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                            <option value="">-- Chọn đơn vị --</option>
                            <?php foreach ($donViList as $dv): ?>
                                <option value="<?php echo htmlspecialchars($dv['madv']); ?>">
                                    <?php echo htmlspecialchars($dv['tendv']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-gray-700 font-semibold mb-2">
                        Ghi chú chung
                    </label>
                    <textarea name="ghichu_<?php echo htmlspecialchars($phieuyc); ?>" rows="2"
                              placeholder="Ghi chú về phiếu bàn giao này..."
                              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"></textarea>
                </div>
                <div class="mt-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="duyet_<?php echo htmlspecialchars($phieuyc); ?>" value="1"
                               class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring focus:ring-green-200">
                        <span class="ml-2 font-semibold text-gray-700">
                            <i class="fas fa-check-circle text-green-600 mr-1"></i>Duyệt phiếu ngay (không lưu nháp)
                        </span>
                    </label>
                </div>
            </div>

            <!-- Danh sách thiết bị -->
            <div class="bg-white p-4 rounded-lg">
                <h3 class="font-semibold mb-3 text-gray-700 flex items-center">
                    <i class="fas fa-list mr-2"></i>Thiết Bị Bàn Giao (<?php echo count($devices); ?>)
                </h3>
                <div class="space-y-3">
                    <?php foreach ($devices as $device): ?>
                    <div class="border rounded-lg p-3 bg-gray-50">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-wrench text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($device['mavt'] . ' - ' . $device['tenvt']); ?>
                                </div>
                                <div class="text-sm text-gray-600 mt-1">
                                    <span class="inline-block mr-3">
                                        <i class="fas fa-barcode mr-1"></i>SN: <?php echo htmlspecialchars($device['somay'] ?: '-'); ?>
                                    </span>
                                    <span class="inline-block mr-3">
                                        <i class="fas fa-calendar mr-1"></i>Sửa xong: <?php echo date('d/m/Y', strtotime($device['ngaykt'])); ?>
                                    </span>
                                    <span class="inline-block">
                                        <i class="fas fa-id-badge mr-1"></i><?php echo htmlspecialchars($device['maql']); ?>
                                    </span>
                                </div>
                                <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Tình trạng khi bàn giao</label>
                                        <input type="text" name="tinhtrang_<?php echo $device['stt']; ?>" 
                                               value="Hoạt động tốt" 
                                               placeholder="VD: Hoạt động tốt, Đã kiểm tra..."
                                               class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Ghi chú thiết bị</label>
                                        <input type="text" name="ghichu_tb_<?php echo $device['stt']; ?>" 
                                               placeholder="Ghi chú riêng cho thiết bị này..."
                                               class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-blue-500">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Buttons -->
        <div class="flex gap-3 pt-4 border-t">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-semibold text-lg">
                <i class="fas fa-save mr-2"></i>Tạo <?php echo $phieuCount; ?> Phiếu Bàn Giao
            </button>
            <a href="phieubangiao.php?action=select" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại Bước 1
            </a>
            <a href="phieubangiao.php" class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-semibold">
                <i class="fas fa-times mr-2"></i>Hủy
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
