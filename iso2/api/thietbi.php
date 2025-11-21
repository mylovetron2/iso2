<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $madv = $_GET['madv'] ?? '';
    
    if (empty($madv)) {
        echo json_encode(['success' => false, 'message' => 'Mã đơn vị không được để trống']);
        exit;
    }
    
    // Get distinct mavt + tenvt for this unit
    $sql = "SELECT DISTINCT mavt, tenvt, model 
            FROM thietbi_iso 
            WHERE madv = :madv 
            ORDER BY mavt ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':madv' => $madv]);
    
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $devices
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
