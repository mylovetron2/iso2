<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $conn = getDBConnection();
    
    // Get all lo
    $sql = "SELECT malo, tenlo FROM lo_iso ORDER BY malo ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $los = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $los
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
