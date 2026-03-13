<?php
declare(strict_types=1);

/**
 * Script thực thi cập nhật nhomsc='RDNGA' cho id 2233-2369
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDBConnection();
    
    echo "Bắt đầu cập nhật nhomsc='RDNGA' cho id từ 2233 đến 2369...\n\n";
    
    // Kiểm tra số lượng bản ghi sẽ được cập nhật
    $checkSql = "SELECT COUNT(*) as total FROM ke_hoach_bao_duong_dinh_ky_iso WHERE id >= 2233 AND id <= 2369";
    $result = $db->query($checkSql);
    $data = $result->fetch(PDO::FETCH_ASSOC);
    
    echo "Số lượng bản ghi sẽ được cập nhật: " . $data['total'] . "\n";
    
    // Thực thi UPDATE
    $updateSql = "UPDATE ke_hoach_bao_duong_dinh_ky_iso 
                  SET nhomsc = 'RDNGA' 
                  WHERE id >= 2233 AND id <= 2369";
    
    $affectedRows = $db->exec($updateSql);
    
    echo "Đã cập nhật thành công $affectedRows bản ghi!\n\n";
    
    // Verify kết quả
    echo "Xác nhận dữ liệu đã cập nhật:\n";
    $verifySql = "SELECT id, ten_thietbi, so_serial, nhomsc 
                  FROM ke_hoach_bao_duong_dinh_ky_iso 
                  WHERE id >= 2233 AND id <= 2369 
                  LIMIT 5";
    $verifyResult = $db->query($verifySql);
    $samples = $verifyResult->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Mẫu 5 bản ghi đầu tiên:\n";
    foreach ($samples as $row) {
        echo "  ID: {$row['id']} | Tên TB: {$row['ten_thietbi']} | S/N: {$row['so_serial']} | Nhóm SC: {$row['nhomsc']}\n";
    }
    
    echo "\n✓ Hoàn thành!\n";
    
} catch (Exception $e) {
    echo "LỖI: " . $e->getMessage() . "\n";
    exit(1);
}
