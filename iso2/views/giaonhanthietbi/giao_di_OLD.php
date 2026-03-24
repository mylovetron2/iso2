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
        <form method="POST" action="giaonhanthietbi.php?action=store_giao_di" class="space-y-6">
            <!-- Thông tin thiết bị -->
            <div class="border-b pb-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-cogs mr-2"></i>Thông Tin Thiết Bị
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Thiết bị <span class="text-red-500">*</span>
                        </label>
                        <select name="thietbi_id" 
                                id="thietbi_id"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Chọn thiết bị --</option>
                            <?php foreach ($thietbiList as $tb): ?>
                                <option value="<?= $tb['id'] ?>" 
                                        data-ten="<?= htmlspecialchars($tb['ten_thiet_bi']) ?>"
                                        data-kymahieu="<?= htmlspecialchars($tb['ky_ma_hieu'] ?? '') ?>">
                                    <?= htmlspecialchars($tb['ten_thiet_bi']) ?> 
                                    <?php if (!empty($tb['ky_ma_hieu'])): ?>
                                        - <?= htmlspecialchars($tb['ky_ma_hieu']) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Tên và ký mã hiệu sẽ tự động điền</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Tên thiết bị
                        </label>
                        <input type="text" 
                               name="ten_thietbi" 
                               id="ten_thietbi"
                               readonly
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600"
                               placeholder="Tự động điền từ thiết bị">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ký mã hiệu
                        </label>
                        <input type="text" 
                               name="ky_ma_hieu" 
                               id="ky_ma_hieu"
                               readonly
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600"
                               placeholder="Tự động điền từ thiết bị">
                    </div>
                </div>
            </div>

            <!-- Thông tin bên giao (Team) -->
            <div class="border-b pb-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-user-tie mr-2"></i>Thông Tin Bên Giao (Team)
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Người giao <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="nguoi_giao" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Nhập tên người giao">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Đơn vị giao <span class="text-red-500">*</span>
                        </label>
                        <select name="donvi_giao" 
                                required
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
                            Ngày giao <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               name="ngay_giao" 
                               required
                               value="<?= date('Y-m-d') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Thông tin bên nhận (Us) -->
            <div class="border-b pb-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-user-check mr-2"></i>Thông Tin Bên Nhận (Xưởng SCTBĐVL)
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Người nhận <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="nguoi_nhan" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Nhập tên người nhận">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Đơn vị nhận
                        </label>
                        <input type="text" 
                               name="donvi_nhan" 
                               value="SCTBDVL"
                               readonly
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600 cursor-not-allowed">
                        <p class="text-xs text-gray-500 mt-1">Đơn vị nhận cố định là Xưởng SCTBĐVL</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ngày nhận <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               name="ngay_nhan" 
                               required
                               value="<?= date('Y-m-d') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Ghi chú -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Ghi chú
                </label>
                <textarea name="ghichu" 
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Nhập ghi chú (nếu có)"></textarea>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 pt-4">
                <button type="submit" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition duration-200 flex items-center">
                    <i class="fas fa-save mr-2"></i>Lưu Phiếu
                </button>
                <a href="giaonhanthietbi.php" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition duration-200 flex items-center">
                    <i class="fas fa-times mr-2"></i>Hủy
                </a>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript để auto-fill tên thiết bị và ký mã hiệu -->
<script>
document.getElementById('thietbi_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const tenThietBi = selectedOption.getAttribute('data-ten') || '';
    const kyMaHieu = selectedOption.getAttribute('data-kymahieu') || '';
    
    document.getElementById('ten_thietbi').value = tenThietBi;
    document.getElementById('ky_ma_hieu').value = kyMaHieu;
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
