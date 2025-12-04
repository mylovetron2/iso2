<?php
require_once __DIR__ . '/../../models/HoSoSCBD.php';

$pageTitle = "Thông tin bàn giao";
$message = '';
$messageType = '';
$record = null;

// Capture filter params from URL to preserve them
$filterParams = [];
foreach (['search', 'madv', 'nhomsc', 'trangthai', 'page'] as $key) {
    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        $filterParams[$key] = $_GET[$key];
    }
}

// Get record ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $message = 'Không tìm thấy mã hồ sơ';
    $messageType = 'error';
} else {
    $id = (int)$_GET['id'];
    $model = new HoSoSCBD();
    $record = $model->findById($id);
    
    if (!$record) {
        $message = 'Hồ sơ không tồn tại';
        $messageType = 'error';
    }
}

// Handle form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $record) {
    try {
        $updateData = [
            'bg' => isset($_POST['bg']) ? 1 : 0,
            'slbg' => $_POST['slbg'] ?? 0,
            'dong' => $_POST['dong'] ?? '',
            'ghichu' => $_POST['ghichu'] ?? '',
            'ghichufinal' => $_POST['ghichufinal'] ?? ''
        ];
        
        if ($model->update($record['stt'], $updateData) !== false) {
            // Build redirect URL with preserved filters
            $redirectUrl = '/iso2/hososcbd.php';
            // Get filter params from POST (hidden inputs) or from initial GET
            $postFilters = [];
            foreach (['search', 'madv', 'nhomsc', 'trangthai', 'page'] as $key) {
                if (isset($_POST['filter_' . $key]) && $_POST['filter_' . $key] !== '') {
                    $postFilters[$key] = $_POST['filter_' . $key];
                }
            }
            $params = !empty($postFilters) ? $postFilters : $filterParams;
            if (!empty($params)) {
                $redirectUrl .= '?' . http_build_query($params);
            }
            header("Location: $redirectUrl");
            exit;
        } else {
            $message = 'Có lỗi xảy ra khi cập nhật';
            $messageType = 'error';
        }
    } catch (Exception $e) {
        error_log("Error updating handover details: " . $e->getMessage());
        $message = 'Lỗi: ' . $e->getMessage();
        $messageType = 'error';
    }
}

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-handshake text-red-500 mr-2"></i>
                Thông tin bàn giao
            </h1>
            <?php
            // Build back URL with filter params
            $backUrl = 'hososcbd.php';
            if (!empty($filterParams)) {
                $backUrl .= '?' . http_build_query($filterParams);
            }
            ?>
            <a href="<?php echo htmlspecialchars($backUrl); ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách
            </a>
        </div>

        <?php if ($record): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-blue-50 p-4 rounded sticky top-0 z-10 shadow-md text-base md:text-lg">
            <div class="bg-indigo-100 p-3 rounded-lg border-l-4 border-indigo-500">
                <span class="text-indigo-700 font-semibold">Phiếu:</span>
                <span class="ml-2 font-bold text-indigo-900"><?php echo htmlspecialchars($record['phieu'] ?? ''); ?></span>
            </div>
            <div class="bg-green-100 p-3 rounded-lg border-l-4 border-green-500">
                <span class="text-green-700 font-semibold">Thiết bị:</span>
                <span class="ml-2 font-bold text-green-900"><?php echo htmlspecialchars($record['mavt'] ?? ''); ?> - <?php echo htmlspecialchars($record['tenvt'] ?? ''); ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
    <div class="mb-6 p-4 rounded border <?php echo $messageType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?>">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <?php if ($record): ?>
    <!-- Form -->
    <div class="bg-white shadow-md rounded-lg p-6">
        <form method="POST" class="space-y-6">
            <!-- Hidden inputs to preserve filters -->
            <?php foreach ($filterParams as $key => $value): ?>
                <input type="hidden" name="filter_<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
            <?php endforeach; ?>
            
            <!-- Handover Information -->
            <div class="border-l-4 border-red-500 pl-4">
                <h2 class="text-lg font-bold mb-4 text-red-700">
                    <i class="fas fa-info-circle mr-2"></i>Thông tin bàn giao
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Checkbox: Đã bàn giao -->
                    <div class="flex items-center">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="bg" value="1" 
                                   <?php echo (isset($record['bg']) && $record['bg'] == 1) ? 'checked' : ''; ?>
                                   class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring focus:ring-blue-200">
                            <span class="ml-2 text-gray-700 font-semibold">Đã bàn giao</span>
                        </label>
                    </div>

                    <!-- Số lần BG -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            Số lần BG
                        </label>
                        <input type="number" name="slbg" min="0" 
                               value="<?php echo isset($record['slbg']) ? $record['slbg'] : '0'; ?>"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    </div>

                    <!-- Dòng -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            Dòng
                        </label>
                        <input type="text" name="dong" 
                               value="<?php echo isset($record['dong']) ? htmlspecialchars($record['dong']) : ''; ?>"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500">
                    </div>
                </div>

                <!-- Ghi chú -->
                <div class="mt-4">
                    <label class="block text-gray-700 font-semibold mb-2">
                        Ghi chú
                    </label>
                    <textarea name="ghichu" rows="3" 
                              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($record['ghichu']) ? htmlspecialchars($record['ghichu']) : ''; ?></textarea>
                </div>

                <!-- Ghi chú cuối -->
                <div class="mt-4">
                    <label class="block text-gray-700 font-semibold mb-2">
                        Ghi chú cuối
                    </label>
                    <textarea name="ghichufinal" rows="3" 
                              class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-500"><?php echo isset($record['ghichufinal']) ? htmlspecialchars($record['ghichufinal']) : ''; ?></textarea>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-2 pt-4 border-t">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded font-semibold">
                    <i class="fas fa-save mr-2"></i>Lưu thông tin
                </button>
                <a href="<?php echo htmlspecialchars($backUrl); ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded font-semibold text-center">
                    <i class="fas fa-times mr-2"></i>Hủy
                </a>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="bg-white shadow-md rounded-lg p-6 text-center">
        <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl mb-4"></i>
        <p class="text-gray-600">Không thể tải thông tin hồ sơ. Vui lòng thử lại.</p>
        <a href="<?php echo htmlspecialchars($backUrl); ?>" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
            Quay lại danh sách
        </a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
