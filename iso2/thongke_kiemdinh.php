<?php
declare(strict_types=1);

// Enable error reporting for debugging
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

try {
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/controllers/ThongKeKiemDinhController.php';

    // Check authentication
    if (!isLoggedIn()) {
        header('Location: views/auth/login.php');
        exit;
    }

    $controller = new ThongKeKiemDinhController();
    
    $action = $_GET['action'] ?? 'index';
    
    if ($action === 'export') {
        $controller->exportWord();
    } elseif ($action === 'exportpdf') {
        $controller->exportPdf();
    } else {
        $controller->index();
    }
} catch (Exception $e) {
    error_log("Error in thongke_kiemdinh.php: " . $e->getMessage());
    echo "<pre>Error: " . htmlspecialchars($e->getMessage()) . "\n\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString() . "</pre>";
    exit;
}
