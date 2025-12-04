<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Sửa Hồ sơ SCBĐ';
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
                    <input type="text" name="mavt" required value="<?php echo isset($error) && isset($_POST['mavt']) ? htmlspecialchars($_POST['mavt']) : htmlspecialchars($item['mavt'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Số máy <span class="text-red-500">*</span></label>
                    <input type="text" name="somay" required value="<?php echo isset($error) && isset($_POST['somay']) ? htmlspecialchars($_POST['somay']) : htmlspecialchars($item['somay'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Model <span class="text-red-500">*</span></label>
                    <input type="text" name="model" required value="<?php echo isset($error) && isset($_POST['model']) ? htmlspecialchars($_POST['model']) : htmlspecialchars($item['model'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Vị trí máy BD <span class="text-red-500">*</span></label>
                    <input type="text" name="vitrimaybd" required value="<?php echo isset($error) && isset($_POST['vitrimaybd']) ? htmlspecialchars($_POST['vitrimaybd']) : htmlspecialchars($item['vitrimaybd'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Lô <span class="text-red-500">*</span></label>
                    <input type="text" name="lo" required value="<?php echo isset($error) && isset($_POST['lo']) ? htmlspecialchars($_POST['lo']) : htmlspecialchars($item['lo'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Giếng <span class="text-red-500">*</span></label>
                    <input type="text" name="gieng" required value="<?php echo isset($error) && isset($_POST['gieng']) ? htmlspecialchars($_POST['gieng']) : htmlspecialchars($item['gieng'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-700 font-semibold mb-2">Mỏ <span class="text-red-500">*</span></label>
                    <input type="text" name="mo" required value="<?php echo isset($error) && isset($_POST['mo']) ? htmlspecialchars($_POST['mo']) : htmlspecialchars($item['mo'] ?? ''); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
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
                    <textarea name="cv" required rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($error) && isset($_POST['cv']) ? htmlspecialchars($_POST['cv']) : displayText($item['cv'] ?? ''); ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-semibold mb-2">Yêu cầu thêm của KH</label>
                    <textarea name="ycthemkh" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($error) && isset($_POST['ycthemkh']) ? htmlspecialchars($_POST['ycthemkh']) : displayText($item['ycthemkh'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Thông tin sửa chữa -->
        <div class="border-l-4 border-orange-500 pl-4">
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

        <!-- Thiết bị đo SC -->
        <div class="border-l-4 border-teal-500 pl-4">
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

        <!-- Bàn giao -->
        <div class="border-l-4 border-red-500 pl-4">
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

        <!-- Buttons -->
        <div class="flex flex-col md:flex-row gap-2 pt-4 border-t">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded text-base font-semibold w-full md:w-auto">
                <i class="fas fa-save mr-2"></i> Cập nhật hồ sơ
            </button>
            <a href="hososcbd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded text-base font-semibold text-center w-full md:w-auto">
                <i class="fas fa-times mr-2"></i> Hủy
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

