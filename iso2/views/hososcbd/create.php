<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Thêm Hồ sơ SCBĐ';
require_once __DIR__ . '/../layouts/header.php'; 

$prefillPhieuValue = isset($prefillPhieu) ? $prefillPhieu : '';

// Helper function to get prefill value - use GLOBALS to ensure proper scope
$GLOBALS['_prefillData'] = $prefillData ?? null;

function getPrefillValue($field, $default = '') {
    // Priority: POST > prefillData > default
    if (isset($_POST[$field])) {
        return $_POST[$field];
    }
    
    $prefillData = $GLOBALS['_prefillData'] ?? null;
    
    if (isset($prefillData[$field]) && $prefillData[$field] !== null && $prefillData[$field] !== '') {
        // Format date if needed
        if ($field === 'ngayyc' && $prefillData[$field]) {
            // Ensure Y-m-d format for date input
            $date = $prefillData[$field];
            if (strtotime($date)) {
                return date('Y-m-d', strtotime($date));
            }
        }
        return $prefillData[$field];
    }
    return $default;
}
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

    <?php if (!empty($prefillData)): ?>
    <div class="bg-green-50 border-l-4 border-green-500 px-4 py-3 rounded mb-4">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-green-600 mt-1 mr-3"></i>
            <div>
                <p class="font-semibold text-green-800">Đang thêm thiết bị vào phiếu: <strong><?php echo htmlspecialchars($prefillPhieuValue); ?></strong></p>
                <p class="text-sm text-green-700 mt-1">
                    Thông tin đơn vị và yêu cầu đã được tự động điền từ phiếu này. 
                    Bạn chỉ cần thêm thông tin thiết bị mới.
                </p>
                <?php if (isset($_GET['debug'])): ?>
                <details class="mt-2">
                    <summary class="cursor-pointer text-xs text-gray-600 hover:text-gray-800">🔍 Debug: Xem dữ liệu prefill</summary>
                    <pre class="mt-2 text-xs bg-white p-2 rounded border overflow-auto"><?php print_r($prefillData); ?></pre>
                </details>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Buttons at Top - FIXED/STICKY -->
    <div class="sticky top-0 z-50 flex gap-3 mb-6 p-4 bg-white rounded border-2 border-blue-500 shadow-lg">
        <button type="button" id="saveButton" onclick="submitFormWithLoading();" 
                style="background-color: #2563eb; color: white; padding: 12px 24px; border-radius: 8px; font-size: 18px; font-weight: bold; border: none; cursor: pointer; display: inline-block;">
            <i class="fas fa-save" style="margin-right: 8px;"></i><span id="saveButtonText">Lưu hồ sơ</span>
        </button>
        <a href="hososcbd.php" id="cancelButton"
           style="background-color: #6b7280; color: white; padding: 12px 24px; border-radius: 8px; font-size: 18px; font-weight: bold; text-decoration: none; display: inline-block;">
            <i class="fas fa-times" style="margin-right: 8px;"></i>Hủy
        </a>
    </div>

    <form method="POST" id="hosoForm" class="space-y-6" onsubmit="return handleFormSubmit(event);">
        <!-- Thông tin cơ bản -->
        <div class="border-l-4 border-blue-500 pl-4">
            <h2 class="text-lg font-bold mb-3 text-blue-700">
                <i class="fas fa-info-circle mr-2"></i>Thông tin cơ bản
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Số phiếu
                        <?php if (!empty($prefillPhieuValue)): ?>
                            <span class="text-green-600 text-xs"><i class="fas fa-lock"></i> Đang thêm vào phiếu này</span>
                        <?php endif; ?>
                    </label>
                    <input type="text" name="phieu" value="<?php echo isset($_POST['phieu']) ? htmlspecialchars($_POST['phieu']) : htmlspecialchars(!empty($prefillPhieuValue) ? $prefillPhieuValue : $nextPhieu); ?>"
                           placeholder="Tự động: <?php echo $nextPhieu; ?>"
                           <?php echo !empty($prefillPhieuValue) ? 'readonly' : ''; ?>
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 <?php echo !empty($prefillPhieuValue) ? 'bg-green-50 border-green-400 font-semibold text-green-800' : ''; ?>">
                    <?php if (!empty($prefillPhieuValue)): ?>
                        <p class="text-xs text-green-600 mt-1"><i class="fas fa-check-circle"></i> Thiết bị sẽ được thêm vào phiếu <strong><?php echo htmlspecialchars($prefillPhieuValue); ?></strong></p>
                    <?php else: ?>
                        <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle"></i> Để trống sẽ tự động sinh số tiếp theo</p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Ngày yêu cầu <span class="text-red-500">*</span>
                        <?php if (!empty($prefillData) && !empty($prefillData['ngayyc'])): ?>
                            <span class="text-green-600 text-xs ml-2"><i class="fas fa-check-circle"></i> Tự động</span>
                        <?php endif; ?>
                    </label>
                    <input type="date" name="ngayyc" required value="<?php echo getPrefillValue('ngayyc', date('Y-m-d')); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 <?php echo (!empty($prefillData) && !empty($prefillData['ngayyc'])) ? 'bg-green-50 border-green-300' : ''; ?>">
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
                    <label class="block text-gray-700 font-semibold mb-2">
                        Đơn vị <span class="text-red-500">*</span>
                        <?php if (!empty($prefillData) && !empty($prefillData['madv'])): ?>
                            <span class="text-green-600 text-xs ml-2"><i class="fas fa-check-circle"></i> Tự động</span>
                        <?php endif; ?>
                    </label>
                    <div class="flex gap-2">
                        <select name="madv" id="madvSelect" required class="flex-1 px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 <?php echo (!empty($prefillData) && !empty($prefillData['madv'])) ? 'bg-green-50 border-green-300' : ''; ?>">
                            <option value="">-- Chọn đơn vị --</option>
                            <?php 
                            $selectedMadv = getPrefillValue('madv');
                            foreach ($donViList as $dv): 
                            ?>
                                <option value="<?php echo htmlspecialchars($dv['madv']); ?>" 
                                        <?php echo ($selectedMadv === $dv['madv']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dv['tendv']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Điện thoại
                        <?php if (!empty($prefillData) && !empty($prefillData['dienthoai'])): ?>
                            <span class="text-green-600 text-xs ml-2"><i class="fas fa-check-circle"></i> Tự động</span>
                        <?php endif; ?>
                    </label>
                    <input type="text" name="dienthoai" value="<?php echo htmlspecialchars(getPrefillValue('dienthoai')); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 <?php echo (!empty($prefillData) && !empty($prefillData['dienthoai'])) ? 'bg-green-50 border-green-300' : ''; ?>">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Người yêu cầu
                        <?php if (!empty($prefillData) && !empty($prefillData['ngyeucau'])): ?>
                            <span class="text-green-600 text-xs ml-2"><i class="fas fa-check-circle"></i> Tự động</span>
                        <?php endif; ?>
                    </label>
                    <input type="text" name="ngyeucau" value="<?php echo htmlspecialchars(getPrefillValue('ngyeucau')); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 <?php echo (!empty($prefillData) && !empty($prefillData['ngyeucau'])) ? 'bg-green-50 border-green-300' : ''; ?>">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Người nhận yêu cầu
                        <?php if (!empty($prefillData) && !empty($prefillData['ngnhyeucau'])): ?>
                            <span class="text-green-600 text-xs ml-2"><i class="fas fa-check-circle"></i> Tự động</span>
                        <?php endif; ?>
                    </label>
                    <input type="text" name="ngnhyeucau" value="<?php echo htmlspecialchars(getPrefillValue('ngnhyeucau')); ?>"
                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 <?php echo (!empty($prefillData) && !empty($prefillData['ngnhyeucau'])) ? 'bg-green-50 border-green-300' : ''; ?>">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-semibold mb-2">
                        Công việc <span class="text-red-500">*</span>
                        <?php if (!empty($prefillData) && !empty($prefillData['cv'])): ?>
                            <span class="text-green-600 text-xs ml-2"><i class="fas fa-check-circle"></i> Tự động</span>
                        <?php endif; ?>
                    </label>
                    <select name="cv" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 <?php echo (!empty($prefillData) && !empty($prefillData['cv'])) ? 'bg-green-50 border-green-300' : ''; ?>">
                        <?php $currentCv = getPrefillValue('cv', 'SC'); ?>
                        <option value="KT" <?php echo ($currentCv === 'KT') ? 'selected' : ''; ?>>KT - Kiểm Tra</option>
                        <option value="BD" <?php echo ($currentCv === 'BD') ? 'selected' : ''; ?>>BD - Bảo Dưỡng</option>
                        <option value="SC" <?php echo ($currentCv === 'SC') ? 'selected' : ''; ?>>SC - Sửa Chữa</option>
                        <option value="BDDK" <?php echo ($currentCv === 'BDDK') ? 'selected' : ''; ?>>BDDK - Bảo Dưỡng Định Kỳ</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-semibold mb-2">
                        Yêu cầu thêm của KH
                        <?php if (!empty($prefillData) && !empty($prefillData['ycthemkh'])): ?>
                            <span class="text-green-600 text-xs ml-2"><i class="fas fa-check-circle"></i> Tự động</span>
                        <?php endif; ?>
                    </label>
                    <textarea name="ycthemkh" rows="2" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500 <?php echo (!empty($prefillData) && !empty($prefillData['ycthemkh'])) ? 'bg-green-50 border-green-300' : ''; ?>"><?php echo htmlspecialchars(getPrefillValue('ycthemkh')); ?></textarea>
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
            <div class="mb-3">
                <h2 class="text-lg font-bold text-green-700">
                    <i class="fas fa-cogs mr-2"></i>Thông tin thiết bị
                    <span class="text-sm font-normal text-gray-600 ml-2">(<span id="deviceCount">0</span> thiết bị)</span>
                </h2>
            </div>
            
            <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg p-4 mb-4">
                <p class="text-sm text-gray-700 flex items-start">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
                    <span>
                        Bạn có thể thêm không giới hạn số lượng thiết bị. 
                        Sử dụng nút <strong class="text-green-700">"Chọn thiết bị"</strong> ở phía dưới để thêm và nút 
                        <strong class="text-red-700">"Xóa"</strong> để xóa thiết bị không cần thiết.
                    </span>
                </p>
            </div>

            <div id="deviceContainer" style="display: none;">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gradient-to-r from-blue-500 to-cyan-500">
                                <th class="border border-gray-300 px-3 py-2 text-center text-white font-semibold w-16">Stt</th>
                                <th class="border border-gray-300 px-3 py-2 text-center text-white font-semibold" style="width: 20%;">Tên thiết bị (Số máy)</th>
                                <th class="border border-gray-300 px-3 py-2 text-center text-white font-semibold" style="width: 20%;">Tình trạng kỹ thuật</th>
                                <th class="border border-gray-300 px-3 py-2 text-center text-white font-semibold" style="width: 20%;">Nội dung yêu cầu</th>
                                <th class="border border-gray-300 px-3 py-2 text-center text-white font-semibold">Máy từ đâu về xưởng</th>
                                <th class="border border-gray-300 px-3 py-2 text-center text-white font-semibold w-20">Xóa</th>
                            </tr>
                        </thead>
                        <tbody id="deviceTableBody">
                            <!-- Devices will be added here dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Nút thêm thiết bị ở phía dưới -->
            <div class="mt-4">
                <button type="button" onclick="openAddDevicePanel()" 
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-semibold transition-colors flex items-center shadow-md hover:shadow-lg">
                    <i class="fas fa-plus mr-2"></i>Chọn thiết bị
                </button>
            </div>
        </div>

<style>
/* Table styling for device list */
.device-item:hover {
    background-color: #f9fafb;
}

@media print {
    .border-gray-300 {
        border-color: #000 !important;
    }
}
</style>

<script>
let deviceIndex = 0;
let isSubmitting = false; // Prevent double submit

// Function to handle form submission with loading state
function submitFormWithLoading() {
    // Prevent double submit
    if (isSubmitting) {
        return false;
    }
    
    const form = document.getElementById('hosoForm');
    const saveButton = document.getElementById('saveButton');
    const saveButtonText = document.getElementById('saveButtonText');
    const cancelButton = document.getElementById('cancelButton');
    
    // Check form validity
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }
    
    // Set submitting state
    isSubmitting = true;
    
    // Disable button and show loading
    saveButton.disabled = true;
    saveButton.style.cursor = 'not-allowed';
    saveButton.style.opacity = '0.7';
    saveButtonText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...';
    
    // Disable cancel button
    cancelButton.style.pointerEvents = 'none';
    cancelButton.style.opacity = '0.5';
    
    // Submit the form
    form.submit();
    
    return false;
}

