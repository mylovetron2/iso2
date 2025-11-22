<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-file-invoice text-blue-600 mr-2"></i>
                    Bàn Giao Theo Phiếu YC
                </h1>
                <p class="text-gray-600 mt-1">Chọn phiếu yêu cầu để tạo phiếu bàn giao nhanh</p>
            </div>
            <?php if (hasPermission('phieubangiao.create')): ?>
            <a href="phieubangiao_phieuyc.php?action=select" 
               class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded font-semibold">
                <i class="fas fa-plus mr-2"></i>Tạo Phiếu BG Nhanh
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Thống kê -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-file-alt text-3xl text-blue-500 mr-4"></i>
                <div>
                    <p class="text-gray-600 text-sm">Tổng phiếu YC</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo count($phieuYCList); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-orange-50 border-l-4 border-orange-500 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-tools text-3xl text-orange-500 mr-4"></i>
                <div>
                    <p class="text-gray-600 text-sm">Thiết bị chưa BG</p>
                    <p class="text-2xl font-bold text-gray-800">
                        <?php echo array_sum(array_column($phieuYCList, 'chua_bangiao')); ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-3xl text-green-500 mr-4"></i>
                <div>
                    <p class="text-gray-600 text-sm">Thiết bị đã BG</p>
                    <p class="text-2xl font-bold text-gray-800">
                        <?php echo array_sum(array_column($phieuYCList, 'da_bangiao')); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Phiếu YC</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Tổng TB</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Đã BG</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Chưa BG</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Ngày sửa</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Tiến độ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($phieuYCList)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Không có phiếu yêu cầu nào có thiết bị chưa bàn giao</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($phieuYCList as $item): 
                            $percent = $item['tong_thietbi'] > 0 
                                ? round(($item['da_bangiao'] / $item['tong_thietbi']) * 100, 1) 
                                : 0;
                            $colorClass = $percent == 0 ? 'bg-red-500' : ($percent < 50 ? 'bg-orange-500' : 'bg-green-500');
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <span class="font-semibold text-blue-600"><?php echo htmlspecialchars($item['phieu']); ?></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="bg-gray-100 px-3 py-1 rounded-full text-sm font-medium">
                                    <?php echo $item['tong_thietbi']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-medium">
                                    <?php echo $item['da_bangiao']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-sm font-medium">
                                    <?php echo $item['chua_bangiao']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <?php if ($item['ngay_sua_som_nhat'] && $item['ngay_sua_som_nhat'] != '0000-00-00'): ?>
                                    <?php echo date('d/m/Y', strtotime($item['ngay_sua_som_nhat'])); ?>
                                    <?php if ($item['ngay_sua_som_nhat'] != $item['ngay_sua_muon_nhat']): ?>
                                        <br><small class="text-gray-400">→ <?php echo date('d/m/Y', strtotime($item['ngay_sua_muon_nhat'])); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-400">Chưa có</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center">
                                    <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="<?php echo $colorClass; ?> h-2 rounded-full" 
                                             style="width: <?php echo $percent; ?>%"></div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600"><?php echo $percent; ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Hướng dẫn -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mt-6 rounded">
        <div class="flex">
            <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
            <div>
                <h3 class="font-semibold text-blue-800 mb-1">Hướng dẫn sử dụng</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Danh sách hiển thị các <strong>phiếu yêu cầu</strong> có thiết bị chưa bàn giao (bg=0)</li>
                    <li>• Click <strong>"Tạo Phiếu BG Nhanh"</strong> để chọn phiếu YC và tạo phiếu bàn giao hàng loạt</li>
                    <li>• Mỗi phiếu YC sẽ tạo ra 1 phiếu bàn giao tương ứng</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
