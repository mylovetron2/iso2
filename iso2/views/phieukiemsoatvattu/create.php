<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Tạo Phiếu Kiểm Soát Vật Tư';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<style>
.vattu-row:hover {
    background-color: #f9fafb;
}
#vattuTable td {
    position: relative;
    overflow: visible !important;
}
.autocomplete-items {
    position: absolute;
    border: 1px solid #d4d4d4;
    border-bottom: none;
    border-top: none;
    z-index: 9999;
    top: 100%;
    left: 0;
    min-width: 400px;
    max-height: 200px;
    overflow-y: auto;
    background-color: #fff;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
.autocomplete-items div {
    padding: 10px;
    cursor: pointer;
    background-color: #fff;
    border-bottom: 1px solid #d4d4d4;
    white-space: normal;
    word-wrap: break-word;
    line-height: 1.4;
}
.autocomplete-items div:hover {
    background-color: #e9e9e9;
}
</style>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold flex items-center">
            <i class="fas fa-plus-circle mr-2 text-green-600"></i> Tạo Phiếu Kiểm Soát Vật Tư
        </h1>
        <a href="phieukiemsoatvattu.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>

    <form method="POST" action="phieukiemsoatvattu.php?action=store" id="phieuForm" class="space-y-6">
        <!-- Thông tin cơ bản -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4 border-b pb-2">Thông tin phiếu</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">1. Loại công việc:</label>
                    <div class="flex gap-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="loai_congviec" value="BD theo kế hoạch" class="mr-2">
                            <span class="text-sm">BD theo kế hoạch</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="loai_congviec" value="KT, BD, SC, gia công đột xuất" class="mr-2" checked>
                            <span class="text-sm">KT, BD, SC, gia công đột xuất</span>
                        </label>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">2. Bộ phận đặt hàng: <span class="text-red-500">*</span></label>
                    <input type="text" name="bophan_dathang" required
                           class="w-full border rounded px-3 py-2"
                           placeholder="VD: Đội Địa vật lý tổng hợp"
                           value="Đội Địa vật lý tổng hợp">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">3. Tên TB: <span class="text-red-500">*</span></label>
                    <input type="text" name="ten_thietbi" required
                           class="w-full border rounded px-3 py-2"
                           placeholder="VD: Thiết bị Halliburton">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Ký mã hiệu:</label>
                    <input type="text" name="ky_mahieu"
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">4. Người lập phiếu: <span class="text-red-500">*</span></label>
                    <input type="text" name="nguoi_lap_phieu" required
                           class="w-full border rounded px-3 py-2"
                           value="<?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? $_SESSION['user']['username'] ?? ''); ?>">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Bộ phận:</label>
                    <input type="text" name="bophan_nguoilap"
                           class="w-full border rounded px-3 py-2"
                           placeholder="VD: Xưởng SCTBĐVL"
                           value="Xưởng SCTBĐVL">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">5. Phiếu xuất kho số:</label>
                    <input type="text" name="phieu_xuat_kho_so"
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Ngày:</label>
                    <input type="date" name="ngay_xuat_kho"
                           class="w-full border rounded px-3 py-2"
                           value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            
            <div class="mt-4">
                <label class="block text-sm font-medium mb-1">Ghi chú:</label>
                <textarea name="ghi_chu" rows="2" class="w-full border rounded px-3 py-2"></textarea>
            </div>
        </div>

        <!-- Danh mục vật tư -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4 border-b pb-2">6. Danh mục vật tư</h2>
            
            <div style="overflow-x: auto; overflow-y: visible;">
                <table class="min-w-full border" id="vattuTable" style="position: relative;">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2 text-sm w-12">STT</th>
                            <th class="border px-2 py-2 text-sm w-32">Mã vật tư</th>
                            <th class="border px-2 py-2 text-sm">Tên vật tư</th>
                            <th class="border px-2 py-2 text-sm w-20">ĐVT</th>
                            <th class="border px-2 py-2 text-sm w-24" style="display: none;">Nhận</th>
                            <th class="border px-2 py-2 text-sm w-24">Tiêu hao</th>
                            <th class="border px-2 py-2 text-sm w-48">Ghi chú</th>
                            <th class="border px-2 py-2 text-sm w-16">Xóa</th>
                        </tr>
                    </thead>
                    <tbody id="vattuTableBody">
                        <!-- Rows will be added here by JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <button type="button" onclick="addVatTuRow()" 
                    class="mt-4 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                <i class="fas fa-plus mr-1"></i> Thêm vật tư
            </button>
        </div>

        <!-- Buttons -->
        <div class="flex justify-end gap-2">
            <a href="phieukiemsoatvattu.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                <i class="fas fa-save mr-1"></i> Lưu phiếu
            </button>
        </div>
        
        <input type="hidden" name="vattu_items" id="vattuItemsInput">
    </form>
</div>

<script>
let rowCounter = 0;
let vattuData = <?php echo json_encode($vattus); ?>;

function addVatTuRow() {
    rowCounter++;
    const tbody = document.getElementById('vattuTableBody');
    const row = document.createElement('tr');
    row.className = 'vattu-row';
    row.id = 'row-' + rowCounter;
    
    row.innerHTML = `
        <td class="border px-2 py-2 text-center text-sm">${rowCounter}</td>
        <td class="border px-2 py-2">
            <div class="relative">
                <input type="text" 
                       class="w-full border-0 px-2 py-1 text-sm mavattu-input" 
                       data-row="${rowCounter}"
                       placeholder="Nhập mã..."
                       onkeyup="searchVatTu(this)">
                <div class="autocomplete-items"></div>
            </div>
        </td>
        <td class="border px-2 py-2">
            <input type="text" readonly
                   class="w-full border-0 px-2 py-1 text-sm tenvattu-display bg-gray-50" 
                   id="tenvattu-${rowCounter}">
        </td>
        <td class="border px-2 py-2 text-center">
            <input type="text" readonly
                   class="w-full border-0 px-2 py-1 text-sm text-center donvi-display bg-gray-50" 
                   id="donvi-${rowCounter}">
        </td>
        <td class="border px-2 py-2" style="display: none;">
            <input type="number" step="0.01" min="0"
                   class="w-full border-0 px-2 py-1 text-sm text-right soluong-nhan" 
                   id="nhan-${rowCounter}">
        </td>
        <td class="border px-2 py-2">
            <input type="number" step="1" min="0"
                   class="w-full border-0 px-2 py-1 text-sm text-right soluong-tieuhao" 
                   id="tieuhao-${rowCounter}">
        </td>
        <td class="border px-2 py-2">
            <input type="text"
                   class="w-full border-0 px-2 py-1 text-sm ghichu-input" 
                   id="ghichu-${rowCounter}">
        </td>
        <td class="border px-2 py-2 text-center">
            <button type="button" onclick="removeRow(${rowCounter})"
                    class="text-red-600 hover:text-red-800">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    row.dataset.vattuStt = '';
    tbody.appendChild(row);
}

function removeRow(rowId) {
    const row = document.getElementById('row-' + rowId);
    if (row) {
        row.remove();
        updateRowNumbers();
    }
}

function updateRowNumbers() {
    const rows = document.querySelectorAll('#vattuTableBody tr');
    rows.forEach((row, index) => {
        row.querySelector('td:first-child').textContent = index + 1;
    });
}

function searchVatTu(input) {
    const searchTerm = input.value.toLowerCase();
    const rowId = input.getAttribute('data-row');
    const autocompleteDiv = input.nextElementSibling;
    
    // Clear previous results
    autocompleteDiv.innerHTML = '';
    
    if (searchTerm.length < 2) return;
    
    // Filter vật tư
    const filtered = vattuData.filter(v => 
        v.mavattu.toLowerCase().includes(searchTerm) ||
        v.ten_tiengviet.toLowerCase().includes(searchTerm)
    );
    
    // Display results
    filtered.slice(0, 10).forEach(vattu => {
        const div = document.createElement('div');
        div.innerHTML = `<strong>${vattu.mavattu}</strong> - ${vattu.ten_tiengviet}`;
        div.addEventListener('click', function() {
            selectVatTu(rowId, vattu);
            autocompleteDiv.innerHTML = '';
        });
        autocompleteDiv.appendChild(div);
    });
}

function selectVatTu(rowId, vattu) {
    const row = document.getElementById('row-' + rowId);
    row.dataset.vattuStt = vattu.stt;
    
    row.querySelector('.mavattu-input').value = vattu.mavattu;
    document.getElementById('tenvattu-' + rowId).value = vattu.ten_tiengviet;
    document.getElementById('donvi-' + rowId).value = vattu.donvi || '';
}

// Close autocomplete when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.classList.contains('mavattu-input')) {
        document.querySelectorAll('.autocomplete-items').forEach(div => {
            div.innerHTML = '';
        });
    }
});

// Form submission
document.getElementById('phieuForm').addEventListener('submit', function(e) {
    const rows = document.querySelectorAll('#vattuTableBody tr');
    const items = [];
    
    rows.forEach(row => {
        const vattuStt = row.dataset.vattuStt;
        if (!vattuStt) return;
        
        const rowId = row.id.split('-')[1];
        items.push({
            vattu_stt: vattuStt,
            soluong_nhan: document.getElementById('nhan-' + rowId).value || 0,
            soluong_tieuhao: document.getElementById('tieuhao-' + rowId).value || 0,
            ghichu: document.getElementById('ghichu-' + rowId).value || ''
        });
    });
    
    if (items.length === 0) {
        e.preventDefault();
        alert('Vui lòng thêm ít nhất một vật tư!');
        return false;
    }
    
    document.getElementById('vattuItemsInput').value = JSON.stringify(items);
});

// Add 10 rows on load
for (let i = 0; i < 10; i++) {
    addVatTuRow();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
