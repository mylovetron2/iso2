<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/PhieuYeuCauController.php';

requireAuth();
if (!hasRole(ROLE_ADMIN)) {
    http_response_code(403);
    exit('Bạn không có quyền quản lý mẫu in Phiếu yêu cầu dịch vụ.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$controller = new PhieuYeuCauController();
if (($_GET['action'] ?? 'index') === 'upload') {
    $controller->uploadWordTemplate();
    exit;
}

$controller->wordTemplate();