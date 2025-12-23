<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Thêm Hồ sơ SCBĐ';
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="max-w-6xl mx-auto bg-white rounded-lg shadow-md p-4 md:p-6">
    <h1 class="text-xl md:text-2xl font-bold mb-4 md:mb-6 flex items-center">
        <i class="fas fa-plus-circle mr-2"></i> Thêm Hồ sơ Sửa chữa Bảo dưỡng
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
                    <input type="text" name="phieu" value="<?php echo isset($_POST['phieu']) ? htmlspecialchars($_POST['phieu']) : $nextPhieu; ?>"
                           placeholder="Tự động: <?php echo $nextPhieu; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle"></i> Để trống sẽ tự động sinh số tiếp theo</p>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Ngày yêu cầu <span class="text-red-500">*</span></label>
                    <input type="date" name="ngayyc" required value="<?php echo isset($_POST['ngayyc']) ? $_POST['ngayyc'] : date('Y-m-d'); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded p-3 mt-3">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-robot mr-1"></i> <strong>Tự động:</strong> 
                    Mã quản lý (maql) và Mã hồ sơ (hoso) sẽ được tạo tự động khi lưu phiếu.
                </p>
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
                    <div class="flex gap-2">
                        <select name="madv" id="madvSelect" required class="flex-1 px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                            <option value="">-- Chọn đơn vị --</option>
                            <?php foreach ($donViList as $dv): ?>
                                <option value="<?php echo htmlspecialchars($dv['madv']); ?>" 
                                        <?php echo (isset($_POST['madv']) && $_POST['madv'] === $dv['madv']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dv['tendv']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" onclick="openAddUnitModal()" 
                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded font-bold text-lg"
                                title="Thêm đơn vị mới">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Điện thoại</label>
                    <input type="text" name="dienthoai" value="<?php echo isset($_POST['dienthoai']) ? htmlspecialchars($_POST['dienthoai']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Người yêu cầu</label>
                    <input type="text" name="ngyeucau" value="<?php echo isset($_POST['ngyeucau']) ? htmlspecialchars($_POST['ngyeucau']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Người nhận yêu cầu</label>
                    <input type="text" name="ngnhyeucau" value="<?php echo isset($_POST['ngnhyeucau']) ? htmlspecialchars($_POST['ngnhyeucau']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-semibold mb-2">Công việc <span class="text-red-500">*</span></label>
                    <textarea name="cv" required rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['cv']) ? htmlspecialchars($_POST['cv']) : ''; ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-semibold mb-2">Yêu cầu thêm của KH</label>
                    <textarea name="ycthemkh" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($_POST['ycthemkh']) ? htmlspecialchars($_POST['ycthemkh']) : ''; ?></textarea>
                </div>
            </div>
            
            <!-- Location Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4 mt-4 pt-4 border-t border-purple-200">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Lô</label>
                    <select name="lo" class="lo-select w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                        <option value="">-- Chọn lô --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Giếng</label>
                    <input type="text" name="gieng" value="<?php echo isset($_POST['gieng']) ? htmlspecialchars($_POST['gieng']) : ''; ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Mỏ</label>
                    <select name="mo" class="mo-select w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                        <option value="">-- Chọn mỏ --</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Quick Search Thiết bị -->
        <div id="quickSearchPanel" class="hidden border-l-4 border-yellow-500 pl-4 bg-gradient-to-br from-yellow-50 to-orange-50 rounded-lg p-4 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold text-yellow-700 flex items-center">
                    <i class="fas fa-plus-circle mr-2"></i>Thêm thiết bị vào hồ sơ
                </h2>
                <span id="searchResultCount" class="text-sm text-gray-600 bg-white px-3 py-1 rounded-full border border-yellow-300">
                    0 kết quả
                </span>
            </div>
            
            <div id="searchModePanel">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
                    <p class="text-sm text-blue-800 flex items-start">
                        <i class="fas fa-info-circle mr-2 mt-0.5"></i>
                        <span>
                            <strong>Cách 1:</strong> Tìm và chọn thiết bị có sẵn trong hệ thống (đã có đầy đủ thông tin)<br>
                            <strong>Cách 2:</strong> Nhập thủ công thiết bị mới (nếu chưa có trong danh sách)
                        </span>
                    </p>
                </div>
                
                <div class="relative mb-2">
                    <input type="text" id="quickSearchInput" 
                           placeholder="🔍 Tìm thiết bị: Gõ mã vật tư, số máy, tên... (dùng ↑↓ Enter)"
                           class="w-full px-4 py-3 pl-10 pr-20 border-2 border-yellow-400 rounded-lg focus:outline-none focus:ring-2 focus:border-yellow-600 text-base shadow-sm"
                           autocomplete="off">
                    <i class="fas fa-search absolute left-3 top-4 text-yellow-500"></i>
                    <button type="button" onclick="closeQuickSearch()" 
                            class="absolute right-3 top-2.5 text-gray-500 hover:text-gray-700 bg-white hover:bg-gray-100 px-2 py-1 rounded transition-colors">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <div id="quickSearchResults" class="max-h-80 overflow-y-auto space-y-2 scroll-smooth mb-3">
                    <!-- Results will be populated here -->
                </div>
                
                <div class="border-t-2 border-dashed border-gray-300 pt-3">
                    <button type="button" onclick="addDeviceManually()" 
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-3 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg flex items-center justify-center">
                        <i class="fas fa-keyboard mr-2"></i>
                        Không tìm thấy? Nhập thủ công thiết bị mới
                    </button>
                    <p class="text-xs text-gray-500 text-center mt-2">
                        Sử dụng nếu thiết bị chưa có trong danh sách của đơn vị
                    </p>
                </div>
            </div>
        </div>

        <!-- Thông tin thiết bị (Dynamic) -->
        <div class="border-l-4 border-green-500 pl-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold text-green-700">
                    <i class="fas fa-cogs mr-2"></i>Thông tin thiết bị
                    <span class="text-sm font-normal text-gray-600 ml-2">(<span id="deviceCount">0</span> thiết bị)</span>
                </h2>
                <button type="button" onclick="openAddDevicePanel()" 
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-semibold transition-colors flex items-center shadow-md hover:shadow-lg">
                    <i class="fas fa-plus mr-2"></i>Chọn thiết bị
                </button>
            </div>
            
            <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg p-4 mb-4">
                <p class="text-sm text-gray-700 flex items-start">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
                    <span>
                        Bạn có thể thêm không giới hạn số lượng thiết bị. 
                        Sử dụng nút <strong class="text-green-700">"Chọn thiết bị"</strong> để thêm và nút 
                        <strong class="text-red-700">"Xóa"</strong> để xóa thiết bị không cần thiết.
                    </span>
                </p>
            </div>

            <div id="deviceContainer" class="space-y-4" style="display: none;">
                <!-- Devices will be added here dynamically -->
            </div>
        </div>

