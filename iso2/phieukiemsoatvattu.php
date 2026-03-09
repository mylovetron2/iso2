<?php
declare(strict_types=1);

session_start();

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';

// Kiểm tra đăng nhập
requireAuth();

// Load controller
require_once __DIR__ . '/controllers/PhieuKiemSoatVatTuController.php';

$controller = new PhieuKiemSoatVatTuController();

// Xử lý action
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'index':
        $controller->index();
        break;
        
    case 'create':
        // Kiểm tra quyền tạo
        if (!hasPermission('phieukiemsoatvattu.create')) {
            $_SESSION['error'] = 'Bạn không có quyền tạo phiếu kiểm soát vật tư';
            header('Location: /iso2/index.php');
            exit;
        }
        $controller->create();
        break;
        
    case 'store':
        // Kiểm tra quyền tạo
        if (!hasPermission('phieukiemsoatvattu.create')) {
            $_SESSION['error'] = 'Bạn không có quyền tạo phiếu kiểm soát vật tư';
            header('Location: /iso2/index.php');
            exit;
        }
        $controller->store();
        break;
        
    case 'view':
        // Kiểm tra quyền xem
        if (!hasPermission('phieukiemsoatvattu.view')) {
            $_SESSION['error'] = 'Bạn không có quyền xem phiếu kiểm soát vật tư';
            header('Location: /iso2/index.php');
            exit;
        }
        $controller->view();
        break;
        
    case 'cancel':
        // Kiểm tra quyền sửa
        if (!hasPermission('phieukiemsoatvattu.edit')) {
            $_SESSION['error'] = 'Bạn không có quyền hủy phiếu kiểm soát vật tư';
            header('Location: /iso2/index.php');
            exit;
        }
        $controller->cancel();
        break;
        
    case 'export_word':
        // Kiểm tra quyền xem
        if (!hasPermission('phieukiemsoatvattu.view')) {
            $_SESSION['error'] = 'Bạn không có quyền xuất phiếu kiểm soát vật tư';
            header('Location: /iso2/index.php');
            exit;
        }
        $controller->exportWord();
        break;
        
    default:
        $_SESSION['error'] = 'Action không hợp lệ';
        header('Location: /iso2/index.php');
        exit;
}
