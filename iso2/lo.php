<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/LoController.php';

requireAuth();

// Kiểm tra quyền (tạm thời cho phép tất cả user đã login, sau này có thể thêm permission riêng)
// if (!hasPermission('lo.view')) {
//     header('Location: /iso2/index.php?error=permission_denied');
//     exit;
// }

$controller = new LoController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'create':
        $controller->create();
        break;
        
    case 'edit':
        $controller->edit();
        break;
        
    case 'delete':
        $controller->delete();
        break;
        
    default:
        $controller->index();
        break;
}
