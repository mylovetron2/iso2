<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/controllers/UserProfileController.php';

$controller = new UserProfileController();

$action = $_GET['action'] ?? 'view';

switch ($action) {
    case 'view':
        $controller->view();
        break;
        
    case 'edit':
        $controller->edit();
        break;
        
    case 'update':
        $controller->update();
        break;
        
    case 'change_password':
        $controller->changePassword();
        break;
        
    default:
        $controller->view();
        break;
}
