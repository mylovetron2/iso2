<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();

if (!hasPermission('hososcbd.view')) {
    header('Location: /iso2/thongke_kiemdinh.php?error=no_permission');
    exit;
}

// Kiểm tra database connection và tables
try {
    require_once __DIR__ . '/controllers/HoSoScBdController.php';
    $controller = new HoSoScBdController();
} catch (PDOException $e) {
    // Database error - hiển thị trang lỗi thân thiện
    $title = 'Lỗi Database';
    require_once __DIR__ . '/views/layouts/header.php';
    
    // Kiểm tra xem đang dùng database nào
    $selectionFile = __DIR__ . '/config/db_selection.php';
    $currentSelection = 'production';
    if (file_exists($selectionFile)) {
        $currentSelection = require $selectionFile;
    }
    ?>
    <div class="max-w-4xl mx-auto p-6">
        <div class="bg-red-100 border-l-4 border-red-500 p-6 rounded">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-500 text-3xl"></i>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-red-700 mb-2">
                        Lỗi kết nối Database
                    </h1>
                    <p class="text-red-600 mb-4">
                        Không thể truy cập trang <strong>Hồ sơ SCBĐ</strong>. 
                        Database hiện tại có thể thiếu tables cần thiết.
                    </p>
                    
                    <?php if ($currentSelection === 'localhost'): ?>
                        <div class="bg-yellow-50 border border-yellow-300 rounded p-4 mb-4">
                            <p class="text-sm text-yellow-800 font-semibold mb-2">
                                <i class="fas fa-info-circle mr-2"></i>
                                Đang sử dụng: <strong>Database Localhost (Debug)</strong>
                            </p>
                            <p class="text-sm text-yellow-700">
                                Database localhost có thể chưa được đồng bộ với production, 
                                dẫn đến thiếu tables <code>hososcbd_iso</code>, <code>donvi_iso</code>, 
                                <code>thietbi_iso</code>, v.v.
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="bg-gray-50 border border-gray-300 rounded p-4 mb-4">
                        <p class="text-sm text-gray-700 font-semibold mb-1">Chi tiết lỗi:</p>
                        <p class="text-sm text-gray-600 font-mono">
                            <?php echo htmlspecialchars($e->getMessage()); ?>
                        </p>
                    </div>
                    
                    <div class="flex gap-3">
                        <a href="check_database_tables.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-semibold">
                            <i class="fas fa-search mr-2"></i>Kiểm tra Database Tables
                        </a>
                        
                        <?php if ($currentSelection === 'localhost'): ?>
                            <a href="admin_database_switch.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded font-semibold">
                                <i class="fas fa-exchange-alt mr-2"></i>Chuyển về Production
                            </a>
                        <?php endif; ?>
                        
                        <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded font-semibold">
                            <i class="fas fa-arrow-left mr-2"></i>Quay lại
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/views/layouts/footer.php';
    exit;
}

$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'view':
        require_once __DIR__ . '/views/hososcbd/view.php';
        break;
        
    case 'create':
        if (!hasPermission('hososcbd.create')) {
            header('Location: /iso2/hososcbd.php?error=permission_denied');
            exit;
        }
        $controller->create();
        break;

    case 'edit':
        if (!hasPermission('hososcbd.edit')) {
            header('Location: /iso2/hososcbd.php?error=permission_denied');
            exit;
        }
        $controller->edit();
        break;

    case 'delete':
        if (!hasPermission('hososcbd.delete')) {
            header('Location: /iso2/hososcbd.php?error=permission_denied');
            exit;
        }
        $controller->delete();
        break;

    case 'exportpdf':
        if (!hasPermission('hososcbd.view')) {
            header('Location: /iso2/hososcbd.php?error=permission_denied');
            exit;
        }
        $controller->exportPdf();
        break;

    case 'exportword':
        if (!hasPermission('hososcbd.view')) {
            header('Location: /iso2/hososcbd.php?error=permission_denied');
            exit;
        }
        $controller->exportWord();
        break;

    case 'exportphieusc':
        if (!hasPermission('hososcbd.view')) {
            header('Location: /iso2/hososcbd.php?error=permission_denied');
            exit;
        }
        $controller->exportPhieuSC();
        break;

    case 'exportlistpdf':
        if (!hasPermission('hososcbd.view')) {
            header('Location: /iso2/hososcbd.php?error=permission_denied');
            exit;
        }
        $controller->exportListPdf();
        break;

    case 'exportlistexcel':
        if (!hasPermission('hososcbd.view')) {
            header('Location: /iso2/hososcbd.php?error=permission_denied');
            exit;
        }
        $controller->exportListExcel();
        break;

    case 'ajax_stats':
        if (!hasPermission('hososcbd.view')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }
        $controller->ajaxStats();
        break;

    case 'ajax_bddk_hckd':
        if (!hasPermission('hososcbd.view')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }
        $controller->ajaxBddkHckd();
        break;

    default:
        $controller->index();
        break;
}
