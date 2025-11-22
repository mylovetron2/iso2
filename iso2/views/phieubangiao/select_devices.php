<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Chọn Thiết Bị Bàn Giao';
require_once __DIR__ . '/../layouts/header.php'; 
?>
<div class="max-w-7xl mx-auto bg-white rounded-lg shadow-md p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-plus-circle mr-2 text-green-600"></i> Tạo Phiếu Bàn Giao - Bước 1: Chọn Thiết Bị
        </h1>
        <a href="phieubangiao.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
            <i class="fas fa-arrow-left mr-2"></i>Hủy
        </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-times-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="bg-gray-50 p-4 rounded-lg mb-6">
        <h3 class="font-semibold mb-3 flex items-center">
            <i class="fas fa-filter mr-2 text-blue-600"></i>Lọc Thiết Bị
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <input type="text" id="searchInput" placeholder="Tìm mã TB, tên, số máy..."
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
            <div>
                <select id="filterDonvi" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    <option value="">-- Tất cả đơn vị --</option>
                    <?php foreach ($donViList as $dv): ?>
                        <option value="<?php echo htmlspecialchars($dv['madv']); ?>">
                            <?php echo htmlspecialchars($dv['tendv']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <input type="text" id="filterPhieu" placeholder="Phiếu YC (VD: 1926)"
                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
            </div>
            <div>
                <button onclick="loadDevices()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    <i class="fas fa-search mr-2"></i>Tìm kiếm
                </button>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div id="summary" class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4 hidden">
        <div class="flex items-center justify-between">
            <div>
                <span class="font-semibold">Đã chọn:</span> 
                <span id="selectedCount" class="text-blue-700 font-bold">0</span> thiết bị từ 
                <span id="phieuCount" class="text-blue-700 font-bold">0</span> phiếu YC
            </div>
            <div class="space-x-2">
                <button onclick="selectAll()" class="text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-check-square mr-1"></i>Chọn tất cả
                </button>
                <button onclick="clearAll()" class="text-red-600 hover:text-red-800 text-sm">
                    <i class="fas fa-times mr-1"></i>Bỏ chọn tất cả
                </button>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div id="loading" class="text-center py-8 hidden">
        <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-2"></i>
        <p class="text-gray-600">Đang tải danh sách thiết bị...</p>
    </div>

    <!-- Device List Form -->
    <form method="POST" id="deviceForm">
        <div id="deviceList" class="space-y-3 mb-6">
            <!-- Devices will be loaded here by JavaScript -->
        </div>

        <div id="noDevices" class="text-center py-8 text-gray-500 hidden">
            <i class="fas fa-inbox text-4xl mb-2"></i>
            <p>Không có thiết bị nào đã sửa xong, chưa bàn giao</p>
        </div>

        <div id="actions" class="flex gap-3 pt-4 border-t hidden">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded font-semibold">
                <i class="fas fa-arrow-right mr-2"></i>Tiếp tục (Bước 2)
            </button>
            <a href="phieubangiao.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded font-semibold">
                <i class="fas fa-times mr-2"></i>Hủy
            </a>
        </div>
    </form>
</div>

<script>
let allDevices = [];
let selectedDevices = new Set();

// Load devices on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDevices();
});

