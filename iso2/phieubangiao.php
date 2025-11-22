<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/PhieuBanGiaoController.php';

requireAuth();

if (!hasPermission('hososcbd.view')) {
    header('Location: /iso2/index.php?error=permission_denied');
    exit;
}

$controller = new PhieuBanGiaoController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'select':
        if (!hasPermission('hososcbd.create')) {
            header('Location: /iso2/phieubangiao.php?error=permission_denied');
            exit;
        }
        $controller->selectDevices();
        break;

    case 'confirm':
        if (!hasPermission('hososcbd.create')) {
            header('Location: /iso2/phieubangiao.php?error=permission_denied');
            exit;
        }
        $controller->confirmCreate();
        break;
        
    case 'view':
        $controller->view();
        break;

    case 'delete':
        if (!hasPermission('hososcbd.delete')) {
            header('Location: /iso2/phieubangiao.php?error=permission_denied');
            exit;
        }
        $controller->delete();
        break;

    default:
        $controller->index();
        break;
}
