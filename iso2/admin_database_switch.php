<?php
declare(strict_types=1);

// Bật hiển thị lỗi để debug
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {
    // constants.php sẽ tự động start session nếu cần
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/models/User.php';

    // Kiểm tra đăng nhập và quyền admin
    requireAuth();
    requireRole(ROLE_ADMIN);
} catch (Exception $e) {
    die("Lỗi khởi tạo: " . $e->getMessage() . "<br>File: " . $e->getFile() . "<br>Line: " . $e->getLine());
}

$selectionFile = __DIR__ . '/config/db_selection.php';
$currentSelection = 'production'; // Mặc định

// Đọc lựa chọn hiện tại
if (file_exists($selectionFile)) {
    $currentSelection = require $selectionFile;
}

// Xử lý chuyển đổi database
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_selection'])) {
    $newSelection = $_POST['db_selection'];
    
    if (in_array($newSelection, ['production', 'localhost'])) {
        $content = "<?php\n/**\n * File lưu lựa chọn database hiện tại\n * Admin có thể chuyển đổi giữa production và localhost\n */\n\n// Giá trị mặc định: 'production' hoặc 'localhost'\nreturn '{$newSelection}';\n";
        
        if (file_put_contents($selectionFile, $content)) {
            $currentSelection = $newSelection;
            $message = "Đã chuyển đổi sang database: " . ($newSelection === 'production' ? 'Production (diavatly.com)' : 'Localhost');
            $messageType = 'success';
            
            // Xóa cache nếu có
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
        } else {
            $message = "Không thể ghi file cấu hình. Kiểm tra quyền ghi thư mục config/";
            $messageType = 'error';
        }
    } else {
        $message = "Lựa chọn không hợp lệ!";
        $messageType = 'error';
    }
}

// Thông tin database
$databases = [
    'production' => [
        'name' => 'Production',
        'host' => 'diavatly.com',
        'database' => 'diavatly_db',
        'user' => 'diavatly_master'
    ],
    'localhost' => [
        'name' => 'Localhost',
        'host' => 'localhost',
        'database' => 'mapselli676e_iso2',
        'user' => 'mapselli676e_iso2'
    ]
];

