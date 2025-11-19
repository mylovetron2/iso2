<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/constants.php';
require_once 'controllers/ThietBiHoTroController.php';

requireAuth();

$action = isset($_GET['action']) ? $_GET['action'] : 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$controller = new ThietBiHoTroController();

switch ($action) {
    case 'create':
        $controller->create();
        break;
    case 'edit':
        if ($id) {
            $controller->edit($id);
        } else {
            header('Location: thietbihotro.php');
        }
        break;
    case 'delete':
        if ($id) {
            $controller->delete($id);
        } else {
            header('Location: thietbihotro.php');
        }
        break;
    case 'view':
        if ($id) {
            $controller->view($id);
        } else {
            header('Location: thietbihotro.php');
        }
        break;
    default:
        $controller->index();
        break;
}
