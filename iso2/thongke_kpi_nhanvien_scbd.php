<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/ThongKeKPINhanVienSCBDController.php';

requireAuth();

if (!hasPermission('hososcbd.view')) {
    http_response_code(403);
    die('Ban khong co quyen xem thong ke KPI nhan vien.');
}

$controller = new ThongKeKPINhanVienSCBDController();
$controller->index();
