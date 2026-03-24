<?php
declare(strict_types=1);
include __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-exchange-alt mr-2"></i>
            Giao Nhận Thiết Bị Kiểm Định
        </h1>
        <div class="flex gap-2">
            <?php if (hasPermission('giaonhanthietbi.create_giao')): ?>
                <a href="giaonhanthietbi.php?action=create_giao_di" 
                   class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center">
                    <i class="fas fa-arrow-up mr-2"></i>
                    Giao Đi Kiểm Định
                </a>
            <?php endif; ?>
            <?php if (hasPermission('giaonhanthietbi.create_nhan')): ?>
                <a href="giaonhanthietbi.php?action=create_nhan_ve" 
                   class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center">
                    <i class="fas fa-arrow-down mr-2"></i>
                    Nhận Về Kiểm Định
                </a>
            <?php endif; ?>
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

    <!-- Bộ lọc -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <form method="GET" action="giaonhanthietbi.php" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <input type="hidden" name="action" value="index">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                <input type="text" 
                       name="search" 
                       value="<?= htmlspecialchars($search ?? '') ?>" 
                       placeholder="Tên thiết bị..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Loại</label>
                <select name="loai" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tất cả --</option>
                    <option value="giao_di_kd" <?= ($loai ?? '') === 'giao_di_kd' ? 'selected' : '' ?>>Giao đi KD</option>
                    <option value="nhan_ve_kd" <?= ($loai ?? '') === 'nhan_ve_kd' ? 'selected' : '' ?>>Nhận về KD</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                <select name="trangthai" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tất cả --</option>
                    <option value="cho_nhan" <?= ($trangthai ?? '') === 'cho_nhan' ? 'selected' : '' ?>>Chờ nhận</option>
                    <option value="da_nhan" <?= ($trangthai ?? '') === 'da_nhan' ? 'selected' : '' ?>>Đã nhận</option>
                    <option value="hoan_thanh" <?= ($trangthai ?? '') === 'hoan_thanh' ? 'selected' : '' ?>>Hoàn thành</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Đơn vị</label>
                <select name="donvi" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($donviList as $dv): ?>
                        <option value="<?= htmlspecialchars($dv['ma_don_vi']) ?>" 
                                <?= ($donvi ?? '') === $dv['ma_don_vi'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dv['ten_don_vi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                <input type="date" 
                       name="tu_ngay" 
                       value="<?= htmlspecialchars($tu_ngay ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                <input type="date" 
                       name="den_ngay" 
                       value="<?= htmlspecialchars($den_ngay ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="md:col-span-3 lg:col-span-6 flex gap-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md transition duration-200">
                    <i class="fas fa-search mr-2"></i>Tìm kiếm
                </button>
                <a href="giaonhanthietbi.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md transition duration-200">
                    <i class="fas fa-redo mr-2"></i>Làm mới
                </a>
            </div>
        </form>
    </div>

    <!-- Bảng danh sách -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số TB</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người Giao</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đơn Vị Giao</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày Giao</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người Nhận</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đơn Vị Nhận</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng Thái</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao Tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>Không có dữ liệu</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $index => $record): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm"><?= $index + 1 ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <?php if ($record['loai_giao_nhan'] === 'giao_di_kd'): ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                            <i class="fas fa-arrow-up"></i> Giao đi
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                            <i class="fas fa-arrow-down"></i> Nhận về
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        <i class="fas fa-cogs mr-1"></i>
                                        <?= (int)($record['so_thietbi'] ?? 0) ?> thiết bị
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <?= htmlspecialchars($record['nguoi_giao'] ?? '') ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <?= htmlspecialchars($record['ten_donvi_giao'] ?? '') ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <?= $record['ngay_giao'] ? date('d/m/Y', strtotime($record['ngay_giao'])) : '' ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <?= htmlspecialchars($record['nguoi_nhan'] ?? '') ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <?= htmlspecialchars($record['ten_donvi_nhan'] ?? '') ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php
                                    $statusClass = [
                                        'cho_nhan' => 'bg-yellow-100 text-yellow-800',
                                        'da_nhan' => 'bg-blue-100 text-blue-800',
                                        'hoan_thanh' => 'bg-green-100 text-green-800'
                                    ];
                                    $statusText = [
                                        'cho_nhan' => 'Chờ nhận',
                                        'da_nhan' => 'Đã nhận',
                                        'hoan_thanh' => 'Hoàn thành'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 text-xs rounded-full <?= $statusClass[$record['trangthai']] ?? '' ?>">
                                        <?= $statusText[$record['trangthai']] ?? '' ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex gap-2">
                                        <a href="giaonhanthietbi.php?action=view&id=<?= $record['id'] ?>" 
                                           class="text-blue-600 hover:text-blue-800" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (hasPermission('giaonhanthietbi.delete')): ?>
                                            <a href="giaonhanthietbi.php?action=delete&id=<?= $record['id'] ?>" 
                                               class="text-red-600 hover:text-red-800"
                                               title="Xóa"
                                               onclick="return confirm('Bạn có chắc chắn muốn xóa phiếu này?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tổng số bản ghi -->
    <?php if (!empty($records)): ?>
        <div class="mt-4 text-sm text-gray-600">
            Tổng số: <strong><?= count($records) ?></strong> bản ghi
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
