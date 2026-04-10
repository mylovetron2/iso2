<?php
/**
 * SỬA PHIẾU NHẬN TỪ ĐỘI (chỉ cho phép khi trạng thái: da_nhan)
 */
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-edit mr-2 text-orange-500"></i>
            Sửa Phiếu Nhận Thiết Bị Từ Đội
        </h1>
        <a href="giaonhanthietbi.php?action=view&id=<?= $phieu['id'] ?>" 
           class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
    </div>

    <!-- Thông báo -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="giaonhanthietbi.php?action=update" id="formEditPhieu">
        <input type="hidden" name="id" value="<?= $phieu['id'] ?>">
        
        <div class="bg-white rounded-lg shadow-md p-6">
            
            <!-- Section 1: Thông tin nhận từ đội -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">
                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                    Thông Tin Nhận Từ Đội
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Người giao -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Người Giao <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="nguoi_giao" 
                               required
                               value="<?= htmlspecialchars($phieu['nguoi_giao']) ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Nhập tên người giao">
                    </div>

                    <!-- Đơn vị giao -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Đơn Vị Giao <span class="text-red-500">*</span>
                        </label>
                        <select name="donvi_giao" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Chọn đơn vị --</option>
                            <?php foreach ($donviList as $dv): ?>
                                <option value="<?= htmlspecialchars($dv['madv']) ?>"
                                        <?= $dv['madv'] === $phieu['donvi_giao'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dv['tendv']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Ngày giao -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ngày Giao <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               name="ngay_giao" 
                               required
                               value="<?= htmlspecialchars($phieu['ngay_giao']) ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Ghi chú chung -->
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Ghi Chú Chung
                    </label>
                    <textarea name="ghichu" 
                              rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Nhập ghi chú (nếu có)"><?= htmlspecialchars($phieu['ghichu'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Quick Search Thiết bị -->
            <div id="quickSearchPanel" class="hidden mb-6 border-l-4 border-yellow-500 pl-4 bg-gradient-to-br from-yellow-50 to-orange-50 rounded-lg p-4 shadow-lg">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-bold text-yellow-700 flex items-center">
                        <i class="fas fa-plus-circle mr-2"></i>Thêm thiết bị vào phiếu
                    </h2>
                    <span id="searchResultCount" class="text-sm text-gray-600 bg-white px-3 py-1 rounded-full border border-yellow-300">
                        0 kết quả
                    </span>
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
                
                <div id="quickSearchResults" class="max-h-80 overflow-y-auto space-y-2 scroll-smooth mb-3"></div>
                
                <div class="border-t-2 border-dashed border-gray-300 pt-3">
                    <button type="button" onclick="addDeviceManually()" 
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-3 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg flex items-center justify-center">
                        <i class="fas fa-keyboard mr-2"></i>
                        Không tìm thấy? Nhập thủ công thiết bị mới
                    </button>
                </div>
            </div>

            <!-- Section 2: Danh sách thiết bị -->
            <div class="mb-6">
                <div class="mb-3">
                    <h2 class="text-lg font-semibold text-gray-700">
                        <i class="fas fa-list mr-2 text-green-500"></i>
                        Danh Sách Thiết Bị <span class="text-red-500">*</span>
                        <span class="text-sm font-normal text-gray-600 ml-2">(<span id="deviceCount"><?= count($devices) ?></span> thiết bị)</span>
                    </h2>
                </div>

                <!-- Container hiển thị dạng bảng -->
                <div id="thietbi-container" style="<?= count($devices) > 0 ? '' : 'display: none;' ?>">
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border border-gray-300">
                            <thead>
                                <tr class="bg-gradient-to-r from-blue-500 to-cyan-500">
                                    <th class="border border-gray-300 px-3 py-2 text-center text-white font-semibold w-16">STT</th>
                                    <th class="border border-gray-300 px-3 py-2 text-center text-white font-semibold" style="width: 30%;">Tên thiết bị (Số máy)</th>
                                    <th class="border border-gray-300 px-3 py-2 text-center text-white font-semibold" style="width: 30%;">Tình trạng khi nhận</th>
                                    <th class="border border-gray-300 px-3 py-2 text-center text-white font-semibold" style="width: 30%;">Ghi chú</th>
                                    <th class="border border-gray-300 px-3 py-2 text-center text-white font-semibold w-20">Xóa</th>
                                </tr>
                            </thead>
                            <tbody id="thietbi-tbody">
                                <?php foreach ($devices as $idx => $device): 
                                    $deviceIndex = $idx + 1;
                                    $displayText = $device['ky_ma_hieu'] 
                                        ? $device['ten_thietbi'] . ' (' . $device['ky_ma_hieu'] . ')' 
                                        : $device['ten_thietbi'];
                                ?>
                                <tr class="device-item hover:bg-gray-50 transition-colors" data-device-index="<?= $deviceIndex ?>">
                                    <td class="border border-gray-300 px-2 py-2 text-center">
                                        <span class="device-number font-bold text-blue-600"><?= $deviceIndex ?></span>
                                    </td>
                                    <td class="border border-gray-300 px-2 py-2">
                                        <input type="text" readonly
                                               value="<?= htmlspecialchars($displayText) ?>"
                                               class="w-full px-2 py-1.5 bg-gray-50 border border-gray-300 rounded text-gray-700 cursor-not-allowed text-sm">
                                        <input type="hidden" name="thietbi_id[<?= $deviceIndex ?>]" value="<?= htmlspecialchars($device['thietbi_id']) ?>">
                                    </td>
                                    <td class="border border-gray-300 px-2 py-2">
                                        <textarea name="tinhtrang[<?= $deviceIndex ?>]" rows="2"
                                                  class="w-full px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:border-blue-500 resize-none text-sm"
                                                  placeholder="Tình trạng khi nhận..."><?= htmlspecialchars($device['tinhtrang'] ?? '') ?></textarea>
                                    </td>
                                    <td class="border border-gray-300 px-2 py-2">
                                        <textarea name="ghichu_thietbi[<?= $deviceIndex ?>]" rows="2"
                                                  class="w-full px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:border-blue-500 resize-none text-sm"
                                                  placeholder="Ghi chú..."><?= htmlspecialchars($device['ghichu'] ?? '') ?></textarea>
                                    </td>
                                    <td class="border border-gray-300 px-2 py-2 text-center">
                                        <button type="button" onclick="removeDevice(this)" 
                                                class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-sm transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Nút thêm thiết bị -->
                <div class="mt-4">
                    <button type="button" onclick="openAddDevicePanel()" 
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors flex items-center shadow-md hover:shadow-lg">
                        <i class="fas fa-plus mr-2"></i>Chọn thiết bị
                    </button>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-2">
                <a href="giaonhanthietbi.php?action=view&id=<?= $phieu['id'] ?>" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-times mr-2"></i>Hủy
                </a>
                <button type="submit" 
                        class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-save mr-2"></i>Cập Nhật
                </button>
            </div>

        </div>
    </form>
</div>

<script>
let deviceIndex = <?= count($devices) ?>;
let selectedSearchIndex = -1;

// Load all devices from thietbihckd_iso
function loadAllDevices() {
    fetch('/iso2/api/thietbi_hckd.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                window.availableDevices = data.data;
                console.log(`Loaded ${data.data.length} devices`);
            } else {
                window.availableDevices = [];
            }
        })
        .catch(error => {
            console.error('Error loading devices:', error);
            window.availableDevices = [];
        });
}

