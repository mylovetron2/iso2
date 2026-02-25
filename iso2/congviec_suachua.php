<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

// Check view permission
if (!hasPermission('congviec_suachua.view')) {
    header('Location: /iso2/index.php?error=no_permission');
    exit;
}

require_once __DIR__ . '/controllers/CongViecSuaChuaController.php';

$controller = new CongViecSuaChuaController();
$action = $_GET['action'] ?? 'index';

// Xử lý các action
switch ($action) {
    case 'create':
        if (!hasPermission('congviec_suachua.create')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Không có quyền tạo công việc']);
            exit;
        }
        $result = $controller->create();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;

    case 'update':
        if (!hasPermission('congviec_suachua.edit')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Không có quyền sửa công việc']);
            exit;
        }
        $result = $controller->update();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;

    case 'delete':
        if (!hasPermission('congviec_suachua.delete')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Không có quyền xóa công việc']);
            exit;
        }
        $result = $controller->delete();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;

    case 'check_gio':
        $result = $controller->checkGioConLai();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;

    case 'lichsu_thietbi':
        $result = $controller->getLichSuThietBi();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;

    default:
        // Hiển thị view
        $formData = $controller->getFormData();
        $nhanvienStt = isset($_GET['nhanvien_stt']) ? (int)$_GET['nhanvien_stt'] : null;
        $ngayLam = $_GET['ngay_lam'] ?? date('Y-m-d');
        
        $viewData = $controller->index();
        
        require_once __DIR__ . '/views/congviec/index.php';
        break;
}