// Handle form submit event (for Enter key or other triggers)
function handleFormSubmit(event) {
    if (isSubmitting) {
        event.preventDefault();
        return false;
    }
    
    const saveButton = document.getElementById('saveButton');
    const saveButtonText = document.getElementById('saveButtonText');
    const cancelButton = document.getElementById('cancelButton');
    
    isSubmitting = true;
    
    // Show loading state
    saveButton.disabled = true;
    saveButton.style.cursor = 'not-allowed';
    saveButton.style.opacity = '0.7';
    saveButtonText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...';
    
    cancelButton.style.pointerEvents = 'none';
    cancelButton.style.opacity = '0.5';
    
    return true;
}

function addDevice() {
    deviceIndex++;
    const tbody = document.getElementById('deviceTableBody');
    const container = document.getElementById('deviceContainer');
    
    // Show container if first device
    if (deviceIndex === 1) {
        container.style.display = 'block';
    }
    
    const deviceRow = `
        <tr class="device-item hover:bg-gray-50 transition-colors" data-device-index="${deviceIndex}">
            <td class="border border-gray-300 px-2 py-2 text-center">
                <span class="device-number font-bold text-blue-600">${deviceIndex}</span>
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <input type="text" name="devices[${deviceIndex}][mavt]" readonly
                       class="device-display w-full px-2 py-1.5 bg-gray-50 border border-gray-300 rounded text-gray-700 cursor-not-allowed text-sm"
                       placeholder="Chọn từ danh sách">
                <input type="hidden" name="devices[${deviceIndex}][somay]" class="somay-hidden">
                <input type="hidden" name="devices[${deviceIndex}][tenvt]" class="tenvt-hidden">
                <input type="hidden" name="devices[${deviceIndex}][model]" class="model-hidden">
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <textarea name="devices[${deviceIndex}][honghoc]" rows="2"
                          class="w-full px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:border-blue-500 resize-none text-sm"
                          placeholder="Nhập tình trạng..."></textarea>
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <textarea name="devices[${deviceIndex}][noidungyc]" rows="2"
                          class="w-full px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:border-blue-500 resize-none text-sm"
                          placeholder="Nhập nội dung yêu cầu..."></textarea>
            </td>
            <td class="border border-gray-300 px-2 py-2">
                <select name="devices[${deviceIndex}][vitrimaybd]"
                        class="vitri-select w-full px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:border-blue-500 text-sm">
                    <option value="">-- Chọn --</option>
                </select>
            </td>
            <td class="border border-gray-300 px-2 py-2 text-center">
                <button type="button" onclick="removeDevice(this)" 
                        class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-sm transition-colors">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    
    tbody.insertAdjacentHTML('beforeend', deviceRow);
    updateDeviceCount();
    
    // Update datalists for new device
    if (window.availableDevices && window.availableDevices.length > 0) {
        updateMavtDataLists();
    }
    
    // Load positions for new select dropdown
    if (window.vitriPositions && window.vitriPositions.length > 0) {
        populateVitriSelect();
    }
    
    // Scroll to new row
    setTimeout(() => {
        const newRow = tbody.lastElementChild;
        if (newRow) {
            newRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }, 100);
}

function removeDevice(button) {
    const row = button.closest('tr');
    const tbody = document.getElementById('deviceTableBody');
    const container = document.getElementById('deviceContainer');
    
    // Add fade out animation
    row.style.opacity = '0';
    row.style.transition = 'opacity 0.3s ease-out';
    
    setTimeout(() => {
        row.remove();
        updateDeviceCount();
        renumberDevices();
        
        // Hide container if no devices left
        if (tbody.children.length === 0) {
            container.style.display = 'none';
        }
    }, 300);
}

function updateDeviceCount() {
    const count = document.querySelectorAll('.device-item').length;
    document.getElementById('deviceCount').textContent = count;
}

function renumberDevices() {
    const rows = document.querySelectorAll('.device-item');
    rows.forEach((row, index) => {
        const displayNumber = index + 1;
        const numberSpan = row.querySelector('.device-number');
        
        if (numberSpan) numberSpan.textContent = displayNumber;
        
        // Update data-device-index attribute
        row.setAttribute('data-device-index', displayNumber);
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
    </form>
</div>

<script>
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
        
        loadDevicesForUnit(madv, this);
    });
    
    // Auto-load devices if unit is already selected (prefill case)
    if (madvSelect.value) {
        console.log('Prefill detected - auto-loading devices for:', madvSelect.value);
        loadDevicesForUnit(madvSelect.value, madvSelect);
    }
});

// Function to load devices for a specific unit
function loadDevicesForUnit(madv, selectElement) {
    if (!madv) return;
    
    // Show loading indicator
    if (selectElement) {
        selectElement.disabled = true;
    }
    
    // Load devices for this unit
    fetch(`/iso2/api/thietbi.php?madv=${encodeURIComponent(madv)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (selectElement) {
                selectElement.disabled = false;
            }
            
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
            if (selectElement) {
                selectElement.disabled = false;
            }
            console.error('Error loading devices:', error);
            window.availableDevices = [];
            showNotification('Lỗi kết nối khi tải thiết bị. Vui lòng thử lại.', 'error');
        });
}

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
        
        if (!mavtInput || !mavtInput.value) {
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
            fillDeviceData(currentTargetDeviceIndex, mavt, somay, model, tenvt);
        }, 100);
    } else {
        fillDeviceData(currentTargetDeviceIndex, mavt, somay, model, tenvt);
    }
    
    closeQuickSearch();
    showNotification(`Đã chọn: ${mavt} - S/N: ${somay}`, 'success');
}

