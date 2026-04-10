<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    
    // Get all devices from thietbihckd_iso (for giao nhan thiet bi module)
    // Return field names that match what the view expects
    $sql = "SELECT stt as id, mavattu as mavt, tenthietbi as tenvt, somay 
            FROM thietbihckd_iso 
            ORDER BY mavattu ASC, somay ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
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
