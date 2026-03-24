<?php
declare(strict_types=1);
include __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-arrow-down mr-2 text-green-500"></i>
            Nhận Thiết Bị Về Sau Kiểm Định
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

    <?php if (empty($phieuGiaoList)): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Không có phiếu giao đi nào đang chờ nhận về
        </div>
    <?php endif; ?>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="giaonhanthietbi.php?action=store_nhan_ve" class="space-y-6">
            <!-- Chọn phiếu giao đi -->
            <div class="border-b pb-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-file-alt mr-2"></i>Chọn Phiếu Giao Đi
                </h3>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Phiếu giao đi <span class="text-red-500">*</span>
                    </label>
                    <select name="phieu_giao_id" 
                            id="phieu_giao_id"
                            required
                            <?= empty($phieuGiaoList) ? 'disabled' : '' ?>
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">-- Chọn phiếu giao đi --</option>
                        <?php foreach ($phieuGiaoList as $pg): ?>
                            <option value="<?= $pg['id'] ?>"
                                    data-sothietbi="<?= (int)($pg['so_thietbi'] ?? 0) ?>"
                                    data-nguoigiao="<?= htmlspecialchars($pg['nguoi_giao']) ?>"
                                    data-donvigiao="<?= htmlspecialchars($pg['donvi_giao']) ?>"
                                    data-ngaygiao="<?= $pg['ngay_giao'] ?>">
                                Phiếu #<?= $pg['id'] ?> - <?= (int)($pg['so_thietbi'] ?? 0) ?> thiết bị
                                - Giao ngày: <?= date('d/m/Y', strtotime($pg['ngay_giao'])) ?>
                                - Từ: <?= htmlspecialchars($pg['donvi_giao']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Chỉ hiển thị các phiếu đang chờ nhận về. Tất cả thiết bị trong phiếu sẽ được tự động copy sang phiếu nhận về.</p>
                </div>
            </div>

            <!-- Thông tin phiếu giao (readonly) -->
            <div class="border-b pb-4 mb-4 bg-gray-50 p-4 rounded">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-info-circle mr-2"></i>Thông Tin Phiếu Giao
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Số thiết bị</label>
                        <input type="text" 
                               id="display_sothietbi" 
                               readonly
                               class="w-full px-3 py-2 border border-gray-200 rounded-md bg-white text-gray-600"
                               placeholder="Chọn phiếu để xem">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Người giao (Team)</label>
                        <input type="text" 
                               id="display_nguoigiao" 
                               readonly
                               class="w-full px-3 py-2 border border-gray-200 rounded-md bg-white text-gray-600"
                               placeholder="Chọn phiếu giao đi để xem">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ngày giao (Team → Us)</label>
                        <input type="text" 
                               id="display_ngaygiao" 
                               readonly
                               class="w-full px-3 py-2 border border-gray-200 rounded-md bg-white text-gray-600"
                               placeholder="Chọn phiếu giao đi để xem">
                    </div>
                </div>
            </div>

            <!-- Thông tin nhận về (Us → Team) -->
            <div class="border-b pb-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-undo mr-2"></i>Thông Tin Trả Lại (Us → Team)
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Người giao (Us) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="nguoi_giao" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Nhập tên người giao từ xưởng">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Đơn vị giao
                        </label>
                        <input type="text" 
                               name="donvi_giao" 
                               value="SCTBDVL"
                               readonly
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600 cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ngày giao lại <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               name="ngay_giao" 
                               required
                               value="<?= date('Y-m-d') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Người nhận (Team) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="nguoi_nhan" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Nhập tên người nhận">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Đơn vị nhận (Team)
                        </label>
                        <input type="text" 
                               id="display_donvinhan" 
                               readonly
                               class="w-full px-3 py-2 border border-gray-200 rounded-md bg-white text-gray-600"
                               placeholder="Tự động từ phiếu giao đi">
                        <input type="hidden" name="donvi_nhan" id="donvi_nhan">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ngày nhận về <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               name="ngay_giao" 
                               required
                               value="<?= date('Y-m-d') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
            </div>

            <!-- Kết quả kiểm định -->
            <div class="border-b pb-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-clipboard-check mr-2"></i>Kết Quả Kiểm Định
                </h3>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nội dung kiểm định
                    </label>
                    <textarea name="noidung_kiemdinh" 
                              rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                              placeholder="Nhập kết quả kiểm định, tình trạng thiết bị..."></textarea>
                </div>
            </div>

            <!-- Ghi chú -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Ghi chú
                </label>
                <textarea name="ghichu" 
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                          placeholder="Nhập ghi chú (nếu có)"></textarea>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 pt-4">
                <button type="submit" 
                        <?= empty($phieuGiaoList) ? 'disabled' : '' ?>
                        class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition duration-200 flex items-center disabled:bg-gray-400 disabled:cursor-not-allowed">
                    <i class="fas fa-save mr-2"></i>Lưu Phiếu Nhận Về
                </button>
                <a href="giaonhanthietbi.php" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition duration-200 flex items-center">
                    <i class="fas fa-times mr-2"></i>Hủy
                </a>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript để auto-fill thông tin từ phiếu giao đi -->
<script>
document.getElementById('phieu_giao_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    
    if (selectedOption.value) {
        const sothietbi = selectedOption.getAttribute('data-sothietbi') || '0';
        const nguoigiao = selectedOption.getAttribute('data-nguoigiao') || '';
        const donvigiao = selectedOption.getAttribute('data-donvigiao') || '';
        const ngaygiao = selectedOption.getAttribute('data-ngaygiao') || '';
        
        document.getElementById('display_sothietbi').value = sothietbi + ' thiết bị';
        document.getElementById('display_nguoigiao').value = nguoigiao;
        document.getElementById('display_ngaygiao').value = ngaygiao ? formatDate(ngaygiao) : '';
        document.getElementById('display_donvinhan').value = donvigiao;
        document.getElementById('donvi_nhan').value = donvigiao;
    } else {
        document.getElementById('display_sothietbi').value = '';
        document.getElementById('display_nguoigiao').value = '';
        document.getElementById('display_ngaygiao').value = '';
        document.getElementById('display_donvinhan').value = '';
        document.getElementById('donvi_nhan').value = '';
    }
});

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
