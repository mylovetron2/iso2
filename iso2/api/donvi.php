<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Handle POST request to create new unit
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $madv = trim($_POST['madv'] ?? '');
        $tendv = trim($_POST['tendv'] ?? '');
        
        if (empty($madv)) {
            echo json_encode(['success' => false, 'message' => 'Mã đơn vị không được để trống']);
            exit;
        }
        
        if (empty($tendv)) {
            echo json_encode(['success' => false, 'message' => 'Tên đơn vị không được để trống']);
            exit;
        }
        
        // Check if madv already exists
        $checkSql = "SELECT COUNT(*) FROM donvi_iso WHERE madv = :madv";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([':madv' => $madv]);
        
        if ($checkStmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Mã đơn vị đã tồn tại']);
            exit;
        }
        
        // Insert new unit
        $sql = "INSERT INTO donvi_iso (madv, tendv) VALUES (:madv, :tendv)";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([
            ':madv' => $madv,
            ':tendv' => $tendv
        ]);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Thêm đơn vị thành công',
                'data' => [
                    'madv' => $madv,
                    'tendv' => $tendv
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi thêm đơn vị']);
        }
        exit;
    }
    
    // Handle GET request to list all units
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT madv, tendv FROM donvi_iso ORDER BY tendv ASC";
        $stmt = $db->query($sql);
        $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $units
        ]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
