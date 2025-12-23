<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/ThietBiController.php';

requireAuth();

// Check permissions
if (!hasPermission('thietbi.view')) {
    header('Location: /iso2/thongke_kiemdinh.php?error=no_permission');
    exit;
}

$controller = new ThietBiController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'create':
        if (!hasPermission('thietbi.create')) {
            header('Location: /iso2/thietbi.php?error=permission_denied');
            exit;
        }
        $controller->create();
        break;

    case 'edit':
        if (!hasPermission('thietbi.edit')) {
            header('Location: /iso2/thietbi.php?error=permission_denied');
            exit;
        }
        $controller->edit();
        break;

    case 'delete':
        if (!hasPermission('thietbi.delete')) {
            header('Location: /iso2/thietbi.php?error=permission_denied');
            exit;
        }
        $controller->delete();
        break;

    default:
        $controller->index();
        break;
}
