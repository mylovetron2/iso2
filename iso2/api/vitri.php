<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $conn = getDBConnection();
    
    // Get all positions
    $sql = "SELECT mavitri, tenvitri FROM vitri_iso ORDER BY tenvitri ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $positions
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
