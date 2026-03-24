<?php
declare(strict_types=1);
include __DIR__ . '/../layouts/header.php';

// Định nghĩa các label hiển thị
$loaiText = [
    'giao_di_kd' => ['text' => 'Giao Đi Kiểm Định', 'icon' => 'fa-arrow-up', 'color' => 'blue'],
    'nhan_ve_kd' => ['text' => 'Nhận Về Kiểm Định', 'icon' => 'fa-arrow-down', 'color' => 'green']
];

$trangthaiText = [
    'cho_nhan' => ['text' => 'Chờ nhận', 'class' => 'bg-yellow-100 text-yellow-800'],
    'da_nhan' => ['text' => 'Đã nhận', 'class' => 'bg-blue-100 text-blue-800'],
    'hoan_thanh' => ['text' => 'Hoàn thành', 'class' => 'bg-green-100 text-green-800']
];

$loaiInfo = $loaiText[$record['loai_giao_nhan']] ?? ['text' => '', 'icon' => '', 'color' => 'gray'];
$trangthaiInfo = $trangthaiText[$record['trangthai']] ?? ['text' => '', 'class' => ''];
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas <?= $loaiInfo['icon'] ?> mr-2 text-<?= $loaiInfo['color'] ?>-500"></i>
            Chi Tiết Phiếu #<?= $record['id'] ?>
        </h1>
        <div class="flex gap-2">
            <?php if (hasPermission('giaonhanthietbi.delete')): ?>
                <form method="POST" action="giaonhanthietbi.php?action=delete" 
                      onsubmit="return confirm('Bạn có chắc chắn muốn xóa phiếu này?\n\nLưu ý: Tất cả thiết bị trong phiếu cũng sẽ bị xóa.')"
                      style="display: inline;">
                    <input type="hidden" name="id" value="<?= $record['id'] ?>">
                    <button type="submit" 
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-trash mr-2"></i>Xóa
                    </button>
                </form>
            <?php endif; ?>
            <a href="giaonhanthietbi.php" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
        </div>
    </div>

    <!-- Thông báo -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Phần thông tin chính -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Thông tin chung -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-700">
                        <i class="fas fa-info-circle mr-2"></i>Thông Tin Chung
                    </h3>
                    <span class="px-3 py-1 rounded-full <?= $trangthaiInfo['class'] ?>">
                        <?= $trangthaiInfo['text'] ?>
                    </span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Loại phiếu</label>
                        <p class="text-gray-900 font-medium">
                            <i class="fas <?= $loaiInfo['icon'] ?> mr-1 text-<?= $loaiInfo['color'] ?>-500"></i>
                            <?= $loaiInfo['text'] ?>
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Trạng thái</label>
                        <span class="inline-block px-3 py-1 rounded-full <?= $trangthaiInfo['class'] ?>">
                            <?= $trangthaiInfo['text'] ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Danh sách thiết bị -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-cogs mr-2"></i>Danh Sách Thiết Bị 
                    <span class="text-sm font-normal text-gray-500">(<?= count($thietbiList) ?> thiết bị)</span>
                </h3>
                
                <?php if (empty($thietbiList)): ?>
                    <p class="text-gray-500 text-center py-4">Không có thiết bị</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên thiết bị</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ký mã hiệu</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tình trạng</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($thietbiList as $index => $tb): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm"><?= $index + 1 ?></td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($tb['ten_thietbi'] ?? '') ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            <?= htmlspecialchars($tb['ky_ma_hieu'] ?? '') ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            <?= htmlspecialchars($tb['tinhtrang'] ?? '') ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            <?= htmlspecialchars($tb['ghichu'] ?? '') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Thông tin giao -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-user-tie mr-2"></i>Thông Tin Bên Giao
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Người giao</label>
                        <p class="text-gray-900"><?= htmlspecialchars($record['nguoi_giao'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Đơn vị giao</label>
                        <p class="text-gray-900"><?= htmlspecialchars($record['donvi_giao'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Ngày giao</label>
                        <p class="text-gray-900">
                            <?= $record['ngay_giao'] ? date('d/m/Y', strtotime($record['ngay_giao'])) : 'N/A' ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Thông tin nhận -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-user-check mr-2"></i>Thông Tin Bên Nhận
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Người nhận</label>
                        <p class="text-gray-900"><?= htmlspecialchars($record['nguoi_nhan'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Đơn vị nhận</label>
                        <p class="text-gray-900"><?= htmlspecialchars($record['donvi_nhan'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Ngày nhận</label>
                        <p class="text-gray-900">
                            <?= $record['ngay_nhan'] ? date('d/m/Y', strtotime($record['ngay_nhan'])) : 'N/A' ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Kết quả kiểm định (chỉ hiển thị với phiếu nhận về) -->
            <?php if ($record['loai_giao_nhan'] === 'nhan_ve_kd'): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">
                        <i class="fas fa-clipboard-check mr-2"></i>Kết Quả Kiểm Định
                    </h3>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-2">Nội dung kiểm định</label>
                        <?php if (!empty($record['noidung_kiemdinh'])): ?>
                            <div class="bg-gray-50 p-3 rounded border border-gray-200">
                                <p class="text-gray-900 whitespace-pre-wrap"><?= htmlspecialchars($record['noidung_kiemdinh']) ?></p>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 italic">Chưa có nội dung kiểm định</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Ghi chú -->
            <?php if (!empty($record['ghichu'])): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">
                        <i class="fas fa-sticky-note mr-2"></i>Ghi Chú
                    </h3>
                    <div class="bg-gray-50 p-3 rounded border border-gray-200">
                        <p class="text-gray-900 whitespace-pre-wrap"><?= htmlspecialchars($record['ghichu']) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Phiếu giao đi liên quan (nếu là phiếu nhận về) -->
            <?php if ($record['loai_giao_nhan'] === 'nhan_ve_kd' && !empty($phieuGiaoDi)): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">
                        <i class="fas fa-link mr-2"></i>Phiếu Giao Đi Liên Quan
                    </h3>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-blue-900">
                                    Phiếu #<?= $phieuGiaoDi['id'] ?> - Giao đi KD
                                </p>
                                <p class="text-sm text-blue-700 mt-1">
                                    Giao: <?= htmlspecialchars($phieuGiaoDi['nguoi_giao']) ?> 
                                    (<?= htmlspecialchars($phieuGiaoDi['donvi_giao']) ?>) 
                                    - <?= date('d/m/Y', strtotime($phieuGiaoDi['ngay_giao'])) ?>
                                </p>
                            </div>
                            <a href="giaonhanthietbi.php?action=view&id=<?= $phieuGiaoDi['id'] ?>" 
                               class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Thông tin hệ thống -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-clock mr-2"></i>Thông Tin Hệ Thống
                </h3>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Người tạo</label>
                        <p class="text-gray-900"><?= htmlspecialchars($record['created_by'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Ngày tạo</label>
                        <p class="text-gray-900">
                            <?= $record['created_at'] ? date('d/m/Y H:i', strtotime($record['created_at'])) : 'N/A' ?>
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Cập nhật lần cuối</label>
                        <p class="text-gray-900">
                            <?= $record['updated_at'] ? date('d/m/Y H:i', strtotime($record['updated_at'])) : 'N/A' ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Thao tác nhanh -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-tasks mr-2"></i>Thao Tác
                </h3>
                
                <div class="space-y-2">
                    <a href="giaonhanthietbi.php" 
                       class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded transition duration-200 text-center">
                        <i class="fas fa-list mr-2"></i>Danh sách phiếu
                    </a>
                    
                    <?php if (hasPermission('giaonhanthietbi.create_giao')): ?>
                        <a href="giaonhanthietbi.php?action=create_giao_di" 
                           class="block w-full bg-blue-100 hover:bg-blue-200 text-blue-800 px-4 py-2 rounded transition duration-200 text-center">
                            <i class="fas fa-arrow-up mr-2"></i>Tạo phiếu giao đi
                        </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('giaonhanthietbi.create_nhan')): ?>
                        <a href="giaonhanthietbi.php?action=create_nhan_ve" 
                           class="block w-full bg-green-100 hover:bg-green-200 text-green-800 px-4 py-2 rounded transition duration-200 text-center">
                            <i class="fas fa-arrow-down mr-2"></i>Tạo phiếu nhận về
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
