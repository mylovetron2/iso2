<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method không hỗ trợ']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$stt = isset($input['stt']) ? (int)$input['stt'] : 0;
$kpi = isset($input['kpi_thietbi']) ? (float)$input['kpi_thietbi'] : -1;

if ($stt <= 0) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID hồ sơ']);
    exit;
}

if ($kpi < 0) {
    echo json_encode(['success' => false, 'message' => 'Giá trị KPI không hợp lệ']);
    exit;
}

try {
    $db = getDBConnection();
    $stmt = $db->prepare("UPDATE hososcbd_iso SET kpi_thietbi = :kpi WHERE stt = :stt");
    $stmt->execute([':kpi' => $kpi, ':stt' => $stt]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy hồ sơ']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Đã cập nhật KPI', 'kpi_thietbi' => $kpi]);
} catch (PDOException $e) {
    error_log('update kpi_thietbi error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}
