<?php
declare(strict_types=1);

/**
 * Router cho Giỏ hàng vật tư thanh lý
 * Xử lý các actions: index, add, update, delete, clear, getCount
 */

session_start();
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/permission_check.php';
require_once __DIR__ . '/controllers/GioHangController.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Lấy action từ query string
$action = $_GET['action'] ?? 'index';

try {
    $controller = new GioHangController();

    switch ($action) {
        case 'index':
            // Xem giỏ hàng
            checkPermission('giohang.view');
            $controller->index();
            break;

        case 'add':
            // Thêm vật tư vào giỏ (AJAX)
            checkPermission('giohang.add');
            $controller->add();
            break;

        case 'update':
            // Cập nhật số lượng (AJAX)
            checkPermission('giohang.edit');
            $controller->update();
            break;

        case 'updateByVattu':
            // Cập nhật số lượng theo vattu_stt (AJAX - dùng cho form create phiếu)
            checkPermission('giohang.edit');
            $controller->updateByVattu();
            break;

        case 'delete':
            // Xóa item khỏi giỏ (AJAX)
            checkPermission('giohang.delete');
            $controller->delete();
            break;

        case 'removeByVattu':
            // Xóa theo vattu_stt (AJAX - dùng cho form create phiếu)
            checkPermission('giohang.delete');
            $controller->removeByVattu();
            break;

        case 'clear':
            // Xóa toàn bộ giỏ hàng (AJAX)
            checkPermission('giohang.delete');
            $controller->clear();
            break;

        case 'getCount':
            // Lấy số lượng items (AJAX) - không cần permission
            $controller->getCount();
            break;

        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Action không tồn tại'
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("Error in giohang.php: " . $e->getMessage());
    
    // Nếu là AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } else {
        // Redirect về trang chủ nếu không phải AJAX
        $_SESSION['error'] = $e->getMessage();
        header('Location: index.php');
    }
    exit;
}
