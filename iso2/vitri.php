<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/VitriController.php';

requireAuth();

if (!hasPermission('vitri.view')) {
    http_response_code(403);
    die('Access Denied. You do not have permission to access this page.');
}

$controller = new VitriController();
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
