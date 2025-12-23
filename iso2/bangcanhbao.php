<?php
declare(strict_types=1);

// Session and Authentication
session_start();
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
requireAuth();

// Check permissions
if (!hasPermission('hieuchuan.view')) {
    header('Location: /iso2/thongke_kiemdinh.php?error=no_permission');
    exit;
}

// Load config and controller
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/BangCanhBaoController.php';

// Khởi tạo controller
$controller = new BangCanhBaoController();

// Lấy action từ URL
$action = $_GET['action'] ?? 'index';

// Route đến các action tương ứng
try {
    switch ($action) {
        case 'index':
        case 'canhbao':
            // Hiển thị bảng cảnh báo
            $controller->index();
            break;
            
        case 'formhoso':
        case 'hoso':
            // Form nhập/sửa hồ sơ HC - yêu cầu quyền create hoặc edit
            if (!hasPermission('hieuchuan.create') && !hasPermission('hieuchuan.edit')) {
                header('Location: /iso2/bangcanhbao.php?error=no_permission');
                exit;
            }
            $controller->formHoSo();
            break;
            
        case 'savehoso':
            // Lưu hồ sơ HC - yêu cầu quyền create hoặc edit
            if (!hasPermission('hieuchuan.create') && !hasPermission('hieuchuan.edit')) {
                header('Location: /iso2/bangcanhbao.php?error=no_permission');
                exit;
            }
            $controller->saveHoSo();
            break;
            
        case 'phieuyc':
        case 'phieuyeucau':
            // Danh sách phiếu yêu cầu
            $controller->phieuYeuCau();
            break;
            
        case 'phieukt':
        case 'hosokt':
            // Form phiếu kiểm tra - yêu cầu quyền edit
            if (!hasPermission('hieuchuan.edit')) {
                header('Location: /iso2/bangcanhbao.php?error=no_permission');
                exit;
            }
            $controller->phieuKiemTra();
            break;
            
        case 'savekt':
            // Lưu kết quả kiểm tra - yêu cầu quyền edit
            if (!hasPermission('hieuchuan.edit')) {
                header('Location: /iso2/bangcanhbao.php?error=no_permission');
                exit;
            }
            $controller->saveKiemTra();
            break;
            
        case 'api_generatesohs':
            // API tạo số hồ sơ tự động - yêu cầu quyền create
            if (!hasPermission('hieuchuan.create')) {
                http_response_code(403);
                echo json_encode(['error' => 'No permission']);
                exit;
            }
            $controller->apiGenerateSoHS();
            break;
            
        default:
            // Mặc định về trang index
            $controller->index();
            break;
    }
} catch (Exception $e) {
    error_log("Error in bangcanhbao.php: " . $e->getMessage());
    http_response_code(500);
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded m-4">';
    echo '<strong>Lỗi:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}
