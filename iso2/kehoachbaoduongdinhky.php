<?php
declare(strict_types=1);

session_start();

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';

// Kiểm tra đăng nhập
requireAuth();

// Load controller
require_once __DIR__ . '/controllers/KeHoachBaoDuongDinhKyController.php';

$controller = new KeHoachBaoDuongDinhKyController();

// Xử lý action
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'index':
        // Kiểm tra quyền xem
        if (!hasPermission('kehoachbaoduong.view')) {
            $_SESSION['error'] = 'Bạn không có quyền xem kế hoạch bảo dưỡng';
            header('Location: /iso2/index.php');
            exit;
        }
        $controller->index();
        break;
        
    case 'import':
        // Kiểm tra quyền tạo
        if (!hasPermission('kehoachbaoduong.create')) {
            $_SESSION['error'] = 'Bạn không có quyền import kế hoạch bảo dưỡng';
            header('Location: /iso2/index.php');
            exit;
        }
        $controller->import();
        break;
        
    case 'processImport':
        // Kiểm tra quyền tạo
        if (!hasPermission('kehoachbaoduong.create')) {
            $_SESSION['error'] = 'Bạn không có quyền import kế hoạch bảo dưỡng';
            header('Location: /iso2/index.php');
            exit;
        }
        $controller->processImport();
        break;
        
    case 'exportExcel':
        // Kiểm tra quyền xem
        if (!hasPermission('kehoachbaoduong.view')) {
            $_SESSION['error'] = 'Bạn không có quyền xuất kế hoạch bảo dưỡng';
            header('Location: /iso2/index.php');
            exit;
        }
        $controller->exportExcel();
        break;
        
    case 'delete':
        // Kiểm tra quyền xóa
        if (!hasPermission('kehoachbaoduong.delete')) {
            $_SESSION['error'] = 'Bạn không có quyền xóa kế hoạch bảo dưỡng';
            header('Location: /iso2/index.php');
            exit;
        }
        $controller->delete();
        break;
        
    case 'updateHoanTat':
        // Kiểm tra quyền chỉnh sửa
        if (!hasPermission('kehoachbaoduong.edit')) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền cập nhật']);
            exit;
        }
        $controller->updateHoanTat();
        break;
        
    case 'updateMultipleHoanTat':
        // Kiểm tra quyền chỉnh sửa
        if (!hasPermission('kehoachbaoduong.edit')) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền cập nhật']);
            exit;
        }
        $controller->updateMultipleHoanTat();
        break;
        
    case 'updateThietbiId':
        // Kiểm tra quyền chỉnh sửa
        if (!hasPermission('kehoachbaoduong.edit')) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền cập nhật']);
            exit;
        }
        $controller->updateThietbiId();
        break;
        
    case 'updateDonViFields':
        // Kiểm tra quyền chỉnh sửa
        if (!hasPermission('kehoachbaoduong.edit')) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền cập nhật']);
            exit;
        }
        $controller->updateDonViFields();
        break;

    case 'getThietbiIsoList':
        // Kiểm tra quyền xem
        if (!hasPermission('kehoachbaoduong.view')) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xem']);
            exit;
        }
        $controller->getThietbiIsoList();
        break;

    case 'addThietbiToKeHoach':
        // Kiểm tra quyền tạo
        if (!hasPermission('kehoachbaoduong.create')) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thêm thiết bị']);
            exit;
        }
        $controller->addThietbiToKeHoach();
        break;
        
    case 'thongke':
        // Kiểm tra quyền xem
        if (!hasPermission('kehoachbaoduong.view')) {
            $_SESSION['error'] = 'Bạn không có quyền xem thống kê';
            header('Location: /iso2/index.php');
            exit;
        }
        $controller->thongke();
        break;
        
    case 'exportPdf':
        // Kiểm tra quyền xem
        if (!hasPermission('kehoachbaoduong.view')) {
            $_SESSION['error'] = 'Bạn không có quyền xuất báo cáo';
            header('Location: /iso2/index.php');
            exit;
        }
        $controller->exportPdf();
        break;
        
    default:
        $_SESSION['error'] = 'Action không hợp lệ';
        header('Location: /iso2/index.php');
        exit;
}