function fillDeviceData(deviceIndex, mavt, somay, model, tenvt = '') {
    const row = document.querySelector(`.device-item[data-device-index="${deviceIndex}"]`);
    if (!row) return;
    
    const mavtInput = row.querySelector(`input[name="devices[${deviceIndex}][mavt]"]`);
    const tenvtHidden = row.querySelector('.tenvt-hidden');
    const somayHidden = row.querySelector('.somay-hidden');
    const modelHidden = row.querySelector('.model-hidden');
    
    // Set mã vật tư + số máy (visible) - hiển thị cả hai trong cùng ô
    if (mavtInput && mavt) {
        mavtInput.removeAttribute('readonly');
        const displayText = somay ? `${mavt} (${somay})` : mavt;
        mavtInput.value = displayText;
        mavtInput.setAttribute('readonly', 'readonly');
    }
    
    // Set tên thiết bị (hidden)
    if (tenvtHidden && tenvt) {
        tenvtHidden.value = tenvt;
    }
    
    // Set số máy (hidden)
    if (somayHidden && somay) {
        somayHidden.value = somay;
    }
    
    // Set model (hidden)
    if (modelHidden && model) {
        modelHidden.value = model;
    }
    
    // Scroll to the row and highlight
    if (row) {
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        row.classList.add('bg-green-100');
        
        setTimeout(() => {
            row.classList.remove('bg-green-100');
        }, 2000);
        
        // Focus on first editable field (tình trạng kỹ thuật)
        setTimeout(() => {
            const honghocInput = row.querySelector(`textarea[name="devices[${deviceIndex}][honghoc]"]`);
            if (honghocInput) {
                honghocInput.focus();
            }
        }, 300);
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
