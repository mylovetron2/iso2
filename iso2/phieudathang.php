<?php
declare(strict_types=1);

/**
 * Router cho Phiếu đặt hàng vật tư
 * Xử lý workflow: index, create, view, approve, receive, stock, cancel
 */

session_start();
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/permission_check.php';
require_once __DIR__ . '/controllers/PhieuDatHangController.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Lấy action từ query string
$action = $_GET['action'] ?? 'index';

try {
    $controller = new PhieuDatHangController();

    switch ($action) {
        case 'index':
            // Danh sách phiếu đặt hàng
            checkPermission('phieudathang.view');
            $controller->index();
            break;

        case 'create':
            // Tạo phiếu mới (GET: form với 2 steps)
            checkPermission('phieudathang.create');
            $controller->create();
            break;

        case 'store':
            // Lưu phiếu mới (POST only - từ form step 2)
            checkPermission('phieudathang.create');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }
            $controller->store();
            break;

        case 'view':
            // Xem chi tiết phiếu
            checkPermission('phieudathang.view');
            $controller->view();
            break;

        case 'approve':
            // Duyệt phiếu (POST only)
            checkPermission('phieudathang.approve');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }
            $controller->approve();
            break;

        case 'receive':
            // Nhận hàng (GET: form, POST: save)
            checkPermission('phieudathang.receive');
            $controller->receive();
            break;

        case 'stock':
            // Nhập kho (POST only)
            checkPermission('phieudathang.stock');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }
            $controller->stock();
            break;

        case 'cancel':
            // Hủy phiếu (POST only)
            checkPermission('phieudathang.cancel');
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }
            $controller->cancel();
            break;

        case 'export':
            // Xuất Excel (tương tự xuatphieudathang cũ)
            checkPermission('phieudathang.export');
            // TODO: Implement export method if needed
            throw new Exception('Chức năng đang phát triển');
            break;
            
        case 'exportExcel':
            // Xuất Excel theo mẫu specification
            checkPermission('phieudathang.view');
            $controller->exportExcel();
            break;

        default:
            throw new Exception('Action không tồn tại');
    }

} catch (Exception $e) {
    error_log("Error in phieudathang.php: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header('Location: vattuthanhly.php');
    exit;
}
