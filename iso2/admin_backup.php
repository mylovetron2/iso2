<?php
declare(strict_types=1);

// Bật hiển thị lỗi để debug
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/models/User.php';
    require_once __DIR__ . '/includes/permissions.php';

    // Kiểm tra đăng nhập và quyền backup
    requireAuth();
    if (!hasRole(ROLE_ADMIN) && !hasPermission('backup.view')) {
        header('Location: ' . BASE_URL . '/hososcbd.php?error=no_permission');
        exit;
    }
} catch (Exception $e) {
    die("Lỗi khởi tạo: " . $e->getMessage() . "<br>File: " . $e->getFile() . "<br>Line: " . $e->getLine());
}

// Danh sách các bảng cần backup
$BACKUP_TABLES = [
    'danhmucvattu_iso',
    'donvi_iso',
    'giao_nhan_thietbi_iso',
    'hosohckd_iso',
    'hososcbd_iso',
    'kehoach_iso',
    'kehoach_kiemdinh_2026_iso',
    'ke_hoach_bao_duong_dinh_ky_iso',
    'kiemdinh_iso',
    'lichsudn_iso',
    'linhkien_iso',
    'link_iso',
    'lo_iso',
    'mo_iso',
    'ngthuchien_iso',
    'nhanvien_iso',
    'nhapxuat_iso',
    'nhatky_iso',
    'phanloai_vattu_thanh_ly_iso',
    'phieubangiao_iso',
    'phieubangiao_thietbi_iso',
    'phieu_kiem_soat_vattu_iso',
    'thietbihckd_iso',
    'thietbihotro_iso',
    'thietbi_iso',
    'vattu_thanh_ly_iso',
    'vattu_thanh_ly_lichsu_iso',
    'vattu_thanh_ly_sudung_iso',
    'vitri_iso',
    'resume',
    'users',
    'roles',
    'role_user'
];

// Xử lý backup
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    // Kiểm tra quyền tạo/download backup
    if (!hasRole(ROLE_ADMIN) && !hasPermission('backup.create') && !hasPermission('backup.download')) {
        $_SESSION['error'] = "Bạn không có quyền tạo backup!";
        header('Location: admin_backup.php');
        exit;
    }
    
    try {
        $db = getDBConnection();
        
        // Sử dụng danh sách bảng đã định nghĩa
        $tables = $BACKUP_TABLES;
        
        // Tạo nội dung SQL
        $sqlContent = "-- Database Backup\n";
        $sqlContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sqlContent .= "-- Database: " . DB_NAME . "\n";
        $sqlContent .= "-- Host: " . DB_HOST . "\n\n";
        $sqlContent .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sqlContent .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sqlContent .= "SET AUTOCOMMIT = 0;\n";
        $sqlContent .= "START TRANSACTION;\n\n";
        
        foreach ($tables as $table) {
            // Lấy cấu trúc bảng
            $stmt = $db->query("SHOW CREATE TABLE `{$table}`");
            $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $sqlContent .= "-- --------------------------------------------------------\n";
            $sqlContent .= "-- Table structure for table `{$table}`\n";
            $sqlContent .= "-- --------------------------------------------------------\n\n";
            $sqlContent .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sqlContent .= $createTable['Create Table'] . ";\n\n";
            
            // Lấy dữ liệu
            $stmt = $db->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $sqlContent .= "-- Dumping data for table `{$table}`\n\n";
                
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } elseif (is_int($value) || is_float($value)) {
                            // Số không cần quote
                            $values[] = $value;
                        } else {
                            // String cần quote
                            $values[] = $db->quote((string)$value);
                        }
                    }
                    
                    $columns = array_keys($row);
                    $columnList = '`' . implode('`, `', $columns) . '`';
                    $valueList = implode(', ', $values);
                    
                    $sqlContent .= "INSERT INTO `{$table}` ({$columnList}) VALUES ({$valueList});\n";
                }
                
                $sqlContent .= "\n";
            }
        }
        
        $sqlContent .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $sqlContent .= "COMMIT;\n";
        
        // Tạo tên file
        $filename = 'backup_' . DB_NAME . '_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Gửi file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sqlContent));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        echo $sqlContent;
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi khi backup: " . $e->getMessage();
        header('Location: admin_backup.php');
        exit;
    }
}

// Lấy thông tin database
$db = getDBConnection();
// Sử dụng danh sách bảng đã định nghĩa
$tables = $BACKUP_TABLES;
$totalTables = count($tables);

// Tính tổng kích thước
$totalSize = 0;
foreach ($tables as $table) {
    $stmt = $db->query("
        SELECT 
            (data_length + index_length) as size
        FROM information_schema.TABLES 
        WHERE table_schema = '" . DB_NAME . "' 
        AND table_name = '{$table}'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $totalSize += (int)$result['size'];
    }
}

