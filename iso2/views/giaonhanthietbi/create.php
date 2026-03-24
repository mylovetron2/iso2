<?php
/**
 * BƯỚC 1: Form nhận thiết bị từ đội (trạng thái: da_nhan)
 */
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-sign-in-alt mr-2 text-blue-500"></i>
            Tạo Phiếu Nhận Thiết Bị Từ Đội
        </h1>
        <a href="giaonhanthietbi.php" 
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
    <form method="POST" action="giaonhanthietbi.php?action=store" id="formNhanTuDoi">
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
                                <option value="<?= htmlspecialchars($dv['madv']) ?>">
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
                               value="<?= date('Y-m-d') ?>"
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
                              placeholder="Nhập ghi chú (nếu có)"></textarea>
                </div>
            </div>

            <!-- Section 2: Danh sách thiết bị -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">
                    <i class="fas fa-list mr-2 text-green-500"></i>
                    Danh Sách Thiết Bị <span class="text-red-500">*</span>
                </h2>

                <div class="mb-3">
                    <button type="button" 
                            onclick="addThietBiRow()"
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-plus mr-2"></i>Thêm thiết bị
                    </button>
                </div>

                <!-- Container cho danh sách thiết bị -->
                <div id="thietbi-container">
                    <!-- Row đầu tiên (template) -->
                    <div class="thietbi-row grid grid-cols-12 gap-2 mb-2 items-start">
                        <!-- Thiết bị (5 cols) -->
                        <div class="col-span-5">
                            <select name="thietbi_id[]" 
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Chọn thiết bị --</option>
                                <?php foreach ($thietbiList as $tb): ?>
                                    <option value="<?= $tb['id'] ?>" 
                                            data-ten="<?= htmlspecialchars($tb['ten_thiet_bi']) ?>"
                                            data-somay="<?= htmlspecialchars($tb['ky_ma_hieu']) ?>">
                                        <?= htmlspecialchars($tb['ten_thiet_bi']) ?> 
                                        [<?= htmlspecialchars($tb['ky_ma_hieu']) ?>]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tình trạng (3 cols) -->
                        <div class="col-span-3">
                            <input type="text" 
                                   name="tinhtrang[]" 
                                   placeholder="Tình trạng khi nhận"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Ghi chú (3 cols) -->
                        <div class="col-span-3">
                            <input type="text" 
                                   name="ghichu_thietbi[]" 
                                   placeholder="Ghi chú"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Nút xóa (1 col) -->
                        <div class="col-span-1">
                            <button type="button" 
                                    onclick="removeThietBiRow(this)"
                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg transition duration-200 w-full">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="text-sm text-gray-600 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Tối thiểu 1 thiết bị. Click "Thêm thiết bị" để thêm nhiều thiết bị.
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-2">
                <a href="giaonhanthietbi.php" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-times mr-2"></i>Hủy
                </a>
                <button type="submit" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-save mr-2"></i>Tạo Phiếu
                </button>
            </div>

        </div>
    </form>
</div>

<script>
/**
 * Thêm dòng thiết bị mới
 */
function addThietBiRow() {
    const container = document.getElementById('thietbi-container');
    const firstRow = container.querySelector('.thietbi-row');
    const newRow = firstRow.cloneNode(true);
    
    // Reset values
    newRow.querySelectorAll('select, input').forEach(el => {
        el.value = '';
    });
    
    container.appendChild(newRow);
}

/**
 * Xóa dòng thiết bị
 */
function removeThietBiRow(button) {
    const container = document.getElementById('thietbi-container');
    const rows = container.querySelectorAll('.thietbi-row');
    
    if (rows.length <= 1) {
        alert('Phải có ít nhất 1 thiết bị!');
        return;
    }
    
    button.closest('.thietbi-row').remove();
}

/**
 * Validate form before submit
 */
document.getElementById('formNhanTuDoi').addEventListener('submit', function(e) {
    const thietbiSelects = document.querySelectorAll('select[name="thietbi_id[]"]');
    const selectedIds = [];
    
    // Kiểm tra trùng lặp
    for (let select of thietbiSelects) {
        const value = select.value;
        if (!value) {
            alert('Vui lòng chọn thiết bị cho tất cả các dòng!');
            e.preventDefault();
            return;
        }
        
        if (selectedIds.includes(value)) {
            alert('Có thiết bị bị trùng lặp! Vui lòng kiểm tra lại.');
            e.preventDefault();
            return;
        }
        
        selectedIds.push(value);
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
