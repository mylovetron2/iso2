<?php
declare(strict_types=1);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/MoController.php';

requireAuth();

// Check permissions - temporarily disabled for debugging
/*
if (!hasPermission('mo.view')) {
    header('Location: /iso2/thongke_kiemdinh.php?error=no_permission');
    exit;
}
*/

$controller = new MoController();
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