<style>
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.device-item.new {
    animation: slideIn 0.3s ease-out;
}

.device-item:hover {
    transform: translateY(-2px);
}
</style>

<script>
let deviceIndex = 0;

function addDevice() {
    deviceIndex++;
    const container = document.getElementById('deviceContainer');
    
    // Show container if first device
    if (deviceIndex === 1) {
        container.style.display = 'block';
    }
    
    const deviceHTML = `
        <div class="device-item new border-2 border-gray-300 rounded-lg p-4 bg-gradient-to-br from-white to-gray-50 shadow-md transition-all duration-300 hover:shadow-lg" data-device-index="${deviceIndex}">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-gray-800 flex items-center">
                    <span class="device-number bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-full w-8 h-8 flex items-center justify-center text-base mr-2 shadow-md">${deviceIndex}</span>
                    <span>Thiết bị <span class="device-index">${deviceIndex}</span></span>
                </h3>
                <button type="button" onclick="removeDevice(this)" 
                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded font-semibold transition-colors flex items-center text-sm">
                    <i class="fas fa-trash mr-1"></i>Xóa
                </button>
            </div>
            
            <!-- Readonly Info Section -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 font-semibold mb-1">Mã vật tư</label>
                        <input type="text" name="devices[${deviceIndex}][mavt]" readonly
                               class="device-input w-full px-3 py-2 bg-white border border-gray-300 rounded text-gray-700 font-semibold cursor-not-allowed"
                               placeholder="Chọn từ danh sách">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 font-semibold mb-1">Số máy</label>
                        <input type="text" name="devices[${deviceIndex}][somay]" readonly
                               class="device-input w-full px-3 py-2 bg-white border border-gray-300 rounded text-gray-700 font-semibold cursor-not-allowed"
                               placeholder="Chọn từ danh sách">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 font-semibold mb-1">Model</label>
                        <input type="text" name="devices[${deviceIndex}][model]" readonly
                               class="device-input w-full px-3 py-2 bg-white border border-gray-300 rounded text-gray-700 font-semibold cursor-not-allowed"
                               placeholder="Chọn từ danh sách">
                    </div>
                </div>
                <p class="text-xs text-blue-600 mt-2 flex items-center">
                    <i class="fas fa-info-circle mr-1"></i>
                    Sử dụng nút "Thêm thiết bị" để chọn từ danh sách
                </p>
            </div>
            
            <!-- Editable Fields Section -->
            <div class="grid grid-cols-1 gap-3">
                <div>
                    <label class="block text-sm text-gray-700 font-semibold mb-1">Vị trí máy BD</label>
                    <select name="devices[${deviceIndex}][vitrimaybd]"
                            class="vitri-select w-full px-3 py-2 border-2 border-gray-300 rounded focus:outline-none focus:ring-2 focus:border-blue-500 transition-all">
                        <option value="">-- Chọn vị trí --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-700 font-semibold mb-1">Tình trạng kỹ thuật</label>
                    <textarea name="devices[${deviceIndex}][tinhtrang]" rows="2"
                              class="w-full px-3 py-2 border-2 border-gray-300 rounded focus:outline-none focus:ring-2 focus:border-blue-500 transition-all"
                              placeholder="Nhập tình trạng kỹ thuật của thiết bị"></textarea>
                </div>
                <div>
                    <label class="block text-sm text-gray-700 font-semibold mb-1">Nội dung yêu cầu</label>
                    <textarea name="devices[${deviceIndex}][noidungyc]" rows="2"
                              class="w-full px-3 py-2 border-2 border-gray-300 rounded focus:outline-none focus:ring-2 focus:border-blue-500 transition-all"
                              placeholder="Nhập nội dung yêu cầu bảo dưỡng"></textarea>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', deviceHTML);
    updateDeviceCount();
    
    // Update datalists for new device
    if (window.availableDevices && window.availableDevices.length > 0) {
        updateMavtDataLists();
    }
    
    // Load positions for new select dropdown
    if (window.vitriPositions && window.vitriPositions.length > 0) {
        populateVitriSelect();
    }
    
    // Remove 'new' class after animation
    setTimeout(() => {
        const newDevice = container.lastElementChild;
        if (newDevice) {
            newDevice.classList.remove('new');
        }
    }, 300);
    
    // Scroll to new device
    setTimeout(() => {
        container.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 100);
}

function removeDevice(button) {
    const deviceItem = button.closest('.device-item');
    const container = document.getElementById('deviceContainer');
    
    // Add fade out animation
    deviceItem.style.opacity = '0';
    deviceItem.style.transform = 'translateX(100px)';
    deviceItem.style.transition = 'all 0.3s ease-out';
    
    setTimeout(() => {
        deviceItem.remove();
        updateDeviceCount();
        renumberDevices();
        
        // Hide container if no devices left
        if (container.children.length === 0) {
            container.style.display = 'none';
        }
    }, 300);
}

function updateDeviceCount() {
    const count = document.querySelectorAll('.device-item').length;
    document.getElementById('deviceCount').textContent = count;
}

function renumberDevices() {
    const devices = document.querySelectorAll('.device-item');
    devices.forEach((device, index) => {
        const displayNumber = index + 1;
        const numberSpan = device.querySelector('.device-number');
        const indexSpan = device.querySelector('.device-index');
        
        if (numberSpan) numberSpan.textContent = displayNumber;
        if (indexSpan) indexSpan.textContent = displayNumber;
    });
}
</script>

        <!-- Link to Repair Details Page (HIDDEN) -->
        <div class="hidden border-l-4 border-orange-500 pl-4 bg-orange-50 rounded-lg p-6">
        <div class="border-l-4 border-orange-500 pl-4 bg-orange-50 rounded-lg p-6">
            <h2 class="text-lg font-bold mb-3 text-orange-700">
                <i class="fas fa-wrench mr-2"></i>Thông tin sửa chữa & Thiết bị đo
            </h2>
            <p class="text-gray-700 mb-4">
                Nhập thông tin chi tiết về quá trình sửa chữa, thiết bị đo sử dụng ở trang riêng.
            </p>
            <a href="hososcbd_repair_details.php" 
               class="inline-block bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded text-base font-semibold">
                <i class="fas fa-arrow-right mr-2"></i>Đi tới trang Thông tin sửa chữa
            </a>
            <p class="text-sm text-gray-500 mt-3">
                <i class="fas fa-info-circle"></i> Bạn có thể nhập thông tin này sau khi đã tạo hồ sơ cơ bản
            </p>
        </div>

        <!-- Bàn giao - Link to separate page (HIDDEN) -->
        <div class="hidden border-l-4 border-red-500 pl-4 bg-red-50 p-4 rounded">
            <h2 class="text-lg font-bold mb-3 text-red-700">
                <i class="fas fa-handshake mr-2"></i>Thông tin bàn giao
            </h2>
            <p class="text-gray-600 mb-3">
                <i class="fas fa-info-circle mr-2"></i>
                Bạn có thể nhập thông tin này sau khi đã tạo hồ sơ cơ bản.
            </p>
            <a href="#" onclick="alert('Vui lòng lưu hồ sơ trước, sau đó sử dụng icon bàn giao trong danh sách để nhập thông tin này.'); return false;" 
               class="inline-block bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded font-semibold">
                <i class="fas fa-arrow-right mr-2"></i>Nhập thông tin bàn giao sau
            </a>
        </div>

        <!-- DEBUG MARKER: FILE VERSION 2024-12-23 -->
        <div style="background: yellow; padding: 10px; margin: 20px 0; border: 3px solid red; text-align: center; font-size: 20px; font-weight: bold;">
            ⚠️ DEBUG: Nếu thấy dòng này nghĩa là file đã update. Nút submit nằm ngay bên dưới ⬇️
        </div>

        <!-- Buttons -->
        <div class="flex flex-col md:flex-row gap-2 pt-4 border-t">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded text-base font-semibold w-full md:w-auto">
                <i class="fas fa-save mr-2"></i> Lưu hồ sơ
            </button>
            <a href="hososcbd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded text-base font-semibold text-center w-full md:w-auto">
                <i class="fas fa-times mr-2"></i> Hủy
            </a>
        </div>
    </form>
</div>

<!-- Modal: Add Unit -->
<div id="addUnitModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="bg-green-600 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
            <h3 class="text-lg font-bold">
                <i class="fas fa-plus-circle mr-2"></i>Thêm đơn vị mới
            </h3>
            <button onclick="closeAddUnitModal()" class="text-white hover:text-gray-200 text-2xl font-bold">
                &times;
            </button>
        </div>
        
        <form id="addUnitForm" class="p-6 space-y-4">
            <div id="modalMessage" class="hidden"></div>
            
            <div>
                <label class="block text-gray-700 font-semibold mb-2">
                    Mã đơn vị <span class="text-red-500">*</span>
                </label>
                <input type="text" id="newMadv" required 
                       placeholder="Ví dụ: XDT, PCC, etc."
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Mã viết tắt, không dấu, chữ hoa</p>
            </div>
            
            <div>
                <label class="block text-gray-700 font-semibold mb-2">
                    Tên đơn vị <span class="text-red-500">*</span>
                </label>
                <input type="text" id="newTendv" required 
                       placeholder="Ví dụ: Xưởng Điện Tử"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
            
            <div class="flex gap-2 pt-4 border-t">
                <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-semibold">
                    <i class="fas fa-save mr-2"></i>Lưu
                </button>
                <button type="button" onclick="closeAddUnitModal()" 
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded font-semibold">
                    <i class="fas fa-times mr-2"></i>Hủy
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions for Add Unit
function openAddUnitModal() {
    document.getElementById('addUnitModal').classList.remove('hidden');
    document.getElementById('newMadv').focus();
}

function closeAddUnitModal() {
    document.getElementById('addUnitModal').classList.add('hidden');
    document.getElementById('addUnitForm').reset();
    document.getElementById('modalMessage').classList.add('hidden');
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddUnitModal();
    }
});

// Close modal on background click
document.getElementById('addUnitModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddUnitModal();
    }
});

// Handle Add Unit Form Submit
document.getElementById('addUnitForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const madv = document.getElementById('newMadv').value.trim().toUpperCase();
    const tendv = document.getElementById('newTendv').value.trim();
    
    if (!madv || !tendv) {
        showModalMessage('Vui lòng điền đầy đủ thông tin', 'error');
        return;
    }
    
    // Create FormData
    const formData = new FormData();
    formData.append('madv', madv);
    formData.append('tendv', tendv);
    
    // Submit to API
    fetch('/iso2/api/donvi.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showModalMessage(data.message, 'success');
            
            // Add new option to select
            const madvSelect = document.getElementById('madvSelect');
            const newOption = document.createElement('option');
            newOption.value = data.data.madv;
            newOption.textContent = data.data.tendv;
            newOption.selected = true;
            madvSelect.appendChild(newOption);
            
            // Trigger change event to reload devices
            madvSelect.dispatchEvent(new Event('change'));
            
            // Close modal after 1 second
            setTimeout(() => {
                closeAddUnitModal();
                showNotification('Đơn vị mới đã được thêm và chọn', 'success');
            }, 1000);
        } else {
            showModalMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showModalMessage('Có lỗi xảy ra khi thêm đơn vị', 'error');
    });
});

function showModalMessage(message, type) {
    const messageDiv = document.getElementById('modalMessage');
    const colors = {
        success: 'bg-green-100 border-green-400 text-green-700',
        error: 'bg-red-100 border-red-400 text-red-700'
    };
    
    messageDiv.className = `border px-4 py-3 rounded mb-4 ${colors[type]}`;
    messageDiv.textContent = message;
    messageDiv.classList.remove('hidden');
}

// Load positions from vitri_iso table
function loadPositions() {
    console.log('Loading positions from API...');
    fetch('/iso2/api/vitri.php')
        .then(response => {
            console.log('API Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('API Data received:', data);
            if (data.success && data.data) {
                window.vitriPositions = data.data;
                console.log('Positions loaded:', window.vitriPositions.length);
                populateVitriSelect();
            } else {
                console.error('Failed to load positions:', data.error || 'Unknown error');
                window.vitriPositions = [];
                loadFallbackPositions();
            }
        })
        .catch(error => {
            console.error('Error loading positions:', error);
            window.vitriPositions = [];
            loadFallbackPositions();
        });
}

// Fallback positions if API fails
function loadFallbackPositions() {
    console.log('Using fallback positions');
    window.vitriPositions = [
        {mavitri: 'VT001', tenvitri: 'Khu vực A - Trạm 1'},
        {mavitri: 'VT002', tenvitri: 'Khu vực B - Nhà máy chính'},
        {mavitri: 'VT003', tenvitri: 'Xưởng sửa chữa'},
        {mavitri: 'VT004', tenvitri: 'Phòng điều khiển'}
    ];
    populateVitriSelect();
}

// Populate all vitri select dropdowns with loaded positions
function populateVitriSelect() {
    console.log('populateVitriSelect called, positions:', window.vitriPositions);
    if (!window.vitriPositions || window.vitriPositions.length === 0) {
        console.log('No positions to populate');
        return;
    }
    
    const vitriSelects = document.querySelectorAll('.vitri-select');
    console.log('Found vitri selects:', vitriSelects.length);
    
    vitriSelects.forEach((select, index) => {
        console.log(`Populating select #${index + 1}`);
        // Save current value
        const currentValue = select.value;
        
        // Clear existing options except the first one (placeholder)
        while (select.options.length > 1) {
            select.remove(1);
        }
        
        // Add new options
        window.vitriPositions.forEach(position => {
            const option = document.createElement('option');
            option.value = position.tenvitri;
            option.textContent = position.tenvitri;
            select.appendChild(option);
        });
        
        console.log(`Added ${window.vitriPositions.length} options to select #${index + 1}`);
        
        // Restore value if it exists in the new options
        if (currentValue) {
            select.value = currentValue;
        }
    });
}

