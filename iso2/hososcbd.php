<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/HoSoScBdController.php';

requireAuth();

if (!hasPermission('hososcbd.view')) {
    header('Location: /iso2/index.php?error=permission_denied');
    exit;
}

$controller = new HoSoScBdController();
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

    case 'exportlistpdf':
        if (!hasPermission('hososcbd.view')) {
            header('Location: /iso2/hososcbd.php?error=permission_denied');
            exit;
        }
        $controller->exportListPdf();
        break;

    default:
        $controller->index();
        break;
}