// Open search panel
function openAddDevicePanel() {
    // Load devices if not yet loaded
    if (!window.availableDevices) {
        loadAllDevices();
    }
    
    const panel = document.getElementById('quickSearchPanel');
    panel.classList.remove('hidden');
    selectedSearchIndex = -1;
    
    setTimeout(() => {
        const input = document.getElementById('quickSearchInput');
        input.focus();
        
        if (window.availableDevices && window.availableDevices.length > 0) {
            displaySearchResults(window.availableDevices);
        } else {
            document.getElementById('quickSearchResults').innerHTML = `
                <div class="text-center py-6 bg-white rounded-lg border-2 border-dashed border-gray-300">
                    <i class="fas fa-inbox text-gray-300 text-4xl mb-2"></i>
                    <p class="text-gray-600 mb-2">Chưa có thiết bị nào trong hệ thống</p>
                </div>
            `;
        }
    }, 100);
}

function closeQuickSearch() {
    document.getElementById('quickSearchPanel').classList.add('hidden');
    document.getElementById('quickSearchInput').value = '';
    document.getElementById('quickSearchResults').innerHTML = '';
    selectedSearchIndex = -1;
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('quickSearchInput');
    
    // Load devices on page load
    loadAllDevices();
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            
            if (!window.availableDevices || window.availableDevices.length === 0) {
                return;
            }
            
            if (!query) {
                displaySearchResults(window.availableDevices);
                return;
            }
            
            const filtered = window.availableDevices.filter(device => {
                const deviceName = device.tenvt || '';
                const deviceSerial = device.somay || '';
                const deviceMavt = device.mavt || '';
                
                const searchText = `${deviceName} ${deviceSerial} ${deviceMavt}`.toLowerCase();
                return searchText.includes(query);
            });
            
            displaySearchResults(filtered, query);
        });
        
        searchInput.addEventListener('keydown', function(e) {
            const results = document.querySelectorAll('.device-result');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedSearchIndex = Math.min(selectedSearchIndex + 1, results.length - 1);
                selectSearchResult(selectedSearchIndex);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedSearchIndex = Math.max(selectedSearchIndex - 1, 0);
                selectSearchResult(selectedSearchIndex);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedSearchIndex >= 0 && results[selectedSearchIndex]) {
                    results[selectedSearchIndex].click();
                }
            } else if (e.key === 'Escape') {
                closeQuickSearch();
            }
        });
    }
});

