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
require_once __DIR__ . '/controllers/GiaoNhanThietBiController.php';

$controller = new GiaoNhanThietBiController();

// Xử lý action
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'index':
        // Kiểm tra quyền xem
        if (!hasPermission('giaonhanthietbi.view')) {
            $_SESSION['error'] = 'Bạn không có quyền xem danh sách giao nhận thiết bị';
            header('Location: /iso2/index.php');
            exit;
        }
        $controller->index();
        break;

    case 'create_giao_di':
        // Kiểm tra quyền tạo phiếu giao đi
        if (!hasPermission('giaonhanthietbi.create_giao')) {
            $_SESSION['error'] = 'Bạn không có quyền tạo phiếu giao thiết bị';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
        $controller->createGiaoDi();
        break;

    case 'store_giao_di':
        // Kiểm tra quyền tạo
        if (!hasPermission('giaonhanthietbi.create_giao')) {
            $_SESSION['error'] = 'Bạn không có quyền tạo phiếu giao thiết bị';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
        $controller->storeGiaoDi();
        break;

    case 'create_nhan_ve':
        // Kiểm tra quyền tạo phiếu nhận về
        if (!hasPermission('giaonhanthietbi.create_nhan')) {
            $_SESSION['error'] = 'Bạn không có quyền tạo phiếu nhận thiết bị';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
        $controller->createNhanVe();
        break;

    case 'store_nhan_ve':
        // Kiểm tra quyền tạo
        if (!hasPermission('giaonhanthietbi.create_nhan')) {
            $_SESSION['error'] = 'Bạn không có quyền tạo phiếu nhận thiết bị';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
        $controller->storeNhanVe();
        break;

    case 'view':
        // Kiểm tra quyền xem
        if (!hasPermission('giaonhanthietbi.view')) {
            $_SESSION['error'] = 'Bạn không có quyền xem chi tiết phiếu';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
        $controller->view();
        break;

    case 'delete':
        // Kiểm tra quyền xóa
        if (!hasPermission('giaonhanthietbi.delete')) {
            $_SESSION['error'] = 'Bạn không có quyền xóa phiếu';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
        $controller->delete();
        break;

    default:
        $_SESSION['error'] = 'Action không hợp lệ';
        header('Location: /iso2/index.php');
        exit;
}
