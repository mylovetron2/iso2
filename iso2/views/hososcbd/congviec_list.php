<?php 
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// Get record ID from URL
$stt = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Capture filter params from URL to preserve them
$filterParams = [];
foreach (['search', 'madv', 'nhomsc', 'trangthai', 'page'] as $key) {
    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        $filterParams[$key] = $_GET[$key];
    }
}

// Build back URL with filters
$backParams = http_build_query($filterParams);
$backUrl = 'hososcbd.php' . ($backParams ? '?' . $backParams : '');

// Load models
require_once __DIR__ . '/../../models/HoSoSCBD.php';
$model = new HoSoSCBD();

// If no ID, redirect back
if (!$stt) {
    header("Location: $backUrl");
    exit;
}

// Load the record
$item = $model->findById($stt);
if (!$item) {
    header("Location: $backUrl");
    exit;
}

require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header with back button -->
    <div class="bg-white rounded-lg shadow-md p-4 md:p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-4">
                <a href="<?= $backUrl ?>" 
                   class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Quay lại danh sách
                </a>
                
                <h1 class="text-2xl font-bold text-purple-700">
                    <i class="fas fa-tasks mr-2"></i>
                    Công việc sửa chữa
                </h1>
            </div>
            
            <div class="flex items-center space-x-2">
                <a href="hososcbd_repair_details.php?id=<?= $stt ?><?= $backParams ? '&' . $backParams : '' ?>" 
                   class="inline-flex items-center px-3 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded text-sm transition-colors">
                    <i class="fas fa-wrench mr-1"></i>
                    Chi tiết sửa chữa
                </a>
                
                <?php /* Ẩn nút bàn giao
                <a href="hososcbd_handover_details.php?id=<?= $stt ?><?= $backParams ? '&' . $backParams : '' ?>" 
                   class="inline-flex items-center px-3 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded text-sm transition-colors">
                    <i class="fas fa-handshake mr-1"></i>
                    Bàn giao
                </a>
                */ ?>
            </div>
        </div>
        
        <!-- Hồ sơ info card -->
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border-l-4 border-purple-500 p-4 rounded">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <span class="text-sm text-gray-600">Phiếu:</span>
                    <span class="font-bold text-lg ml-2"><?= htmlspecialchars($item['phieu']) ?></span>
                </div>
                <div>
                    <span class="text-sm text-gray-600">Thiết bị:</span>
                    <span class="font-semibold ml-2"><?= htmlspecialchars($item['mavt']) ?></span>
                    <?php if (!empty($item['somay'])): ?>
                        <span class="text-gray-500"> - #<?= htmlspecialchars($item['somay']) ?></span>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="text-sm text-gray-600">Công việc:</span>
                    <span class="ml-2"><?= htmlspecialchars($item['cv']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Widget công việc -->
    <div class="bg-white rounded-lg shadow-md p-4 md:p-6">
        <?php include __DIR__ . '/components/congviec_widget.php'; ?>
    </div>
</div>

<!-- Choices.js for searchable select -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
