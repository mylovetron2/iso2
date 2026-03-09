<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Quản lý Vật Tư Thanh Lý';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<style>
/* Master Table Styling */
#masterTable {
    border-collapse: separate;
    border-spacing: 0 8px;
    background: transparent;
}

#masterTable thead th {
    background: #2196F3;
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    padding: 12px 16px;
    border: none;
    position: sticky;
    top: 0;
    z-index: 10;
}

#masterTable thead th:first-child {
    border-radius: 8px 0 0 8px;
    background: #1565C0 !important;
    font-size: 0.85rem;
}

#masterTable tbody td:first-child {
    background: #f0f8ff;
    font-weight: 700;
}

#masterTable thead th:last-child {
    border-radius: 0 8px 8px 0;
}

/* Master Row - Card Style */
.master-row {
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.master-row td {
    padding: 16px;
    border: none;
    border-top: 1px solid #f0f0f0;
    border-bottom: 1px solid #f0f0f0;
    font-size: 0.95rem;
}

.master-row td:first-child {
    border-left: 1px solid #f0f0f0;
    border-radius: 8px 0 0 8px;
    font-weight: 700;
    color: #2196F3;
    position: relative;
    padding-left: 24px;
}

.master-row td:last-child {
    border-right: 1px solid #f0f0f0;
    border-radius: 0 8px 8px 0;
}

.master-row:hover {
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.15);
    transform: translateY(-2px);
}

.master-row code:hover {
    opacity: 0.8;
    transform: scale(1.02);
    transition: all 0.2s ease;
}

.master-row.active {
    box-shadow: 0 6px 20px rgba(33, 150, 243, 0.25);
    background: #e3f2fd;
}

.master-row.active td {
    border-color: #2196F3;
}

/* Detail Row */
.detail-row {
    display: none;
    background: transparent;
}

.detail-row.active {
    display: table-row;
    animation: fadeIn 0.4s ease-out;
}

.detail-row td {
    padding: 0 0 16px 0 !important;
    border: none !important;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Detail Content */
.detail-content {
    margin-left: 60px;
    margin-right: 20px;
    padding: 0;
    background: transparent;
    animation: slideDown 0.4s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        max-height: 2000px;
        transform: translateY(0);
    }
}

.detail-content > .bg-white {
    background: #fafbfc !important;
    border: 2px solid #e1e4e8;
    border-radius: 12px;
    padding: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.detail-content button[onclick*="showAddForm"] {
    background: #2196F3;
    border: none;
    padding: 6px 14px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    margin-bottom: 8px;
}

.detail-content button[onclick*="showAddForm"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
    background: #1976D2;
}

/* Detail Form */
.detail-content #addForm_\\d+ {
    background: white;
    border: 2px dashed #d1d5db;
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 12px;
}

.detail-content form h5 {
    color: #2196F3;
    font-size: 0.9rem;
    margin-bottom: 10px;
}

.detail-content form label {
    font-weight: 600;
    font-size: 0.8rem;
    color: #4b5563;
    margin-bottom: 4px;
}

.detail-content form input,
.detail-content form select,
.detail-content form textarea {
    border: 1.5px solid #e5e7eb;
    border-radius: 6px;
    font-size: 0.85rem;
    padding: 6px 10px;
    transition: all 0.2s ease;
}

.detail-content form input:focus,
.detail-content form select:focus,
.detail-content form textarea:focus {
    border-color: #2196F3;
    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
    outline: none;
}

/* Detail Table */
.detail-content table {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
    font-size: 0.82rem;
}

.detail-content table thead th {
    background: #f3f4f6 !important;
    color: #374151 !important;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 8px;
    border-bottom: 2px solid #d1d5db;
}

.detail-content table thead th:first-child {
    background: #fed7aa !important;
    color: #7c2d12 !important;
    font-weight: 700 !important;
}

.detail-content tbody tr {
    transition: background 0.2s ease;
}

.detail-content tbody tr:hover {
    background: #f9fafb;
}

.detail-content tbody td {
    padding: 6px 8px;
    border-bottom: 1px solid #f3f4f6;
}

