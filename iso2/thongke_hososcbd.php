<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

if (!isLoggedIn()) {
    header('Location: views/auth/login.php');
    exit;
}

$conn = getDBConnection();

require_once __DIR__ . '/controllers/ThongKeHoSoSCBDController.php';

$controller = new ThongKeHoSoSCBDController();
$action = $_GET['action'] ?? 'index';

try {
    switch ($action) {
        case 'exportpdf':
            $controller->exportPdf();
            break;
        case 'index':
        default:
            $controller->index();
            break;
    }
} catch (Exception $e) {
    echo '<div class="error">Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}
