<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Quản lý Vật Tư Thanh Lý';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<style>
.master-row {
    cursor: pointer;
    transition: all 0.2s;
}

.master-row:hover {
    background-color: #e3f2fd !important;
}

.master-row.active {
    background-color: #bbdefb !important;
    font-weight: 600;
}

.master-row td:first-child::before {
    content: '▶';
    display: inline-block;
    margin-right: 8px;
    transition: transform 0.3s;
    font-size: 10px;
    color: #666;
}

.master-row.active td:first-child::before {
    transform: rotate(90deg);
}

.detail-row {
    display: none;
    background-color: #f8f9fa;
}

.detail-row.active {
    display: table-row;
}

.detail-row td {
    padding: 0 !important;
    border: none !important;
}

.detail-content {
    padding: 1.5rem;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-left: 4px solid #4299e1;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
}

.badge-success { background-color: #d4edda; color: #155724; }
.badge-warning { background-color: #fff3cd; color: #856404; }
.badge-danger { background-color: #f8d7da; color: #721c24; }
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
        <div class="flex gap-2">
            <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                   placeholder="Tìm kiếm mã VT, tên, người quản lý..." 
                   class="flex-1 border rounded px-3 py-2">
            
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
            <?php endif; ?>
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
                        <th class="px-4 py-2 border text-left">STT</th>
                        <th class="px-4 py-2 border text-left">Mã VT</th>
                        <th class="px-4 py-2 border text-left">Tên (TA/Nga/Việt)</th>
                        <th class="px-4 py-2 border text-center">ĐVT</th>
                        <th class="px-4 py-2 border text-right">SL còn lại</th>
                        <th class="px-4 py-2 border text-right">Đơn giá</th>
                        <th class="px-4 py-2 border text-center">Ngày nhận</th>
                        <th class="px-4 py-2 border text-center">Số lần SD</th>
                        <th class="px-4 py-2 border text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Không có vật tư nào</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr class="master-row" data-vattu-stt="<?php echo $item['stt']; ?>" 
                        data-mavattu="<?php echo htmlspecialchars($item['mavattu']); ?>"
                        data-tenkyhieu="<?php echo htmlspecialchars($item['ten_tiengviet'] ?? $item['ten_tienganh'] ?? ''); ?>">
                        <td class="px-4 py-2 border"><?php echo $item['stt']; ?></td>
                        <td class="px-4 py-2 border">
                            <code class="bg-blue-100 px-2 py-1 rounded text-sm font-semibold">
                                <?php echo htmlspecialchars($item['mavattu']); ?>
                            </code>
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
                            <?php echo number_format($item['soluong_conlai'] ?? 0, 2); ?>
                        </td>
                        <td class="px-4 py-2 border text-right">
                            <?php echo $item['dongia'] ? number_format($item['dongia'], 0) : '-'; ?>
                        </td>
                        <td class="px-4 py-2 border text-center">
                            <?php echo $item['ngaynhan'] ? date('d/m/Y', strtotime($item['ngaynhan'])) : '-'; ?>
                        </td>
                        <td class="px-4 py-2 border text-center">
                            <span class="badge badge-<?php echo ($item['so_lan_sudung'] ?? 0) > 0 ? 'success' : 'warning'; ?>">
                                <?php echo $item['so_lan_sudung'] ?? 0; ?> lần
                            </span>
                        </td>
                        <td class="px-4 py-2 border text-center">
                            <?php if (hasPermission('vattu.edit')): ?>
                            <a href="vattuthanhly.php?action=edit&id=<?php echo $item['stt']; ?>" 
                               class="text-green-600 hover:text-green-800 mx-1" title="Sửa" onclick="event.stopPropagation();">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('vattu.delete')): ?>
                            <form method="POST" action="vattuthanhly.php?action=delete" 
                                  onsubmit="event.stopPropagation(); return confirm('Bạn có chắc muốn xóa vật tư này?');" 
                                  class="inline">
                                <input type="hidden" name="id" value="<?php echo $item['stt']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-800 mx-1" title="Xóa">
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
    // Create detail row
    const detailRow = document.createElement('tr');
    detailRow.id = `detail-row-${vattuStt}`;
    detailRow.className = 'detail-row active';
    detailRow.innerHTML = `
        <td colspan="9">
            <div class="detail-content">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-info-circle mr-2"></i>Chi tiết sử dụng: 
                        <span class="text-blue-600">${mavattu} - ${tenkyhieu}</span>
                    </h4>
                </div>
                
                <div class="bg-white rounded-lg p-4 shadow">
                    <?php if (hasPermission('vattu.edit')): ?>
                    <button onclick="showAddForm_${vattuStt}()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mb-3">
                        <i class="fas fa-plus mr-1"></i> Thêm chi tiết sử dụng
                    </button>
                    <?php endif; ?>
                    
                    <!-- Form thêm chi tiết -->
                    <div id="addForm_${vattuStt}" style="display: none;" class="mb-4 p-4 bg-gray-50 rounded border">
                        <h5 class="font-semibold mb-3">Thêm chi tiết sử dụng mới</h5>
                        <form id="chiTietForm_${vattuStt}" class="grid grid-cols-2 gap-3">
                            <input type="hidden" name="vattu_stt" value="${vattuStt}">
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">Người sử dụng</label>
                                <input type="text" name="nguoisudung" class="w-full border rounded px-3 py-2" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">Ngày SD/Nhận</label>
                                <input type="date" name="ngaysd_nhan" class="w-full border rounded px-3 py-2" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">Số lượng</label>
                                <input type="number" step="0.01" name="soluong" class="w-full border rounded px-3 py-2" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">Bộ phận</label>
                                <input type="text" name="bophan" class="w-full border rounded px-3 py-2">
                            </div>
                            
                            <div class="col-span-2">
                                <label class="block text-sm font-medium mb-1">Mục đích sử dụng</label>
                                <textarea name="mucdich_sudung" class="w-full border rounded px-3 py-2" rows="2"></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">Trạng thái</label>
                                <select name="trangthai" class="w-full border rounded px-3 py-2">
                                    <option value="dangdung">Đang dùng</option>
                                    <option value="dahoan">Đã hoàn</option>
                                    <option value="thanh_ly">Thanh lý</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">Ghi chú</label>
                                <input type="text" name="ghichu" class="w-full border rounded px-3 py-2">
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
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 border text-left">Người sử dụng</th>
                                    <th class="px-3 py-2 border text-center">Ngày SD/Nhận</th>
                                    <th class="px-3 py-2 border text-right">Số lượng</th>
                                    <th class="px-3 py-2 border text-left">Bộ phận</th>
                                    <th class="px-3 py-2 border text-left">Mục đích</th>
                                    <th class="px-3 py-2 border text-center">Trạng thái</th>
                                    <th class="px-3 py-2 border text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="detailTableBody_${vattuStt}">
                                <tr>
                                    <td colspan="7" class="px-4 py-4 text-center text-gray-400">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>Đang tải...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </td>
    `;
    
    // Insert after master row
    masterRow.parentNode.insertBefore(detailRow, masterRow.nextSibling);
    
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
            } else {
                alert('Lỗi: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra');
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
        tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-4 text-center text-gray-400">Chưa có chi tiết sử dụng</td></tr>';
        return;
    }
    
    const trangthaiMap = {
        'dangdung': '<span class="badge badge-success">Đang dùng</span>',
        'dahoan': '<span class="badge badge-warning">Đã hoàn</span>',
        'thanh_ly': '<span class="badge badge-danger">Thanh lý</span>'
    };
    
    tbody.innerHTML = items.map(item => `
        <tr>
            <td class="px-3 py-2 border">${item.nguoisudung || '-'}</td>
            <td class="px-3 py-2 border text-center">${item.ngaysd_nhan ? new Date(item.ngaysd_nhan).toLocaleDateString('vi-VN') : '-'}</td>
            <td class="px-3 py-2 border text-right font-semibold">${parseFloat(item.soluong || 0).toFixed(2)}</td>
            <td class="px-3 py-2 border">${item.bophan || '-'}</td>
            <td class="px-3 py-2 border">${item.mucdich_sudung || '-'}</td>
            <td class="px-3 py-2 border text-center">${trangthaiMap[item.trangthai] || item.trangthai}</td>
            <td class="px-3 py-2 border text-center">
                <?php if (hasPermission('vattu.delete')): ?>
                <button onclick="deleteChiTiet(${item.id}, ${vattuStt})" class="text-red-600 hover:text-red-800" title="Xóa">
                    <i class="fas fa-trash"></i>
                </button>
                <?php endif; ?>
            </td>
        </tr>
    `).join('');
}

function deleteChiTiet(id, vattuStt) {
    if (!confirm('Bạn có chắc muốn xóa chi tiết này?')) return;
    
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
        } else {
            alert('Lỗi: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra');
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