// Load lo from lo_iso table
function loadLo() {
    console.log('Loading lo from API...');
    fetch('/iso2/api/lo.php')
        .then(response => response.json())
        .then(data => {
            console.log('Lo data received:', data);
            if (data.success && data.data) {
                window.loList = data.data;
                console.log('Lo loaded:', window.loList.length);
                populateLoSelect();
            } else {
                console.error('Failed to load lo:', data.error || 'Unknown error');
                window.loList = [];
            }
        })
        .catch(error => {
            console.error('Error loading lo:', error);
            window.loList = [];
        });
}

// Populate lo select dropdowns
function populateLoSelect() {
    if (!window.loList || window.loList.length === 0) {
        console.log('No lo to populate');
        return;
    }
    
    const loSelects = document.querySelectorAll('.lo-select');
    console.log('Found lo selects:', loSelects.length);
    
    loSelects.forEach((select, index) => {
        const currentValue = select.value;
        
        while (select.options.length > 1) {
            select.remove(1);
        }
        
        window.loList.forEach(lo => {
            const option = document.createElement('option');
            option.value = lo.tenlo;
            option.textContent = `${lo.malo} - ${lo.tenlo}`;
            select.appendChild(option);
        });
        
        if (currentValue) {
            select.value = currentValue;
        }
    });
}

