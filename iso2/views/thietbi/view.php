<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
$title = 'Chi tiết Thiết Bị';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold flex items-center">
            <i class="fas fa-cogs mr-2"></i> Chi tiết Thiết Bị
        </h1>
        <div class="flex gap-2 no-print">
            <a href="thietbi.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-base">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
            <button onclick="printLichSuSuaChua()" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-base">
                <i class="fas fa-print mr-1"></i> In Lịch sử SC/BD
            </button>
            <?php if (hasPermission('thietbi.edit')): ?>
            <a href="thietbi.php?action=edit&id=<?php echo $item['stt']; ?>" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-base">
                <i class="fas fa-edit mr-1"></i> Sửa
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Thông tin cơ bản -->
    <div class="bg-gray-50 rounded-lg p-4 mb-6">
        <h2 class="text-xl font-semibold mb-3 text-blue-700 border-b border-blue-700 pb-2">
            <i class="fas fa-info-circle mr-2"></i> Thông tin cơ bản
        </h2>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            <div class="border-l-2 border-blue-500 pl-3">
                <div class="text-sm text-gray-600">Mã vật tư</div>
                <div class="text-base font-semibold text-blue-700">
                    <?php echo htmlspecialchars($item['mavt'] ?? ''); ?>
                </div>
            </div>
            
            <div class="border-l-2 border-green-500 pl-3">
                <div class="text-sm text-gray-600">Tên thiết bị</div>
                <div class="text-base font-semibold">
                    <?php echo htmlspecialchars($item['tenvt'] ?? ''); ?>
                </div>
            </div>
            
            <div class="border-l-2 border-purple-500 pl-3">
                <div class="text-sm text-gray-600">Số máy</div>
                <div class="text-base font-semibold">
                    <?php echo htmlspecialchars($item['somay'] ?? ''); ?>
                </div>
            </div>
            
            <div class="border-l-2 border-red-500 pl-3">
                <div class="text-sm text-gray-600">Model</div>
                <div class="text-base font-semibold">
                    <?php echo htmlspecialchars($item['model'] ?? ''); ?>
                </div>
            </div>
            
            <div class="border-l-2 border-yellow-500 pl-3">
                <div class="text-sm text-gray-600">Hộp máy</div>
                <div class="text-base font-semibold">
                    <?php echo htmlspecialchars($item['homay'] ?? ''); ?>
                </div>
            </div>
            
            <div class="border-l-2 border-indigo-500 pl-3">
                <div class="text-sm text-gray-600">Mã máy</div>
                <div class="text-base font-semibold">
                    <?php echo htmlspecialchars($item['mamay'] ?? ''); ?>
                </div>
            </div>
            
            <div class="border-l-2 border-pink-500 pl-3">
                <div class="text-sm text-gray-600">Điện áp</div>
                <div class="text-base font-semibold">
                    <?php echo htmlspecialchars($item['dienap'] ?? ''); ?>
                </div>
            </div>
            
            <div class="border-l-2 border-teal-500 pl-3">
                <div class="text-sm text-gray-600">Loại dầu</div>
                <div class="text-base font-semibold">
                    <?php echo htmlspecialchars($item['loaidau'] ?? ''); ?>
                </div>
            </div>
            
            <div class="border-l-2 border-orange-500 pl-3">
                <div class="text-sm text-gray-600">Mức dầu</div>
                <div class="text-base font-semibold">
                    <?php echo htmlspecialchars($item['mucdau'] ?? ''); ?>
                </div>
            </div>
            
            <div class="border-l-2 border-cyan-500 pl-3">
                <div class="text-sm text-gray-600">Đơn vị</div>
                <div class="text-base font-semibold">
                    <?php 
                    if (isset($donVi) && $donVi) {
                        echo htmlspecialchars($donVi['tendv'] ?? $item['madv']);
                    } else {
                        echo htmlspecialchars($item['madv'] ?? '');
                    }
                    ?>
                </div>
            </div>
            
            <div class="border-l-2 border-lime-500 pl-3">
                <div class="text-sm text-gray-600">Ngày KTSD</div>
                <div class="text-base font-semibold">
                    <?php 
                    if (!empty($item['ngayktsd'])) {
                        echo date('d/m/Y', strtotime($item['ngayktsd']));
                    }
                    ?>
                </div>
            </div>
            
            <div class="border-l-2 border-gray-500 pl-3">
                <div class="text-sm text-gray-600">TG bảo dưỡng</div>
                <div class="text-base font-semibold">
                    <?php echo htmlspecialchars($item['bdtime'] ?? '0'); ?> tháng
                </div>
            </div>
        </div>
        
        <?php if (!empty($item['thongtincb']) || !empty($item['tlkt']) || !empty($item['hosomay'])): ?>
        <div class="mt-3 space-y-2">
            <?php if (!empty($item['thongtincb'])): ?>
            <details class="bg-blue-50 rounded border border-blue-200">
                <summary class="cursor-pointer p-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                    <i class="fas fa-info-circle mr-1"></i> Thông tin công bố
                </summary>
                <div class="p-2 text-sm border-t border-blue-200">
                    <?php echo nl2br(htmlspecialchars($item['thongtincb'])); ?>
                </div>
            </details>
            <?php endif; ?>
            
            <?php if (!empty($item['tlkt'])): ?>
            <details class="bg-green-50 rounded border border-green-200">
                <summary class="cursor-pointer p-2 text-sm font-semibold text-green-700 hover:bg-green-100">
                    <i class="fas fa-clipboard-check mr-1"></i> Tỷ lệ kiểm tra
                </summary>
                <div class="p-2 text-sm border-t border-green-200">
                    <?php echo nl2br(htmlspecialchars($item['tlkt'])); ?>
                </div>
            </details>
            <?php endif; ?>
            
            <?php if (!empty($item['hosomay'])): ?>
            <details class="bg-yellow-50 rounded border border-yellow-200">
                <summary class="cursor-pointer p-2 text-sm font-semibold text-yellow-700 hover:bg-yellow-100">
                    <i class="fas fa-folder mr-1"></i> Hồ sơ máy
                </summary>
                <div class="p-2 text-sm border-t border-yellow-200">
                    <?php echo nl2br(htmlspecialchars($item['hosomay'])); ?>
                </div>
            </details>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tab navigation -->
    <div class="mb-6 no-print">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button onclick="showTab('suachua')" 
                        id="tab-suachua"
                        class="tab-button border-b-2 border-blue-500 text-blue-600 py-4 px-1 text-base font-medium">
                    <i class="fas fa-tools mr-2"></i>
                    Lịch sử Sửa chữa/Bảo dưỡng
                    <span class="ml-2 bg-blue-100 text-blue-600 py-1 px-2 rounded-full text-sm">
                        <?php echo count($lichSuSuaChua); ?>
                    </span>
                </button>
                
                <button onclick="showTab('kiemdinh')" 
                        id="tab-kiemdinh"
                        class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1 text-base font-medium">
                    <i class="fas fa-clipboard-check mr-2"></i>
                    Lịch sử Kiểm định
                    <span class="ml-2 bg-gray-100 text-gray-600 py-1 px-2 rounded-full text-sm">
                        <?php echo count($lichSuKiemDinh); ?>
                    </span>
                </button>
                
                <button onclick="showTab('bangiao')" 
                        id="tab-bangiao"
                        class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1 text-base font-medium">
                    <i class="fas fa-exchange-alt mr-2"></i>
                    Lịch sử Bàn giao
                    <span class="ml-2 bg-gray-100 text-gray-600 py-1 px-2 rounded-full text-sm">
                        <?php echo count($lichSuBanGiao); ?>
                    </span>
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab content: Lịch sử Sửa chữa -->
    <div id="content-suachua" class="tab-content">
        <h3 class="text-xl font-semibold mb-4 text-blue-700">
            <i class="fas fa-tools mr-2"></i> Lịch sử Sửa chữa/Bảo dưỡng
        </h3>
        
        <?php if (empty($lichSuSuaChua)): ?>
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-inbox text-6xl mb-4"></i>
                <p class="text-xl">Chưa có lịch sử sửa chữa/bảo dưỡng</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($lichSuSuaChua as $index => $ls): ?>
                <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center">
                            <div class="bg-blue-100 text-blue-700 rounded-full w-12 h-12 flex items-center justify-center font-bold text-lg mr-3">
                                <?php echo $index + 1; ?>
                            </div>
                            <div>
                                <div class="text-xl font-semibold text-blue-700">
                                    <?php echo $ls['ngaykt'] ? date('d/m/Y', strtotime($ls['ngaykt'])) : 'N/A'; ?>
                                </div>
                                <div class="text-base text-gray-600">
                                    <?php if (!empty($ls['phieu'])): ?>
                                        Phiếu: <span class="font-semibold"><?php echo htmlspecialchars($ls['phieu']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($ls['hoso'])): ?>
                                        | Hồ sơ: <span class="font-semibold"><?php echo htmlspecialchars($ls['hoso']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <a href="hososcbd.php?action=view&id=<?php echo $ls['stt'] ?? ''; ?>" 
                           class="text-blue-600 hover:text-blue-800 text-base"
                           target="_blank">
                            <i class="fas fa-external-link-alt mr-1"></i> Xem chi tiết
                        </a>
                    </div>
                    
                    <?php if (!empty($ls['honghoc'])): ?>
                    <div class="mb-3 p-3 bg-red-50 border-l-4 border-red-500 rounded">
                        <div class="text-base font-semibold text-red-700 mb-1">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Hỏng hóc:
                        </div>
                        <div class="text-base text-gray-700">
                            <?php 
                            $text = strip_tags($ls['honghoc']); 
                            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'); 
                            echo nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')); 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($ls['khacphuc'])): ?>
                    <div class="mb-3 p-3 bg-green-50 border-l-4 border-green-500 rounded">
                        <div class="text-base font-semibold text-green-700 mb-1">
                            <i class="fas fa-check-circle mr-1"></i> Khắc phục:
                        </div>
                        <div class="text-base text-gray-700">
                            <?php 
                            $text = strip_tags($ls['khacphuc']); 
                            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'); 
                            echo nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')); 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($ls['noidung'])): ?>
                    <div class="p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                        <div class="text-base font-semibold text-blue-700 mb-1">
                            <i class="fas fa-file-alt mr-1"></i> Nội dung:
                        </div>
                        <div class="text-base text-gray-700">
                            <?php 
                            $text = strip_tags($ls['noidung']); 
                            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'); 
                            echo nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')); 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tab content: Lịch sử Kiểm định -->
    <div id="content-kiemdinh" class="tab-content hidden">
        <h3 class="text-xl font-semibold mb-4 text-green-700">
            <i class="fas fa-clipboard-check mr-2"></i> Lịch sử Kiểm định
        </h3>
        
        <?php if (empty($lichSuKiemDinh)): ?>
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-inbox text-6xl mb-4"></i>
                <p class="text-xl">Chưa có lịch sử kiểm định</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($lichSuKiemDinh as $index => $ld): ?>
                <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center">
                            <div class="bg-green-100 text-green-700 rounded-full w-12 h-12 flex items-center justify-center font-bold text-lg mr-3">
                                <?php echo $index + 1; ?>
                            </div>
                            <div>
                                <div class="text-xl font-semibold text-green-700">
                                    <?php echo $ld['ngaykt'] ? date('d/m/Y', strtotime($ld['ngaykt'])) : 'N/A'; ?>
                                </div>
                                <div class="text-base text-gray-600">
                                    <?php if (!empty($ld['phieu'])): ?>
                                        Phiếu: <span class="font-semibold"><?php echo htmlspecialchars($ld['phieu']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($ld['hoso'])): ?>
                                        | Hồ sơ: <span class="font-semibold"><?php echo htmlspecialchars($ld['hoso']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <a href="hososcbd.php?action=view&id=<?php echo $ld['stt'] ?? ''; ?>" 
                           class="text-green-600 hover:text-green-800 text-base"
                           target="_blank">
                            <i class="fas fa-external-link-alt mr-1"></i> Xem chi tiết
                        </a>
                    </div>
                    
                    <?php if (!empty($ld['honghoc'])): ?>
                    <div class="mb-3 p-3 bg-red-50 border-l-4 border-red-500 rounded">
                        <div class="text-base font-semibold text-red-700 mb-1">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Phát hiện:
                        </div>
                        <div class="text-base text-gray-700">
                            <?php 
                            $text = strip_tags($ld['honghoc']); 
                            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'); 
                            echo nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')); 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($ld['khacphuc'])): ?>
                    <div class="mb-3 p-3 bg-green-50 border-l-4 border-green-500 rounded">
                        <div class="text-base font-semibold text-green-700 mb-1">
                            <i class="fas fa-check-circle mr-1"></i> Xử lý:
                        </div>
                        <div class="text-base text-gray-700">
                            <?php 
                            $text = strip_tags($ld['khacphuc']); 
                            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'); 
                            echo nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')); 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($ld['noidung'])): ?>
                    <div class="p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                        <div class="text-base font-semibold text-blue-700 mb-1">
                            <i class="fas fa-file-alt mr-1"></i> Nội dung:
                        </div>
                        <div class="text-base text-gray-700">
                            <?php 
                            $text = strip_tags($ld['noidung']); 
                            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'); 
                            echo nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')); 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tab content: Lịch sử Bàn giao -->
    <div id="content-bangiao" class="tab-content hidden">
        <h3 class="text-xl font-semibold mb-4 text-purple-700">
            <i class="fas fa-exchange-alt mr-2"></i> Lịch sử Bàn giao
        </h3>
        
        <?php if (empty($lichSuBanGiao)): ?>
            <div class="text-center py-12 text-gray-500">
                <i class="fas fa-inbox text-6xl mb-4"></i>
                <p class="text-xl">Chưa có lịch sử bàn giao</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($lichSuBanGiao as $index => $bg): ?>
                <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center">
                            <div class="bg-purple-100 text-purple-700 rounded-full w-12 h-12 flex items-center justify-center font-bold text-lg mr-3">
                                <?php echo $index + 1; ?>
                            </div>
                            <div>
                                <div class="text-xl font-semibold text-purple-700">
                                    <?php echo $bg['ngaybangiao'] ? date('d/m/Y', strtotime($bg['ngaybangiao'])) : 'N/A'; ?>
                                </div>
                                <div class="text-base text-gray-600">
                                    Số phiếu: <span class="font-semibold"><?php echo htmlspecialchars($bg['sophieu'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>
                        <a href="phieubangiao.php?action=view&id=<?php echo $bg['sophieu'] ?? ''; ?>" 
                           class="text-purple-600 hover:text-purple-800 text-base"
                           target="_blank">
                            <i class="fas fa-external-link-alt mr-1"></i> Xem chi tiết
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                        <div class="p-3 bg-orange-50 border-l-4 border-orange-500 rounded">
                            <div class="text-base font-semibold text-orange-700 mb-1">
                                <i class="fas fa-user-tie mr-1"></i> Người giao:
                            </div>
                            <div class="text-base text-gray-700 font-medium">
                                <?php echo htmlspecialchars($bg['nguoigiao'] ?? 'N/A'); ?>
                            </div>
                        </div>
                        
                        <div class="p-3 bg-teal-50 border-l-4 border-teal-500 rounded">
                            <div class="text-base font-semibold text-teal-700 mb-1">
                                <i class="fas fa-user-check mr-1"></i> Người nhận:
                            </div>
                            <div class="text-base text-gray-700 font-medium">
                                <?php echo htmlspecialchars($bg['nguoinhan'] ?? 'N/A'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($bg['donvi_nhan'])): ?>
                    <div class="mb-3 p-3 bg-indigo-50 border-l-4 border-indigo-500 rounded">
                        <div class="text-base font-semibold text-indigo-700 mb-1">
                            <i class="fas fa-building mr-1"></i> Đơn vị nhận:
                        </div>
                        <div class="text-base text-gray-700 font-medium">
                            <?php echo htmlspecialchars($bg['donvi_nhan']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($bg['noidung'])): ?>
                    <div class="mb-3 p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                        <div class="text-base font-semibold text-blue-700 mb-1">
                            <i class="fas fa-file-alt mr-1"></i> Nội dung:
                        </div>
                        <div class="text-base text-gray-700">
                            <?php echo nl2br(htmlspecialchars($bg['noidung'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($bg['ghichu'])): ?>
                    <div class="p-3 bg-yellow-50 border-l-4 border-yellow-500 rounded">
                        <div class="text-base font-semibold text-yellow-700 mb-1">
                            <i class="fas fa-sticky-note mr-1"></i> Ghi chú:
                        </div>
                        <div class="text-base text-gray-700">
                            <?php echo nl2br(htmlspecialchars($bg['ghichu'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active styling from all buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    // Add active styling to selected button
    const activeButton = document.getElementById('tab-' + tabName);
    activeButton.classList.remove('border-transparent', 'text-gray-500');
    activeButton.classList.add('border-blue-500', 'text-blue-600');
}

function printLichSuSuaChua() {
    showTab('suachua');
    window.print();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
