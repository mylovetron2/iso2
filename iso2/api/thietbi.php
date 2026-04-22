<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    
    $madv = $_GET['madv'] ?? '';
    $phieu = $_GET['phieu'] ?? ''; // Số phiếu hiện tại (nếu đang thêm vào phiếu có sẵn)
    $excludeStt = $_GET['excludeStt'] ?? ''; // STT cần loại trừ (khi edit)
    
    // Get devices from thietbi_iso (for hososcbd module)
    // Check if device is available based on:
    // 1. If phieu is provided: device is NOT available if already in same phieu (any bg status)
    // 2. Device is NOT available if in other phieu with bg=0 (not delivered yet)
    // 3. If excludeStt is provided: exclude that record from check (when editing)
    
    if ($phieu) {
        // Có số phiếu: kiểm tra cả trong phiếu này và phiếu khác
        $excludeCondition = '';
        $params = [];
        
        if ($excludeStt) {
            $excludeCondition = ' AND h.stt != ?';
            $params[] = (int)$excludeStt;
        }
        
        // Add phieu params
        $params[] = $phieu;
        $params[] = $phieu;
        
        $sql = "SELECT t.stt as id, t.mavt, t.tenvt, t.somay, t.model,
                       CASE 
                           WHEN EXISTS (
                               SELECT 1 FROM hososcbd_iso h 
                               WHERE h.mavt = t.mavt 
                                 AND h.somay = t.somay 
                                 {$excludeCondition}
                                 AND (
                                     -- Đã tồn tại trong chính phiếu này (bất kể bg)
                                     h.phieu = ?
                                     OR
                                     -- Hoặc đang ở phiếu khác và chưa bàn giao
                                     (h.phieu != ? AND h.bg = 0)
                                 )
                           ) THEN 0
                           ELSE 1
                       END as is_available
                FROM thietbi_iso t";
        
        if ($madv) {
            $sql .= " WHERE t.madv = ?";
            $params[] = $madv;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    } else {
        // Không có số phiếu: chỉ kiểm tra thiết bị đang được sử dụng (bg=0)
        $sql = "SELECT t.stt as id, t.mavt, t.tenvt, t.somay, t.model,
                       CASE 
                           WHEN EXISTS (
                               SELECT 1 FROM hososcbd_iso h 
                               WHERE h.mavt = t.mavt 
                                 AND h.somay = t.somay 
                                 AND h.bg = 0
                           ) THEN 0
                           ELSE 1
                       END as is_available
                FROM thietbi_iso t";
        
        if ($madv) {
            $sql .= " WHERE t.madv = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$madv]);
        } else {
            $stmt = $db->prepare($sql);
            $stmt->execute();
        }
    }
    
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert is_available to boolean
    foreach ($devices as &$device) {
        $device['is_available'] = (bool)$device['is_available'];
    }
    
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