function displaySearchResults(devices, query = '') {
    const resultsDiv = document.getElementById('quickSearchResults');
    const countDiv = document.getElementById('searchResultCount');
    
    if (!devices || devices.length === 0) {
        resultsDiv.innerHTML = '<div class="text-center py-8"><i class="fas fa-inbox text-gray-300 text-4xl mb-2"></i><p class="text-gray-500">Không tìm thấy thiết bị nào</p></div>';
        countDiv.textContent = '0 kết quả';
        return;
    }
    
    countDiv.textContent = `${devices.length} kết quả`;
    
    resultsDiv.innerHTML = devices.map((device, index) => {
        const deviceId = device.id || device.stt;
        const deviceName = device.tenvt;
        const deviceSerial = device.somay || '';
        
        return `
        <div class="device-result ${index === selectedSearchIndex ? 'ring-2 ring-green-400 border-green-500' : ''} bg-white border-2 border-gray-300 hover:border-green-500 rounded-lg p-3 cursor-pointer transition-all hover:shadow-md group"
             data-index="${index}"
             onclick="selectDeviceFromSearch('${escapeHtml(deviceId)}', '${escapeHtml(deviceName)}', '${escapeHtml(deviceSerial)}')">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-bold text-blue-600 text-base">${highlightText(escapeHtml(deviceName), query)}</span>
                        ${deviceSerial ? `<span class="text-gray-400">•</span><span class="font-semibold text-green-600 text-base"><i class="fas fa-barcode text-xs mr-1"></i>${highlightText(escapeHtml(deviceSerial), query)}</span>` : ''}
                    </div>
                </div>
                <i class="fas fa-chevron-right text-gray-400 group-hover:text-green-500 transition-colors"></i>
            </div>
        </div>`;
    }).join('');
    
    selectedSearchIndex = -1;
}

function highlightText(text, query) {
    if (!query) return text;
    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<mark class="bg-yellow-300 font-semibold">$1</mark>');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function selectSearchResult(index) {
    const results = document.querySelectorAll('.device-result');
    if (index < 0) index = 0;
    if (index >= results.length) index = results.length - 1;
    
    results.forEach(r => r.classList.remove('ring-2', 'ring-green-400', 'border-green-500'));
    if (results[index]) {
        results[index].classList.add('ring-2', 'ring-green-400', 'border-green-500');
        results[index].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    selectedSearchIndex = index;
}

function selectDeviceFromSearch(thietbiId, tenThietBi, soMay) {
    addDevice(thietbiId, tenThietBi, soMay);
    closeQuickSearch();
}

function addDeviceManually() {
    closeQuickSearch();
    addDevice('', '', '');
}

function addDevice(thietbiId = '', tenThietBi = '', soMay = '') {
    deviceIndex++;
    
    const tbody = document.getElementById('thietbi-tbody');
    const container = document.getElementById('thietbi-container');
    
    if (deviceIndex === 1) {
        container.style.display = 'block';
    }
    
    const displayText = soMay ? `${tenThietBi} (${soMay})` : tenThietBi;
    
    const row = document.createElement('tr');
    row.className = 'device-item hover:bg-gray-50 transition-colors';
    row.setAttribute('data-device-index', deviceIndex);
    row.innerHTML = `
        <td class="border border-gray-300 px-2 py-2 text-center">
            <span class="device-number font-bold text-blue-600">${deviceIndex}</span>
        </td>
        <td class="border border-gray-300 px-2 py-2">
            <input type="text" readonly value="${escapeHtml(displayText)}"
                   class="w-full px-2 py-1.5 bg-gray-50 border border-gray-300 rounded text-gray-700 cursor-not-allowed text-sm">
            <input type="hidden" name="thietbi_id[${deviceIndex}]" value="${escapeHtml(thietbiId)}">
        </td>
        <td class="border border-gray-300 px-2 py-2">
            <textarea name="tinhtrang[${deviceIndex}]" rows="2"
                      class="w-full px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:border-blue-500 resize-none text-sm"></textarea>
        </td>
        <td class="border border-gray-300 px-2 py-2">
            <textarea name="ghichu_thietbi[${deviceIndex}]" rows="2"
                      class="w-full px-2 py-1.5 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:border-blue-500 resize-none text-sm"></textarea>
        </td>
        <td class="border border-gray-300 px-2 py-2 text-center">
            <button type="button" onclick="removeDevice(this)" 
                    class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-sm transition-colors">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
    updateDeviceCount();
}

function removeDevice(button) {
    const row = button.closest('tr');
    const tbody = document.getElementById('thietbi-tbody');
    const container = document.getElementById('thietbi-container');
    
    row.remove();
    updateDeviceCount();
    renumberDevices();
    
    if (tbody.children.length === 0) {
        container.style.display = 'none';
    }
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
        row.setAttribute('data-device-index', displayNumber);
    });
}

document.getElementById('formEditPhieu').addEventListener('submit', function(e) {
    const deviceCount = document.querySelectorAll('.device-item').length;
    if (deviceCount === 0) {
        e.preventDefault();
        alert('Vui lòng thêm ít nhất 1 thiết bị!');
        return false;
    }
    return true;
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
