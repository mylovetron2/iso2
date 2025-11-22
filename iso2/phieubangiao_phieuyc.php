<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/controllers/PhieuBanGiaoPhieuYCController.php';

// Kiểm tra đăng nhập
requireLogin();

// Kiểm tra quyền
if (!hasPermission('phieubangiao.view')) {
    $_SESSION['error'] = 'Bạn không có quyền truy cập chức năng này';
    header('Location: /iso2/index.php');
    exit;
}

$controller = new PhieuBanGiaoPhieuYCController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'select':
        // Bước 1: Chọn phiếu YC
        if (!hasPermission('phieubangiao.create')) {
            $_SESSION['error'] = 'Bạn không có quyền tạo phiếu bàn giao';
            header('Location: /iso2/phieubangiao_phieuyc.php');
            exit;
        }
        $controller->selectPhieuYC();
        break;

    case 'confirm':
        // Bước 2: Xác nhận và tạo phiếu BG
        if (!hasPermission('phieubangiao.create')) {
            $_SESSION['error'] = 'Bạn không có quyền tạo phiếu bàn giao';
            header('Location: /iso2/phieubangiao_phieuyc.php');
            exit;
        }
        $controller->confirmCreate();
        break;

    case 'index':
    default:
        // Hiển thị danh sách phiếu YC có thiết bị chưa bàn giao
        $controller->index();
        break;
}