.detail-content table tbody td:first-child {
    background: #f3f4f6 !important;
    font-weight: 600 !important;
    color: #4b5563 !important;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 3px 8px;
    font-size: 0.7rem;
    font-weight: 600;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.badge-success {
    background: #10b981;
    color: white;
}

.badge-warning { 
    background: #f59e0b;
    color: white;
}

.badge-danger { 
    background: #ef4444;
    color: white;
}

/* Code Badge */
code.bg-blue-100 {
    background: #2196F3 !important;
    color: white;
    font-weight: 800;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.95rem;
    letter-spacing: 1px;
    display: inline-block;
    box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);
}

/* Language Tags */
.master-row td strong {
    color: #2196F3;
    font-weight: 700;
}

/* Empty State */
td[colspan] {
    text-align: center;
    padding: 40px !important;
    color: #9ca3af;
}

/* Buttons */
button[type="submit"] {
    background: #10b981;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

button[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    background: #059669;
}

button[type="button"] {
    background: #6b7280;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.85rem;
}

button[type="button"]:hover {
    background: #4b5563;
}

/* Delete Button */
button.text-red-600 {
    transition: all 0.2s ease;
}

button.text-red-600:hover {
    transform: scale(1.2);
}
</style>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-2xl font-bold mb-4 flex items-center">
        <i class="fas fa-boxes mr-2"></i> Quản lý Vật Tư Thanh Lý
    </h1>

    <!-- Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php
            switch ($_GET['success']) {
                case 'created': echo 'Tạo vật tư thành công!'; break;
                case 'updated': echo 'Cập nhật vật tư thành công!'; break;
                case 'deleted': echo 'Xóa vật tư thành công!'; break;
            }
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Filter & Search -->
    <form method="get" class="mb-4">
        <div class="flex gap-2 mb-2">
            <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                   placeholder="Tìm kiếm mã VT, tên, người quản lý..." 
                   class="flex-1 border rounded px-3 py-2">
            
            <select name="phanloai_id" class="border rounded px-3 py-2" style="min-width: 180px;">
                <option value="">-- Tất cả phân loại --</option>
                <?php foreach ($phanLoaiList ?? [] as $pl): ?>
                    <option value="<?php echo $pl['id']; ?>" 
                            <?php echo (isset($_GET['phanloai_id']) && $_GET['phanloai_id'] == $pl['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($pl['ten_phanloai']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                <i class="fas fa-search mr-1"></i> Tìm
            </button>
            <a href="vattuthanhly.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                <i class="fas fa-redo mr-1"></i> Xóa lọc
            </a>
            
            <?php if (hasPermission('vattu.create')): ?>
            <a href="vattuthanhly.php?action=create" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                <i class="fas fa-plus mr-1"></i> Thêm vật tư
            </a>
            <a href="import_vattu_excel.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded">
                <i class="fas fa-file-excel mr-1"></i> Import Excel
            </a>
            <?php endif; ?>
            
            <a href="thongke_vattu_thanh_ly.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded">
                <i class="fas fa-chart-bar mr-1"></i> Thống kê thanh lý
            </a>
            
            <a href="phieukiemsoatvattu.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
                <i class="fas fa-file-alt mr-1"></i> Phiếu kiểm soát VT
            </a>
        </div>
    </form>

    <div>
        <h3 class="text-lg font-semibold mb-3">
            <i class="fas fa-list"></i> Danh sách vật tư (<?php echo $total; ?>)
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border" id="masterTable">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 border text-left">Mã VT</th>
                        <th class="px-4 py-2 border text-left">Số serial</th>
                        <th class="px-4 py-2 border text-left" style="display: none;">Phân loại</th>
                        <th class="px-4 py-2 border text-left">Tên (TA/Nga/Việt)</th>
                        <th class="px-4 py-2 border text-center">ĐVT</th>
                        <th class="px-4 py-2 border text-right">SL còn lại</th>
                        <th class="px-4 py-2 border text-right">Đơn giá</th>
                        <th class="px-4 py-2 border text-center">Ngày nhận</th>
                        <th class="px-4 py-2 border text-center">Số HĐ</th>
                        <th class="px-4 py-2 border text-left">Người quản lý</th>
                        <th class="px-4 py-2 border text-center">Số lần thanh lý</th>
                        <th class="px-4 py-2 border text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Không có vật tư nào</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr class="master-row" data-vattu-stt="<?php echo $item['stt']; ?>" 
                        data-mavattu="<?php echo htmlspecialchars($item['mavattu']); ?>"
                        data-tenkyhieu="<?php echo htmlspecialchars($item['ten_tiengviet'] ?? $item['ten_tienganh'] ?? ''); ?>">
                        <td class="px-4 py-2 border">
                            <a href="vattuthanhly.php?action=view&id=<?php echo $item['stt']; ?>" class="text-decoration-none">
                                <code class="rounded font-semibold <?php echo htmlspecialchars($item['phanloai_mau_sac'] ?? 'bg-blue-100 text-blue-800'); ?>" style="font-size: 14px; padding: 6px 12px; display: inline-block; min-width: 140px; text-align: center; line-height: 1.4; cursor: pointer;">
                                    <?php echo htmlspecialchars($item['mavattu']); ?>
                                </code>
                            </a>
                            <?php if (!empty($item['ten_tiengviet'])): ?>
                                <div class="text-sm text-red-600 font-semibold mt-1">
                                    <?php echo htmlspecialchars(mb_substr($item['ten_tiengviet'], 0, 50)) . (mb_strlen($item['ten_tiengviet']) > 50 ? '...' : ''); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2 border text-center">
                            <?php if (!empty($item['so_serial'])): ?>
                                <span class="bg-gray-100 px-2 py-1 rounded text-xs font-mono">
                                    <?php echo htmlspecialchars($item['so_serial']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2 border text-center" style="display: none;">
                            <?php if (!empty($item['ten_phanloai'])): ?>
                                <span class="px-2 py-1 rounded text-xs font-semibold <?php echo htmlspecialchars($item['phanloai_mau_sac'] ?? 'bg-gray-100 text-gray-800'); ?>">
                                    <?php echo htmlspecialchars($item['ten_phanloai']); ?>
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-800">
                                    Chưa phân loại
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2 border">
                            <?php if (!empty($item['ten_tienganh'])): ?>
                                <div class="text-sm"><strong>EN:</strong> <?php echo htmlspecialchars(mb_substr($item['ten_tienganh'], 0, 40)) . (mb_strlen($item['ten_tienganh']) > 40 ? '...' : ''); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['ten_tiengnga'])): ?>
                                <div class="text-sm text-blue-700"><strong>RU:</strong> <?php echo htmlspecialchars(mb_substr($item['ten_tiengnga'], 0, 40)) . (mb_strlen($item['ten_tiengnga']) > 40 ? '...' : ''); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['ten_tiengviet'])): ?>
                                <div class="text-sm text-green-700"><strong>VN:</strong> <?php echo htmlspecialchars(mb_substr($item['ten_tiengviet'], 0, 40)) . (mb_strlen($item['ten_tiengviet']) > 40 ? '...' : ''); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2 border text-center">
                            <?php echo htmlspecialchars($item['dvt_tiengviet'] ?? $item['dvt_tiengnga'] ?? '-'); ?>
                        </td>
                        <td class="px-4 py-2 border text-right font-semibold">
                            <?php echo number_format($item['soluong_conlai'] ?? 0, 0); ?>
                        </td>
                        <td class="px-4 py-2 border text-right">
                            <?php echo $item['dongia'] ? number_format($item['dongia'], 0) : '-'; ?>
                        </td>
                        <td class="px-4 py-2 border text-center">
                            <?php echo $item['ngaynhan'] ? date('d/m/Y', strtotime($item['ngaynhan'])) : '-'; ?>
                        </td>
                        <td class="px-4 py-2 border text-center">
                            <?php echo !empty($item['sohd']) ? htmlspecialchars($item['sohd']) : '-'; ?>
                        </td>
                        <td class="px-4 py-2 border">
                            <?php echo !empty($item['nguoiquanly']) ? htmlspecialchars($item['nguoiquanly']) : '-'; ?>
                        </td>
                        <td class="px-4 py-2 border text-center">
                            <span class="badge badge-<?php echo ($item['so_lan_sudung'] ?? 0) > 0 ? 'success' : 'warning'; ?>">
                                <?php echo $item['so_lan_sudung'] ?? 0; ?> lần
                            </span>
                        </td>
                        <td class="px-4 py-2 border text-center">
                            <a href="vattuthanhly.php?action=view&id=<?php echo $item['stt']; ?>" 
                               class="text-blue-600 hover:text-blue-800 mx-1" title="Xem chi tiết" onclick="event.stopPropagation();">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (hasPermission('vattu.edit')): ?>
                            <a href="vattuthanhly.php?action=edit&id=<?php echo $item['stt']; ?>" 
                               class="text-green-600 hover:text-green-800 mx-1" title="Sửa" onclick="event.stopPropagation();">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('vattu.delete')): ?>
                            <form method="POST" action="vattuthanhly.php?action=delete" 
                                  onsubmit="return handleDeleteVatTu(event);" 
                                  class="inline">
                                <input type="hidden" name="id" value="<?php echo $item['stt']; ?>">
                                <button type="submit" class="delete-vattu-btn text-red-600 hover:text-red-800 mx-1" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mt-6 flex justify-center">
        <nav class="flex space-x-2">
            <?php
            $queryParams = $_GET;
            
            if ($page > 1):
                $queryParams['page'] = $page - 1;
                $url = 'vattuthanhly.php?' . http_build_query($queryParams);
            ?>
                <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++):
                $queryParams['page'] = $i;
                $url = 'vattuthanhly.php?' . http_build_query($queryParams);
                $active = ($page === $i);
            ?>
                <a href="<?php echo $url; ?>" 
                   class="px-3 py-2 rounded <?php echo $active ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages):
                $queryParams['page'] = $page + 1;
                $url = 'vattuthanhly.php?' . http_build_query($queryParams);
            ?>
                <a href="<?php echo $url; ?>" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
