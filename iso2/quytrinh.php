<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/controllers/QuyTrinhController.php';

requireAuth();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$controller = new QuyTrinhController();
$action = (string)($_GET['action'] ?? 'index');

switch ($action) {
    case 'view':
        $title = 'Chi tiết Quy trình';
        $controller->view();
        break;
    case 'pending':
        $title = 'Góp ý Quy trình cần xem xét';
        $controller->pending();
        break;
    case 'gopy':
        $controller->addGopY();
        break;
    case 'update_gopy':
        $controller->updateGopY();
        break;
    case 'delete_gopy':
        $controller->deleteGopY();
        break;
    case 'upload':
        $controller->upload();
        break;
    case 'update_meta':
        $controller->updateMeta();
        break;
    default:
        $title = 'Danh sách Quy trình';
        $controller->index();
}
