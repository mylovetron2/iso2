<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/controllers/BieuMauController.php';

requireAuth();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$controller = new BieuMauController();
$action = (string)($_GET['action'] ?? 'index');

switch ($action) {
    case 'upload':
        $controller->upload();
        break;
    case 'create_folder':
        $controller->createFolder();
        break;
    case 'rename_folder':
        $controller->renameFolder();
        break;
    case 'delete_folder':
        $controller->deleteFolder();
        break;
    case 'rename':
        $controller->rename();
        break;
    case 'move':
        $controller->move();
        break;
    case 'delete':
        $controller->delete();
        break;
    default:
        $title = 'Biểu mẫu';
        $controller->index();
}