// Danh sách đơn vị
const donViList = <?php echo json_encode($donViList ?? [], JSON_UNESCAPED_UNICODE); ?>;

// Handle delete vat tu with loading indicator
function handleDeleteVatTu(event) {
    event.stopPropagation();
    
    if (!confirm('Bạn có chắc muốn xóa vật tư này?')) {
        event.preventDefault();
        return false;
    }
    
    const form = event.target;
    const button = form.querySelector('button[type="submit"]');
    
    if (button) {
        // Show loading
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }
    
    // The form will submit naturally, but we've shown the loading state
    return true;
}

// Click vào master row để toggle detail
document.querySelectorAll('.master-row').forEach(row => {
    row.addEventListener('click', function(e) {
        // Prevent toggle when clicking on action buttons
        if (e.target.closest('a, button, form')) {
            return;
        }
        
        const vattuStt = this.getAttribute('data-vattu-stt');
        const mavattu = this.getAttribute('data-mavattu');
        const tenkyhieu = this.getAttribute('data-tenkyhieu');
        
        // Check if detail row already exists
        const existingDetailRow = document.getElementById(`detail-row-${vattuStt}`);
        
        if (existingDetailRow) {
            // Toggle existing detail
            const isActive = existingDetailRow.classList.contains('active');
            if (isActive) {
                existingDetailRow.classList.remove('active');
                this.classList.remove('active');
            } else {
                existingDetailRow.classList.add('active');
                this.classList.add('active');
            }
        } else {
            // Create new detail row
            this.classList.add('active');
            createDetailRow(this, vattuStt, mavattu, tenkyhieu);
        }
    });
});

