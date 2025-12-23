<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    
    $madv = $_GET['madv'] ?? '';
    
    if (empty($madv)) {
        echo json_encode(['success' => false, 'message' => 'Mã đơn vị không được để trống']);
        exit;
    }
    
    // Get all devices (mavt + somay + model) for this unit
    $sql = "SELECT mavt, tenvt, somay, model, mamay 
            FROM thietbi_iso 
            WHERE madv = ? 
            ORDER BY mavt ASC, somay ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$madv]);
    
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
