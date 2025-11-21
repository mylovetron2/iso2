<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/DonViController.php';

requireAuth();

// Check permissions
if (!hasPermission('donvi.view')) {
    header('Location: /iso2/index.php?error=permission_denied');
    exit;
}

$controller = new DonViController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'create':
        if (!hasPermission('donvi.create')) {
            header('Location: /iso2/donvi.php?error=permission_denied');
            exit;
        }
        $controller->create();
        break;

    case 'edit':
        if (!hasPermission('donvi.edit')) {
            header('Location: /iso2/donvi.php?error=permission_denied');
            exit;
        }
        $controller->edit();
        break;

    case 'delete':
        if (!hasPermission('donvi.delete')) {
            header('Location: /iso2/donvi.php?error=permission_denied');
            exit;
        }
        $controller->delete();
        break;

    default:
        $controller->index();
        break;
}
