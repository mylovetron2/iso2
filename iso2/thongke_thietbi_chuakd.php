<?php
declare(strict_types=1);

// Display errors for debugging
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Load dependencies
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Get database connection
$conn = getDBConnection();

// Load controller
require_once __DIR__ . '/controllers/ThongKeThietBiChuaKDController.php';

// Initialize controller
$controller = new ThongKeThietBiChuaKDController();

// Get action
$action = $_GET['action'] ?? 'index';

// Route to appropriate method
switch ($action) {
    case 'exportpdf':
        $controller->exportPdf();
        break;
    case 'index':
    default:
        $controller->index();
        break;
}
