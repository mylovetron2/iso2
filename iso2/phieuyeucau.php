<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/PhieuYeuCauController.php';

requireAuth();

// Kiểm tra quyền xem
if (!hasPermission('phieuyeucau.view')) {
    $_SESSION['error'] = 'Bạn không có quyền truy cập chức năng này';
    header('Location: /iso2/index.php');
    exit;
}

$controller = new PhieuYeuCauController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'view':
        // Xem chi tiết phiếu
        $controller->view();
        break;
        
    case 'create':
    case 'edit':
        // Đã ẩn chức năng tạo/sửa phiếu: tạo phiếu thực hiện ở hososcbd.php?action=create
        header('Location: /iso2/phieuyeucau.php');
        exit;

    case 'delete':
        // Xóa phiếu
        if (!hasPermission('phieuyeucau.delete')) {
            $_SESSION['error'] = 'Bạn không có quyền xóa phiếu';
            header('Location: /iso2/phieuyeucau.php');
            exit;
        }
        $controller->delete();
        break;

    case 'exportword':
        // Export Word
        $controller->exportWord();
        break;

    case 'exportworddetail':
        // Export Word Detail (danh sách thiết bị với BDDK)
        $controller->exportWordDetail();
        break;

    case 'exportpdf':
        // Export PDF
        $controller->exportPdf();
        break;

    default:
        // Mặc định: danh sách phiếu
        $controller->index();
        break;
}
