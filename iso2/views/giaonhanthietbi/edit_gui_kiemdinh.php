<?php
/**
 * BƯỚC 2: Form gửi đi kiểm định (trạng thái: dang_kiem_dinh)
 */
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-shipping-fast mr-2 text-orange-500"></i>
            Gửi Đi Kiểm Định - Phiếu #<?= $record['id'] ?>
        </h1>
        <a href="giaonhanthietbi.php?action=view&id=<?= $record['id'] ?>" 
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
    <form method="POST" action="giaonhanthietbi.php?action=updateGuiKiemDinh">
        <input type="hidden" name="id" value="<?= $record['id'] ?>">

        <div class="bg-white rounded-lg shadow-md p-6">
            
            <!-- Section 1: Thông tin đã nhận từ đội (readonly) -->
            <div class="mb-6 bg-blue-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">
                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                    Thông Tin Phiếu Nhận Từ Đội <span class="text-sm text-gray-500">(Chỉ xem)</span>
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Người Giao</label>
                        <div class="text-gray-800 font-medium"><?= htmlspecialchars($record['nguoi_giao']) ?></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Đơn Vị Giao</label>
                        <div class="text-gray-800 font-medium"><?= htmlspecialchars($record['ten_donvi_giao'] ?? $record['donvi_giao']) ?></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Ngày Giao</label>
                        <div class="text-gray-800 font-medium"><?= date('d/m/Y', strtotime($record['ngay_giao'])) ?></div>
                    </div>
                </div>

                <?php if ($record['ghichu']): ?>
                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-600 mb-1">Ghi Chú</label>
                        <div class="text-gray-800"><?= nl2br(htmlspecialchars($record['ghichu'])) ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section 2: Danh sách thiết bị (readonly) -->
            <div class="mb-6 bg-green-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">
                    <i class="fas fa-list mr-2 text-green-500"></i>
                    Danh Sách Thiết Bị (<?= count($thietbiList) ?> thiết bị)
                </h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">STT</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Tên Thiết Bị</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Ký Mã Hiệu</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Tình Trạng</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Ghi Chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($thietbiList as $index => $tb): ?>
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm"><?= $index + 1 ?></td>
                                    <td class="px-4 py-2 text-sm font-medium"><?= htmlspecialchars($tb['ten_thietbi']) ?></td>
                                    <td class="px-4 py-2 text-sm"><?= htmlspecialchars($tb['ky_ma_hieu']) ?></td>
                                    <td class="px-4 py-2 text-sm"><?= htmlspecialchars($tb['tinhtrang'] ?? '') ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-600"><?= htmlspecialchars($tb['ghichu'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 3: Thông tin gửi kiểm định (editable) -->
            <div class="mb-6 bg-orange-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">
                    <i class="fas fa-shipping-fast mr-2 text-orange-500"></i>
                    Thông Tin Gửi Kiểm Định <span class="text-red-500">*</span>
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Người gửi -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Người Gửi Kiểm Định <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="nguoi_gui_kiemdinh" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                               placeholder="Nhập tên người gửi">
                    </div>

                    <!-- Đơn vị gửi -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Đơn Vị Gửi <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="donvi_gui_kiemdinh" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                               placeholder="Nhập đơn vị gửi kiểm định">
                    </div>

                    <!-- Ngày gửi -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ngày Gửi Kiểm Định <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               name="ngay_gui_kiemdinh" 
                               required
                               value="<?= date('Y-m-d') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>

                <div class="mt-4 bg-orange-100 border-l-4 border-orange-500 p-3 rounded">
                    <i class="fas fa-exclamation-triangle mr-2 text-orange-600"></i>
                    <span class="text-sm text-orange-800">
                        Sau khi lưu, trạng thái phiếu sẽ chuyển sang <strong>"Đang Kiểm Định"</strong>
                    </span>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-2">
                <a href="giaonhanthietbi.php?action=view&id=<?= $record['id'] ?>" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-times mr-2"></i>Hủy
                </a>
                <button type="submit" 
                        class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-paper-plane mr-2"></i>Xác Nhận Gửi Kiểm Định
                </button>
            </div>

        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