$title = 'Chuyển đổi Database';
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <!-- Admin Navigation -->
        <div class="mb-6 pb-4 border-b">
            <div class="flex flex-wrap gap-2 items-center mb-4">
                <h1 class="text-2xl font-bold flex items-center mr-4">
                    <i class="fas fa-database mr-3 text-blue-600"></i>
                    Chuyển đổi Database
                </h1>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="/iso2/admin_user_permissions.php" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-users-cog mr-1"></i> Phân quyền User
                </a>
                <a href="/iso2/views/admin/permissions_manager.php" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-key mr-1"></i> Quản lý quyền
                </a>
                <a href="/iso2/views/admin/activity_logs.php" class="bg-purple-500 hover:bg-purple-600 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-history mr-1"></i> Nhật ký
                </a>
                <a href="/iso2/admin_backup.php" class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-download mr-1"></i> Backup
                </a>
                <a href="/iso2/hososcbd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Trang chủ
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded <?php echo $messageType === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-yellow-800">Cảnh báo quan trọng!</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p class="mb-2">Chuyển đổi database sẽ ảnh hưởng đến toàn bộ hệ thống:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Tất cả người dùng sẽ kết nối đến database mới</li>
                            <li>Dữ liệu hiển thị sẽ khác nhau giữa 2 database</li>
                            <li>Đề xuất tạo backup trước khi chuyển đổi</li>
                            <li>Chỉ chuyển khi cần thiết và đã thông báo người dùng</li>
                            <li><strong class="text-red-700">Database Localhost có thể thiếu tables → Phải kiểm tra trước!</strong></li>
                        </ul>
                        <div class="mt-3">
                            <a href="check_database_tables.php" class="inline-block bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded font-semibold">
                                <i class="fas fa-search mr-2"></i>Kiểm tra Tables Database
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Database hiện tại -->
        <div class="mb-6 p-4 <?php echo $currentSelection === 'localhost' ? 'bg-yellow-50 border-yellow-400' : 'bg-blue-50 border-blue-200'; ?> border rounded">
            <h3 class="font-bold text-lg mb-3 <?php echo $currentSelection === 'localhost' ? 'text-yellow-800' : 'text-blue-800'; ?>">
                <i class="fas fa-info-circle mr-2"></i>Database đang sử dụng
                <?php if ($currentSelection === 'localhost'): ?>
                    <span class="ml-2 bg-yellow-500 text-black text-sm px-3 py-1 rounded font-semibold">
                        <i class="fas fa-bug mr-1"></i>BẢN DEBUG
                    </span>
                <?php endif; ?>
            </h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-600 font-semibold">Tên:</span>
                    <span class="ml-2 text-gray-800 font-bold"><?php echo $databases[$currentSelection]['name']; ?></span>
                </div>
                <div>
                    <span class="text-gray-600 font-semibold">Host:</span>
                    <span class="ml-2 text-gray-800"><?php echo $databases[$currentSelection]['host']; ?></span>
                </div>
                <div>
                    <span class="text-gray-600 font-semibold">Database:</span>
                    <span class="ml-2 text-gray-800"><?php echo $databases[$currentSelection]['database']; ?></span>
                </div>
                <div>
                    <span class="text-gray-600 font-semibold">User:</span>
                    <span class="ml-2 text-gray-800"><?php echo $databases[$currentSelection]['user']; ?></span>
                </div>
            </div>
            
            <?php if ($currentSelection === 'localhost'): ?>
                <div class="mt-4 p-3 bg-red-100 border border-red-300 rounded">
                    <p class="text-sm text-red-700 font-semibold">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <strong>Lưu ý:</strong> Database localhost có thể thiếu tables cần thiết!
                    </p>
                    <p class="text-sm text-red-600 mt-1">
                        Nếu gặp lỗi khi truy cập các trang như <code>/hososcbd.php</code>, 
                        hãy kiểm tra database có đầy đủ tables chưa.
                    </p>
                    <div class="mt-2">
                        <a href="check_database_tables.php" class="inline-block bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm font-semibold">
                            <i class="fas fa-search mr-1"></i>Kiểm tra ngay
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Form chuyển đổi -->
        <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn chuyển đổi database?\n\nĐiều này sẽ ảnh hưởng đến tất cả người dùng đang sử dụng hệ thống!');">
            <div class="space-y-4">
                <h3 class="font-bold text-lg mb-3">
                    <i class="fas fa-exchange-alt mr-2"></i>Chọn database mới
                </h3>
                
                <?php foreach ($databases as $key => $db): ?>
                    <label class="flex items-start p-4 border rounded cursor-pointer hover:bg-gray-50 <?php echo $currentSelection === $key ? 'border-blue-500 bg-blue-50' : 'border-gray-300'; ?>">
                        <input type="radio" name="db_selection" value="<?php echo $key; ?>" 
                               <?php echo $currentSelection === $key ? 'checked' : ''; ?>
                               class="mt-1 mr-3">
                        <div class="flex-1">
                            <div class="font-bold text-lg text-gray-800">
                                <?php echo $db['name']; ?>
                                <?php if ($key === 'localhost'): ?>
                                    <span class="ml-2 text-sm bg-yellow-500 text-black px-2 py-1 rounded font-semibold">
                                        <i class="fas fa-bug mr-1"></i>BẢN DEBUG
                                    </span>
                                <?php endif; ?>
                                <?php if ($currentSelection === $key): ?>
                                    <span class="ml-2 text-sm bg-blue-500 text-white px-2 py-1 rounded">Đang sử dụng</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-sm text-gray-600 mt-2 space-y-1">
                                <div><strong>Host:</strong> <?php echo $db['host']; ?></div>
                                <div><strong>Database:</strong> <?php echo $db['database']; ?></div>
                                <div><strong>User:</strong> <?php echo $db['user']; ?></div>
                            </div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded font-semibold">
                    <i class="fas fa-sync-alt mr-2"></i>Chuyển đổi Database
                </button>
                <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded font-semibold">
                    <i class="fas fa-times mr-2"></i>Hủy
                </a>
            </div>
        </form>

        <!-- Hướng dẫn -->
        <div class="mt-8 p-4 bg-gray-50 border border-gray-200 rounded">
            <h3 class="font-bold text-lg mb-3">
                <i class="fas fa-question-circle mr-2"></i>Hướng dẫn sử dụng
            </h3>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700">
                <li>Chọn database muốn chuyển sang (Production hoặc Localhost)</li>
                <li>Nhấn nút "Chuyển đổi Database" và xác nhận</li>
                <li>Hệ thống sẽ lưu lựa chọn và áp dụng ngay lập tức</li>
                <li>Tất cả người dùng sẽ tự động kết nối đến database mới</li>
                <li>Không cần khởi động lại server</li>
            </ol>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
