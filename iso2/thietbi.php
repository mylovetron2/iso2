<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/ThietBiController.php';

requireAuth();

// Check permissions
if (!hasPermission('thietbi.view')) {
    header('Location: /iso2/thongke_kiemdinh.php?error=no_permission');
    exit;
}

$controller = new ThietBiController();
$action = $_GET['action'] ?? 'index';

// Support malformed direct links like action=create%C2%A9_from=... or action=create_from
if (is_string($action) && preg_match('/create.*from/i', $action) && !in_array($action, ['create', 'copy'], true)) {
    $action = 'create';
}

if (is_string($action) && preg_match('/^copy/i', $action)) {
    $action = 'create';
}

if (is_string($action) && strpos($action, 'create') !== false && strpos($action, 'from') !== false) {
    $action = 'create';
}

if (isset($_GET['copy_from'])) {
    $_GET['copy_from'] = (string)$_GET['copy_from'];
}

if (!isset($_GET['copy_from']) && isset($_GET['create_from'])) {
    $_GET['copy_from'] = $_GET['create_from'];
}

switch ($action) {
    case 'view':
        $controller->view();
        break;

    case 'create':
        if (!hasPermission('thietbi.create')) {
            header('Location: /iso2/thietbi.php?error=permission_denied');
            exit;
        }
        $controller->create();
        break;

    case 'edit':
        if (!hasPermission('thietbi.edit')) {
            header('Location: /iso2/thietbi.php?error=permission_denied');
            exit;
        }
        $controller->edit();
        break;

    case 'delete':
        if (!hasPermission('thietbi.delete')) {
            header('Location: /iso2/thietbi.php?error=permission_denied');
            exit;
        }
        $controller->delete();
        break;

    default:
        $controller->index();
        break;
}
