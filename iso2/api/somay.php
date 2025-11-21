<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $madv = $_GET['madv'] ?? '';
    $mavt = $_GET['mavt'] ?? '';
    
    if (empty($madv)) {
        echo json_encode(['success' => false, 'message' => 'Mã đơn vị không được để trống']);
        exit;
    }
    
    if (empty($mavt)) {
        echo json_encode(['success' => false, 'message' => 'Mã vật tư không được để trống']);
        exit;
    }
    
    // Get serial numbers for this device
    $sql = "SELECT DISTINCT somay, model, vitri 
            FROM thietbi_iso 
            WHERE madv = :madv 
              AND mavt = :mavt 
            ORDER BY somay ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':madv' => $madv,
        ':mavt' => $mavt
    ]);
    
    $serials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $serials
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