// Load mo from mo_iso table
function loadMo() {
    console.log('Loading mo from API...');
    fetch('/iso2/api/mo.php')
        .then(response => response.json())
        .then(data => {
            console.log('Mo data received:', data);
            if (data.success && data.data) {
                window.moList = data.data;
                console.log('Mo loaded:', window.moList.length);
                populateMoSelect();
            } else {
                console.error('Failed to load mo:', data.error || 'Unknown error');
                window.moList = [];
            }
        })
        .catch(error => {
            console.error('Error loading mo:', error);
            window.moList = [];
        });
}

// Populate mo select dropdowns
function populateMoSelect() {
    if (!window.moList || window.moList.length === 0) {
        console.log('No mo to populate');
        return;
    }
    
    const moSelects = document.querySelectorAll('.mo-select');
    console.log('Found mo selects:', moSelects.length);
    
    moSelects.forEach((select, index) => {
        const currentValue = select.value;
        
        while (select.options.length > 1) {
            select.remove(1);
        }
        
        window.moList.forEach(mo => {
            const option = document.createElement('option');
            option.value = mo.tenmo;
            option.textContent = `${mo.mamo} - ${mo.tenmo}`;
            select.appendChild(option);
        });
        
        if (currentValue) {
            select.value = currentValue;
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const madvSelect = document.querySelector('select[name="madv"]');
    
    if (!madvSelect) return;
    
    // Load positions from vitri_iso
    loadPositions();
    
    // Load lo from lo_iso
    loadLo();
    
    // Load mo from mo_iso
    loadMo();
    
    // When unit changes, load available devices for that unit
    madvSelect.addEventListener('change', function() {
        const madv = this.value;
        
        if (!madv) {
            window.availableDevices = [];
            clearAllDataLists();
            return;
        }
        
        // Show loading indicator
        const originalText = this.options[this.selectedIndex].text;
        this.disabled = true;
        
        // Load devices for this unit
        fetch(`/iso2/api/thietbi.php?madv=${encodeURIComponent(madv)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                this.disabled = false;
                
                if (data.success && data.data) {
                    window.availableDevices = data.data;
                    
                    // Update all mavt inputs with datalist
                    updateMavtDataLists();
                    
                    const count = data.data.length;
                    if (count > 0) {
                        showNotification(`Đã tải ${count} loại thiết bị cho đơn vị này`, 'success');
                    } else {
                        showNotification('Không có thiết bị nào cho đơn vị này', 'warning');
                    }
                } else {
                    window.availableDevices = [];
                    showNotification(data.message || 'Không thể tải danh sách thiết bị', 'error');
                }
            })
            .catch(error => {
                this.disabled = false;
                console.error('Error loading devices:', error);
                window.availableDevices = [];
                showNotification('Lỗi kết nối khi tải thiết bị. Vui lòng thử lại.', 'error');
            });
    });
});

// Update all mavt inputs with datalist from available devices
function updateMavtDataLists() {
    if (!window.availableDevices || window.availableDevices.length === 0) {
        clearAllDataLists();
        return;
    }
    
    // Get all device items (including dynamically added ones)
    const deviceItems = document.querySelectorAll('.device-item');
    
    deviceItems.forEach((item, index) => {
        const deviceIndex = item.getAttribute('data-device-index');
        const mavtInput = item.querySelector(`input[name="devices[${deviceIndex}][mavt]"]`);
        
        if (!mavtInput) return;
        
        // Create unique datalist ID
        const datalistId = `mavt-list-${deviceIndex}`;
        let datalist = document.getElementById(datalistId);
        
        // Remove old datalist if exists
        if (datalist) {
            datalist.remove();
        }
        
        // Create new datalist
        datalist = document.createElement('datalist');
        datalist.id = datalistId;
        
        // Populate datalist with available devices
        datalist.innerHTML = window.availableDevices.map(d => 
            `<option value="${d.mavt}">${d.mavt} - ${d.tenvt}</option>`
        ).join('');
        
        // Attach datalist to input
        mavtInput.setAttribute('list', datalistId);
        mavtInput.parentNode.appendChild(datalist);
        
        // Add change event to auto-fill model when mavt is selected
        mavtInput.addEventListener('change', function() {
            const selectedDevice = window.availableDevices.find(d => d.mavt === this.value);
            if (selectedDevice && selectedDevice.model) {
                const modelInput = item.querySelector(`input[name="devices[${deviceIndex}][model]"]`);
                if (modelInput && !modelInput.value) {
                    modelInput.value = selectedDevice.model;
                }
            }
        });
    });
}

// Clear all datalists
function clearAllDataLists() {
    document.querySelectorAll('datalist[id^="mavt-list-"]').forEach(dl => dl.remove());
}

// Helper: Show notification
function showNotification(message, type = 'info') {
    const colors = {
        success: 'bg-green-100 border-green-400 text-green-700',
        warning: 'bg-yellow-100 border-yellow-400 text-yellow-700',
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

// Quick Search Functions
let currentTargetDeviceIndex = null;
let selectedSearchIndex = -1;

function openAddDevicePanel() {
    const madvSelect = document.querySelector('select[name="madv"]');
    
    if (!madvSelect || !madvSelect.value) {
        showNotification('Vui lòng chọn đơn vị trước', 'warning');
        return;
    }
    
    const panel = document.getElementById('quickSearchPanel');
    panel.classList.remove('hidden');
    
    // Reset selection
    selectedSearchIndex = -1;
    
    // Check if devices available
    if (window.availableDevices && window.availableDevices.length > 0) {
        // Show search mode
        document.getElementById('searchModePanel').classList.remove('hidden');
        
        // Focus search input
        setTimeout(() => {
            const input = document.getElementById('quickSearchInput');
            input.focus();
            input.select();
        }, 100);
        
        // Show all devices initially
        displaySearchResults(window.availableDevices);
    } else {
        // No devices available, show manual input option only
        document.getElementById('searchModePanel').classList.remove('hidden');
        document.getElementById('quickSearchResults').innerHTML = `
            <div class="text-center py-6 bg-white rounded-lg border-2 border-dashed border-gray-300">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-2"></i>
                <p class="text-gray-600 mb-2">Đơn vị này chưa có thiết bị nào trong hệ thống</p>
                <p class="text-sm text-gray-500">Vui lòng sử dụng nút "Nhập thủ công" bên dưới</p>
            </div>
        `;
        document.getElementById('searchResultCount').textContent = '0 thiết bị';
    }
}

function addDeviceManually() {
    // Close search panel
    closeQuickSearch();
    
    // Add new empty device slot
    addDevice();
    
    // Show notification
    showNotification('Đã thêm thiết bị mới. Vui lòng điền thông tin.', 'info');
}

function closeQuickSearch() {
    document.getElementById('quickSearchPanel').classList.add('hidden');
    document.getElementById('quickSearchInput').value = '';
    document.getElementById('quickSearchResults').innerHTML = '';
    selectedSearchIndex = -1;
}

function highlightText(text, query) {
    if (!query) return text;
    
    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<mark class="bg-yellow-300 font-semibold">$1</mark>');
}

function displaySearchResults(devices, query = '') {
    const resultsDiv = document.getElementById('quickSearchResults');
    const countDiv = document.getElementById('searchResultCount');
    
    if (!devices || devices.length === 0) {
        resultsDiv.innerHTML = '<div class="text-center py-8"><i class="fas fa-inbox text-gray-300 text-4xl mb-2"></i><p class="text-gray-500">Không tìm thấy thiết bị nào</p></div>';
        countDiv.textContent = '0 kết quả';
        return;
    }
    
    // Update count
    countDiv.textContent = `${devices.length} kết quả`;
    
    // Sort by relevance (exact match first)
    const sorted = [...devices].sort((a, b) => {
        if (!query) return 0;
        const queryLower = query.toLowerCase();
        
        const aExact = a.mavt.toLowerCase() === queryLower || a.somay?.toLowerCase() === queryLower;
        const bExact = b.mavt.toLowerCase() === queryLower || b.somay?.toLowerCase() === queryLower;
        
        if (aExact && !bExact) return -1;
        if (!aExact && bExact) return 1;
        
        const aStarts = a.mavt.toLowerCase().startsWith(queryLower) || a.somay?.toLowerCase().startsWith(queryLower);
        const bStarts = b.mavt.toLowerCase().startsWith(queryLower) || b.somay?.toLowerCase().startsWith(queryLower);
        
        if (aStarts && !bStarts) return -1;
        if (!aStarts && bStarts) return 1;
        
        return 0;
    });
    
    resultsDiv.innerHTML = sorted.map((device, index) => `
        <div class="device-result ${index === selectedSearchIndex ? 'selected' : ''} bg-white border-2 border-gray-300 hover:border-green-500 rounded-lg p-3 cursor-pointer transition-all hover:shadow-md group"
             data-index="${index}"
             onclick="selectDeviceFromSearch('${escapeHtml(device.mavt)}', '${escapeHtml(device.somay || '')}', '${escapeHtml(device.model || '')}', '${escapeHtml(device.tenvt)}')">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-bold text-blue-600 text-base">${highlightText(escapeHtml(device.mavt), query)}</span>
                        <span class="text-gray-400">•</span>
                        <span class="font-semibold text-green-600 text-base">
                            <i class="fas fa-barcode text-xs mr-1"></i>${highlightText(escapeHtml(device.somay || 'N/A'), query)}
                        </span>
                        ${device.model ? `<span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">${highlightText(escapeHtml(device.model), query)}</span>` : ''}
                    </div>
                    <div class="text-sm text-gray-700 mt-1.5">${highlightText(escapeHtml(device.tenvt), query)}</div>
                    ${device.mamay ? `<div class="text-xs text-gray-500 mt-1 font-mono">${highlightText(escapeHtml(device.mamay), query)}</div>` : ''}
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded hidden group-hover:inline-block">Enter để chọn</span>
                    <i class="fas fa-chevron-right text-gray-400 group-hover:text-green-500 transition-colors"></i>
                </div>
            </div>
        </div>
    `).join('');
    
    // Auto-select first result
    selectedSearchIndex = -1;
}

function selectSearchResult(index) {
    const results = document.querySelectorAll('.device-result');
    
    if (index < 0) index = 0;
    if (index >= results.length) index = results.length - 1;
    
    // Remove previous selection
    results.forEach(r => r.classList.remove('selected', 'ring-2', 'ring-green-400', 'border-green-500'));
    
    // Add new selection
    if (results[index]) {
        results[index].classList.add('selected', 'ring-2', 'ring-green-400', 'border-green-500');
        results[index].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    selectedSearchIndex = index;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function selectDeviceFromSearch(mavt, somay, model, tenvt) {
    // Find first empty device slot or create new one
    const deviceItems = document.querySelectorAll('.device-item');
    let targetItem = null;
    
    for (let item of deviceItems) {
        const index = item.getAttribute('data-device-index');
        const mavtInput = item.querySelector(`input[name="devices[${index}][mavt]"]`);
        
        if (!mavtInput.value) {
            targetItem = item;
            currentTargetDeviceIndex = index;
            break;
        }
    }
    
    // If no empty slot, add new device
    if (!targetItem) {
        addDevice();
        // Wait for device to be added
        setTimeout(() => {
            const newDeviceItems = document.querySelectorAll('.device-item');
            targetItem = newDeviceItems[newDeviceItems.length - 1];
            currentTargetDeviceIndex = targetItem.getAttribute('data-device-index');
            fillDeviceData(currentTargetDeviceIndex, mavt, somay, model);
        }, 100);
    } else {
        fillDeviceData(currentTargetDeviceIndex, mavt, somay, model);
    }
    
    closeQuickSearch();
    showNotification(`Đã chọn: ${mavt} - S/N: ${somay}`, 'success');
}

function fillDeviceData(deviceIndex, mavt, somay, model) {
    const mavtInput = document.querySelector(`input[name="devices[${deviceIndex}][mavt]"]`);
    const somayInput = document.querySelector(`input[name="devices[${deviceIndex}][somay]"]`);
    const modelInput = document.querySelector(`input[name="devices[${deviceIndex}][model]"]`);
    
    if (mavtInput) {
        // Temporarily remove readonly to set value
        mavtInput.removeAttribute('readonly');
        mavtInput.value = mavt;
        mavtInput.setAttribute('readonly', 'readonly');
        mavtInput.dispatchEvent(new Event('change'));
    }
    
    if (somayInput && somay) {
        somayInput.removeAttribute('readonly');
        somayInput.value = somay;
        somayInput.setAttribute('readonly', 'readonly');
    }
    
    if (modelInput && model) {
        modelInput.removeAttribute('readonly');
        modelInput.value = model;
        modelInput.setAttribute('readonly', 'readonly');
    }
    
    // Scroll to the device and highlight
    const deviceItem = document.querySelector(`.device-item[data-device-index="${deviceIndex}"]`);
    if (deviceItem) {
        deviceItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
        deviceItem.classList.add('ring-4', 'ring-green-400');
        
        // Focus on first editable field
        setTimeout(() => {
            const vitriInput = deviceItem.querySelector(`input[name="devices[${deviceIndex}][vitrimaybd]"]`);
            if (vitriInput) {
                vitriInput.focus();
            }
        }, 300);
        
        setTimeout(() => {
            deviceItem.classList.remove('ring-4', 'ring-green-400');
        }, 2000);
    }
}

// Search input listener
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('quickSearchInput');
    
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        
        if (!query) {
            displaySearchResults(window.availableDevices);
            return;
        }
        
        // Smart search: split query into words for better matching
        const queryWords = query.split(/\s+/);
        
        const filtered = window.availableDevices.filter(device => {
            const searchText = [
                device.mavt,
                device.tenvt,
                device.somay || '',
                device.model || '',
                device.mamay || ''
            ].join(' ').toLowerCase();
            
            // Check if all query words are present
            return queryWords.every(word => searchText.includes(word));
        });
        
        displaySearchResults(filtered, query);
        selectedSearchIndex = -1;
    });
    
    // Keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        const results = document.querySelectorAll('.device-result');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (results.length > 0) {
                selectSearchResult(selectedSearchIndex + 1);
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (results.length > 0) {
                selectSearchResult(selectedSearchIndex - 1);
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedSearchIndex >= 0 && results[selectedSearchIndex]) {
                results[selectedSearchIndex].click();
            } else if (results.length > 0) {
                // Select first result if none selected
                results[0].click();
            }
        } else if (e.key === 'Escape') {
            closeQuickSearch();
        }
    });
});
</script>

<style>
.device-result.selected {
    border-color: #10b981 !important;
    background: linear-gradient(to right, #ecfdf5, #ffffff);
}

mark {
    background-color: #fef3c7;
    padding: 2px 4px;
    border-radius: 2px;
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
