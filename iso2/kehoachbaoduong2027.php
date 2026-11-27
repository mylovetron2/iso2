<?php
declare(strict_types=1);

session_start();

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';

requireAuth();

require_once __DIR__ . '/controllers/KeHoachBaoDuongNamTiepController.php';

$controller = new KeHoachBaoDuongNamTiepController();
$action = $_GET['action'] ?? 'index';

function denyJson(string $message): void
{
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($action) {
    case 'index':
        if (!hasPermission('kehoachbaoduong.view')) {
            http_response_code(403);
            exit('Bạn không có quyền xem kế hoạch bảo dưỡng.');
        }
        $controller->index();
        break;

    case 'addRow':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        if (!hasPermission('kehoachbaoduong.create') && !hasPermission('kehoachbaoduong.edit')) {
            denyJson('Bạn không có quyền thêm thiết bị');
        }
        $controller->addRow();
        break;

    case 'deleteRow':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        if (($_SESSION['username'] ?? '') !== 'admin') {
            denyJson('Chỉ tài khoản admin mới có quyền xóa kế hoạch');
        }
        $controller->deleteRow();
        break;

    case 'saveQuarter':
    case 'saveDonVi':
    case 'saveGhiChu':
    case 'applyDefaults':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        if (!hasPermission('kehoachbaoduong.edit')) {
            denyJson('Bạn không có quyền cập nhật');
        }
        $controller->{$action}();
        break;

    default:
        $_SESSION['error'] = 'Action không hợp lệ';
        header('Location: /iso2/index.php');
        exit;
}
