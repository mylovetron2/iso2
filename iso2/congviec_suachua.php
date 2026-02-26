<?php
declare(strict_types=1);

// Start output buffering to catch any unexpected output
ob_start();

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

// Helper function to check if request is AJAX
function isAjaxRequest(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Check authentication
if (!isLoggedIn()) {
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
        exit;
    }
    requireAuth();
}

// TODO: Uncomment sau khi chạy execute_add_congviec_permissions.php
// Check view permission
/*
if (!hasPermission('congviec_suachua.view')) {
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Không có quyền xem công việc']);
        exit;
    }
    header('Location: /iso2/index.php?error=no_permission');
    exit;
}
*/

require_once __DIR__ . '/controllers/CongViecSuaChuaController.php';

$controller = new CongViecSuaChuaController();
$action = $_GET['action'] ?? $_POST['action'] ?? 'index';

// Xử lý các action
try {
    // Debug logging
    error_log("CongViec Action: $action");
    error_log("POST data: " . print_r($_POST, true));
    
    switch ($action) {
        case 'create':
        case 'save': // Alias for create
            // TODO: Uncomment sau khi migration
            /*
            if (!hasPermission('congviec_suachua.create')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Không có quyền tạo công việc']);
                exit;
            }
            */
            $result = $controller->create();
            error_log("Create result: " . print_r($result, true));
            
            // Clean output buffer and send JSON
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;

        case 'get':
            // Get single work item for editing
            $stt = (int)($_GET['stt'] ?? 0);
            if (!$stt) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Thiếu STT công việc']);
                exit;
            }
            
            $result = $controller->get($stt);
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;

    case 'update':
        // TODO: Uncomment sau khi migration
        /*
        if (!hasPermission('congviec_suachua.edit')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Không có quyền sửa công việc']);
            exit;
        }
        */
        $result = $controller->update();
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;

    case 'delete':
        // TODO: Uncomment sau khi migration
        /*
        if (!hasPermission('congviec_suachua.delete')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Không có quyền xóa công việc']);
            exit;
        }
        */
        $result = $controller->delete();
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;

    case 'check_gio':
        $result = $controller->checkGioConLai();
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;

    case 'lichsu_thietbi':
        $result = $controller->getLichSuThietBi();
        ob_clean();
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
} catch (Exception $e) {
    // Nếu là AJAX request, trả về JSON error
    if (isAjaxRequest() || in_array($action, ['create', 'save', 'update', 'delete', 'check_gio', 'lichsu_thietbi'])) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
            'debug' => [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ]);
        exit;
    }
    throw $e; // Re-throw for non-AJAX requests
}
