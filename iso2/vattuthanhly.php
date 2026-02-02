<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/VatTuThanhLyController.php';

requireAuth();

// Check permissions
if (!hasPermission('vattu.view')) {
    header('Location: /iso2/index.php?error=no_permission');
    exit;
}

$controller = new VatTuThanhLyController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'create':
        if (!hasPermission('vattu.create')) {
            header('Location: /iso2/vattuthanhly.php?error=permission_denied');
            exit;
        }
        $controller->create();
        break;

    case 'edit':
        if (!hasPermission('vattu.edit')) {
            header('Location: /iso2/vattuthanhly.php?error=permission_denied');
            exit;
        }
        $controller->edit();
        break;

    case 'delete':
        if (!hasPermission('vattu.delete')) {
            header('Location: /iso2/vattuthanhly.php?error=permission_denied');
            exit;
        }
        $controller->delete();
        break;
        
    case 'getChiTiet':
        $controller->getChiTiet();
        break;
        
    case 'addChiTiet':
        if (!hasPermission('vattu.edit')) {
            echo json_encode(['error' => 'No permission']);
            exit;
        }
        $controller->addChiTiet();
        break;
        
    case 'editChiTiet':
        if (!hasPermission('vattu.edit')) {
            echo json_encode(['error' => 'No permission']);
            exit;
        }
        $controller->editChiTiet();
        break;
        
    case 'deleteChiTiet':
        if (!hasPermission('vattu.delete')) {
            echo json_encode(['error' => 'No permission']);
            exit;
        }
        $controller->deleteChiTiet();
        break;

    default:
        $controller->index();
        break;
}
