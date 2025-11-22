<?php 
require_once __DIR__ . '/../layouts/header.php'; 
$phieuCount = count($groupedByPhieu);
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-check-circle text-green-600 mr-2"></i>
                    Bước 3: Xác Nhận & Tạo Phiếu Bàn Giao
                </h1>
                <p class="text-gray-600 mt-1">Kiểm tra và điền thông tin cho <?php echo $phieuCount; ?> phiếu bàn giao</p>
            </div>
            <a href="phieubangiao_phieuyc.php?action=select_devices" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
        </div>
    </div>

    <!-- Progress Steps -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-green-600 text-white rounded-full flex items-center justify-center font-bold">
                    <i class="fas fa-check"></i>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-semibold text-gray-700">Bước 1</div>
                    <div class="text-xs text-gray-500">Chọn Phiếu YC</div>
                </div>
            </div>
            <div class="flex-1 h-1 bg-green-600 mx-4"></div>
            <div class="flex items-center">
                <div class="w-10 h-10 bg-green-600 text-white rounded-full flex items-center justify-center font-bold">
                    <i class="fas fa-check"></i>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-semibold text-gray-700">Bước 2</div>
                    <div class="text-xs text-gray-500">Chọn Thiết Bị</div>
                </div>
            </div>
            <div class="flex-1 h-1 bg-green-600 mx-4"></div>
            <div class="flex items-center">
                <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">
                    3
                </div>
                <div class="ml-3">
                    <div class="text-sm font-semibold text-gray-700">Bước 3</div>
                    <div class="text-xs text-gray-500">Xác Nhận</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thông báo -->
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
        <div class="flex items-center">
            <i class="fas fa-check-circle text-green-600 text-2xl mr-3"></i>
            <div>
                <p class="font-semibold text-green-800">Hệ thống sẽ tạo <?php echo $phieuCount; ?> phiếu bàn giao</p>
                <p class="text-sm text-green-700">
                    Bàn giao <strong><?php echo array_sum(array_map('count', $groupedByPhieu)); ?> thiết bị</strong> đã chọn 
                    (có thể còn thiết bị khác chưa bàn giao - sẽ xử lý đợt sau)
                </p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="phieubangiao_phieuyc.php?action=confirm" class="space-y-6">
        <?php $index = 0; foreach ($groupedByPhieu as $phieuyc => $devices): $index++; ?>
        <div class="bg-white rounded-lg shadow-md border-l-4 border-blue-500 p-6">
            <!-- Header phiếu -->
            <div class="flex items-center justify-between mb-4 pb-4 border-b">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-file-alt text-blue-600 mr-2"></i>
                    Phiếu BG #<?php echo $index; ?> - Phiếu YC: <span class="text-blue-600"><?php echo htmlspecialchars($phieuyc); ?></span>
                </h2>
                <span class="bg-blue-100 text-blue-800 px-4 py-2 rounded-full font-semibold">
                    <i class="fas fa-wrench mr-1"></i><?php echo count($devices); ?> thiết bị
                </span>
            </div>

            <!-- Thông tin phiếu -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Ngày bàn giao <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="ngaybg_<?php echo htmlspecialchars($phieuyc); ?>" 
                           value="<?php echo date('Y-m-d'); ?>" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Đơn vị giao <span class="text-gray-500 text-xs">(tự động)</span>
                    </label>
                    <input type="text" value="<?php echo htmlspecialchars($devices[0]['madv']); ?>" readonly
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Người giao <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nguoigiao_<?php echo htmlspecialchars($phieuyc); ?>" required
                           placeholder="Họ tên người giao thiết bị"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Người nhận <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nguoinhan_<?php echo htmlspecialchars($phieuyc); ?>" required
                           placeholder="Họ tên người nhận thiết bị"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Đơn vị nhận <span class="text-red-500">*</span>
                    </label>
                    <select name="donvinhan_<?php echo htmlspecialchars($phieuyc); ?>" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Chọn đơn vị nhận --</option>
                        <?php foreach ($donViList as $dv): ?>
                            <option value="<?php echo htmlspecialchars($dv['madv']); ?>">
                                <?php echo htmlspecialchars($dv['madv'] . ' - ' . $dv['tendv']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Ghi chú chung
                    </label>
                    <textarea name="ghichu_<?php echo htmlspecialchars($phieuyc); ?>" rows="2"
                              placeholder="Ghi chú về phiếu bàn giao này..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center cursor-pointer bg-green-50 p-3 rounded-lg border border-green-200">
                        <input type="checkbox" name="duyet_<?php echo htmlspecialchars($phieuyc); ?>" value="1"
                               class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-2 focus:ring-green-500">
                        <span class="ml-3 font-semibold text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Duyệt phiếu ngay (cập nhật bg=1 cho thiết bị)
                        </span>
                    </label>
                </div>
            </div>

            <!-- Danh sách thiết bị -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-list-ul mr-2 text-blue-600"></i>
                    Danh Sách Thiết Bị (<?php echo count($devices); ?>)
                </h3>
                <div class="space-y-3">
                    <?php foreach ($devices as $device): ?>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-wrench text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900 text-lg">
                                    <?php echo htmlspecialchars($device['mavt']); ?>
                                    <?php if (!empty($device['tenvt'])): ?>
                                        <span class="text-gray-600 font-normal">- <?php echo htmlspecialchars($device['tenvt']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm text-gray-600 mt-2 grid grid-cols-2 md:grid-cols-3 gap-2">
                                    <div>
                                        <i class="fas fa-barcode text-gray-400 mr-1"></i>
                                        <strong>SN:</strong> <?php echo htmlspecialchars($device['somay'] ?: '-'); ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-id-badge text-gray-400 mr-1"></i>
                                        <strong>Mã QL:</strong> <?php echo htmlspecialchars($device['maql']); ?>
                                    </div>
                                    <?php if ($device['ngaykt'] && $device['ngaykt'] != '0000-00-00'): ?>
                                    <div>
                                        <i class="fas fa-calendar-check text-gray-400 mr-1"></i>
                                        <strong>Sửa xong:</strong> <?php echo date('d/m/Y', strtotime($device['ngaykt'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Tình trạng và ghi chú thiết bị -->
                                <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                                            Tình trạng bàn giao
                                        </label>
                                        <input type="text" 
                                               name="tinhtrang_<?php echo $device['stt']; ?>" 
                                               value="Hoạt động tốt" 
                                               placeholder="VD: Hoạt động tốt"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                                            Ghi chú thiết bị
                                        </label>
                                        <input type="text" 
                                               name="ghichu_device_<?php echo $device['stt']; ?>" 
                                               placeholder="Ghi chú riêng cho thiết bị này..."
                                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
        <div class="flex gap-3 justify-end bg-white rounded-lg shadow-md p-6">
            <a href="phieubangiao_phieuyc.php?action=select" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
            <button type="submit" 
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold">
                <i class="fas fa-save mr-2"></i>Tạo <?php echo $phieuCount; ?> Phiếu Bàn Giao
            </button>
        </div>
    </form>

    <!-- Lưu ý -->
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mt-6 rounded">
        <div class="flex">
            <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
            <div>
                <h3 class="font-semibold text-yellow-800 mb-1">Lưu ý quan trọng</h3>
                <ul class="text-sm text-yellow-700 space-y-1">
                    <li>• Mỗi phiếu YC sẽ tạo ra <strong>1 phiếu bàn giao riêng</strong></li>
                    <li>• Nếu chọn <strong>"Duyệt phiếu ngay"</strong>, trạng thái bg của thiết bị sẽ được cập nhật thành 1</li>
                    <li>• Nếu không duyệt, phiếu sẽ lưu ở trạng thái nháp (trangthai=0)</li>
                    <li>• Kiểm tra kỹ thông tin trước khi tạo</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
