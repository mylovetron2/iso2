<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config/constants.php';
require_once 'controllers/ProjectController.php';
requireAuth();
$action = isset($_GET['action']) ? $_GET['action'] : 'index';
$id = isset($_GET['id']) ? $_GET['id'] : null;
$controller = new ProjectController();
switch ($action) {
    case 'index':
        $controller->index();
        break;
    case 'create':
        $controller->create();
        break;
    case 'edit':
        if ($id) {
            $controller->edit($id);
        }
        break;
    case 'delete':
        if ($id) {
            $controller->delete($id);
        }
        break;
    default:
        $controller->index();
        break;
}
