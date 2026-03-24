<?php
$title = 'Chọn Vật Tư Đặt Hàng';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-blue-600 text-white px-6 py-4">
            <h5 class="text-xl font-bold mb-0">
                <i class="fas fa-shopping-cart mr-2"></i> Chọn Vật Tư Để Đặt Hàng
            </h5>
        </div>
        <div class="p-6">
            <form id="formChonDatHang">
                <!-- Bộ lọc -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
                    <div class="md:col-span-4">
                        <input type="text" id="searchInput" class="w-full border rounded px-3 py-2" placeholder="🔍 Tìm kiếm...">
                    </div>
                    <div class="md:col-span-3">
                        <select id="filterPhanLoai" class="w-full border rounded px-3 py-2">
                            <option value="">-- Tất cả phân loại --</option>
                            <?php foreach ($phanLoaiList as $pl): ?>
                                <option value="<?= $pl['id'] ?>"><?= htmlspecialchars($pl['ten_phanloai']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <button type="button" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-medium" id="btnTaoPhieu">
                            <i class="fas fa-file-invoice mr-1"></i> Tạo Phiếu (<span id="selectedCount">0</span>)
                        </button>
                    </div>
                    <div class="md:col-span-2">
                        <button type="button" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded" id="btnSelectAll">
                            <i class="fas fa-check-square mr-1"></i> Chọn tất cả
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border" id="tableVatTu">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-2 py-2 text-center" style="width: 50px;">
                                    <input type="checkbox" id="checkAll" class="w-4 h-4">
                                </th>
                                <th class="border px-2 py-2" style="width: 60px;">STT</th>
                                <th class="border px-2 py-2" style="width: 120px;">Mã VT</th>
                                <th class="border px-2 py-2">Tên tiếng Anh</th>
                                <th class="border px-2 py-2">Tên tiếng Nga</th>
                                <th class="border px-2 py-2">Tên tiếng Việt</th>
                                <th class="border px-2 py-2 text-center" style="width: 100px;">Đơn vị</th>
                                <th class="border px-2 py-2 text-center" style="width: 100px;">Tồn kho</th>
                                <th class="border px-2 py-2" style="width: 100px;">Phân loại</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyVatTu">
                            <?php foreach ($items as $index => $item): ?>
                            <tr class="item-row hover:bg-gray-50" 
                                data-id="<?= $item['stt'] ?>"
                                data-phanloai="<?= $item['phanloai_id'] ?? '' ?>"
                                data-search="<?= htmlspecialchars(strtolower(
                                    ($item['mavattu'] ?? '') . ' ' .
                                    ($item['ten_tienganh'] ?? '') . ' ' .
                                    ($item['ten_tiengnga'] ?? '') . ' ' .
                                    ($item['ten_tiengviet'] ?? '')
                                )) ?>">
                                <td class="border px-2 py-2 text-center">
                                    <input type="checkbox" class="item-checkbox w-4 h-4" value="<?= $item['stt'] ?>">
                                </td>
                                <td class="border px-2 py-2"><?= $item['stt'] ?></td>
                                <td class="border px-2 py-2"><?= htmlspecialchars($item['mavattu'] ?? '') ?></td>
                                <td class="border px-2 py-2 text-sm"><?= htmlspecialchars($item['ten_tienganh'] ?? '') ?></td>
                                <td class="border px-2 py-2 text-sm"><?= htmlspecialchars($item['ten_tiengnga'] ?? '') ?></td>
                                <td class="border px-2 py-2 text-sm"><?= htmlspecialchars($item['ten_tiengviet'] ?? '') ?></td>
                                <td class="border px-2 py-2 text-center"><?= htmlspecialchars($item['dvt_tiengviet'] ?? $item['dvt_tiengnga'] ?? '') ?></td>
                                <td class="border px-2 py-2 text-center">
                                    <?= $item['soluong_conlai'] > 0 
                                        ? '<span class="inline-block px-2 py-1 text-xs font-semibold text-white bg-green-500 rounded">' . number_format($item['soluong_conlai'], 0) . '</span>'
                                        : '<span class="inline-block px-2 py-1 text-xs font-semibold text-white bg-red-500 rounded">0</span>' 
                                    ?>
                                </td>
                                <td class="border px-2 py-2">
                                    <?php if (!empty($item['ten_phanloai'])): ?>
                                        <span class="inline-block px-2 py-1 text-xs font-semibold text-white rounded" style="background-color: <?= $item['mau_sac'] ?? '#6c757d' ?>">
                                            <?= htmlspecialchars($item['ten_phanloai']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Check all
    $('#checkAll').on('change', function() {
        $('.item-checkbox:visible').prop('checked', this.checked);
        updateSelectedCount();
    });
    
    // Individual checkbox
    $(document).on('change', '.item-checkbox', function() {
        updateSelectedCount();
    });
    
    // Select all visible
    $('#btnSelectAll').on('click', function() {
        $('.item-checkbox:visible').prop('checked', true);
        updateSelectedCount();
    });
    
    // Update count
    function updateSelectedCount() {
        const count = $('.item-checkbox:checked').length;
        $('#selectedCount').text(count);
    }
    
    // Search filter
    $('#searchInput').on('keyup', function() {
        filterTable();
    });
    
    $('#filterPhanLoai').on('change', function() {
        filterTable();
    });
    
    function filterTable() {
        const search = $('#searchInput').val().toLowerCase();
        const phanloai = $('#filterPhanLoai').val();
        
        $('.item-row').each(function() {
            const $row = $(this);
            const searchText = $row.data('search');
            const rowPhanLoai = $row.data('phanloai').toString();
            
            let show = true;
            
            if (search && searchText.indexOf(search) === -1) {
                show = false;
            }
            
            if (phanloai && rowPhanLoai !== phanloai) {
                show = false;
            }
            
            $row.toggle(show);
        });
    }
    
    // Create order
    $('#btnTaoPhieu').on('click', function() {
        const selected = [];
        $('.item-checkbox:checked').each(function() {
            selected.push($(this).val());
        });
        
        if (selected.length === 0) {
            alert('Vui lòng chọn ít nhất 1 vật tư!');
            return;
        }
        
        window.location.href = '/iso2/vattuthanhly.php?action=taophieudathang&ids=' + selected.join(',');
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
        
        window.location.href = '/iso2/vattuthanhly.php?action=taophieudathang&ids=' + selected.join(',');
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