// Lấy danh sách backup hiện có (nếu có thư mục backups)
$backupDir = __DIR__ . '/backups';
$existingBackups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $existingBackups[] = [
                'name' => $file,
                'size' => filesize($backupDir . '/' . $file),
                'date' => filemtime($backupDir . '/' . $file)
            ];
        }
    }
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

$title = 'Backup Database';
require_once __DIR__ . '/views/layouts/header.php';
?>

<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <!-- Admin Navigation -->
        <div class="mb-6 pb-4 border-b">
            <div class="flex flex-wrap gap-2 items-center mb-4">
                <h1 class="text-2xl font-bold flex items-center mr-4">
                    <i class="fas fa-database mr-3 text-blue-600"></i>
                    Backup Database
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
                <a href="/iso2/admin_database_switch.php" class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-database mr-1"></i> Chuyển DB
                </a>
                <a href="/iso2/hososcbd.php" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Trang chủ
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 rounded bg-green-100 border border-green-400 text-green-700">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 rounded bg-red-100 border border-red-400 text-red-700">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Thông tin Database -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Database</p>
                        <p class="text-xl font-bold text-blue-700"><?php echo htmlspecialchars(DB_NAME); ?></p>
                        <p class="text-xs text-gray-500 mt-1">Host: <?php echo htmlspecialchars(DB_HOST); ?></p>
                    </div>
                    <i class="fas fa-server text-3xl text-blue-300"></i>
                </div>
            </div>

            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Số bảng backup</p>
                        <p class="text-xl font-bold text-green-700"><?php echo $totalTables; ?></p>
                        <p class="text-xs text-gray-500 mt-1">Tables (selected)</p>
                    </div>
                    <i class="fas fa-table text-3xl text-green-300"></i>
                </div>
            </div>

            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Kích thước</p>
                        <p class="text-xl font-bold text-purple-700"><?php echo formatBytes($totalSize); ?></p>
                        <p class="text-xs text-gray-500 mt-1">Total Size</p>
                    </div>
                    <i class="fas fa-hdd text-3xl text-purple-300"></i>
                </div>
            </div>
        </div>

        <!-- Backup Actions -->
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-yellow-800">Lưu ý quan trọng</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Backup sẽ tạo file SQL chứa cấu trúc và dữ liệu của <?php echo $totalTables; ?> bảng đã chọn</li>
                            <li>Chỉ backup các bảng quan trọng của hệ thống ISO2</li>
                            <li>File backup có thể rất lớn nếu database có nhiều dữ liệu</li>
                            <li>Nên backup thường xuyên để đảm bảo an toàn dữ liệu</li>
                            <li>Lưu trữ file backup ở nơi an toàn, tránh mất dữ liệu</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mb-6">
            <a href="admin_backup.php?action=download" 
               class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-lg font-semibold shadow-lg hover:shadow-xl transition-all"
               onclick="return confirm('Bạn có chắc muốn tạo backup database này? Quá trình có thể mất vài phút.');">
                <i class="fas fa-download mr-3"></i>
                Tạo Backup & Tải xuống
            </a>
        </div>

        <!-- Danh sách bảng -->
        <div class="mt-8">
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                <i class="fas fa-list mr-2"></i>
                Danh sách bảng sẽ được backup (<?php echo $totalTables; ?> bảng)
            </h2>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                <?php foreach ($tables as $table): ?>
                    <div class="bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm">
                        <i class="fas fa-table text-gray-400 mr-2"></i>
                        <span class="font-mono"><?php echo htmlspecialchars($table); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Existing Backups (nếu có) -->
        <?php if (!empty($existingBackups)): ?>
        <div class="mt-8">
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                <i class="fas fa-history mr-2"></i>
                Các file backup đã lưu (<?php echo count($existingBackups); ?>)
            </h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 border text-left text-sm font-semibold">Tên file</th>
                            <th class="px-4 py-2 border text-left text-sm font-semibold">Kích thước</th>
                            <th class="px-4 py-2 border text-left text-sm font-semibold">Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($existingBackups as $backup): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border text-sm font-mono">
                                <i class="fas fa-file-code text-blue-500 mr-2"></i>
                                <?php echo htmlspecialchars($backup['name']); ?>
                            </td>
                            <td class="px-4 py-2 border text-sm">
                                <?php echo formatBytes($backup['size']); ?>
                            </td>
                            <td class="px-4 py-2 border text-sm">
                                <?php echo date('d/m/Y H:i:s', $backup['date']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/views/layouts/footer.php'; ?>
