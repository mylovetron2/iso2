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
require_once __DIR__ . '/controllers/GiaoNhanThietBiController.php';

$controller = new GiaoNhanThietBiController();

// Xử lý action
$action = $_GET['action'] ?? 'index';

/**
 * REFACTORED ROUTER
 * =================
 * WORKFLOW: 1 phiếu duy nhất với 3 bước
 * 
 * 1. CREATE/STORE: Tạo phiếu nhận từ đội (da_nhan)
 * 2. EDIT_GUI_KIEMDINH/UPDATE_GUI_KIEMDINH: Gửi đi kiểm định (dang_kiem_dinh)
 * 3. EDIT_GIAO_LAI/UPDATE_GIAO_LAI: Giao lại cho đội (da_giao)
 */

switch ($action) {
    // ============================================================
    // DANH SÁCH & XEM CHI TIẾT
    // ============================================================
    
    case 'index':
        if (!hasPermission('giaonhanthietbi.view')) {
            $_SESSION['error'] = 'Bạn không có quyền xem danh sách giao nhận thiết bị';
            header('Location: /iso2/index.php');
            exit;
        }
        $controller->index();
        break;

    case 'view':
        if (!hasPermission('giaonhanthietbi.view')) {
            $_SESSION['error'] = 'Bạn không có quyền xem chi tiết';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
        $controller->view();
        break;

    // ============================================================
    // BƯỚC 1: TẠO PHIẾU NHẬN TỪ ĐỘI (da_nhan)
    // ============================================================
    
    case 'create':
        if (!hasPermission('giaonhanthietbi.create')) {
            $_SESSION['error'] = 'Bạn không có quyền tạo phiếu';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
        $controller->create();
        break;

    case 'store':
        if (!hasPermission('giaonhanthietbi.create')) {
            $_SESSION['error'] = 'Bạn không có quyền tạo phiếu';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
        $controller->store();
        break;

    // ============================================================
    // BƯỚC 2: GỬI ĐI KIỂM ĐỊNH (dang_kiem_dinh)
    // ============================================================
    
    case 'editGuiKiemDinh':
        if (!hasPermission('giaonhanthietbi.edit')) {
            $_SESSION['error'] = 'Bạn không có quyền cập nhật';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
        $controller->editGuiKiemDinh();
        break;

    case 'updateGuiKiemDinh':
        if (!hasPermission('giaonhanthietbi.edit')) {
            $_SESSION['error'] = 'Bạn không có quyền cập nhật';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
        $controller->updateGuiKiemDinh();
        break;

    // ============================================================
    // BƯỚC 3: GIAO LẠI CHO ĐỘI (da_giao)
    // ============================================================
    
    case 'editGiaoLai':
        if (!hasPermission('giaonhanthietbi.edit')) {
            $_SESSION['error'] = 'Bạn không có quyền cập nhật';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
        $controller->editGiaoLai();
        break;

    case 'updateGiaoLai':
        if (!hasPermission('giaonhanthietbi.edit')) {
            $_SESSION['error'] = 'Bạn không có quyền cập nhật';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
        $controller->updateGiaoLai();
        break;

    // ============================================================
    // XÓA PHIẾU
    // ============================================================
    
    case 'delete':
        if (!hasPermission('giaonhanthietbi.delete')) {
            $_SESSION['error'] = 'Bạn không có quyền xóa';
            header('Location: /iso2/giaonhanthietbi.php');
            exit;
        }
        $controller->delete();
        break;

    // ============================================================
    // DEFAULT
    // ============================================================
    
    default:
        $_SESSION['error'] = 'Action không hợp lệ';
        header('Location: /iso2/giaonhanthietbi.php');
        exit;
}
