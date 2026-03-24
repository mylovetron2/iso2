<?php
declare(strict_types=1);
include __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-arrow-up mr-2 text-blue-500"></i>
            Giao Thiết Bị Đi Kiểm Định
        </h1>
        <a href="giaonhanthietbi.php" 
           class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
    </div>

    <!-- Thông báo -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="giaonhanthietbi.php?action=store_giao_di" class="space-y-6" id="formGiaoDi">
            
            <!-- Thông tin người giao (Đội) -->
            <div class="border-b pb-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-user-tie mr-2"></i>Thông Tin Bên Giao (Đội)
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Đơn vị giao <span class="text-red-500">*</span>
                        </label>
                        <select name="donvi_giao" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Chọn đơn vị --</option>
                            <?php foreach ($donviList as $dv): ?>
                                <option value="<?= htmlspecialchars($dv['ma_don_vi']) ?>">
                                    <?= htmlspecialchars($dv['ten_don_vi']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Người giao <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nguoi_giao" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Họ tên người giao">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ngày giao <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="ngay_giao" required
                               value="<?= date('Y-m-d') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Thông tin người nhận (Mình) -->
            <div class="border-b pb-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-user-check mr-2"></i>Thông Tin Bên Nhận (SCTBDVL)
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Người nhận <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nguoi_nhan" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Họ tên người nhận (bên mình)">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Đơn vị nhận
                        </label>
                        <input type="text" readonly value="SCTBDVL"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600">
                    </div>
                </div>
            </div>

            <!-- Danh sách thiết bị -->
            <div class="border-b pb-4 mb-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-700">
                        <i class="fas fa-cogs mr-2"></i>Danh Sách Thiết Bị
                    </h3>
                    <button type="button" onclick="addThietBiRow()" 
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md transition duration-200">
                        <i class="fas fa-plus mr-2"></i>Thêm thiết bị
                    </button>
                </div>
                
                <div id="thietbiContainer" class="space-y-3">
                    <!-- Row template sẽ được thêm bằng JS -->
                     <div class="thietbi-row grid grid-cols-12 gap-2 items-start p-3 bg-gray-50 rounded">
                        <div class="col-span-5">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Thiết bị *</label>
                            <select name="thietbi_id[]" required 
                                    class="w-full px-2 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Chọn thiết bị --</option>
                                <?php foreach ($thietbiList as $tb): ?>
                                    <option value="<?= $tb['id'] ?>">
                                        <?= htmlspecialchars($tb['ten_thiet_bi']) ?> 
                                        <?php if (!empty($tb['ky_ma_hieu'])): ?>
                                            - <?= htmlspecialchars($tb['ky_ma_hieu']) ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-span-3">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Tình trạng</label>
                            <input type="text" name="tinhtrang[]" 
                                   class="w-full px-2 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Tình trạng thiết bị">
                        </div>
                        
                        <div class="col-span-3">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Ghi chú</label>
                            <input type="text" name="ghichu_thietbi[]" 
                                   class="w-full px-2 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Ghi chú">
                        </div>
                        
                        <div class="col-span-1 flex items-end">
                            <button type="button" onclick="removeThietBiRow(this)" 
                                    class="w-full py-2 bg-red-500 hover:bg-red-600 text-white rounded transition duration-200">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ghi chú chung -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Ghi chú chung
                </label>
                <textarea name="ghichu" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Ghi chú chung cho phiếu giao nhận"></textarea>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 pt-4">
                <button type="submit" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-save mr-2"></i>Lưu phiếu
                </button>
                <a href="giaonhanthietbi.php" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-times mr-2"></i>Hủy
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Template cho row thiết bị mới
function getThietBiRowTemplate() {
    return `
        <div class="thietbi-row grid grid-cols-12 gap-2 items-start p-3 bg-gray-50 rounded">
            <div class="col-span-5">
                <label class="block text-xs font-medium text-gray-700 mb-1">Thiết bị *</label>
                <select name="thietbi_id[]" required 
                        class="w-full px-2 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Chọn thiết bị --</option>
                    <?php foreach ($thietbiList as $tb): ?>
                        <option value="<?= $tb['id'] ?>">
                            <?= htmlspecialchars($tb['ten_thiet_bi']) ?> 
                            <?php if (!empty($tb['ky_ma_hieu'])): ?>
                                - <?= htmlspecialchars($tb['ky_ma_hieu']) ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-span-3">
                <label class="block text-xs font-medium text-gray-700 mb-1">Tình trạng</label>
                <input type="text" name="tinhtrang[]" 
                       class="w-full px-2 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Tình trạng thiết bị">
            </div>
            
            <div class="col-span-3">
                <label class="block text-xs font-medium text-gray-700 mb-1">Ghi chú</label>
                <input type="text" name="ghichu_thietbi[]" 
                       class="w-full px-2 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Ghi chú">
            </div>
            
            <div class="col-span-1 flex items-end">
                <button type="button" onclick="removeThietBiRow(this)" 
                        class="w-full py-2 bg-red-500 hover:bg-red-600 text-white rounded transition duration-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
}

function addThietBiRow() {
    const container = document.getElementById('thietbiContainer');
    const template = document.createElement('div');
    template.innerHTML = getThietBiRowTemplate();
    container.appendChild(template.firstElementChild);
}

function removeThietBiRow(button) {
    const container = document.getElementById('thietbiContainer');
    const rows = container.querySelectorAll('.thietbi-row');
    
    // Phải có ít nhất 1 row
    if (rows.length <= 1) {
        alert('Phải có ít nhất 1 thiết bị!');
        return;
    }
    
    button.closest('.thietbi-row').remove();
}

// Validate form
document.getElementById('formGiaoDi').addEventListener('submit', function(e) {
    const thietbiSelects = document.querySelectorAll('select[name="thietbi_id[]"]');
    const selectedIds = Array.from(thietbiSelects).map(s => s.value).filter(v => v);
    
    if (selectedIds.length === 0) {
        e.preventDefault();
        alert('Vui lòng chọn ít nhất 1 thiết bị!');
        return false;
    }
    
    // Check duplicates
    const uniqueIds = new Set(selectedIds);
    if (uniqueIds.size !== selectedIds.length) {
        e.preventDefault();
        alert('Có thiết bị bị trùng! Vui lòng kiểm tra lại.');
        return false;
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