function loadDevices() {
    const search = document.getElementById('searchInput').value;
    const madv = document.getElementById('filterDonvi').value;
    const phieu = document.getElementById('filterPhieu').value;
    
    const params = new URLSearchParams({
        search: search,
        madv: madv,
        phieuyc: phieu
    });
    
    showLoading(true);
    
    fetch('/iso2/api/phieubangiao_available_devices.php?' + params)
        .then(response => response.json())
        .then(data => {
            showLoading(false);
            
            if (data.success) {
                allDevices = data.data;
                renderDevices(allDevices);
                updateSummary();
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            showLoading(false);
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi tải dữ liệu');
        });
}

function renderDevices(devices) {
    const container = document.getElementById('deviceList');
    const noDevices = document.getElementById('noDevices');
    const actions = document.getElementById('actions');
    
    if (devices.length === 0) {
        container.innerHTML = '';
        noDevices.classList.remove('hidden');
        actions.classList.add('hidden');
        return;
    }
    
    noDevices.classList.add('hidden');
    actions.classList.remove('hidden');
    
    // Group by phieu
    const grouped = {};
    devices.forEach(device => {
        if (!grouped[device.phieu]) {
            grouped[device.phieu] = [];
        }
        grouped[device.phieu].push(device);
    });
    
    let html = '';
    Object.keys(grouped).forEach(phieu => {
        const deviceList = grouped[phieu];
        const madv = deviceList[0].madv;
        const tendv = deviceList[0].tendv;
        
        html += `
            <div class="border rounded-lg p-4 bg-gray-50">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-lg flex items-center">
                        <i class="fas fa-file-alt mr-2 text-blue-600"></i>
                        Phiếu YC: ${phieu} - ${tendv}
                        <span class="ml-2 bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm">
                            ${deviceList.length} thiết bị
                        </span>
                    </h3>
                    <button type="button" onclick="selectPhieu('${phieu}')" 
                            class="text-sm text-blue-600 hover:text-blue-800">
                        <i class="fas fa-check-square mr-1"></i>Chọn tất cả phiếu này
                    </button>
                </div>
                <div class="space-y-2">
        `;
        
        deviceList.forEach(device => {
            const isChecked = selectedDevices.has(device.stt);
            html += `
                <label class="flex items-start p-3 bg-white border rounded hover:bg-blue-50 cursor-pointer">
                    <input type="checkbox" name="selected_devices[]" value="${device.stt}"
                           ${isChecked ? 'checked' : ''}
                           onchange="toggleDevice(${device.stt})"
                           class="mt-1 w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring focus:ring-blue-200">
                    <div class="ml-3 flex-1">
                        <div class="font-semibold text-gray-900">
                            ${device.mavt} - ${device.tenvt}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                            <span class="inline-block mr-3">
                                <i class="fas fa-barcode mr-1"></i>SN: ${device.somay || '-'}
                            </span>
                            <span class="inline-block mr-3">
                                <i class="fas fa-calendar mr-1"></i>Sửa xong: ${formatDate(device.ngay_sua_xong)}
                            </span>
                            <span class="inline-block">
                                <i class="fas fa-id-badge mr-1"></i>Mã QL: ${device.maql}
                            </span>
                        </div>
                    </div>
                </label>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function toggleDevice(stt) {
    if (selectedDevices.has(stt)) {
        selectedDevices.delete(stt);
    } else {
        selectedDevices.add(stt);
    }
    updateSummary();
}

function selectPhieu(phieu) {
    allDevices.forEach(device => {
        if (device.phieu === phieu) {
            selectedDevices.add(device.stt);
            const checkbox = document.querySelector(`input[value="${device.stt}"]`);
            if (checkbox) checkbox.checked = true;
        }
    });
    updateSummary();
}

function selectAll() {
    allDevices.forEach(device => {
        selectedDevices.add(device.stt);
    });
    document.querySelectorAll('input[name="selected_devices[]"]').forEach(cb => {
        cb.checked = true;
    });
    updateSummary();
}

function clearAll() {
    selectedDevices.clear();
    document.querySelectorAll('input[name="selected_devices[]"]').forEach(cb => {
        cb.checked = false;
    });
    updateSummary();
}

function updateSummary() {
    const summary = document.getElementById('summary');
    const count = selectedDevices.size;
    
    if (count > 0) {
        summary.classList.remove('hidden');
        document.getElementById('selectedCount').textContent = count;
        
        // Count unique phieu
        const phieus = new Set();
        selectedDevices.forEach(stt => {
            const device = allDevices.find(d => d.stt == stt);
            if (device) phieus.add(device.phieu);
        });
        document.getElementById('phieuCount').textContent = phieus.size;
    } else {
        summary.classList.add('hidden');
    }
}

function showLoading(show) {
    document.getElementById('loading').classList.toggle('hidden', !show);
    document.getElementById('deviceList').classList.toggle('hidden', show);
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('vi-VN');
}

// Form validation
document.getElementById('deviceForm').addEventListener('submit', function(e) {
    if (selectedDevices.size === 0) {
        e.preventDefault();
        alert('Vui lòng chọn ít nhất 1 thiết bị');
        return false;
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