function createDetailRow(masterRow, vattuStt, mavattu, tenkyhieu) {
    // Get available quantity from master row (column 6: SL còn lại)
    const qtyCell = masterRow.querySelector('td:nth-child(6)');
    const availableQty = qtyCell ? qtyCell.textContent.trim().replace(/,/g, '') : '0';
    
    // Create detail row
    const detailRow = document.createElement('tr');
    detailRow.id = `detail-row-${vattuStt}`;
    detailRow.className = 'detail-row active';
    detailRow.innerHTML = `
        <td colspan="11">
            <div class="detail-content">

                <div class="bg-white rounded-lg p-4 shadow">
                    
                    <!-- Form thêm chi tiết -->
                    <div id="addForm_${vattuStt}" style="display: none;" class="mb-4 p-4 bg-gray-50 rounded border">
                        <h5 class="font-semibold mb-3">Thêm chi tiết sử dụng mới</h5>
                        <div class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded">
                            <span class="font-semibold text-blue-800">Số lượng còn lại: </span>
                            <span id="availableQty_${vattuStt}" class="text-xl font-bold text-blue-600">${availableQty}</span>
                        </div>
                        <form id="chiTietForm_${vattuStt}" class="grid grid-cols-2 gap-3">
                            <input type="hidden" name="vattu_stt" value="${vattuStt}">
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">Người sử dụng</label>
                                <input type="text" name="nguoisudung" class="w-full border rounded px-3 py-2" required style="color: #000; font-weight: normal;">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">Ngày SD/Nhận</label>
                                <input type="date" name="ngaysd_nhan" value="${new Date().toISOString().split('T')[0]}" class="w-full border rounded px-3 py-2" required style="color: #000; font-weight: normal;">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">Số lượng thanh lý <span class="text-red-600">*</span></label>
                                <input type="number" step="0.01" name="soluong" max="${availableQty}" 
                                       class="w-full border rounded px-3 py-2" required
                                       onchange="validateQuantity_${vattuStt}(this)" style="color: #000; font-weight: normal;">
                                <small class="text-gray-500">Tối đa: ${availableQty}</small>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">Bộ phận</label>
                                <select name="bophan" class="w-full border rounded px-3 py-2" style="color: #000; font-weight: normal;">
                                    <option value="">-- Chọn bộ phận --</option>
                                    ${donViList.map(dv => `<option value="${dv.madv}">${dv.tendv}</option>`).join('')}
                                </select>
                            </div>
                            
                            <div class="col-span-2">
                                <label class="block text-sm font-medium mb-1">Mục đích sử dụng</label>
                                <textarea name="mucdich_sudung" class="w-full border rounded px-3 py-2" rows="2" style="color: #000; font-weight: normal;"></textarea>
                            </div>
                            
                            <div class="col-span-2">
                                <label class="block text-sm font-medium mb-1">Ghi chú</label>
                                <input type="text" name="ghichu" class="w-full border rounded px-3 py-2" style="color: #000; font-weight: normal;">
                            </div>
                            
                            <div class="col-span-2 flex gap-2">
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                                    <i class="fas fa-save mr-1"></i> Lưu
                                </button>
                                <button type="button" onclick="hideAddForm_${vattuStt}()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                    Hủy
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Bảng chi tiết -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 border text-left" style="background: #fed7aa !important; color: #7c2d12 !important; font-weight: 700;">Người sử dụng</th>
                                    <th class="px-3 py-2 border text-center">Ngày SD/Nhận</th>
                                    <th class="px-3 py-2 border text-right">Số lượng</th>
                                    <th class="px-3 py-2 border text-left">Bộ phận</th>
                                    <th class="px-3 py-2 border text-left">Mục đích</th>
                                    <th class="px-3 py-2 border text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="detailTableBody_${vattuStt}">
                                <tr>
                                    <td colspan="6" class="px-4 py-4 text-center text-gray-400">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>Đang tải...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (hasPermission('vattu.edit')): ?>
                    <div class="mt-3 text-right">
                        <button onclick="showAddForm_${vattuStt}()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                            <i class="fas fa-plus mr-1"></i> Thêm chi tiết sử dụng
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </td>
    `;
    
    // Insert after master row
    masterRow.parentNode.insertBefore(detailRow, masterRow.nextSibling);
    
    // Create validation function for this form
    window[`validateQuantity_${vattuStt}`] = function(input) {
        const maxQty = parseFloat(availableQty);
        const value = parseFloat(input.value);
        if (value > maxQty) {
            alert(`Số lượng thanh lý không được vượt quá ${maxQty}`);
            input.value = maxQty;
        }
        if (value <= 0) {
            alert('Số lượng phải lớn hơn 0');
            input.value = '';
        }
    };
    
    // Create dynamic functions for this specific detail
    window[`showAddForm_${vattuStt}`] = function() {
        document.getElementById(`addForm_${vattuStt}`).style.display = 'block';
    };
    
    window[`hideAddForm_${vattuStt}`] = function() {
        document.getElementById(`addForm_${vattuStt}`).style.display = 'none';
        document.getElementById(`chiTietForm_${vattuStt}`).reset();
    };
    
    // Add form submit handler
    document.getElementById(`chiTietForm_${vattuStt}`).addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Disable button and show loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...';
        
        fetch('vattuthanhly.php?action=addChiTiet', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Thêm chi tiết thành công!');
                window[`hideAddForm_${vattuStt}`]();
                loadDetail(vattuStt);
                
                // Cập nhật số lượng còn lại trên master row
                if (data.soluong_conlai_moi !== undefined) {
                    const masterRow = document.querySelector(`tr.master-row[data-vattu-stt="${vattuStt}"]`);
                    const qtyCell = masterRow.querySelector('td:nth-child(6)');
                    if (qtyCell) {
                        const formatted = Math.round(parseFloat(data.soluong_conlai_moi)).toLocaleString('en-US');
                        qtyCell.innerHTML = `<span class="font-semibold">${formatted}</span>`;
                    }
                    
                    // Cập nhật số lần thanh lý (cột 11)
                    if (data.so_lan_sudung !== undefined) {
                        const countCell = masterRow.querySelector('td:nth-child(11)');
                        if (countCell) {
                            const badgeClass = data.so_lan_sudung > 0 ? 'badge-success' : 'badge-warning';
                            countCell.innerHTML = `<span class="badge ${badgeClass}">${data.so_lan_sudung} lần</span>`;
                        }
                    }
                    
                    // Cập nhật số lượng còn lại trong form
                    const qtyDisplay = document.getElementById(`availableQty_${vattuStt}`);
                    if (qtyDisplay) {
                        qtyDisplay.textContent = Math.round(data.soluong_conlai_moi);
                    }
                    const qtyInput = document.querySelector(`#chiTietForm_${vattuStt} input[name="soluong"]`);
                    if (qtyInput) {
                        qtyInput.max = data.soluong_conlai_moi;
                        const smallEl = qtyInput.parentElement.querySelector('small');
                        if (smallEl) {
                            smallEl.textContent = `Tối đa: ${Math.round(data.soluong_conlai_moi)}`;
                        }
                    }
                }
            } else {
                alert('Lỗi: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra');
        })
        .finally(() => {
            // Re-enable button and restore text
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
    
    // Load detail data
    loadDetail(vattuStt);
}

function loadDetail(vattuStt) {
    fetch(`vattuthanhly.php?action=getChiTiet&vattu_stt=${vattuStt}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderDetailTable(vattuStt, data.data);
            } else {
                alert('Lỗi: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi tải chi tiết');
        });
}

function renderDetailTable(vattuStt, items) {
    const tbody = document.getElementById(`detailTableBody_${vattuStt}`);
    
    if (!tbody) return;
    
    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-4 text-center text-gray-400">Chưa có chi tiết sử dụng</td></tr>';
        return;
    }
    
    tbody.innerHTML = items.map(item => `
        <tr id="detailRow_${item.id}">
            <td class="px-3 py-2 border" style="color: #000; font-weight: normal;">${item.nguoisudung || '-'}</td>
            <td class="px-3 py-2 border text-center" style="color: #000; font-weight: normal;">${item.ngaysd_nhan ? new Date(item.ngaysd_nhan).toLocaleDateString('vi-VN') : '-'}</td>
            <td class="px-3 py-2 border text-right font-semibold" style="color: #000;">${Math.round(parseFloat(item.soluong || 0))}</td>
            <td class="px-3 py-2 border" style="color: #000; font-weight: normal;">${item.bophan || '-'}</td>
            <td class="px-3 py-2 border" style="color: #000; font-weight: normal;">${item.mucdich_sudung || '-'}</td>
            <td class="px-3 py-2 border text-center">
                <?php if (hasPermission('vattu.edit')): ?>
                <button onclick="editChiTiet(${item.id}, ${vattuStt}, ${JSON.stringify(item).replace(/"/g, '&quot;')})" class="text-blue-600 hover:text-blue-800 mr-2" title="Sửa">
                    <i class="fas fa-edit"></i>
                </button>
                <?php endif; ?>
                <?php if (hasPermission('vattu.delete')): ?>
                <button onclick="deleteChiTiet(${item.id}, ${vattuStt})" class="text-red-600 hover:text-red-800" title="Xóa">
                    <i class="fas fa-trash"></i>
                </button>
                <?php endif; ?>
            </td>
        </tr>
        <tr id="editRow_${item.id}" style="display: none;">
            <td colspan="6" class="px-3 py-2 border bg-yellow-50">
                <form id="editForm_${item.id}" class="grid grid-cols-2 gap-3">
                    <input type="hidden" name="id" value="${item.id}">
                    <input type="hidden" name="vattu_stt" value="${vattuStt}">
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Người sử dụng</label>
                        <input type="text" name="nguoisudung" value="${item.nguoisudung || ''}" class="w-full border rounded px-2 py-1 text-sm" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Ngày SD/Nhận</label>
                        <input type="date" name="ngaysd_nhan" value="${item.ngaysd_nhan || ''}" class="w-full border rounded px-2 py-1 text-sm" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Số lượng</label>
                        <input type="number" step="0.01" name="soluong" value="${item.soluong || ''}" class="w-full border rounded px-2 py-1 text-sm" required readonly>
                        <small class="text-gray-500">Không cho phép sửa số lượng</small>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Bộ phận</label>
                        <select name="bophan" class="w-full border rounded px-2 py-1 text-sm">
                            <option value="">-- Chọn bộ phận --</option>
                            ${donViList.map(dv => `<option value="${dv.madv}" ${(item.bophan === dv.madv) ? 'selected' : ''}>${dv.tendv}</option>`).join('')}
                        </select>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium mb-1">Mục đích sử dụng</label>
                        <textarea name="mucdich_sudung" class="w-full border rounded px-2 py-1 text-sm" rows="2">${item.mucdich_sudung || ''}</textarea>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium mb-1">Ghi chú</label>
                        <input type="text" name="ghichu" value="${item.ghichu || ''}" class="w-full border rounded px-2 py-1 text-sm">
                    </div>
                    
                    <div class="col-span-2 flex gap-2">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                            <i class="fas fa-save mr-1"></i> Lưu
                        </button>
                        <button type="button" onclick="cancelEdit(${item.id})" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                            Hủy
                        </button>
                    </div>
                </form>
            </td>
        </tr>
    `).join('');
    
    // Add event listeners for all edit forms
    items.forEach(item => {
        const form = document.getElementById(`editForm_${item.id}`);
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                saveEditChiTiet(item.id, vattuStt);
            });
        }
    });
}

function editChiTiet(id, vattuStt, item) {
    // Hide detail row and show edit row
    document.getElementById(`detailRow_${id}`).style.display = 'none';
    document.getElementById(`editRow_${id}`).style.display = 'table-row';
}

function cancelEdit(id) {
    // Show detail row and hide edit row
    document.getElementById(`detailRow_${id}`).style.display = 'table-row';
    document.getElementById(`editRow_${id}`).style.display = 'none';
}

function saveEditChiTiet(id, vattuStt) {
    const form = document.getElementById(`editForm_${id}`);
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang lưu...';
    
    fetch('vattuthanhly.php?action=editChiTiet', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Cập nhật chi tiết thành công!');
            cancelEdit(id);
            loadDetail(vattuStt);
        } else {
            alert('Lỗi: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function deleteChiTiet(id, vattuStt) {
    if (!confirm('Bạn có chắc muốn xóa chi tiết này?')) return;
    
    // Show loading indicator on the delete button
    const deleteBtn = event.target.closest('button');
    const originalHTML = deleteBtn.innerHTML;
    deleteBtn.disabled = true;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('vattuthanhly.php?action=deleteChiTiet', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Xóa thành công!');
            loadDetail(vattuStt);
            
            // Cập nhật lại số lượng còn lại sau khi xóa (cộng lại)
            if (data.soluong_conlai_moi !== undefined) {
                const masterRow = document.querySelector(`tr.master-row[data-vattu-stt="${vattuStt}"]`);
                const qtyCell = masterRow.querySelector('td:nth-child(6)');
                if (qtyCell) {
                    const formatted = Math.round(parseFloat(data.soluong_conlai_moi)).toLocaleString('en-US');
                    qtyCell.innerHTML = `<span class="font-semibold">${formatted}</span>`;
                }
                
                // Cập nhật số lần thanh lý (cột 11)
                if (data.so_lan_sudung !== undefined) {
                    const countCell = masterRow.querySelector('td:nth-child(11)');
                    if (countCell) {
                        const badgeClass = data.so_lan_sudung > 0 ? 'badge-success' : 'badge-warning';
                        countCell.innerHTML = `<span class="badge ${badgeClass}">${data.so_lan_sudung} lần</span>`;
                    }
                }
                
                // Cập nhật trong form nếu đang hiển thị
                const qtyDisplay = document.getElementById(`availableQty_${vattuStt}`);
                if (qtyDisplay) {
                    qtyDisplay.textContent = Math.round(data.soluong_conlai_moi);
                }
                const qtyInput = document.querySelector(`#chiTietForm_${vattuStt} input[name="soluong"]`);
                if (qtyInput) {
                    qtyInput.max = data.soluong_conlai_moi;
                    const smallEl = qtyInput.parentElement.querySelector('small');
                    if (smallEl) {
                        smallEl.textContent = `Tối đa: ${Math.round(data.soluong_conlai_moi)}`;
                    }
                }
            }
        } else {
            alert('Lỗi: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra');
    })
    .finally(() => {
        // Restore button state
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = originalHTML;
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
