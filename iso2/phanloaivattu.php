<?php
declare(strict_types=1);

// Enable error reporting for debugging
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/models/User.php';
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/permissions.php';
    require_once __DIR__ . '/controllers/PhanLoaiVatTuController.php';

    requireAuth();

    // Check permissions - sử dụng quyền vattu hoặc phanloai_vattu
    if (!hasPermission('vattu.view') && !hasPermission('phanloai_vattu.view')) {
        header('Location: /iso2/index.php?error=no_permission');
        exit;
    }

    $controller = new PhanLoaiVatTuController();
    $action = $_GET['action'] ?? 'index';

    switch ($action) {
        case 'create':
            if (!hasPermission('vattu.create') && !hasPermission('phanloai_vattu.create')) {
                header('Location: /iso2/phanloaivattu.php?error=permission_denied');
                exit;
            }
            $controller->create();
            break;

        case 'edit':
            if (!hasPermission('vattu.edit') && !hasPermission('phanloai_vattu.edit')) {
                header('Location: /iso2/phanloaivattu.php?error=permission_denied');
                exit;
            }
            $controller->edit();
            break;

        case 'delete':
            if (!hasPermission('vattu.delete') && !hasPermission('phanloai_vattu.delete')) {
                header('Location: /iso2/phanloaivattu.php?error=permission_denied');
                exit;
            }
            $controller->delete();
            break;

        default:
            $controller->index();
            break;
    }
} catch (Exception $e) {
    // Log error
    error_log("Error in phanloaivattu.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Display error
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Lỗi</title></head><body>";
    echo "<h1 style='color: red;'>Lỗi xảy ra</h1>";
    echo "<div style='background: #fee; padding: 20px; border: 2px solid red;'>";
    echo "<h2>Chi tiết lỗi:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<h3>Stack trace:</h3>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
    echo "<p><a href='test_phanloaivattu.php'>→ Chạy test để debug</a></p>";
    echo "<p><a href='setup_phanloai_vattu.php'>→ Setup lại bảng và quyền</a></p>";
    echo "</body></html>";
}
