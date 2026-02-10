<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<style>
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}
.stat-box {
    transition: all 0.3s ease;
    cursor: pointer;
}
.stat-box:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
h6 {
    font-weight: 700;
    color: #495057;
    border-left: 4px solid #3b82f6;
    padding-left: 12px;
    margin-top: 2rem !important;
}
@media print {
    .no-print {
        display: none !important;
    }
    .card {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>

<div class="container mx-auto px-4 py-3">
    <!-- Breadcrumb -->
    <nav class="mb-3 text-sm">
        <ol class="flex items-center space-x-2">
            <li><a href="/iso2/index.php" class="text-blue-600 hover:text-blue-800">Trang chủ</a></li>
            <li class="text-gray-500">/</li>
            <li><a href="/iso2/vattuthanhly.php" class="text-blue-600 hover:text-blue-800">Vật tư thanh lý</a></li>
            <li class="text-gray-500">/</li>
            <li class="text-gray-700">Chi tiết vật tư #<?php echo $vattu['stt']; ?></li>
        </ol>
    </nav>

    <!-- Header with Actions -->
    <div class="flex justify-between items-center mb-4 no-print">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
            Chi tiết vật tư
        </h2>
        <div class="flex space-x-2">
            <a href="/iso2/vattuthanhly.php" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm" title="Quay lại danh sách">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
            <?php if (hasPermission('vattu.edit')): ?>
            <a href="/iso2/vattuthanhly.php?action=edit&id=<?php echo $vattu['stt']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-2 rounded text-sm" title="Chỉnh sửa thông tin">
                <i class="fas fa-edit"></i> Sửa
            </a>
            <?php endif; ?>
            <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm" title="In trang">
                <i class="fas fa-print"></i> In
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Left Column - Main Info -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Thông tin cơ bản -->
            <div class="card bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-blue-600 text-white px-4 py-3">
                    <h5 class="text-lg font-bold"><i class="fas fa-clipboard-list mr-2"></i>Thông tin cơ bản</h5>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="bg-blue-50 border border-blue-200 rounded p-3">
                            <small class="text-gray-600 block mb-1">STT</small>
                            <h4 class="text-2xl font-bold text-blue-600">#<?php echo $vattu['stt']; ?></h4>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded p-3">
                            <small class="text-gray-600 block mb-1">Vị trí sắp xếp</small>
                            <h4 class="text-2xl font-bold text-blue-600"><?php echo $vattu['vi_tri_sap_xep'] ?? '-'; ?></h4>
                        </div>
                    </div>
                    
                    <table class="w-full border-collapse border border-gray-300">
                        <tbody>
                            <tr class="border-b">
                                <th class="bg-gray-100 px-4 py-2 text-left w-1/3">Mã vật tư</th>
                                <td class="px-4 py-2">
                                    <code class="<?php echo htmlspecialchars($vattu['phanloai_mau_sac'] ?? 'bg-blue-100 text-blue-800'); ?> px-4 py-2 rounded font-semibold text-base inline-block min-w-[160px] text-center">
                                        <?php echo htmlspecialchars($vattu['mavattu']); ?>
                                    </code>
                                </td>
                            </tr>
                            <?php if (!empty($vattu['so_serial'])): ?>
                            <tr class="border-b">
                                <th class="bg-gray-100 px-4 py-2 text-left">Số Serial</th>
                                <td class="px-4 py-2">
                                    <span class="bg-gray-200 px-3 py-1 rounded text-sm font-mono">
                                        <?php echo htmlspecialchars($vattu['so_serial']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr class="border-b">
                                <th class="bg-gray-100 px-4 py-2 text-left">Phân loại</th>
                                <td class="px-4 py-2">
                                    <?php if (!empty($vattu['ten_phanloai'])): ?>
                                        <span class="<?php echo htmlspecialchars($vattu['phanloai_mau_sac'] ?? 'bg-gray-200 text-gray-800'); ?> px-3 py-1 rounded text-sm font-semibold">
                                            <?php echo htmlspecialchars($vattu['ten_phanloai']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">Chưa phân loại</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <hr class="my-4">

                    <!-- Tên vật tư -->
                    <h6 class="mb-3"><i class="fas fa-language mr-2"></i>Tên vật tư</h6>
                    <table class="w-full border-collapse border border-gray-300">
                        <tbody>
                            <tr class="border-b">
                                <th class="bg-gray-100 px-4 py-2 text-left w-1/3">
                                    <i class="fas fa-flag mr-1"></i> Tiếng Anh
                                </th>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($vattu['ten_tienganh'] ?? '-'); ?></td>
                            </tr>
                            <tr class="border-b">
                                <th class="bg-gray-100 px-4 py-2 text-left">
                                    <i class="fas fa-flag mr-1"></i> Tiếng Nga
                                </th>
                                <td class="px-4 py-2 text-blue-700"><?php echo htmlspecialchars($vattu['ten_tiengnga'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th class="bg-gray-100 px-4 py-2 text-left">
                                    <i class="fas fa-flag mr-1"></i> Tiếng Việt
                                </th>
                                <td class="px-4 py-2 text-green-700"><?php echo htmlspecialchars($vattu['ten_tiengviet'] ?? '-'); ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <hr class="my-4">

                    <!-- Đặc tính kỹ thuật -->
                    <h6 class="mb-3"><i class="fas fa-cogs mr-2"></i>Đặc tính kỹ thuật</h6>
                    <table class="w-full border-collapse border border-gray-300">
                        <tbody>
                            <tr class="border-b">
                                <th class="bg-gray-100 px-4 py-2 text-left w-1/3">
                                    <i class="fas fa-flag mr-1"></i> Tiếng Nga
                                </th>
                                <td class="px-4 py-2 text-blue-700 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($vattu['dactinhkt_tiengnga'] ?? '-')); ?></td>
                            </tr>
                            <tr>
                                <th class="bg-gray-100 px-4 py-2 text-left">
                                    <i class="fas fa-flag mr-1"></i> Tiếng Việt
                                </th>
                                <td class="px-4 py-2 text-green-700 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($vattu['dactinhkt_tiengviet'] ?? '-')); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <hr class="my-4">
                    
                    <!-- Đơn vị tính -->
                    <h6 class="mb-3"><i class="fas fa-balance-scale mr-2"></i>Đơn vị tính</h6>
                    <table class="w-full border-collapse border border-gray-300">
                        <tbody>
                            <tr class="border-b">
                                <th class="bg-gray-100 px-4 py-2 text-left w-1/3">
                                    <i class="fas fa-flag mr-1"></i> Tiếng Nga
                                </th>
                                <td class="px-4 py-2 text-blue-700"><?php echo htmlspecialchars($vattu['dvt_tiengnga'] ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th class="bg-gray-100 px-4 py-2 text-left">
                                    <i class="fas fa-flag mr-1"></i> Tiếng Việt
                                </th>
                                <td class="px-4 py-2 text-green-700"><?php echo htmlspecialchars($vattu['dvt_tiengviet'] ?? '-'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Lịch sử sử dụng -->
            <div class="card bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-green-600 text-white px-4 py-3 flex justify-between items-center">
                    <h5 class="text-lg font-bold"><i class="fas fa-history mr-2"></i>Lịch sử sử dụng</h5>
                    <?php if (hasPermission('vattu.edit')): ?>
                    <button class="bg-white text-green-600 px-3 py-1 rounded text-sm font-semibold hover:bg-gray-100" onclick="showAddChiTietModal()">
                        <i class="fas fa-plus mr-1"></i> Thêm chi tiết
                    </button>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <?php if (empty($chiTietList)): ?>
                        <p class="text-gray-500 text-center py-3">
                            <i class="fas fa-inbox mr-2"></i>Chưa có lịch sử sử dụng
                        </p>
                    <?php else: ?>
                        <div class="bg-blue-50 border border-blue-200 rounded px-4 py-2 mb-3">
                            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                            <strong>Tổng số lượt thanh lý:</strong> <?php echo count($chiTietList); ?> lượt
                        </div>
                        <div class="overflow-auto max-h-[500px]">
                            <table class="w-full border-collapse border border-gray-300 text-sm">
                                <thead class="bg-green-600 text-white sticky top-0">
                                    <tr>
                                        <th class="border border-gray-300 px-3 py-2 text-center">#ID</th>
                                        <th class="border border-gray-300 px-3 py-2"><i class="fas fa-user mr-1"></i>Người sử dụng</th>
                                        <th class="border border-gray-300 px-3 py-2 text-center"><i class="fas fa-calendar mr-1"></i>Ngày nhận</th>
                                        <th class="border border-gray-300 px-3 py-2 text-right"><i class="fas fa-sort-numeric-up mr-1"></i>SL</th>
                                        <th class="border border-gray-300 px-3 py-2"><i class="fas fa-building mr-1"></i>Bộ phận</th>
                                        <th class="border border-gray-300 px-3 py-2"><i class="fas fa-bullseye mr-1"></i>Mục đích</th>
                                        <th class="border border-gray-300 px-3 py-2 text-center"><i class="fas fa-info-circle mr-1"></i>TT</th>
                                        <th class="border border-gray-300 px-3 py-2 text-center"><i class="fas fa-check mr-1"></i>Hoàn thành</th>
                                        <th class="border border-gray-300 px-3 py-2"><i class="fas fa-comment mr-1"></i>Ghi chú</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <?php foreach ($chiTietList as $index => $ct): ?>
                                    <tr class="<?php echo $index % 2 == 0 ? 'bg-gray-50' : 'bg-white'; ?> hover:bg-blue-50">
                                        <td class="border border-gray-300 px-3 py-2 text-center font-bold"><?php echo $ct['id']; ?></td>
                                        <td class="border border-gray-300 px-3 py-2">
                                            <i class="fas fa-user-circle text-blue-600 mr-1"></i>
                                            <?php echo htmlspecialchars($ct['nguoisudung'] ?? '-'); ?>
                                        </td>
                                        <td class="border border-gray-300 px-3 py-2 text-center">
                                            <?php echo $ct['ngaysd_nhan'] ? '<span class="bg-gray-200 px-2 py-1 rounded text-xs">' . date('d/m/Y', strtotime($ct['ngaysd_nhan'])) . '</span>' : '-'; ?>
                                        </td>
                                        <td class="border border-gray-300 px-3 py-2 text-right">
                                            <strong class="text-red-600"><?php echo number_format($ct['soluong'] ?? 0, 0); ?></strong>
                                        </td>
                                        <td class="border border-gray-300 px-3 py-2"><?php echo htmlspecialchars($ct['bophan'] ?? '-'); ?></td>
                                        <td class="border border-gray-300 px-3 py-2"><small><?php echo htmlspecialchars($ct['mucdich_sudung'] ?? '-'); ?></small></td>
                                        <td class="border border-gray-300 px-3 py-2 text-center">
                                            <?php if ($ct['trangthai'] === 'dangdung'): ?>
                                                <span class="bg-green-500 text-white px-2 py-1 rounded text-xs"><i class="fas fa-check-circle mr-1"></i>Đang dùng</span>
                                            <?php elseif ($ct['trangthai'] === 'datra'): ?>
                                                <span class="bg-blue-500 text-white px-2 py-1 rounded text-xs"><i class="fas fa-undo mr-1"></i>Đã trả</span>
                                            <?php elseif ($ct['trangthai'] === 'thanh_ly'): ?>
                                                <span class="bg-yellow-500 text-gray-900 px-2 py-1 rounded text-xs"><i class="fas fa-recycle mr-1"></i>Thanh lý</span>
                                            <?php elseif ($ct['trangthai'] === 'dahoan'): ?>
                                                <span class="bg-purple-500 text-white px-2 py-1 rounded text-xs"><i class="fas fa-check-double mr-1"></i>Đã hoàn</span>
                                            <?php else: ?>
                                                <span class="bg-gray-500 text-white px-2 py-1 rounded text-xs"><?php echo htmlspecialchars($ct['trangthai']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="border border-gray-300 px-3 py-2 text-center">
                                            <?php echo !empty($ct['ngayhoanthanh']) ? '<span class="bg-green-500 text-white px-2 py-1 rounded text-xs">' . date('d/m/Y', strtotime($ct['ngayhoanthanh'])) . '</span>' : '<span class="text-gray-400">-</span>'; ?>
                                        </td>
                                        <td class="border border-gray-300 px-3 py-2">
                                            <small class="text-gray-600"><?php echo htmlspecialchars($ct['ghichu'] ?? '-'); ?></small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column - Stats & Info -->
        <div class="lg:col-span-1 space-y-4">
            <!-- Thống kê tổng quan -->
            <div class="card bg-white rounded-lg shadow-md overflow-hidden border-t-4 border-blue-600">
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-4 py-3">
                    <h5 class="text-lg font-bold"><i class="fas fa-chart-pie mr-2"></i>Thống kê tổng quan</h5>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="stat-box bg-gray-100 border border-gray-300 rounded p-3 text-center">
                            <i class="fas fa-boxes text-green-600 text-3xl mb-2"></i>
                            <h3 class="text-2xl font-bold text-green-600"><?php echo number_format($vattu['soluong_conlai'] ?? 0, 0); ?></h3>
                            <small class="text-gray-600 font-semibold block">Còn lại</small>
                        </div>
                        <div class="stat-box bg-gray-100 border border-gray-300 rounded p-3 text-center">
                            <i class="fas fa-tools text-blue-600 text-3xl mb-2"></i>
                            <h3 class="text-2xl font-bold text-blue-600"><?php echo number_format($vattu['soluong_dangdung'] ?? 0, 0); ?></h3>
                            <small class="text-gray-600 font-semibold block">Đang dùng</small>
                        </div>
                        <div class="stat-box bg-gray-100 border border-gray-300 rounded p-3 text-center">
                            <i class="fas fa-history text-yellow-600 text-3xl mb-2"></i>
                            <h3 class="text-2xl font-bold text-yellow-600"><?php echo $vattu['so_lan_sudung'] ?? 0; ?></h3>
                            <small class="text-gray-600 font-semibold block">Lần thanh lý</small>
                        </div>
                        <div class="stat-box bg-gray-100 border border-gray-300 rounded p-3 text-center">
                            <i class="fas fa-coins text-purple-600 text-3xl mb-2"></i>
                            <h4 class="text-lg font-bold text-purple-600">
                                <?php echo $vattu['dongia'] ? number_format($vattu['tong_tien'] ?? 0, 0) : '0'; ?>
                            </h4>
                            <small class="text-gray-600 font-semibold block">Giá trị (₫)</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Chi tiết giá -->
            <div class="card bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-cyan-600 text-white px-4 py-3">
                    <h5 class="text-lg font-bold"><i class="fas fa-calculator mr-2"></i>Chi tiết giá & số lượng</h5>
                </div>
                <div class="p-4">
                    <table class="w-full text-sm">
                        <tbody>
                            <tr class="border-b hover:bg-gray-50">
                                <th class="py-2 text-left text-gray-700">
                                    <i class="fas fa-box text-green-600 mr-1"></i> SL còn lại
                                </th>
                                <td class="py-2 text-right">
                                    <span class="bg-green-500 text-white px-2 py-1 rounded text-xs font-bold">
                                        <?php echo number_format($vattu['soluong_conlai'] ?? 0, 0); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr class="border-b hover:bg-gray-50">
                                <th class="py-2 text-left text-gray-700">
                                    <i class="fas fa-tag text-blue-600 mr-1"></i> Đơn giá
                                </th>
                                <td class="py-2 text-right font-bold">
                                    <?php echo $vattu['dongia'] ? number_format($vattu['dongia'], 0) . ' ₫' : '-'; ?>
                                </td>
                            </tr>
                            <tr class="border-b hover:bg-blue-50">
                                <th class="py-2 text-left text-gray-700">
                                    <i class="fas fa-money-bill-wave text-green-600 mr-1"></i> Tổng giá trị
                                </th>
                                <td class="py-2 text-right">
                                    <strong class="text-blue-600 text-lg">
                                        <?php echo number_format($vattu['tong_tien'] ?? 0, 0); ?> ₫
                                    </strong>
                                </td>
                            </tr>
                            <tr class="border-b hover:bg-gray-50">
                                <th class="py-2 text-left text-gray-700">
                                    <i class="fas fa-tools text-cyan-600 mr-1"></i> SL đang dùng
                                </th>
                                <td class="py-2 text-right">
                                    <span class="bg-cyan-500 text-white px-2 py-1 rounded text-xs font-bold">
                                        <?php echo number_format($vattu['soluong_dangdung'] ?? 0, 0); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <th class="py-2 text-left text-gray-700">
                                    <i class="fas fa-recycle text-yellow-600 mr-1"></i> Số lần thanh lý
                                </th>
                                <td class="py-2 text-right">
                                    <?php 
                                    $badgeClass = ($vattu['so_lan_sudung'] ?? 0) > 0 ? 'bg-green-500' : 'bg-yellow-500 text-gray-900';
                                    ?>
                                    <span class="<?php echo $badgeClass; ?> text-white px-2 py-1 rounded text-xs font-bold">
                                        <?php echo $vattu['so_lan_sudung'] ?? 0; ?> lần
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Thông tin hợp đồng -->
            <div class="card bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-yellow-500 text-gray-900 px-4 py-3">
                    <h5 class="text-lg font-bold"><i class="fas fa-file-contract mr-2"></i>Hợp đồng & Quản lý</h5>
                </div>
                <div class="p-4">
                    <table class="w-full text-sm">
                        <tbody>
                            <tr class="border-b hover:bg-gray-50">
                                <th class="py-2 text-left text-gray-700">
                                    <i class="fas fa-calendar-alt text-blue-600 mr-1"></i> Ngày nhận
                                </th>
                                <td class="py-2 text-right font-bold"><?php echo $vattu['ngaynhan'] ? date('d/m/Y', strtotime($vattu['ngaynhan'])) : '-'; ?></td>
                            </tr>
                            <tr class="border-b hover:bg-gray-50">
                                <th class="py-2 text-left text-gray-700">
                                    <i class="fas fa-file-signature text-green-600 mr-1"></i> Số HĐ
                                </th>
                                <td class="py-2 text-right">
                                    <?php if (!empty($vattu['sohd'])): ?>
                                        <span class="bg-gray-200 px-2 py-1 rounded text-xs"><?php echo htmlspecialchars($vattu['sohd']); ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr class="border-b hover:bg-gray-50">
                                <th class="py-2 text-left text-gray-700">
                                    <i class="fas fa-calendar-check text-cyan-600 mr-1"></i> Ngày ký HĐ
                                </th>
                                <td class="py-2 text-right font-bold"><?php echo !empty($vattu['ngaykyhd']) ? date('d/m/Y', strtotime($vattu['ngaykyhd'])) : '-'; ?></td>
                            </tr>
                            <tr class="border-b hover:bg-gray-50">
                                <th class="py-2 text-left text-gray-700">
                                    <i class="fas fa-user-tie text-blue-600 mr-1"></i> Người quản lý
                                </th>
                                <td class="py-2 text-right"><strong class="text-blue-600"><?php echo htmlspecialchars($vattu['nguoiquanly'] ?? '-'); ?></strong></td>
                            </tr>
                            <tr class="border-b hover:bg-gray-50">
                                <th class="py-2 text-left text-gray-700">
                                    <i class="fas fa-map-marker-alt text-red-600 mr-1"></i> Vị trí bảo quản
                                </th>
                                <td class="py-2 text-right"><?php echo htmlspecialchars($vattu['vitribaoquan'] ?? '-'); ?></td>
                            </tr>
                            <tr class="border-b bg-gray-50">
                                <th class="py-2 text-left text-gray-600">
                                    <i class="fas fa-clock text-gray-500 mr-1"></i> Ngày tạo
                                </th>
                                <td class="py-2 text-right"><small class="text-gray-600"><?php echo !empty($vattu['created_at']) ? date('d/m/Y H:i:s', strtotime($vattu['created_at'])) : '-'; ?></small></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <th class="py-2 text-left text-gray-600">
                                    <i class="fas fa-history text-cyan-600 mr-1"></i> Cập nhật cuối
                                </th>
                                <td class="py-2 text-right"><small class="text-gray-600"><?php echo !empty($vattu['updated_at']) ? date('d/m/Y H:i:s', strtotime($vattu['updated_at'])) : '-'; ?></small></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ghi chú -->
            <?php if (!empty($vattu['ghichu'])): ?>
            <div class="card bg-white rounded-lg shadow-md overflow-hidden border-l-4 border-gray-500">
                <div class="bg-gray-500 text-white px-4 py-3">
                    <h5 class="text-lg font-bold"><i class="fas fa-sticky-note mr-2"></i>Ghi chú</h5>
                </div>
                <div class="p-4 bg-gray-50">
                    <div class="bg-white border-l-4 border-gray-500 rounded p-3">
                        <p class="text-gray-700 italic whitespace-pre-wrap">
                            <?php echo nl2br(htmlspecialchars($vattu['ghichu'])); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Chi Tiet Modal (reuse from index.php if needed) -->
<div class="hidden modal-backdrop fixed inset-0 bg-black bg-opacity-50 z-40" id="modalBackdrop"></div>
<div class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" id="addChiTietModal">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h5 class="text-lg font-bold">Thêm chi tiết sử dụng</h5>
            <button class="text-gray-400 hover:text-gray-600" onclick="closeAddChiTietModal()">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="px-6 py-4">
            <form id="addChiTietForm">
                <input type="hidden" name="vattu_stt" value="<?php echo $vattu['stt']; ?>">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Người sử dụng</label>
                    <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" name="nguoisudung" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Ngày nhận</label>
                    <input type="date" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" name="ngaysd_nhan" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Số lượng <small class="text-gray-500">(Còn lại: <?php echo number_format($vattu['soluong_conlai'] ?? 0, 0); ?>)</small>
                    </label>
                    <input type="number" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" name="soluong" step="0.01" required max="<?php echo $vattu['soluong_conlai'] ?? 0; ?>">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Bộ phận</label>
                    <select class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" name="bophan">
                        <option value="">-- Chọn bộ phận --</option>
                        <?php foreach ($donViList as $dv): ?>
                        <option value="<?php echo htmlspecialchars($dv['madv']); ?>">
                            <?php echo htmlspecialchars($dv['tendv']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mục đích sử dụng</label>
                    <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" name="mucdich_sudung">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Ghi chú</label>
                    <textarea class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" name="ghichu" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="flex justify-end space-x-2 px-6 py-4 border-t bg-gray-50">
            <button class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded" onclick="closeAddChiTietModal()">Đóng</button>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded" onclick="submitAddChiTiet()">Lưu</button>
        </div>
    </div>
</div>

<script>
function showAddChiTietModal() {
    document.getElementById('addChiTietModal').classList.remove('hidden');
    document.getElementById('modalBackdrop').classList.remove('hidden');
}

function closeAddChiTietModal() {
    document.getElementById('addChiTietModal').classList.add('hidden');
    document.getElementById('modalBackdrop').classList.add('hidden');
}

function submitAddChiTiet() {
    const form = document.getElementById('addChiTietForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    fetch('/iso2/vattuthanhly.php?action=addChiTiet', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            alert('Thêm chi tiết thành công!');
            location.reload();
        } else {
            alert('Lỗi: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Có lỗi xảy ra!');
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
