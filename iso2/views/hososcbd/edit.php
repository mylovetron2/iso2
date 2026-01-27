<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Sửa Hồ sơ SCBĐ';

// Helper function to safely display text
if (!function_exists('displayText')) {
    function displayText($text) {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="max-w-6xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <h1 class="text-xl md:text-2xl font-bold mb-4 md:mb-6 flex items-center">
        <i class="fas fa-edit mr-2"></i> Sửa Hồ sơ Sửa chữa Bảo dưỡng
    </h1>

    <?php if (isset($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <!-- Action Buttons at Top -->
    <div class="flex gap-3 mb-6 p-4 bg-gray-50 rounded border-2 border-blue-500">
        <button type="button" id="saveButton" onclick="document.querySelector('form').submit();" 
                style="background-color: #2563eb; color: white; padding: 12px 24px; border-radius: 8px; font-size: 18px; font-weight: bold; border: none; cursor: pointer; display: inline-block;">
            <i class="fas fa-save" style="margin-right: 8px;"></i>Cập nhật hồ sơ
        </button>
        <a href="hososcbd.php" 
           style="background-color: #6b7280; color: white; padding: 12px 24px; border-radius: 8px; font-size: 18px; font-weight: bold; text-decoration: none; display: inline-block;">
            <i class="fas fa-times" style="margin-right: 8px;"></i>Hủy
        </a>
    </div>

    <form method="POST" class="space-y-6">
        <!-- Thông tin cơ bản -->
        <div class="border-l-4 border-blue-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-blue-700">
                <i class="fas fa-info-circle mr-2"></i>Thông tin cơ bản
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Số phiếu</label>
                    <input type="text" name="phieu" value="<?php echo isset($error) && isset($_POST['phieu']) ? htmlspecialchars($_POST['phieu']) : htmlspecialchars($item['phieu'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ngày yêu cầu <span class="text-red-500">*</span></label>
                    <input type="date" name="ngayyc" required value="<?php echo isset($error) && isset($_POST['ngayyc']) ? $_POST['ngayyc'] : ($item['ngayyc'] ?? date('Y-m-d')); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded p-3 mt-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-600">Mã quản lý (tự động):</label>
                        <div class="font-mono text-sm font-semibold text-blue-800 bg-white px-2 py-1 rounded mt-1">
                            <?php echo htmlspecialchars($item['maql'] ?? 'Chưa tạo'); ?>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Mã hồ sơ (tự động):</label>
                        <div class="font-mono text-sm font-semibold text-blue-800 bg-white px-2 py-1 rounded mt-1">
                            <?php echo htmlspecialchars($item['hoso'] ?? 'Chưa tạo'); ?>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-600 mt-2">
                    <i class="fas fa-info-circle"></i> Mã này sẽ được cập nhật tự động khi lưu thay đổi
                </p>
            </div>
        </div>

        <!-- Thông tin thiết bị -->
        <div class="border-l-4 border-green-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-green-700">
                <i class="fas fa-cogs mr-2"></i>Thông tin thiết bị
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Mã vật tư <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="text" name="mavt" id="mavt" required readonly value="<?php echo isset($error) && isset($_POST['mavt']) ? htmlspecialchars($_POST['mavt']) : htmlspecialchars($item['mavt'] ?? ''); ?>"
                               class="flex-1 px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 bg-blue-50">
                        <button type="button" onclick="openDeviceSearch()" 
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-semibold transition-colors">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle"></i> Nhấn nút tìm kiếm để thay đổi thiết bị</p>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Số máy <span class="text-red-500">*</span></label>
                    <input type="text" name="somay" id="somay" required readonly value="<?php echo isset($error) && isset($_POST['somay']) ? htmlspecialchars($_POST['somay']) : htmlspecialchars($item['somay'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 bg-blue-50">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Model <span class="text-red-500">*</span></label>
                    <input type="text" name="model" id="model" required readonly value="<?php echo isset($error) && isset($_POST['model']) ? htmlspecialchars($_POST['model']) : htmlspecialchars($item['model'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 bg-blue-50">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Vị trí máy BD</label>
                    <select name="vitrimaybd" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 vitri-select"
                            data-current="<?php echo isset($error) && isset($_POST['vitrimaybd']) ? htmlspecialchars($_POST['vitrimaybd']) : htmlspecialchars($item['vitrimaybd'] ?? ''); ?>">
                        <option value="">-- Chọn vị trí --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Lô</label>
                    <select name="lo" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 lo-select"
                            data-current="<?php echo isset($error) && isset($_POST['lo']) ? htmlspecialchars($_POST['lo']) : htmlspecialchars($item['lo'] ?? ''); ?>">
                        <option value="">-- Chọn lô --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Giếng</label>
                    <input type="text" name="gieng" value="<?php echo isset($error) && isset($_POST['gieng']) ? htmlspecialchars($_POST['gieng']) : htmlspecialchars($item['gieng'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Mỏ</label>
                    <select name="mo" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 mo-select"
                            data-current="<?php echo isset($error) && isset($_POST['mo']) ? htmlspecialchars($_POST['mo']) : htmlspecialchars($item['mo'] ?? ''); ?>">
                        <option value="">-- Chọn mỏ --</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Thông tin đơn vị & yêu cầu -->
        <div class="border-l-4 border-purple-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-purple-700">
                <i class="fas fa-building mr-2"></i>Thông tin đơn vị & Yêu cầu
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Đơn vị <span class="text-red-500">*</span></label>
                    <select name="madv" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                        <option value="">-- Chọn đơn vị --</option>
                        <?php 
                        $selectedMadv = isset($error) && isset($_POST['madv']) ? $_POST['madv'] : ($item['madv'] ?? '');
                        foreach ($donViList as $dv): 
                        ?>
                            <option value="<?php echo htmlspecialchars($dv['madv']); ?>" 
                                    <?php echo ($selectedMadv === $dv['madv']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dv['tendv']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Điện thoại</label>
                    <input type="text" name="dienthoai" value="<?php echo isset($error) && isset($_POST['dienthoai']) ? htmlspecialchars($_POST['dienthoai']) : htmlspecialchars($item['dienthoai'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Người yêu cầu</label>
                    <input type="text" name="ngyeucau" value="<?php echo isset($error) && isset($_POST['ngyeucau']) ? htmlspecialchars($_POST['ngyeucau']) : htmlspecialchars($item['ngyeucau'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Người nhận yêu cầu</label>
                    <input type="text" name="ngnhyeucau" value="<?php echo isset($error) && isset($_POST['ngnhyeucau']) ? htmlspecialchars($_POST['ngnhyeucau']) : htmlspecialchars($item['ngnhyeucau'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-semibold mb-2">Công việc <span class="text-red-500">*</span></label>
                    <select name="cv" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                        <?php 
                        $currentCv = isset($error) && isset($_POST['cv']) ? $_POST['cv'] : ($item['cv'] ?? 'SC');
                        ?>
                        <option value="KT" <?php echo ($currentCv === 'KT') ? 'selected' : ''; ?>>KT - Kiểm Tra</option>
                        <option value="BD" <?php echo ($currentCv === 'BD') ? 'selected' : ''; ?>>BD - Bảo Dưỡng</option>
                        <option value="SC" <?php echo ($currentCv === 'SC') ? 'selected' : ''; ?>>SC - Sửa Chữa</option>
                        <option value="BDDK" <?php echo ($currentCv === 'BDDK') ? 'selected' : ''; ?>>BDDK - Bảo Dưỡng Định Kỳ</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-semibold mb-2">Yêu cầu thêm của KH</label>
                    <textarea name="ycthemkh" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($error) && isset($_POST['ycthemkh']) ? htmlspecialchars($_POST['ycthemkh']) : displayText($item['ycthemkh'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Thông tin sửa chữa (HIDDEN) -->
        <div class="hidden border-l-4 border-orange-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-orange-700">
                <i class="fas fa-wrench mr-2"></i>Thông tin sửa chữa
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Nhóm SC <span class="text-red-500">*</span></label>
                    <input type="text" name="nhomsc" required value="<?php echo isset($error) && isset($_POST['nhomsc']) ? htmlspecialchars($_POST['nhomsc']) : htmlspecialchars($item['nhomsc'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ngày bắt đầu TT</label>
                    <input type="date" name="ngaybdtt" value="<?php echo isset($error) && isset($_POST['ngaybdtt']) ? $_POST['ngaybdtt'] : ($item['ngaybdtt'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ngày thực hiện</label>
                    <input type="date" name="ngayth" value="<?php echo isset($error) && isset($_POST['ngayth']) ? $_POST['ngayth'] : ($item['ngayth'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ngày kết thúc</label>
                    <input type="date" name="ngaykt" value="<?php echo isset($error) && isset($_POST['ngaykt']) ? $_POST['ngaykt'] : ($item['ngaykt'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div style="display: none;">
                    <input type="hidden" name="solg" value="<?php echo isset($error) && isset($_POST['solg']) ? $_POST['solg'] : ($item['solg'] ?? '0'); ?>">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Tình trạng kỹ thuật trước khi SC/BĐ</label>
                    <textarea name="ttktbefore" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($error) && isset($_POST['ttktbefore']) ? htmlspecialchars($_POST['ttktbefore']) : displayText($item['ttktbefore'] ?? ''); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Hỏng hóc</label>
                    <textarea name="honghoc" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($error) && isset($_POST['honghoc']) ? htmlspecialchars($_POST['honghoc']) : displayText($item['honghoc'] ?? ''); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Khắc phục</label>
                    <textarea name="khacphuc" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($error) && isset($_POST['khacphuc']) ? htmlspecialchars($_POST['khacphuc']) : displayText($item['khacphuc'] ?? ''); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Nội dung sửa chữa</label>
                    <textarea name="noidung" rows="4" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($error) && isset($_POST['noidung']) ? htmlspecialchars($_POST['noidung']) : displayText($item['noidung'] ?? ''); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Tình trạng kỹ thuật sau khi SC/BĐ</label>
                    <?php $ttktafter_value = isset($error) && isset($_POST['ttktafter']) ? $_POST['ttktafter'] : ($item['ttktafter'] ?? ''); ?>
                    <select name="ttktafter" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                        <option value="">-- Chọn trạng thái --</option>
                        <option value="Đạt" <?php echo $ttktafter_value === 'Đạt' ? 'selected' : ''; ?>>Đạt</option>
                        <option value="Hỏng" <?php echo $ttktafter_value === 'Hỏng' ? 'selected' : ''; ?>>Hỏng (Không khắc phục được)</option>
                        <option value="Chờ vật tư thay thế" <?php echo $ttktafter_value === 'Chờ vật tư thay thế' ? 'selected' : ''; ?>>Chờ vật tư thay thế</option>
                        <option value="Chưa kết luận" <?php echo $ttktafter_value === 'Chưa kết luận' ? 'selected' : ''; ?>>Chưa kết luận</option>
                        <option value="Đang sửa chữa" <?php echo $ttktafter_value === 'Đang sửa chữa' ? 'selected' : ''; ?>>Đang sửa chữa</option>
                        <option value="TTKTDB" <?php echo $ttktafter_value === 'TTKTDB' ? 'selected' : ''; ?>>TTKT Đặc biệt</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Kết luận</label>
                    <textarea name="ketluan" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($error) && isset($_POST['ketluan']) ? htmlspecialchars($_POST['ketluan']) : displayText($item['ketluan'] ?? ''); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Xem xét xưởng</label>
                    <textarea name="xemxetxuong" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($error) && isset($_POST['xemxetxuong']) ? htmlspecialchars($_POST['xemxetxuong']) : htmlspecialchars($item['xemxetxuong'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Thiết bị đo SC (HIDDEN) -->
        <div class="hidden border-l-4 border-teal-500 pl-4">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-lg font-bold text-teal-700">
                    <i class="fas fa-tools mr-2"></i>Thiết bị hỗ trợ
                </h2>
                <button type="button" onclick="addDeviceRow()" class="bg-teal-600 hover:bg-teal-700 text-white px-3 py-1 rounded text-sm">
                    <i class="fas fa-plus mr-1"></i> Thêm thiết bị
                </button>
            </div>
            <div id="deviceList" class="space-y-2">
                <?php 
                // Collect existing devices
                $devices = [];
                for ($i = 0; $i <= 4; $i++) {
                    $tbField = $i == 0 ? 'tbdosc' : "tbdosc$i";
                    $serialField = $i == 0 ? 'serialtbdosc' : "serialtbdosc$i";
                    $tbValue = isset($error) && isset($_POST[$tbField]) ? $_POST[$tbField] : ($item[$tbField] ?? '');
                    $serialValue = isset($error) && isset($_POST[$serialField]) ? $_POST[$serialField] : ($item[$serialField] ?? '');
                    if (!empty($tbValue) || !empty($serialValue)) {
                        $devices[] = [
                            'tb' => $tbValue,
                            'serial' => $serialValue,
                            'tbField' => $tbField,
                            'serialField' => $serialField
                        ];
                    }
                }
                // If no devices, show at least one empty row
                if (empty($devices)) {
                    $devices[] = ['tb' => '', 'serial' => '', 'tbField' => 'tbdosc', 'serialField' => 'serialtbdosc'];
                }
                
                foreach ($devices as $idx => $device): 
                ?>
                <div class="device-row flex gap-2 items-start bg-teal-50 p-2 rounded">
                    <div class="flex-1">
                        <input type="text" name="<?php echo $device['tbField']; ?>" list="tbhtList" placeholder="Tên thiết bị hỗ trợ" 
                               value="<?php echo htmlspecialchars($device['tb']); ?>"
                               class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-teal-500"
                               onchange="fillSerial(this)">
                    </div>
                    <div class="flex-1">
                        <input type="text" name="<?php echo $device['serialField']; ?>" placeholder="Serial/Mã số" 
                               value="<?php echo htmlspecialchars($device['serial']); ?>"
                               class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-teal-500">
                    </div>
                    <button type="button" onclick="removeDeviceRow(this)" class="text-red-600 hover:text-red-800 px-2 py-1" title="Xóa">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Datalist for thiết bị hỗ trợ -->
        <datalist id="tbhtList">
            <?php foreach ($thietBiHoTroList as $tb): ?>
                <option value="<?php echo htmlspecialchars($tb['tenthietbi']); ?>" 
                        data-serial="<?php echo htmlspecialchars($tb['serialnumber']); ?>"
                        data-tenvt="<?php echo htmlspecialchars($tb['tenvt']); ?>">
                    <?php echo htmlspecialchars($tb['tenthietbi'] . ' - ' . $tb['serialnumber']); ?>
                </option>
            <?php endforeach; ?>
        </datalist>

        <script>
        let deviceIndex = <?php echo count($devices); ?>;
        
        // Auto-fill serial when device is selected
        function fillSerial(input) {
            const selectedValue = input.value;
            const datalist = document.getElementById('tbhtList');
            const options = datalist.querySelectorAll('option');
            
            for (let option of options) {
                if (option.value === selectedValue) {
                    const serialInput = input.closest('.device-row').querySelector('input[name*="serial"]');
                    if (serialInput && option.dataset.serial) {
                        serialInput.value = option.dataset.serial;
                    }
                    break;
                }
            }
        }
        
        function addDeviceRow() {
            const container = document.getElementById('deviceList');
            const fieldName = deviceIndex === 0 ? 'tbdosc' : `tbdosc${deviceIndex}`;
            const serialName = deviceIndex === 0 ? 'serialtbdosc' : `serialtbdosc${deviceIndex}`;
            
            const row = document.createElement('div');
            row.className = 'device-row flex gap-2 items-start bg-teal-50 p-2 rounded';
            row.innerHTML = `
                <div class="flex-1">
                    <input type="text" name="${fieldName}" list="tbhtList" placeholder="Tên thiết bị hỗ trợ" 
                           class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-teal-500"
                           onchange="fillSerial(this)">
                </div>
                <div class="flex-1">
                    <input type="text" name="${serialName}" placeholder="Serial/Mã số" 
                           class="w-full px-2 py-1 text-sm border rounded focus:outline-none focus:ring focus:border-teal-500">
                </div>
                <button type="button" onclick="removeDeviceRow(this)" class="text-red-600 hover:text-red-800 px-2 py-1" title="Xóa">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(row);
            deviceIndex++;
        }
        
        function removeDeviceRow(button) {
            const row = button.closest('.device-row');
            if (document.querySelectorAll('.device-row').length > 1) {
                row.remove();
            } else {
                alert('Phải có ít nhất 1 dòng thiết bị');
            }
        }
        </script>

        <!-- Bàn giao (HIDDEN) -->
        <div class="hidden border-l-4 border-red-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-red-700">
                <i class="fas fa-handshake mr-2"></i>Thông tin bàn giao
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
                <div>
                    <label class="flex items-center cursor-pointer">
                        <?php 
                        $bgChecked = isset($error) && isset($_POST['bg']) ? ($_POST['bg'] == 1) : (($item['bg'] ?? 0) == 1);
                        ?>
                        <input type="checkbox" name="bg" value="1" <?php echo $bgChecked ? 'checked' : ''; ?>
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring focus:ring-blue-200">
                        <span class="ml-2 text-gray-700 font-semibold">Đã bàn giao</span>
                    </label>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Số lần BG</label>
                    <input type="number" name="slbg" min="0" value="<?php echo isset($error) && isset($_POST['slbg']) ? $_POST['slbg'] : ($item['slbg'] ?? '0'); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Dòng</label>
                    <input type="text" name="dong" value="<?php echo isset($error) && isset($_POST['dong']) ? htmlspecialchars($_POST['dong']) : htmlspecialchars($item['dong'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Ghi chú</label>
                    <textarea name="ghichu" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($error) && isset($_POST['ghichu']) ? htmlspecialchars($_POST['ghichu']) : htmlspecialchars($item['ghichu'] ?? ''); ?></textarea>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Ghi chú cuối</label>
                    <textarea name="ghichufinal" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($error) && isset($_POST['ghichufinal']) ? htmlspecialchars($_POST['ghichufinal']) : htmlspecialchars($item['ghichufinal'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

    </form>

    <!-- Quick Device Search Panel -->
    <div id="deviceSearchPanel" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="if(event.target === this) closeDeviceSearch()">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-600 to-blue-600 text-white px-6 py-4 flex justify-between items-center">
                <h3 class="text-xl font-bold flex items-center">
                    <i class="fas fa-search mr-3"></i>Tìm kiếm và chọn thiết bị
                </h3>
                <button onclick="closeDeviceSearch()" class="text-white hover:text-gray-200 text-3xl font-bold leading-none">
                    &times;
                </button>
            </div>
            
            <!-- Search Input -->
            <div class="p-4 border-b bg-gray-50">
                <div class="relative">
                    <input type="text" id="deviceSearchInput" 
                           placeholder="Nhập mã thiết bị, số máy, model..."
                           class="w-full px-4 py-3 pl-12 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 text-lg"
                           onkeyup="searchDevices()">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-xl"></i>
                </div>
                <p class="text-sm text-gray-600 mt-2" id="deviceSearchCount"></p>
            </div>
            
            <!-- Results -->
            <div id="deviceSearchResults" class="flex-1 overflow-y-auto p-4">
                <!-- Results will be inserted here -->
            </div>
        </div>
    </div>
</div>

<script>
// Store available devices
let availableDevices = [];

// Load devices for selected unit
function loadDevicesForUnit() {
    const madvSelect = document.querySelector('select[name="madv"]');
    if (!madvSelect || !madvSelect.value) return;
    
    const madv = madvSelect.value;
    
    fetch(`/iso2/api/thietbi.php?madv=${encodeURIComponent(madv)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                availableDevices = data.data || [];
                console.log(`Loaded ${availableDevices.length} devices for unit ${madv}`);
            }
        })
        .catch(error => console.error('Error loading devices:', error));
}

// Open device search panel
function openDeviceSearch() {
    const madvSelect = document.querySelector('select[name="madv"]');
    
    if (!madvSelect || !madvSelect.value) {
        alert('Vui lòng chọn đơn vị trước');
        return;
    }
    
    const panel = document.getElementById('deviceSearchPanel');
    panel.classList.remove('hidden');
    
    // Load devices and show results
    const madv = madvSelect.value;
    fetch(`/iso2/api/thietbi.php?madv=${encodeURIComponent(madv)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                availableDevices = data.data || [];
                console.log(`Loaded ${availableDevices.length} devices for unit ${madv}`);
                
                // Show all devices initially
                const input = document.getElementById('deviceSearchInput');
                input.focus();
                displayDeviceResults(availableDevices, '');
            } else {
                alert('Không thể tải danh sách thiết bị');
            }
        })
        .catch(error => {
            console.error('Error loading devices:', error);
            alert('Lỗi khi tải danh sách thiết bị');
        });
}

// Close device search panel
function closeDeviceSearch() {
    document.getElementById('deviceSearchPanel').classList.add('hidden');
    document.getElementById('deviceSearchInput').value = '';
    document.getElementById('deviceSearchResults').innerHTML = '';
}

// Search devices
function searchDevices() {
    const query = document.getElementById('deviceSearchInput').value.toLowerCase().trim();
    
    let filtered = availableDevices;
    if (query) {
        // Split query into keywords
        const keywords = query.split(/\s+/).filter(k => k.length > 0);
        
        filtered = availableDevices.filter(device => {
            const searchText = [
                device.mavt || '',
                device.somay || '',
                device.model || '',
                device.mamay || ''
            ].join(' ').toLowerCase();
            
            // All keywords must be found in the combined text
            return keywords.every(keyword => searchText.includes(keyword));
        });
    }
    
    displayDeviceResults(filtered, query);
}

// Display device search results
function displayDeviceResults(devices, query = '') {
    const resultsDiv = document.getElementById('deviceSearchResults');
    const countDiv = document.getElementById('deviceSearchCount');
    
    countDiv.textContent = `Tìm thấy ${devices.length} thiết bị`;
    
    if (devices.length === 0) {
        resultsDiv.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-search text-gray-300 text-5xl mb-3"></i>
                <p class="text-gray-500 text-lg">Không tìm thấy thiết bị nào</p>
            </div>
        `;
        return;
    }
    
    const html = devices.map(device => {
        const mavtText = highlightText(device.mavt || '', query);
        const somayText = highlightText(device.somay || '', query);
        const modelText = highlightText(device.model || '', query);
        
        return `
            <div class="border rounded-lg p-4 mb-3 hover:bg-blue-50 cursor-pointer transition-colors"
                 onclick="selectDevice('${escapeHtml(device.mavt)}', '${escapeHtml(device.somay)}', '${escapeHtml(device.model)}')">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="font-semibold text-lg text-blue-600 mb-1">${mavtText}</div>
                        <div class="text-gray-700"><strong>Số máy:</strong> ${somayText}</div>
                        <div class="text-gray-700"><strong>Model:</strong> ${modelText}</div>
                    </div>
                    <button type="button" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-semibold text-sm">
                        <i class="fas fa-check mr-1"></i>Chọn
                    </button>
                </div>
            </div>
        `;
    }).join('');
    
    resultsDiv.innerHTML = html;
}

// Select device
function selectDevice(mavt, somay, model) {
    document.getElementById('mavt').value = mavt;
    document.getElementById('somay').value = somay;
    document.getElementById('model').value = model;
    
    closeDeviceSearch();
    
    // Show notification
    showNotification('Đã cập nhật thông tin thiết bị', 'success');
}

// Helper functions
function highlightText(text, query) {
    if (!query) return text;
    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<mark class="bg-yellow-300 font-semibold">$1</mark>');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showNotification(message, type = 'info') {
    const colors = {
        success: 'bg-green-100 border-green-400 text-green-700',
        error: 'bg-red-100 border-red-400 text-red-700',
        info: 'bg-blue-100 border-blue-400 text-blue-700'
    };
    
    const notification = document.createElement('div');
    notification.className = `${colors[type]} border px-4 py-3 rounded mb-4 fixed top-4 right-4 z-50 shadow-lg max-w-md`;
    notification.innerHTML = `
        <span class="block sm:inline">${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-4 font-bold">&times;</button>
    `;
    
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 5000);
}

// Load positions from vitri_iso table
function loadPositions() {
    fetch('/iso2/api/vitri.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateVitriSelect(data.data);
            }
        })
        .catch(error => console.error('Error loading positions:', error));
}

// Populate vitri select dropdowns
function populateVitriSelect(positions) {
    const selects = document.querySelectorAll('.vitri-select');
    selects.forEach(select => {
        const currentValue = select.getAttribute('data-current');
        positions.forEach(pos => {
            const option = document.createElement('option');
            option.value = pos.tenvitri;
            option.textContent = `[${pos.mavitri}] - ${pos.tenvitri}`;
            if (currentValue && pos.tenvitri === currentValue) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    });
}

// Load lo from lo_iso table
function loadLo() {
    fetch('/iso2/api/lo.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateLoSelect(data.data);
            }
        })
        .catch(error => console.error('Error loading lo:', error));
}

// Populate lo select dropdowns
function populateLoSelect(loList) {
    const selects = document.querySelectorAll('.lo-select');
    selects.forEach(select => {
        const currentValue = select.getAttribute('data-current');
        loList.forEach(lo => {
            const option = document.createElement('option');
            option.value = lo.tenlo;
            option.textContent = `[${lo.malo}] - ${lo.tenlo}`;
            if (currentValue && lo.tenlo === currentValue) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    });
}

// Load mo from mo_iso table
function loadMo() {
    fetch('/iso2/api/mo.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateMoSelect(data.data);
            }
        })
        .catch(error => console.error('Error loading mo:', error));
}

// Populate mo select dropdowns
function populateMoSelect(moList) {
    const selects = document.querySelectorAll('.mo-select');
    selects.forEach(select => {
        const currentValue = select.getAttribute('data-current');
        moList.forEach(mo => {
            const option = document.createElement('option');
            option.value = mo.tenmo;
            option.textContent = `[${mo.mamo}] - ${mo.tenmo}`;
            if (currentValue && mo.tenmo === currentValue) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadPositions();
    loadLo();
    loadMo();
    
    // Load devices for current unit
    const madvSelect = document.querySelector('select[name="madv"]');
    if (madvSelect && madvSelect.value) {
        loadDevicesForUnit();
    }
    
    // Reload devices when unit changes
    if (madvSelect) {
        madvSelect.addEventListener('change', function() {
            availableDevices = [];
            if (this.value) {
                loadDevicesForUnit();
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

