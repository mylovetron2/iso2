<?php
declare(strict_types=1);

/**
 * Script tìm các bản ghi có nhomsc NULL
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    
    echo "Tìm các bản ghi có nhomsc = NULL...\n\n";
    
    // Đếm số lượng
    $countSql = "SELECT COUNT(*) as total FROM ke_hoach_bao_duong_dinh_ky_iso WHERE nhomsc IS NULL";
    $result = $db->query($countSql);
    $data = $result->fetch(PDO::FETCH_ASSOC);
    
    echo "Tổng số bản ghi có nhomsc = NULL: " . $data['total'] . "\n\n";
    
    if ($data['total'] > 0) {
        // Lấy danh sách
        $sql = "SELECT id, ten_thietbi, so_serial, nhomsc, nam 
                FROM ke_hoach_bao_duong_dinh_ky_iso 
                WHERE nhomsc IS NULL
                ORDER BY id ASC";
        
        $stmt = $db->query($sql);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Danh sách các bản ghi:\n";
        echo str_repeat("-", 100) . "\n";
        printf("%-8s | %-40s | %-20s | %-10s | %s\n", "ID", "Tên thiết bị", "S/N", "Nhóm SC", "Năm");
        echo str_repeat("-", 100) . "\n";
        
        foreach ($records as $row) {
            printf("%-8s | %-40s | %-20s | %-10s | %s\n", 
                $row['id'], 
                mb_substr($row['ten_thietbi'], 0, 40),
                $row['so_serial'], 
                $row['nhomsc'] ?? 'NULL',
                $row['nam']
            );
        }
        
        echo str_repeat("-", 100) . "\n";
        echo "\nTổng cộng: " . count($records) . " bản ghi\n";
    } else {
        echo "Không có bản ghi nào có nhomsc = NULL!\n";
    }
    
} catch (Exception $e) {
    echo "LỖI: " . $e->getMessage() . "\n";
    exit(1);
}
