<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-info-circle mr-2"></i> Chi tiết Thiết bị
        </h1>
        <div class="flex gap-2">
            <?php if (hasPermission(PERMISSION_PROJECT_EDIT)): ?>
            <a href="thietbihotro.php?action=edit&id=<?php echo $device['stt']; ?>" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                <i class="fas fa-edit mr-1"></i> Sửa
            </a>
            <?php endif; ?>
            <a href="thietbihotro.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-600 mb-1">Tên thiết bị</label>
                <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($device['tenthietbi']); ?></p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-600 mb-1">Chủ sở hữu</label>
                <p class="text-gray-800"><?php echo htmlspecialchars($device['chusohuu']); ?></p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-600 mb-1">Tên vật tư</label>
                <p class="text-gray-800"><?php echo htmlspecialchars($device['tenvt']); ?></p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-600 mb-1">Serial Number</label>
                <p class="text-gray-800">
                    <code class="bg-gray-100 px-3 py-1 rounded"><?php echo htmlspecialchars($device['serialnumber']); ?></code>
                </p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-600 mb-1">Hồ sơ máy</label>
                <p class="text-gray-800"><?php echo htmlspecialchars($device['hosomay']); ?></p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-600 mb-1">Tỷ lệ kiểm tra</label>
                <p class="text-gray-800"><?php echo htmlspecialchars($device['tlkt']); ?></p>
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-blue-50 p-4 rounded-lg">
                <label class="block text-sm font-semibold text-gray-600 mb-2">Thông tin kiểm định</label>
                
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ngày kiểm định:</span>
                        <span class="font-semibold">
                            <?php echo $device['ngaykd'] ? date('d/m/Y', strtotime($device['ngaykd'])) : '-'; ?>
                        </span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Hạn kiểm định:</span>
                        <span class="font-semibold"><?php echo $device['hankd']; ?> tháng</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ngày KĐ tiếp theo:</span>
                        <span class="font-semibold">
                            <?php 
                            if ($device['ngaykdtt']) {
                                echo date('d/m/Y', strtotime($device['ngaykdtt']));
                            } else {
                                echo '-';
                            }
                            ?>
                        </span>
                    </div>
                    
                    <?php if ($device['ngaykdtt']): ?>
                    <div class="mt-3 pt-3 border-t border-blue-200">
                        <?php 
                        $ngaykdtt = strtotime($device['ngaykdtt']);
                        $today = strtotime('today');
                        $diff = ($ngaykdtt - $today) / 86400;
                        
                        if ($diff < 0): ?>
                            <div class="bg-red-100 text-red-800 px-3 py-2 rounded text-center font-semibold">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Đã quá hạn <?php echo abs(round($diff)); ?> ngày
                            </div>
                        <?php elseif ($diff <= 30): ?>
                            <div class="bg-yellow-100 text-yellow-800 px-3 py-2 rounded text-center font-semibold">
                                <i class="fas fa-clock mr-1"></i>
                                Còn <?php echo round($diff); ?> ngày
                            </div>
                        <?php else: ?>
                            <div class="bg-green-100 text-green-800 px-3 py-2 rounded text-center font-semibold">
                                <i class="fas fa-check-circle mr-1"></i>
                                Còn hạn
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Công dụng</label>
                    <p class="text-2xl font-bold text-blue-600"><?php echo $device['cdung']; ?></p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Thủ lý</label>
                    <p class="text-2xl font-bold text-blue-600"><?php echo $device['thly']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 pt-6 border-t border-gray-200 text-sm text-gray-500 text-center">
        Mã thiết bị: #<?php echo $device['stt']; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
